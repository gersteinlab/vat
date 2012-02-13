ACLOCAL_AMFLAGS = -I m4

if MAKE_MASTER

SUBDIRS = lib src web master

else

SUBDIRS = lib src web

endif

pkgconfigdir = $(libdir)/pkgconfig
pkgconfig_DATA = vat.pc

DIST_SUBDIRS = \
	lib \
	src \
	web \
	master

EXTRA_DIST = \
	scripts \
	default.vatrc \
	m4/gnulib-cache.m4


install-data-hook:
	scripts/install_rc.sh
