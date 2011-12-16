ACLOCAL_AMFLAGS = -I m4

pkgconfigdir = $(libdir)/pkgconfig
pkgconfig_DATA = vat.pc


EXTRA_DIST = \
	scripts \
	web \
	default.vatrc \
	m4/gnulib-cache.m4

if MAKE_MASTER

SUBDIRS = lib src master

else

SUBDIRS = lib src

endif

install-data-hook:
	scripts/install_rc.sh
