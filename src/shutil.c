#include <bios/format.h>
#include <bios/hlrmisc.h>
#include "util.h"
#include "shutil.h"

int shell_mv (char *src, char *dst)
{
    Stringa cmd = stringCreate (20);
    int ret;

    stringPrintf (cmd, "mv %s %s", src, dst);
    ret = hlr_system (string (cmd), 1);
    stringDestroy (cmd);

    return ret;
}

int shell_cp (char *src, char *dst)
{
    Stringa cmd = stringCreate (20);
    int ret;

    stringPrintf (cmd, "cp %s %s", src, dst);
    ret = hlr_system (string (cmd), 1);
    stringDestroy (cmd);

    return ret;
}

int shell_mkdir (char *path)
{
    Stringa cmd = stringCreate (20);
    int ret;

    stringPrintf (cmd, "mkdir %s", path);
    ret = hlr_system (string (cmd), 1);
    stringDestroy (cmd);

    return ret;
}
