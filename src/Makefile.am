lib_LTLIBRARIES = libvat.la
libvat_la_SOURCES = vcf.c util.c
libvat_la_LIBADD = -lbios
include_HEADERS = vcf.h util.h

bin_PROGRAMS = gencode2interval interval2sequences snpMapper indelMapper svMapper genericMapper vcf2images vcfSummary vcfSubsetByGene vcfModifyHeader vat_cgi vat_fileUpload_cgi

gencode2interval_SOURCES = gencode2interval.c
gencode2interval_LDADD = -lbios

interval2sequences_SOURCES = interval2sequences.c
interval2sequences_LDADD = -lbios

snpMapper_SOURCES = snpMapper.c
snpMapper_LDADD = libvat.la -lbios -lm

indelMapper_SOURCES = indelMapper.c
indelMapper_LDADD = libvat.la -lbios -lm

svMapper_SOURCES = svMapper.c
svMapper_LDADD = libvat.la -lbios -lm

genericMapper_SOURCES = genericMapper.c
genericMapper_LDADD = libvat.la -lbios -lm

vcf2images_SOURCES = vcf2images.c
vcf2images_LDADD = libvat.la -lbios -lgd -lpng -lz -lfreetype -lm

vcfSummary_SOURCES = vcfSummary.c
vcfSummary_LDADD = libvat.la -lbios -lm

vcfSubsetByGene_SOURCES = vcfSubsetByGene.c
vcfSubsetByGene_LDADD = libvat.la -lbios -lm

vcfModifyHeader_SOURCES = vcfModifyHeader.c
vcfModifyHeader_LDADD = libvat.la -lbios -lm

vat_cgi_SOURCES = vat_cgi.c
vat_cgi_LDADD = libvat.la -lbios -lm

vat_fileUpload_cgi_SOURCES = vat_fileUpload_cgi.c
vat_fileUpload_cgi_LDADD = libvat.la -lbios -lm