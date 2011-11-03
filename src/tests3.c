#include <stdio.h>
#include <stdlib.h>
#include <bios/array.h>
#include <bios/format.h>
#include <bios/hlrmisc.h>
#include "util.h"
#include "s3.h"

int main(int argc, char **argv)
{
    util_configInit ("VAT_CONFIG_FILE");
    Stringa cmd = stringCreate (20);

    printf ("Initiate I/O layer\n");
    if (cfio_init () != 0) {
        fprintf (stderr, "Cannot initialize I/O layer\n");
        return -1;
    }

    if (cfio_push_data (1956) != 0) {
        fprintf (stderr, "Cannot push data\n");
        perror(0);
        return -1;
    }

    /*
    printf ("Touch test file f___foo\n");
    stringPrintf (cmd, "touch f___foo");
    if (hlr_system (string (cmd), 1) != 0) {
        fprintf (stderr, "Error executing %s\n", string (cmd));
        perror (0);
        return -1;
    }

    printf ("Putting f___foo to %s\n",
            util_getConfigValue ("AWS_S3_DATA_BUCKET"));
    if (s3_put ("f___foo", util_getConfigValue ("AWS_S3_DATA_BUCKET"), "f___foo") != 0) {
        fprintf (stderr, "Cannot put f___foo to %s\n",
                 util_getConfigValue ("AWS_S3_DATA_BUCKET"));
        return -1;
    }

    printf ("Creating test directory f___dir\n");
    if (shell_mkdir ("f___dir") != 0) {
        fprintf (stderr, "Could not mkdir f___dir\n");
        return -1;
    }

    printf ("Touching test files f___dir/f___bar and f___dir/f___baz\n");
    stringClear (cmd);
    stringPrintf (cmd, "touch f___dir/f___bar f___dir/f___baz");
    if (hlr_system (string (cmd), 1) != 0) {
        fprintf (stderr, "Error executing %s\n", string (cmd));
        return -1;
    }

    printf ("Putting f___dir to bucket %s\n",
            util_getConfigValue ("AWS_S3_DATA_BUCKET"));
    if (s3_put_dir ("f___dir", util_getConfigValue ("AWS_S3_DATA_BUCKET")) != 0) {
        fprintf (stderr, "Error putting f___dir to bucket\n",
                 util_getConfigValue ("AWS_S3_DATA_BUCKET"));
        return -1;
    }

    printf ("Getting f___foo\n");
    if (s3_get (util_getConfigValue ("AWS_S3_DATA_BUCKET"), "f___foo", "f___foo2") != 0) {
        fprintf (stderr, "Error getting f___foo from %s\n",
                 util_getConfigValue ("AWS_S3_DATA_BUCKET"));
        return -1;
    }

    printf ("Getting f___dir/f___baz and f___dir/f___bar\n");
    if (s3_get (util_getConfigValue ("AWS_S3_DATA_BUCKET"), "f___dir/f___baz", "f___baz2") != 0) {
        fprintf (stderr, "Error getting f___dir/f___baz\n");
        return -1;
    }
    if (s3_get (util_getConfigValue ("AWS_S3_DATA_BUCKET"), "f___dir/f___bar", "f___bar2") != 0) {
        fprintf (stderr, "Error getting f___dir/f___baz\n");
        return -1;
    }

    Array entries;
    if ((entries = s3_list (util_getConfigValue ("AWS_S3_DATA_BUCKET"))) == NULL) {
        fprintf (stderr, "Could not get list of bucket %s\n",
                 util_getConfigValue ("AWS_S3_DATA_BUCKET"));
        return -1;
    }

    int i;
    printf ("Listing contents of bucket %s\n",
            util_getConfigValue ("AWS_S3_DATA_BUCKET"));
    printf ("-----------------------------------\n");
    for (i = 0; i < arrayMax (entries); i++) {
        FileEntry *entry = arrayp (entries, i, FileEntry);
        printf ("%s\t%s\t%s\n", entry->key, entry->size, entry->time);
    }

    printf ("Tests complete\n");
    stringDestroy (cmd);*/
    cfio_deinit ();
    util_configDeInit ();

    return 0;
}
