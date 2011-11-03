#ifndef CFIO_H
#define CFIO_H

#include <bios/array.h>

#define OUT_RAW                 0x01
#define OUT_SVMAPPER            0x02
#define OUT_BGZIP               0x04
#define OUT_TABIX               0x08
#define OUT_GENE_SUMMARY        0x10
#define OUT_SAMPLE_SUMMARY      0x20
#define OUT_IMAGES              0x40

int cfio_init ();

int cfio_get_data (int pid, int filemask);

int cfio_get_gene_data (int pid, char *geneId);

int cfio_get_raw (char *filename);

int cfio_delete_raw (char *filename);

int cfio_push_data (int pid);

int cfio_push_raw (char *filename);

int cfio_clear_working (int pid);

Array cfio_get_list_raw (void);

Array cfio_get_list_data (void);

void cfio_deinit (void);

#endif
