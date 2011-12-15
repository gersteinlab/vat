ACLOCAL_AMFLAGS = -I m4

SUBDIRS = lib src
EXTRA_DIST = \
	scripts \
	check.png \
	processing.gif \
	vatConfirgurationTemplate.txt \
	default.vatrc \
	m4/gnulib-cache.m4

pkgconfigdir = $(libdir)/pkgconfig
pkgconfig_DATA = vat.pc

install-data-hook:
	scripts/install_rc.sh
