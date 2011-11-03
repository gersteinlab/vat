
#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <stdint.h>
#include <sys/stat.h>
#include <time.h>
#include <assert.h>
#include <dirent.h>
#include <libs3.h>
#include "util.h"
#include "md5.h"
#include "growbuffer.h"
#include "s3.h"

#define ___S3_DEBUG 0

#define print_nonnull(name, field)                           \
    do {                                                     \
        if (properties-> field) {                            \
            printf("%s: %s\n", name, properties-> field);    \
        }                                                    \
    } while (0)


/* ----------------------------------------------------------------------------
 * Typedefs and sturctures. These are mainly used as callback data for the
 * S3 callback functions.
 */

/**
 * Callback data for putting an object
 */
typedef struct put_object_callback_data
{
    FILE       *infile;
    GrowBuffer *gb;
    uint64_t   content_length;
    uint64_t   original_content_length;
    int        no_status;
} put_object_callback_data;

/**
 * Callback for listing a bucket
 */
typedef struct list_bucket_callback_data
{
    int   is_truncated;
    char  next_marker[1024];
    int   key_count;
    int   all_details;
    Array bucket_entries;
} list_bucket_callback_data;


/* ----------------------------------------------------------------------------
 * Various global variables. Some of these are used as constants and should
 * be declared as macros instead.
 */

static int S3_retries = S3_RETRIES;
static int S3_status = 0;
static S3Protocol S3_protocol = S3ProtocolHTTPS;
static S3UriStyle S3_uri_style = S3UriStylePath;
static char S3_error_details[4096] = { 0 };
static int S3_show_response_properties = 0;


/* ----------------------------------------------------------------------------
 * Utility functions such as md5 functions
 */

char *md5_string (char *str)
{
    MD5_CTX md_context;
    unsigned int len = strlen (str);
    unsigned char digest[16];

    if (str == NULL)
        return NULL;

    MD5Init (&md_context);
    MD5Update (&md_context, str, len);
    MD5Final (digest, &md_context);

    return strdup (digest);
}

char *md5_file (char *filename)
{
    FILE *fp = fopen (filename, "rb");
    MD5_CTX md_context;
    int bytes;
    unsigned char data[1024];
    unsigned char digest[16];

    if (!fp) {
        fprintf (stderr, "Cannot open file %s\n", filename);
        perror (0);
        return NULL;
    }

    MD5Init (&md_context);
    while ((bytes = fread (data, 1, 1024, fp)) != 0)
        MD5Update (&md_context, data, bytes);

    MD5Final (digest, &md_context);

    fclose (fp);

    return strdup (digest);
}

void format_size (uint64_t size, char sizebuf[16])
{
    if (size < 100000) {
        sprintf(sizebuf, "%5llu", (unsigned long long) size);
    } else if (size < (1024 * 1024)) {
        sprintf(sizebuf, "%4lluK",
                ((unsigned long long) size) / 1024ULL);
    } else if (size < (10 * 1024 * 1024)) {
        float f = size;
        f /= (1024 * 1024);
        sprintf(sizebuf, "%1.2fM", f);
    } else if (size < (1024 * 1024 * 1024)) {
        sprintf(sizebuf, "%4lluM",
                ((unsigned long long) size) /
                (1024ULL * 1024ULL));
    } else {
        float f = (size / 1024);
        f /= (1024 * 1024);
        sprintf(sizebuf, "%1.2fG", f);
    }
}

static int should_retry ()
{
    if (S3_retries--) {
        // Sleep before next retry; start out with a 1 second sleep
        static int retrySleepInterval = 1 * S3_SLEEP_UNITS_PER_SECOND;
        sleep (retrySleepInterval);
        // Next sleep 1 second longer
        retrySleepInterval++;
        return 1;
    }

    S3_retries = 5;
    return 0;
}

static void print_error()
{
    if (S3_status < S3StatusErrorAccessDenied) {
        fprintf(stderr, "\nERROR: %s\n", S3_get_status_name(S3_status));
    } else {
        fprintf(stderr, "\nERROR: %s\n", S3_get_status_name(S3_status));
        fprintf(stderr, "%s\n", S3_error_details);
    }
}

/* ----------------------------------------------------------------------------
 * General callback functions
 */

/**
 * response properties callback
 * This callback does the same thing for every request type: prints out the
 * properties if the user has requested them to be so
 */
static S3Status responsePropertiesCallback
    (const S3ResponseProperties *properties, void *callbackData)
{
    (void) callbackData;

    if (!S3_show_response_properties) {
        return S3StatusOK;
    }

    print_nonnull("Content-Type", contentType);
    print_nonnull("Request-Id", requestId);
    print_nonnull("Request-Id-2", requestId2);

    if (properties->contentLength > 0) {
        printf("Content-Length: %lld\n",
               (unsigned long long) properties->contentLength);
    }
    print_nonnull("Server", server);
    print_nonnull("ETag", eTag);

    if (properties->lastModified > 0) {
        char timebuf[256];
        time_t t = (time_t) properties->lastModified;
        // gmtime is not thread-safe but we don't care here.
        strftime(timebuf, sizeof(timebuf), "%Y-%m-%dT%H:%M:%SZ", gmtime(&t));
        printf("Last-Modified: %s\n", timebuf);
    }

    int i;
    for (i = 0; i < properties->metaDataCount; i++) {
        printf("x-amz-meta-%s: %s\n", properties->metaData[i].name,
               properties->metaData[i].value);
    }

    return S3StatusOK;
}

/**
 * response complete callback
 * This callback does the same thing for every request type: saves the status
 * and error stuff in global variables
 */
static void responseCompleteCallback (S3Status status,
                                      const S3ErrorDetails *error,
                                      void *callbackData)
{
    (void) callbackData;

    S3_status = status;
    // Compose the error details message now, although we might not use it.
    // Can't just save a pointer to [error] since it's not guaranteed to last
    // beyond this callback
    int len = 0;
    if (error && error->message) {
        len += snprintf (&(S3_error_details[len]), sizeof (S3_error_details) - len,
                         "  Message: %s\n", error->message);
    }
    if (error && error->resource) {
        len += snprintf (&(S3_error_details[len]), sizeof (S3_error_details) - len,
                         "  Resource: %s\n", error->resource);
    }
    if (error && error->furtherDetails) {
        len += snprintf (&(S3_error_details[len]), sizeof (S3_error_details) - len,
                         "  Further Details: %s\n", error->furtherDetails);
    }
    if (error && error->extraDetailsCount) {
        len += snprintf (&(S3_error_details[len]), sizeof (S3_error_details) - len,
                         "%s", "  Extra Details:\n");
        int i;
        for (i = 0; i < error->extraDetailsCount; i++) {
            len += snprintf (&(S3_error_details[len]),
                             sizeof (S3_error_details) - len, "    %s: %s\n",
                             error->extraDetails[i].name,
                             error->extraDetails[i].value);
        }
    }
}

/* ----------------------------------------------------------------------------
 * Initiation and deinitiation
 */

int s3_init ()
{
    S3Status status;

    if ((status = S3_initialize ("s3", S3_INIT_ALL, NULL)) != S3StatusOK) {
        fprintf (stderr, "Failed to initialize libs3: %s\n",
                 S3_get_status_name (status));
        return -1;
    }

    return 0;
}

void s3_deinit ()
{
    S3_deinitialize ();
}

/* ----------------------------------------------------------------------------
 * Functions used to put an object to a S3 bucket
 */

static int putObjectDataCallback(int buffer_size, char *buffer, void *callback_data)
{
    put_object_callback_data *data = (put_object_callback_data *) callback_data;
    int ret = 0;

    if (data->content_length) {
        int to_read = ((data->content_length > (unsigned int) buffer_size)
                    ? (unsigned int) buffer_size
                    : data->content_length);

        if (data->gb) {
            growbuffer_read (&(data->gb), to_read, &ret, buffer);
        } else if (data->infile) {
            ret = fread (buffer, 1, to_read, data->infile);
        }
    }

    data->content_length -= ret;

    if (data->content_length && !data->no_status) {
        // Avoid a weird bug in MingW, which won't print the second integer
        // value properly when it's in the same call, so print separately
        printf("%llu bytes remaining ", (unsigned long long) data->content_length);
        printf("(%d%% complete) ...\n",
               (int) (((data->original_content_length - data->content_length) * 100) /
                       data->original_content_length));
    }

    return ret;
}

/**
 * @pre S3_initialize has been called
 */
int s3_put (char *filename, char *bucket, char *key)
{
    put_object_callback_data data;
    uint64_t content_length = 0;

    if (filename == NULL || bucket == NULL || key == NULL)
        return -1;

    struct stat statbuf;
    if (stat (filename, &statbuf) == -1) {
        fprintf(stderr, "Failed to stat file %s\n", filename);
        perror(0);
        return -1;
    }
    content_length = statbuf.st_size;

    if (!(data.infile = fopen(filename, "r"))) {
        fprintf(stderr, "Failed to open input file %s\n", filename);
        perror(0);
        return -1;
    }

    data.content_length = data.original_content_length = content_length;
    data.gb = NULL;

    S3BucketContext bucket_context = {
        0,
        bucket,
        S3_protocol,
        S3_uri_style,
        util_getConfigValue("AWS_ACCESS_KEY_ID"),
        util_getConfigValue("AWS_SECRET_ACCESS_KEY")
    };

    S3PutProperties put_properties = {
        NULL,                       // ContentType
        NULL,                       // md5
        NULL,                       // cacheControl
        NULL,                       // contentDispositionFilename
        NULL,                       // contentEncoding
        0,                          // expires
        S3CannedAclPublicRead,      // cannedAcl
        0,                          // metaDataCount
        NULL                        // metaData
    };

#if ___S3_DEBUG > 0
    printf ("put_properties = {\n"
            "\tcontentType: %s\n"
            "\tmd5: %s\n"
            "\tcacheControl: %s\n"
            "\tcontentDispositionFilename: %s\n"
            "\tcontentEncoding: %s\n"
            "\texpires: %s\n"
            "\tcannedAcl: %d\n"
            "\tmetaDataCount: %d\n"
            "\tmetaData: %d\n"
            "}\n",
            (put_properties.contentType == NULL) ? "NULL" : put_properties.contentType,
            (put_properties.md5 == NULL) ? "NULL" : put_properties.md5,
            (put_properties.cacheControl == NULL) ? "NULL" : put_properties.cacheControl,
            (put_properties.contentDispositionFilename == NULL) ? "NULL" : put_properties.contentDispositionFilename,
            (put_properties.contentEncoding == NULL) ? "NULL" : put_properties.contentEncoding,
            put_properties.expires,
            (int) put_properties.cannedAcl,
            put_properties.metaDataCount,
            (put_properties.metaData == NULL));
#endif

    S3PutObjectHandler putObjectHandler = {
        { &responsePropertiesCallback, &responseCompleteCallback },
        &putObjectDataCallback
    };

    do {
        S3_put_object(&bucket_context, key, content_length, &put_properties,
                      0, &putObjectHandler, &data);
    } while (S3_status_is_retryable (S3_status) && should_retry ());

    fclose (data.infile);
    assert (data.gb == NULL);

    if (S3_status != S3StatusOK) {
        print_error();
        return -1;
    } else if (data.content_length) {
        fprintf (stderr, "Error: failed to read remaining %llu bytes from input",
                 (unsigned long long) data.content_length);
        return -1;
    }

    return 0;
}

int s3_put_dir (char *dir, char *bucket, char *prefix)
{
    DIR *dirp;
    struct dirent *direntry;
    Stringa file = stringCreate (20);
    Stringa key  = stringCreate (20);

    if (dir == NULL || bucket == NULL)
        return -1;

    if ((dirp = opendir (dir)) == NULL) {
        fprintf (stderr, "Cannot open directory %s\n", dir);
        return -1;
    }

    while ((direntry = readdir (dirp)) != NULL) {

        if (strcmp (direntry->d_name, "..") == 0 ||
            strcmp (direntry->d_name, ".") == 0)
            continue;

        stringClear (file);
        stringClear (key);
        stringPrintf (file, "%s/%s", dir, direntry->d_name);
        stringPrintf (key, "%s/%s", prefix, direntry->d_name);

#if ___S3_DEBUG > 0
        printf ("Putting file %s\n", string (file));
#endif

        if (s3_put (string (file), bucket, string (key)) != 0) {
            fprintf (stderr, "Could not put file %s to bucket %s\n",
                     string (file), bucket);
            return -1;
        }
    }

    stringDestroy (file);
    closedir (dirp);

    return 0;
}

/* ----------------------------------------------------------------------------
 * Functions used to get an object from an S3 bucket
 */

static S3Status getObjectDataCallback (int bufferSize, const char *buffer,
                                       void *callbackData)
{
    FILE *outfile = (FILE *) callbackData;

    size_t wrote = fwrite (buffer, 1, bufferSize, outfile);

    return ((wrote < (size_t) bufferSize)
           ? S3StatusAbortedByCallback : S3StatusOK);
}

int s3_get (char *bucket, char *key, char *filename)
{
    if (bucket == NULL || key == NULL || filename == NULL)
        return -1;

    FILE *outfile = 0;

    // If file does not exist, create it by open it in w mode.
    struct stat buf;
    if (stat (filename, &buf) == -1) {
        outfile = fopen (filename, "w");
    } else {
        // Open the file in r+ mode so file does not get truncated. In case
        // there is an error and we write no bytes, the file will remain
        // unmodified
        outfile = fopen (filename, "r+");
    }

    if (!outfile) {
        fprintf (stderr, "Could not open output file %s\n", filename);
        perror (0);
        return -1;
    }

    S3BucketContext bucketContext = {
        0,
        bucket,
        S3_protocol,
        S3_uri_style,
        util_getConfigValue("AWS_ACCESS_KEY_ID"),
        util_getConfigValue("AWS_SECRET_ACCESS_KEY")
    };
    S3GetConditions getConditions = {
        -1,                     // ifModifiedSince
        -1,                     // ifNotModifiedSince
        NULL,                   // ifMatchETag
        NULL,                   // ifNotMatchETag
    };

    S3GetObjectHandler getObjectHandler = {
        { &responsePropertiesCallback, &responseCompleteCallback },
        &getObjectDataCallback
    };

    do {
        S3_get_object (&bucketContext, key, &getConditions, 0, 0, 0,
                       &getObjectHandler, outfile);
    } while (S3_status_is_retryable (S3_status) && should_retry ());

    if (S3_status != S3StatusOK) {
        print_error ();
        return -1;
    }

    fclose (outfile);

    return 0;
}

/* ----------------------------------------------------------------------------
 * Functions used to delete an object from an S3 bucket
 */

int s3_delete (char *bucket, char *key)
{
    if (bucket == NULL || key == NULL)
        return -1;

    S3BucketContext bucketContext = {
        0,
        bucket,
        S3_protocol,
        S3_uri_style,
        util_getConfigValue("AWS_ACCESS_KEY_ID"),
        util_getConfigValue("AWS_SECRET_ACCESS_KEY")
    };

    S3ResponseHandler responseHandler = {
        0,
        &responseCompleteCallback
    };

    do {
        S3_delete_object (&bucketContext, key, 0, &responseHandler, 0);
    } while (S3_status_is_retryable (S3_status) && should_retry ());

    if ((S3_status != S3StatusOK) &&
        (S3_status != S3StatusErrorPreconditionFailed)) {
        print_error();
        return -1;
    }

    return 0;
}


/* ----------------------------------------------------------------------------
 * Functions used to list all files in an S3 bucket
 */


static S3Status listBucketCallback(int isTruncated,
                                   const char *nextMarker,
                                   int contentsCount,
                                   const S3ListBucketContent *contents,
                                   int commonPrefixesCount,
                                   const char **commonPrefixes,
                                   void *callbackData)
{
    list_bucket_callback_data *data =
        (list_bucket_callback_data *) callbackData;
    FileEntry *entry = NULL;

    data->is_truncated = isTruncated;

    // XXX Handle nextMarker and delimiter to support pagination in future.

    int i;
    for (i = 0; i < contentsCount; i++) {

        const S3ListBucketContent *content = &(contents[i]);
        char timebuf[256];
        time_t t = (time_t) content->ownerId;
        strftime (timebuf, sizeof (timebuf), "%Y-%m-%dT%H:%M:%SZ", gmtime (&t));
        char sizebuf[16];

        format_size (content->size, sizebuf);

#if ___S3_DEBUG > 0
        printf("%-50s  %s  %s\n", content->key, timebuf, sizebuf);
#endif

        entry = arrayp (data->bucket_entries, arrayMax (data->bucket_entries),
                        FileEntry);

        entry->key = strdup (content->key);
        strncpy (entry->time, timebuf, 256);
        strncpy (entry->size, sizebuf, 16);
    }

    data->key_count += contentsCount;

    return S3StatusOK;
}


Array s3_list (char *bucket)
{
    if (!bucket)
        return NULL;

    S3BucketContext bucketContext = {
        0,
        bucket,
        S3_protocol,
        S3_uri_style,
        util_getConfigValue("AWS_ACCESS_KEY_ID"),
        util_getConfigValue("AWS_SECRET_ACCESS_KEY")
    };

    S3ListBucketHandler listBucketHandler = {
        { &responsePropertiesCallback, &responseCompleteCallback },
        &listBucketCallback
    };

    list_bucket_callback_data data;

    snprintf (data.next_marker, sizeof (data.next_marker), "%s", NULL);
    data.key_count = 0;
    data.all_details = 0;
    data.bucket_entries = arrayCreate (100, FileEntry);

    do {
        data.is_truncated = 0;
        do {
            S3_list_bucket (&bucketContext, 0, 0, 0, 0, 0,
                            &listBucketHandler, &data);
        } while (S3_status_is_retryable (S3_status) && should_retry ());
        if (S3_status != S3StatusOK)
            break;
    } while (data.is_truncated);

    if (S3_status == S3StatusOK) {
        if (!data.key_count) {
            printf ("No keys\n");
        }
    } else {
        print_error();
        return NULL;
    }

    return data.bucket_entries;
}

