#AM_CPPFLAGS = -I$(top_srcdir)/lib

noinst_LIBRARIES = libvat.a
libvat_a_SOURCES = \
	vcf.c vcf.h \
	util.c util.h \
	shutil.c shutil.h \
	cfio.c cfio.h \
	md5.c md5.h \
	growbuffer.c growbuffer.h \
	s3.c s3.h
libvat_a_LIBADD = $(top_srcdir)/lib/libgnu.a $(LIBINTL)

bin_PROGRAMS = \
	gencode2interval \
	interval2sequences \
	snpMapper \
	indelMapper \
	svMapper \
	genericMapper \
	vcf2images \
	vcfSummary \
	vcfSubsetByGene \
	vcfModifyHeader\
	vatson

gencode2interval_SOURCES = gencode2interval.c
gencode2interval_LDADD = -lbios

interval2sequences_SOURCES = interval2sequences.c
interval2sequences_LDADD = -lbios

snpMapper_SOURCES = snpMapper.c
snpMapper_LDADD = libvat.a -lbios -lm

indelMapper_SOURCES = indelMapper.c
indelMapper_LDADD = libvat.a -lbios -lm

svMapper_SOURCES = svMapper.c
svMapper_LDADD = libvat.a -lbios -lm

genericMapper_SOURCES = genericMapper.c
genericMapper_LDADD = libvat.a -lbios -lm

vcf2images_SOURCES = vcf2images.c
vcf2images_LDADD = libvat.a -lbios -lgd -lpng -lz -lfreetype -lm

vcfSummary_SOURCES = vcfSummary.c
vcfSummary_LDADD = libvat.a -lbios -lm

vcfSubsetByGene_SOURCES = vcfSubsetByGene.c
vcfSubsetByGene_LDADD = libvat.a -lbios -lm

vcfModifyHeader_SOURCES = vcfModifyHeader.c
vcfModifyHeader_LDADD = libvat.a -lbios -lm

vatson_SOURCES = vatson.c
vatson_LDADD = libvat.a -lbios -lm -ls3
