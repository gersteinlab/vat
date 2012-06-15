EXTRA_DIST = 
CLEANFILES =
ACLOCAL_AMFLAGS = -I m4 ${ACLOCAL_FLAGS}
AM_MAKEFLAGS = --no-print-directory

AM_CPPFLAGS = \
	-include $(top_builddir)/config.h \
	-I${top_srcdir}/src

AM_CFLAGS = ${my_CFLAGS} -g


PC_SED = \
	$(AM_V_GEN)$(MKDIR_P) $(dir $@) && $(SED) \
	-e 's,@VERSION\@,$(VERSION),g' \
	-e 's,@prefix\@,$(prefix),g' \
	-e 's,@exec_prefix\@,$(exec_prefix),g' \
	-e 's,@libdir\@,$(libdir),g' \
	-e 's,@includedir\@,$(includedir),g' \
	< $< > $@ || rm $@

%.pc: %.pc.in Makefile
	$(PC_SED)

# Convenience library
noinst_LIBRARIES = src/libvat.a
src_libvat_a_SOURCES = \
	src/vcf.c src/vcf.h \
	src/util.c src/util.h

# VAT programs
bin_PROGRAMS = \
	src/gencode2interval \
	src/interval2sequences \
	src/snpMapper \
	src/indelMapper \
	src/svMapper \
	src/genericMapper \
	src/vcf2images \
	src/vcfSummary \
	src/vcfSubsetByGene \
	src/vcfModifyHeader \
	src/vatson

LDADD = -lbios -lm src/libvat.a
src_vcf2images_LDADD = -lbios -lm src/libvat.a -lgd -lpng -lz -lfreetype

# Web
all-local: web

web:
	@echo '  GEN    web/vat.conf'
	@cp -r $(top_srcdir)/default.vatrc $(top_srcdir)/web/vat.conf

CLEANFILES += web/vat.conf

master:
	@echo '  GEN    master/vat.conf'
	@cp -r $(top_srcdir)/default.vatrc $(top_srcdir)/master/vat.conf
	@echo '  GEN    css'
	@cp -r $(top_srcdir)/web/css $(top_srcdir)/master
	@echo '  GEN    image'
	@cp -r $(top_srcdir)/web/image $(top_srcdir)/master
	@echo '  GEN    js'
	@cp -r $(top_srcdir)/web/js $(top_srcdir)/master
	@echo '  GEN    lib'
	@cp -r $(top_srcdir)/web/lib $(top_srcdir)/master

CLEANFILES += master/vat.conf

clean-local:
	-rm -rf master/css master/image master/js master/lib

.PHONY: web master

# pkg-config
pkgconfigdir = $(libdir)/pkgconfig
pkgconfig_DATA = src/vat.pc
EXTRA_DIST += src/vat.pc.in
CLEANFILES += src/vat.pc web/vat.conf

install-data-hook:
	scripts/install_rc.sh
