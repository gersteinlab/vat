SUBDIRS = src
#ACLOCAL_AMFLAGS = -I m4
EXTRA_DIST = web check.png processing.gif vatConfirgurationTemplate.txt default.vatrc

install-data-hook:
	scripts/install_rc.sh
	scripts/install_web.pl

pkgconfigdir = $(libdir)/pkgconfig
pkgconfig_DATA = vat.pc

if USE_PHPUNIT

check-local:
	phpunit web/tests

.PHONY: install-data-hook check-local

else

.PHONY: install-data-hook

endif
