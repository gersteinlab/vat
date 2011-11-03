/**
 * I/O layer for VAT
 *
 * @author    David Z. Chen
 * @package   VAT
 * @copyright (c) 2011 Gerstein Lab
 */

#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <dirent.h>
#include <time.h>
#include <sys/stat.h>
#include <sys/types.h>
#include <libs3.h>
#include <bios/log.h>
#include <bios/array.h>
#include <bios/format.h>
#include "s3.h"
#include "util.h"
#include "shutil.h"
#include "cfio.h"


/**
 * Initializes S3 library
 */
int cfio_init ()
{
    S3Status status;
    char *hostname;

    if (strcmp (util_getConfigValue ("AWS_USE_S3"), "true") == 0) {
        hostname = util_getConfigValue ("AWS_S3_HOSTNAME");

        if ((status = S3_initialize ("s3", S3_INIT_ALL, hostname)) != S3StatusOK) {
            fprintf (stderr, "Failed to initialize libs3: %s\n",
    		     S3_get_status_name(status));
            return -1;
        }
    }

    return 0;
}

/**
 * Makes a request to S3 bucket and
 */
int cfio_get_data (int pid, int filemask)
{
    Stringa src = stringCreate (20);
    Stringa dst = stringCreate (20);

    if (strcmp (util_getConfigValue ("AWS_USE_S3"), "true") == 0) {
        // S3 support is enabled. Make a GET request to AWS_S3_DATA_BUCKET
        // to get the files

        if (filemask & OUT_RAW) {
            stringPrintf (src, "vat.%d.raw.vcf", pid);
            stringPrintf (dst, "%s/vat.%d.raw.vcf",
                          util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);

            if (s3_get (util_getConfigValue ("AWS_S3_DATA_BUCKET"),
                        string (src), string (dst)) != 0) {
                fprintf (stderr, "Cannot get %s %s\n", string (src), string (dst));
                return -1;
            }
        }
        if (filemask & OUT_SVMAPPER) {
            stringClear (src);
            stringClear (dst);
            stringPrintf (src, "vat.%d.vcf", pid);
            stringPrintf (dst, "%s/vat.%d.vcf",
                          util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);


            if (s3_get (util_getConfigValue ("AWS_S3_DATA_BUCKET"),
                        string (src), string (dst)) != 0) {
                fprintf (stderr, "Cannot get %s %s\n", string (src), string (dst));
                return -1;
            }
        }
        if (filemask & OUT_BGZIP) {
            stringClear (src);
            stringClear (dst);
            stringPrintf (src, "vat.%d.vcf.gz", pid);
            stringPrintf (dst, "%s/vat.%d.vcf.gz",
                          util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);

            if (s3_get (util_getConfigValue ("AWS_S3_DATA_BUCKET"),
                        string (src), string (dst)) != 0) {
                fprintf (stderr, "Cannot get %s %s\n", string (src), string (dst));
                return -1;
            }
        }
        if (filemask & OUT_TABIX) {
            stringClear (src);
            stringClear (dst);
            stringPrintf (src, "vat.%d.vcf.gz.tbi", pid);
            stringPrintf (dst, "%s/vat.%d.vcf.gz.tbi",
                          util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);

            if (s3_get (util_getConfigValue ("AWS_S3_DATA_BUCKET"),
                        string (src), string (dst)) != 0) {
                fprintf (stderr, "Cannot get %s %s\n", string (src), string (dst));
                return -1;
            }
        }
        if (filemask & OUT_GENE_SUMMARY) {
            stringClear (src);
            stringClear (dst);
            stringPrintf (src, "vat.%d.geneSummary.txt", pid);
            stringPrintf (dst, "%s/vat.%d.geneSummary.txt",
                          util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);

            if (s3_get (util_getConfigValue ("AWS_S3_DATA_BUCKET"),
                        string (src), string (dst)) != 0) {
                fprintf (stderr, "Cannot get %s %s\n", string (src), string (dst));
                return -1;
            }
        }
        if (filemask & OUT_SAMPLE_SUMMARY) {
            stringClear (src);
            stringClear (dst);
            stringPrintf (src, "vat.%d.sampleSummary.txt", pid);
            stringPrintf (dst, "%s/vat.%d.sampleSummary.txt",
                          util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);

            if (s3_get (util_getConfigValue ("AWS_S3_DATA_BUCKET"),
                        string (src), string (dst)) != 0) {
                fprintf (stderr, "Cannot get %s %s\n", string (src), string (dst));
                return -1;
            }
        }
    } else {
        // S3 support is not enabled, so just copy the files from WEB_DATA_DIR
        // into WEB_DATA_WORKING DIR, preserving filenames

        if (filemask & OUT_RAW) {
            stringPrintf (src, "%s/vat.%d.raw.vcf",
                          util_getConfigValue ("WEB_DATA_DIR"), pid);
            stringPrintf (dst, "%s/vat.%d.raw.vcf",
                          util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);

            if (shell_cp (string (src), string (dst)) != 0) {
                fprintf (stderr, "Cannot cp %s %s\n", string (src), string (dst));
                return -1;
            }
        }
        if (filemask & OUT_SVMAPPER) {
            stringClear (src);
            stringClear (dst);
            stringPrintf (src, "%s/vat.%d.vcf",
                          util_getConfigValue ("WEB_DATA_DIR"), pid);
            stringPrintf (dst, "%s/vat.%d.vcf",
                          util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);

            if (shell_cp (string (src), string (dst)) != 0) {
                fprintf (stderr, "Cannot cp %s %s\n", string (src), string (dst));
                return -1;
            }
        }
        if (filemask & OUT_BGZIP) {
            stringClear (src);
            stringClear (dst);
            stringPrintf (src, "%s/vat.%d.vcf.gz",
                          util_getConfigValue ("WEB_DATA_DIR"), pid);
            stringPrintf (dst, "%s/vat.%d.vcf.gz",
                          util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);

            if (shell_cp (string (src), string (dst)) != 0) {
                fprintf (stderr, "Cannot cp %s %s\n", string (src), string (dst));
                return -1;
            }
        }
        if (filemask & OUT_TABIX) {
            stringClear (src);
            stringClear (dst);
            stringPrintf (src, "%s/vat.%d.vcf.gz.tbi",
                          util_getConfigValue ("WEB_DATA_DIR"), pid);
            stringPrintf (dst, "%s/vat.%d.vcf.gz.tbi",
                          util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);

            if (shell_cp (string (src), string (dst)) != 0) {
                fprintf (stderr, "Cannot cp %s %s\n", string (src), string (dst));
                return -1;
            }
        }
        if (filemask & OUT_GENE_SUMMARY) {
            stringClear (src);
            stringClear (dst);
            stringPrintf (src, "%s/vat.%d.geneSummary.txt",
                          util_getConfigValue ("WEB_DATA_DIR"), pid);
            stringPrintf (dst, "%s/vat.%d.geneSummary.txt",
                          util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);

            if (shell_cp (string (src), string (dst)) != 0) {
                fprintf (stderr, "Cannot cp %s %s\n", string (src), string (dst));
                return -1;
            }
        }
        if (filemask & OUT_SAMPLE_SUMMARY) {
            stringClear (src);
            stringClear (dst);
            stringPrintf (src, "%s/vat.%d.sampleSummary.txt",
                          util_getConfigValue ("WEB_DATA_DIR"), pid);
            stringPrintf (dst, "%s/vat.%d.sampleSummary.txt",
                          util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);

            if (shell_cp (string (src), string (dst)) != 0) {
                fprintf (stderr, "Cannot cp %s %s\n", string (src), string (dst));
                return -1;
            }
        }
    }

    return 0;
}

int cfio_get_raw (char *filename)
{
    Stringa srcfile  = stringCreate (20);
    Stringa destfile = stringCreate (20);

    stringPrintf (destfile, "%s/%s",
                  util_getConfigValue ("WEB_DATA_WORKING_DIR"), filename);

    if (strcmp (util_getConfigValue ("AWS_USE_S3"), "true") == 0) {
        if (s3_get (util_getConfigValue ("AWS_S3_RAW_BUCKET"), filename, string (destfile)) < 0) {
            fprintf (stderr, "Could not get file from S3: %s\n", filename);
            return -1;
        }
    } else {
        stringPrintf (srcfile, "%s/%s",
                      util_getConfigValue ("WEB_DATA_RAW_DIR"), filename);
        if (shell_cp (string (srcfile), string (destfile)) != 0) {
            fprintf (stderr, "Could not cp %s %s\n",
                     string(srcfile), string (destfile));
            return -1;
        }
    }

    stringDestroy (destfile);
    return 0;
}

int cfio_delete_raw (char *filename)
{
    Stringa path = stringCreate (20);

    if (strcmp (util_getConfigValue ("AWS_USE_S3"), "true") == 0) {
        if (s3_delete (util_getConfigValue ("AWS_S3_RAW_BUCKET"), filename) < 0) {
            fprintf (stderr, "Could not delete file from S3: %s\n", filename);
            return -1;
        }
    } else {
        stringPrintf (path, "%s/%s",
                      util_getConfigValue ("WEB_DATA_RAW_DIR"), filename);
        if (unlink (string (path)) < 0) {
            fprintf (stderr, "Could not unlink file %s\n", filename);
            perror (0);
            return -1;
        }
    }

    stringDestroy (path);
    return 0;
}

int cfio_push_raw (char *filename)
{
    Stringa src = stringCreate (20);
    Stringa dst = stringCreate (20);

    if (strcmp (util_getConfigValue ("AWS_USE_S3"), "true") == 0) {
        stringPrintf (src, "%s/%s", util_getConfigValue ("WEB_DATA_WORKING_DIR"), filename);

        if (s3_put (string (src), util_getConfigValue ("AWS_S3_RAW_BUCKET"), filename) != 0) {
            fprintf (stderr, "Cannot put %s\n", filename);
            return -1;
        }
    } else {
        stringPrintf (src, "%s/%s", util_getConfigValue ("WEB_DATA_WORKING_DIR"), filename);
        stringPrintf (dst, "%s/%s", util_getConfigValue ("WEB_DATA_RAW_DIR"), filename);

        if (shell_cp (string (src), string (dst)) != 0) {
            fprintf (stderr, "Cannot cp %s %s\n", string (src), string (dst));
            return -1;
        }
    }

    stringDestroy (src);
    stringDestroy (dst);
    return 0;
}

/**
 * Called after a new VCF file has been processed through the pipeline. The
 * resulting data files are store in WEB_DATA_DIR/pid
 */
int cfio_push_data (int pid)
{
    Stringa src = stringCreate (20);
    Stringa dst = stringCreate (20);

    if (strcmp (util_getConfigValue ("AWS_USE_S3"), "true") == 0) {
        char *bucket = util_getConfigValue ("AWS_S3_DATA_BUCKET");

        // Move raw vcf
        stringPrintf (src, "%s/vat.%d.raw.vcf",
                      util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);
        stringPrintf (dst, "vat.%d.raw.vcf", pid);
        if (s3_put (string (src), bucket, string (dst)) != 0) {
            fprintf (stderr, "Could not put %s to bucket %s",
                     string (src), bucket);
            return -1;
        }

        // Move output vcf
        stringClear (src);
        stringClear (dst);
        stringPrintf (src, "%s/vat.%d.vcf",
                      util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);
        stringPrintf (dst, "vat.%d.vcf", pid);
        if (s3_put (string (src), bucket, string (dst)) != 0) {
            fprintf (stderr, "Could not put %s to bucket %s",
                     string (src), bucket);
            return -1;
        }

        // Move bgzip output
        stringClear (src);
        stringClear (dst);
        stringPrintf (src, "%s/vat.%d.vcf.gz",
                      util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);
        stringPrintf (dst, "vat.%d.vcf.gz", pid);
        if (s3_put (string (src), bucket, string (dst)) != 0) {
            fprintf (stderr, "Could not put %s to bucket %s",
                     string (src), bucket);
            return -1;
        }

        // Move tabix output
        stringClear (src);
        stringClear (dst);
        stringPrintf (src, "%s/vat.%d.vcf.gz.tbi",
                      util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);
        stringPrintf (dst, "vat.%d.vcf.gz.tbi", pid);
        if (s3_put (string (src), bucket, string (dst)) != 0) {
            fprintf (stderr, "Could not put %s to bucket %s",
                     string (src), bucket);
            return -1;
        }

        // Move sample summary
        stringClear (src);
        stringClear (dst);
        stringPrintf (src, "%s/vat.%d.sampleSummary.txt",
                      util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);
        stringPrintf (dst, "vat.%d.sampleSummary.txt", pid);
        if (s3_put (string (src), bucket, string (dst)) != 0) {
            fprintf (stderr, "Could not put %s to bucket %s",
                     string (src), bucket);
            return -1;
        }

        // Move gene summary
        stringClear (src);
        stringClear (dst);
        stringPrintf (src, "%s/vat.%d.geneSummary.txt",
                      util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);
        stringPrintf (dst, "vat.%d.geneSummary.txt", pid);
        if (s3_put (string (src), bucket, string (dst)) != 0) {
            fprintf (stderr, "Could not put %s to bucket %s",
                     string (src), bucket);
            return -1;
        }

        // Move images
        stringClear (src);
        stringClear (dst);
        stringPrintf (src, "%s/vat.%d",
                      util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);
        if (s3_put_dir (string (src), bucket) != 0) {
            fprintf (stderr, "Could not put images directory %s to bucket %s\n",
                     string (src), bucket);
            return -1;
        }

    } else {
        // S3 support is not enabled. Simply call mv on the shell to move files
        // from WEB_DATA_WORKING_DIR to WEB_DATA_DIR

        // Move raw vcf
        stringPrintf (src, "%s/vat.%d.raw.vcf",
                      util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);
        stringPrintf (dst, "%s/vat.%d.raw.vcf",
                      util_getConfigValue ("WEB_DATA_DIR"), pid);
        if (shell_mv (string (src), string (dst)) != 0) {
            fprintf (stderr, "Could not mv %s %s", string (src), string (dst));
            return -1;
        }

        // Move output vcf
        stringClear (src);
        stringClear (dst);
        stringPrintf (src, "%s/vat.%d.vcf",
                      util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);
        stringPrintf (dst, "%s/vat.%d.vcf",
                      util_getConfigValue ("WEB_DATA_DIR"), pid);
        if (shell_mv (string (src), string (dst)) != 0) {
            fprintf (stderr, "Could not mv %s %s", string (src), string (dst));
            return -1;
        }

        // Move bgzip output
        stringClear (src);
        stringClear (dst);
        stringPrintf (src, "%s/vat.%d.vcf.gz",
                      util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);
        stringPrintf (dst, "%s/vat.%d.vcf.gz",
                      util_getConfigValue ("WEB_DATA_DIR"), pid);
        if (shell_mv (string (src), string (dst)) != 0) {
            fprintf (stderr, "Could not mv %s %s", string (src), string (dst));
            return -1;
        }

        // Move tabix output
        stringClear (src);
        stringClear (dst);
        stringPrintf (src, "%s/vat.%d.vcf.gz.tbi",
                      util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);
        stringPrintf (dst, "%s/vat.%d.vcf.gz.tbi",
                      util_getConfigValue ("WEB_DATA_DIR"), pid);
        if (shell_mv (string (src), string (dst)) != 0) {
            fprintf (stderr, "Could not mv %s %s", string (src), string (dst));
            return -1;
        }

        // Move gene summary
        stringClear (src);
        stringClear (dst);
        stringPrintf (src, "%s/vat.%d.geneSummary.txt",
                      util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);
        stringPrintf (dst, "%s/vat.%d.geneSummary.txt",
                      util_getConfigValue ("WEB_DATA_DIR"), pid);
        if (shell_mv (string (src), string (dst)) != 0) {
            fprintf (stderr, "Could not mv %s %s", string (src), string (dst));
            return -1;
        }

        // Move sample summary
        stringClear (src);
        stringClear (dst);
        stringPrintf (src, "%s/vat.%d.sampleSummary.txt",
                      util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);
        stringPrintf (dst, "%s/vat.%d.sampleSummary.txt",
                      util_getConfigValue ("WEB_DATA_DIR"), pid);
        if (shell_mv (string (src), string (dst)) != 0) {
            fprintf (stderr, "Could not mv %s %s", string (src), string (dst));
            return -1;
        }

        // Move images
        stringClear (src);
        stringClear (dst);
        stringPrintf (src, "%s/vat.%d",
                      util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);
        stringPrintf (dst, "%s/vat.%d",
                      util_getConfigValue ("WEB_DATA_DIR"), pid);
        if (shell_mv (string (src), string (dst)) != 0) {
            fprintf (stderr, "Could not mv %s %s", string (src), string (dst));
            return -1;
        }
    }

    stringDestroy (src);
    stringDestroy (dst);
    return 0;
}


int cfio_clear_working (int pid)
{
    Stringa file = stringCreate (20);

    stringDestroy (file);
    return 0;
}

Array cfio_get_list_raw ()
{
    if (strcmp (util_getConfigValue ("AWS_USE_S3"), "true") == 0) {
        return s3_list (util_getConfigValue ("AWS_S3_RAW_BUCKET"));
    }

    Array entries = arrayCreate (100, FileEntry);
    DIR *dirp;
    struct dirent *direntry;
    FileEntry *entry = NULL;
    Stringa file = stringCreate (20);

    if ((dirp = opendir (util_getConfigValue ("WEB_DATA_RAW_DIR"))) == NULL) {
        fprintf (stderr, "Could not open directory %s\n",
                 util_getConfigValue ("WEB_DATA_RAW_DIR"));
        return NULL;
    }

    while ((direntry = readdir (dirp)) != NULL) {
        if (strcmp (direntry->d_name, "..") == 0 ||
            strcmp (direntry->d_name, ".") == 0)
            continue;

        struct stat statbuf;
        stringPrintf (file, "%s/%s", util_getConfigValue ("WEB_DATA_RAW_DIR"),
                      direntry->d_name);
        if (stat (string (file), &statbuf) != 0) {
            fprintf (stderr, "Could not stat %s\n", string (file));
            perror (0);
            return NULL;
        }

        entry = arrayp (entries, arrayMax (entries), FileEntry);
        time_t t = (time_t) statbuf.st_mtime;
        strftime (entry->time, sizeof (entry->time), "%Y-%m-%dT%H:%M:%SZ",
                  gmtime (&t));
        format_size (statbuf.st_size, entry->size);
        entry->key = strdup (direntry->d_name);
    }


    stringDestroy (file);
    return entries;
}

Array cfio_get_list_data ()
{
    Array entries;
    DIR *dirp;
    struct dirent *direntry;
    FileEntry *entry = NULL;
    Stringa file = stringCreate (20);

    if (strcmp (util_getConfigValue ("AWS_USE_S3"), "true") == 0) {
        return s3_list (util_getConfigValue ("AWS_S3_RAW_BUCKET"));
    } else {
        entries = arrayCreate (100, FileEntry);

        if ((dirp = opendir (util_getConfigValue ("WEB_DATA_RAW_DIR"))) == NULL) {
            fprintf (stderr, "Could not open directory %s\n",
                     util_getConfigValue ("WEB_DATA_RAW_DIR"));
            return NULL;
        }

        while ((direntry = readdir (dirp)) != NULL) {
            if (strcmp (direntry->d_name, "..") == 0 ||
                strcmp (direntry->d_name, ".") == 0)
                continue;

            struct stat statbuf;
            stringPrintf (file, "%s/%s", util_getConfigValue ("WEB_DATA_RAW_DIR"),
                          direntry->d_name);
            if (stat (string (file), &statbuf) != 0) {
                fprintf (stderr, "Could not stat %s\n", string (file));
                perror (0);
                return NULL;
            }

            entry = arrayp (entries, arrayMax (entries), FileEntry);
            time_t t = (time_t) statbuf.st_mtime;
            strftime (entry->time, sizeof (entry->time), "%Y-%m-%dT%H:%M:%SZ",
                      gmtime (&t));
            format_size (statbuf.st_size, entry->size);
            entry->key = strdup (direntry->d_name);
        }
    }

    // Process them

    stringDestroy (file);
    return entries;
}


void cfio_deinit ()
{
    if (strcmp (util_getConfigValue ("AWS_USE_S3"), "true") == 0) {
        S3_deinitialize();
    }
}
