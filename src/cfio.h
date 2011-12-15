#ifndef CFIO_H
#define CFIO_H

#include <bios/array.h>

/**
 * Options passed to cfio_get_data to specify which files to get. Options are
 * OR'ed together for cfio_get_data to retrieve multiple files.
 */
#define OUT_RAW                 0x01    // Copy of raw VCF input file
#define OUT_SVMAPPER            0x02    // Output of svMapper
#define OUT_BGZIP               0x04    // Output of bgzip
#define OUT_TABIX               0x08    // Output of tabix
#define OUT_GENE_SUMMARY        0x10    // Gene summary file
#define OUT_SAMPLE_SUMMARY      0x20    // Sample summary file

/**
 * Initializes I/O layer and, if S3 is enabled, the S3 functions and library
 *
 * @return 0 if successful, -1 if unsuccessful
 */
int cfio_init ();

/**
 * Retrieves one or more files from WEB_DATA_DIR if S3 support is disabled or
 * from the bucket specified by AWS_S3_DATA_BUCKET if S3 support is enabled.
 * Which file to retrieve is specified by the flags OR'ed and passed in
 * filemask.
 *
 * @param pid      - which data set ID to retrieve
 * @param filemask - options to specify which files to retrieve
 * @return 0 if successful, -1 if unsuccessful
 */
int cfio_get_data (int pid, int filemask);


/**
 * Retrieve the VCF file for a specific gene generated during subsetting. File
 * is retrieved from WEB_DATA_DIR/pid/vat.pid if S3 support is disabled or
 * from AWS_S3_DATA_BUCKET if S3 support is enabled.
 *
 * @param pid    - data set ID
 * @param geneId - the gene for which the VCF file is retrieved
 * @return 0 if successful, -1 if unsuccessful
 */
int cfio_get_gene_data (int pid, char *geneId);


/**
 * Retrieve a raw VCF file from WEB_DATA_RAW_DIR is S3 support is disabled or
 * from the AWS_S3_RAW_BUCKET if S3 support is enabled.
 *
 * @param filename - the raw VCF file to retrieve
 * @return 0 if successful, -1 if unsuccessful
 */
int cfio_get_raw (char *filename);

/**
 * Deletes an object from AWS_S3_RAW_BUCKET with the specified filename as the
 * key
 *
 * @return 0 if successful, -1 if unsuccessful
 */
int cfio_delete_raw (char *filename);

/**
 * Copies the data files from the working directory to AWS_S3_DATA_BUCKET if
 * S3 support is enabled. All objects will have a key consisting of
 * pid/filename. The image directory vat.pid will be copied by calling
 * s3_put_dir(). If S3 support is disabled, the directory will simply be moved
 * to WEB_DATA_DIR
 *
 * @param pid - set ID denoting the set of data files
 * @return 0 if successful, -1 if unsuccessful
 */
int cfio_push_data (int pid);

/**
 * Copies the raw VCF file WEB_DATA_WORKING_DIR/filename to the bucket
 * AWS_S3_RAW_BUCKET, preserving the filename as the object's key. If S3 support
 * is disabled, file is moved to WEB_DATA_RAW_DIR
 *
 * @param filename - file to copied to S3 bucket or moved
 * @return 0 if successful, -1 if unsuccessful
 */
int cfio_push_raw (char *filename);

/**
 * Clears the working directory WEB_DATA_WORKING_DIR/pid.
 *
 * @param pid - the ID for current instance
 * @return 0 if successful, -1 if unsuccessful
 */
int cfio_clear_working (int pid);

/**
 * Retrieves an array
 */
Array cfio_get_list_raw (void);


/**
 * Retrieves a file listing
 */
Array cfio_get_list_data (void);


/**
 * Deinitializes I/O layer
 */
void cfio_deinit (void);

#endif
