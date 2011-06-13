#ifndef DEF_VCF_H
#define DEF_VCF_H



typedef struct {
  char *genotype;
  char *details;
  char *group;
  char *sample;
} VcfGenotype;



typedef struct {
  char *geneId;
  char *geneName;
  char strand;
  char *type;
  char *fraction;
  Texta transcriptNames;
  Texta transcriptIds;
  Texta transcriptDetails;
  int alleleNumber;
 } VcfAnnotation;



typedef struct {
  char *chromosome;
  int position;
  char *id;
  char *referenceAllele;
  char *alternateAllele;
  char *quality;
  char *filter;
  char *info;
  char *genotypeFormat;
  Array genotypes; // of type VcfGenotype
  Array annotations; // of type VcfAnnotation
} VcfEntry;



typedef struct {
  char *geneId;
  char *geneName;
  Array transcripts; // of type Interval*
  Array vcfEntries; // of type VcfEntry*
} VcfGene;



extern void vcf_init (char *fileName);
extern void vcf_initFromPipe (char *command);
extern VcfEntry* vcf_nextEntry (void);
extern Array vcf_parse (void);
extern void vcf_freeEntries (Array vcfEntries);
extern void vcf_deInit (void);
extern int vcf_isInvalidEntry (VcfEntry *currEntry);
extern int vcf_hasMultipleAlternateAlleles (VcfEntry *currEntry);
extern Texta vcf_getAlternateAlleles (VcfEntry *currEntry);
extern Texta vcf_getColumnHeaders (void);
extern char* vcf_writeEntry (VcfEntry *currEntry);
extern char* vcf_writeMetaData (void);
extern char* vcf_writeColumnHeaders (void);
extern void vcf_addComment (char *comment);
extern Array vcf_getGeneSummaries (Array vcfEntries, char *annotationFile);
extern int vcf_getCountsForVcfAnnotationType (VcfGene *currVcfGene, char *type);
extern Texta vcf_getGroupsFromColumnHeaders (void);
extern Texta vcf_getSamplesFromColumnHeaders (void);
extern int vcf_getAllelesFromGenotype (char *genotype, int *allele1, int *allele2);
extern void vcf_getAlleleInformation (VcfEntry *currVcfEntry, char *group, int alleleNumber, int *alleleCount, int *totalAlleleCount);



#endif
