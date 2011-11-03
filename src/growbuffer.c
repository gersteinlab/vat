#include <stdlib.h>
#include <string.h>
#include "growbuffer.h"

// returns nonzero on success, zero on out of memory
int growbuffer_append(GrowBuffer **gb, const char *data, int dataLen)
{
    while (dataLen) {
        GrowBuffer *buf = *gb ? (*gb)->prev : 0;
        if (!buf || (buf->size == sizeof(buf->data))) {
            buf = (GrowBuffer *) malloc(sizeof(GrowBuffer));
            if (!buf) {
                return 0;
            }
            buf->size = 0;
            buf->start = 0;
            if (*gb) {
                buf->prev = (*gb)->prev;
                buf->next = *gb;
                (*gb)->prev->next = buf;
                (*gb)->prev = buf;
            }
            else {
                buf->prev = buf->next = buf;
                *gb = buf;
            }
        }

        int toCopy = (sizeof(buf->data) - buf->size);
        if (toCopy > dataLen) {
            toCopy = dataLen;
        }

        memcpy(&(buf->data[buf->size]), data, toCopy);

        buf->size += toCopy, data += toCopy, dataLen -= toCopy;
    }

    return 1;
}


void growbuffer_read(GrowBuffer **gb, int amt, int *amtReturn,
                            char *buffer)
{
    *amtReturn = 0;

    GrowBuffer *buf = *gb;

    if (!buf) {
        return;
    }

    *amtReturn = (buf->size > amt) ? amt : buf->size;

    memcpy(buffer, &(buf->data[buf->start]), *amtReturn);

    buf->start += *amtReturn, buf->size -= *amtReturn;

    if (buf->size == 0) {
        if (buf->next == buf) {
            *gb = 0;
        }
        else {
            *gb = buf->next;
            buf->prev->next = buf->next;
            buf->next->prev = buf->prev;
        }
        free(buf);
    }
}


void growbuffer_destroy(GrowBuffer *gb)
{
    GrowBuffer *start = gb;

    while (gb) {
        GrowBuffer *next = gb->next;
        free(gb);
        gb = (next == start) ? 0 : next;
    }
}
