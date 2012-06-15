#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.

AC_INIT([vat], [2.1.1], [lukas.habegger@yale.edu])
AC_CONFIG_SRCDIR([src/vcf.c])
AC_CONFIG_MACRO_DIR([m4])
AC_CONFIG_HEADER([config.h])
AM_INIT_AUTOMAKE([silent-rules subdir-objects])
m4_ifdef([AM_SILENT_RULES], [AM_SILENT_RULES([yes])])

# Checks for programs.
AC_PROG_CC
AM_PROG_CC_C_O
AC_PROG_SED
AC_PROG_MKDIR_P
AC_PROG_RANLIB
AC_CHECK_PROG([TABIX], [tabix], yes, no)
AC_CHECK_PROG([BGZIP], [bgzip], yes, no)
AC_CHECK_PROG([TWOBITTOFA], [twoBitToFa], yes, no)

my_CFLAGS='-Wunused'
AC_SUBST([my_CFLAGS])

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

AC_CONFIG_FILES([Makefile])
AC_OUTPUT
AC_MSG_RESULT([
	$PACKAGE $VERSION
	=====

	prefix:			${prefix}
	sysconfdir:		${sysconfdir}
	libdir:			${libdir}
	includedir:		${includedir}

	compiler:		${CC}
	cflags:			${CFLAGS}
	ldflags:		${LDFLAGS}
])
