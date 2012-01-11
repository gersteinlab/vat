#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.

AC_INIT([vat], [2.0.0], [lukas.habegger@yale.edu])
AC_CONFIG_SRCDIR([src/vcf.c])
AC_CONFIG_MACRO_DIR([m4])
AC_CONFIG_HEADER([config.h])

AM_INIT_AUTOMAKE([-Wall -Werror])
m4_ifdef([AM_SILENT_RULES], [AM_SILENT_RULES([yes])])

# Checks for programs.
AC_PROG_CC
gl_EARLY
gl_INIT
AC_PROG_RANLIB
AC_CHECK_PROG([TABIX], [tabix], yes, no)
AC_CHECK_PROG([BGZIP], [bgzip], yes, no)
AC_CHECK_PROG([TWOBITTOFA], [twoBitToFa], yes, no)
AC_CHECK_PROG([PHPUNIT], [phpunit], yes, no)
if test -z "$PHPUNIT"; then
    AC_MSG_WARN([PHPUnit not found - PHP testsuite not active])
fi
AM_CONDITIONAL([USE_PHPUNIT], [test -n "$PHPUNIT"])

AC_ARG_ENABLE([master], 
    [AS_HELP_STRING([--enable-master], [Enable master-worker cluster setup (default is no)])], 
    [case "${enableval}" in
        yes) master=true ;;
        no)  master=false ;;
        *) AC_MSG_ERROR([bad value ${enableval} for --enable-master]) ;;
    esac], 
    [master="false"])

AM_CONDITIONAL([MAKE_MASTER], [test x$master = xtrue])

# Checks for libraries.
AC_CHECK_LIB([m], [log], [], [AC_MSG_ERROR([Cannot find standard math library])])
AC_CHECK_LIB([gslcblas], [cblas_dgemm], [], [AC_MSG_ERROR([Cannot find cblas library])])
AC_CHECK_LIB([gsl], [gsl_ran_hypergeometric_pdf], [], [AC_MSG_ERROR([Cannot find gsl library])])
AC_CHECK_LIB([bios], [needMem], [], [AC_MSG_ERROR([Cannot find bios library])])
AC_CHECK_LIB([gd], [gdImageCreate], [], [AC_MSG_ERROR([Cannot find gd library])])
AC_CHECK_LIB([s3], [S3_initialize], [], [AC_MSG_ERROR([Cannot find libs3])])

# Checks for header files.
AC_HEADER_STDC
AC_CHECK_HEADERS([math.h])
AC_CHECK_HEADERS([stdlib.h unistd.h])

# Checks for typedefs, structures, and compiler characteristics.

# Checks for library functions.
AC_CHECK_FUNCS([floor strchr strpbrk strrchr strstr])

AM_COND_IF([MAKE_MASTER], [AC_CONFIG_FILES([master/Makefile])])
AC_CONFIG_FILES([
    Makefile 
    lib/Makefile 
    src/Makefile
    web/Makefile 
    vat.pc:vat.pc.in
])
AC_OUTPUT
