SUBDIRS = src
EXTRA_DIST = scripts check.png processing.gif vatConfirgurationTemplate.txt

pkgconfigdir = $(libdir)/pkgconfig
pkgconfig_DATA = vat.pc

install-data-hook:
	scripts/install_rc.sh