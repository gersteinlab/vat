#ifndef GROWBUFFER_H
#define GROWBUFFER_H

typedef struct growbuffer
{
    // The total number of bytes, and the start byte
    int size;
    // The start byte
    int start;
    // The blocks
    char data[64 * 1024];
    struct growbuffer *prev, *next;
} GrowBuffer;


int growbuffer_append(GrowBuffer **gb, const char *data, int dataLen);

void growbuffer_read(GrowBuffer **gb, int amt, int *amtReturn,
                            char *buffer);

void growbuffer_destroy(GrowBuffer *gb);

#endif
