#ifndef S3_H
#define S3_H

#include <stdint.h>

#define S3_RETRIES 5
#define S3_SLEEP_UNITS_PER_SECOND 1

typedef struct file_entry {
    char *key;
    char time[256];
    char size[16];
} FileEntry;

void format_size (uint64_t size, char sizebuf[16]);

/**
 * Takes a string and returns a md5 digest of the string. The returned string
 * is heap-allocated and must be free-d by the caller.
 *
 * @param input string
 * @return md5 digest
 */
char *md5_string (char *str);

/**
 * Takes a name of a file and generates the md5 digest for the file. The
 * returned string is heap-allocated and must be free-d by the caller.
 *
 * @param filename - file name of input file
 * @return md5 digest
 */
char *md5_file (char *filename);

/**
 * Initializes a S3 session
 *
 * @return 0 if successful, -1 if unsuccessful
 */
int s3_init();

/**
 * Put a specified file as an object in specified S3 bucket with specified key.
 *
 * @pre S3_initialize has been called
 * @param filename - name of input file
 * @param bucket   - bucket to put object in
 * @param key      - key for object after being put in bucket
 * @return 0 if successful, -1 if unsuccessful
 */
int s3_put (char *filename, char *bucket, char *key);

/**
 *
 */
int s3_put_dir (char *dir, char *bucket);

/**
 * Gets an object with specified key from specified bucket and writes it to
 * a file with specified filename
 *
 * @pre S3_initialize has been called
 * @param bucket   - bucket to get object from
 * @param key      - key for file
 * @param filename - name of file to write object to
 * @return 0 if successful, -1 if unsuccessful
 */
int s3_get (char *bucket, char *key, char *filename);

/**
 * Deletes an object from specified bucket with specified key.
 *
 * @precondition S3_initialize has been called
 * @param bucket - bucket to delete object from
 * @param key    - key of object to be deleted
 * @return 0 if successful, -1 if unsuccessful
 */
int s3_delete (char *bucket, char *key);

Array s3_list (char *bucket);

/**
 * De-initializes a S3 session
 */
void s3_deinit();

#endif
