#ifndef SHUTIL_H
#define SHUTIL_H


int shell_mv (char *src, char *dst);

int shell_cp (char *src, char *dst);

int shell_mkdir (char *path);

int shell_rmrf (char *path);

#endif
