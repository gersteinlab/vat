#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.

AC_INIT([vat], [1.0.0], [lukas.habegger@yale.edu])
AC_CONFIG_SRCDIR([src/vcf.c])
AC_CONFIG_MACRO_DIR([m4])
AC_CONFIG_HEADER([config.h])
AM_INIT_AUTOMAKE([-Wall -Werror])
LT_INIT

# Checks for programs.
AC_PROG_CC
AC_PROG_LIBTOOL

AC_CHECK_PROG(TABIX, tabix, yes, no)
AC_CHECK_PROG(BGZIP, bgzip, yes, no)
AC_CHECK_PROG(TWOBITTOFA, twoBitToFa, yes, no)

# Checks for libraries.
AC_CHECK_LIB([m], [log], [], [AC_MSG_ERROR([Cannot find standard math library])])
AC_CHECK_LIB([gslcblas], [cblas_dgemm], [], [AC_MSG_ERROR([Cannot find cblas library])])
AC_CHECK_LIB([gsl], [gsl_ran_hypergeometric_pdf], [], [AC_MSG_ERROR([Cannot find gsl library])])
AC_CHECK_LIB([bios], [needMem], [], [AC_MSG_ERROR([Cannot find bios library])])
AC_CHECK_LIB([gd], [gdImageCreate], [], [AC_MSG_ERROR([Cannot find gd library])])

# Checks for header files.
AC_HEADER_STDC
AC_CHECK_HEADERS([math.h])
AC_CHECK_HEADERS([stdlib.h unistd.h])

# Checks for typedefs, structures, and compiler characteristics.

# Checks for library functions.
AC_CHECK_FUNCS([floor strchr strpbrk strrchr strstr])

AC_CONFIG_FILES([Makefile src/Makefile])
AC_OUTPUT
