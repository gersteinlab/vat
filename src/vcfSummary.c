#include <bios/log.h>
#include <bios/format.h>
#include <bios/linestream.h>
#include "util.h"
#include "vcf.h"
#include <stdio.h>



static char *headers[] = {"Number of synonymous SNPs","Number of nonsynonymous SNPs",
                          "Number of prematureStop SNPs","Number of removedStop SNPs",
                          "Number of splice overlaps","Number of frameshift indels",
                          "Number of non-frameshift indels",
                          "Number of SV overlaps",
                          "Number of LOF variants",
                          "Number of non-coding variants"};


static int printFlags[NUMELE (headers)];



typedef struct {
  char *id;
  int values[NUMELE (headers)];
} Row;



static void printFileHeader (FILE *fp)
{
  int i;

  for (i = 0; i < NUMELE (headers); i++) {
    if (printFlags[i] == 1) {
      fprintf (fp,"\t%s",headers[i]);
    }
  }
  fprintf (fp,"\n");
}



int main (int argc, char *argv[])
{
  Array vcfEntries;
  Array vcfGenes;
  VcfGene *currVcfGene;
  VcfAnnotation *currVcfAnnotation;
  VcfGenotype *currVcfGenotype;
  int i,j,k,l;
  VcfEntry *currVcfEntry;
  LineStream ls;
  char *chromosome;
  char *pos;
  Stringa buffer;
  char *prefix = NULL;
  FILE *fp1,*fp2;
  Array geneRows;
  Array sampleRows;
  Row *currRow;
  int first;
  int allele1,allele2;


  if (argc != 3) {
    usage ("%s <file.vcf.gz> <file.interval>",argv[0]);
  }
  util_configInit ("VAT_CONFIG_FILE");
  buffer = stringCreate (100);
  strReplace (&prefix,argv[1]);
  pos = strstr (prefix,".vcf");
  if (pos == NULL) {
    die ("Invalid input file (expected *.vcf): %s",argv[1]);
  }
  *pos = '\0';
  stringPrintf (buffer,"%s.geneSummary.txt",prefix);
  fp1 = fopen (string (buffer),"w");
  if (fp1 == NULL) {
    die ("Unable to open file: %s",string (buffer));
  }
  stringPrintf (buffer,"%s.sampleSummary.txt",prefix);
  fp2 = fopen (string (buffer),"w");
  if (fp2 == NULL) {
    die ("Unable to open file: %s",string (buffer));
  }
  first = 1;
  geneRows = arrayCreate (20000,Row);
  sampleRows = arrayCreate (1000,Row);
  stringPrintf (buffer,"%s/tabix -l %s",util_getConfigValue ("TABIX_DIR"),argv[1]);
  ls = ls_createFromPipe (string (buffer));
  while (chromosome = ls_nextLine (ls)) {
    stringPrintf (buffer,"%s/tabix -h %s %s",util_getConfigValue ("TABIX_DIR"),argv[1],chromosome);
    vcf_initFromPipe (string (buffer));
    vcfEntries = vcf_parse ();
    vcfGenes = vcf_getGeneSummaries (vcfEntries,argv[2]);
    for (i = 0; i < arrayMax (vcfGenes); i++) {
      currVcfGene = arrp (vcfGenes,i,VcfGene);
      currRow = arrayp (geneRows,arrayMax (geneRows),Row);
      for (j = 0; j < NUMELE (headers); j++) {
        currRow->values[j] = 0;
      }
      stringPrintf (buffer,"%s\t%s\t%d",currVcfGene->geneId,currVcfGene->geneName,arrayMax (currVcfGene->transcripts));
      currRow->id = hlr_strdup (string  (buffer));
      currRow->values[0] = vcf_getCountsForVcfAnnotationType (currVcfGene,"synonymous");
      currRow->values[1] = vcf_getCountsForVcfAnnotationType (currVcfGene,"nonsynonymous");  
      currRow->values[2] = vcf_getCountsForVcfAnnotationType (currVcfGene,"prematureStop");
      currRow->values[3] = vcf_getCountsForVcfAnnotationType (currVcfGene,"removedStop");
      currRow->values[4] = vcf_getCountsForVcfAnnotationType (currVcfGene,"spliceOverlap");
      currRow->values[5] = vcf_getCountsForVcfAnnotationType (currVcfGene,"insertionFS") + \
        vcf_getCountsForVcfAnnotationType (currVcfGene,"deletionFS");
      currRow->values[6] = vcf_getCountsForVcfAnnotationType (currVcfGene,"insertionNFS") + \
        vcf_getCountsForVcfAnnotationType (currVcfGene,"deletionNFS");
      currRow->values[7] = vcf_getCountsForVcfAnnotationType (currVcfGene,"svOverlap");
      currRow->values[8] = vcf_getCountsForVcfAnnotationType (currVcfGene,"insertionFS") + \
        vcf_getCountsForVcfAnnotationType (currVcfGene,"deletionFS") + \
        vcf_getCountsForVcfAnnotationType (currVcfGene,"spliceOverlap") + \
        vcf_getCountsForVcfAnnotationType (currVcfGene,"prematureStop");
      currRow->values[9] = vcf_getCountsForVcfAnnotationType (currVcfGene,"ncVariant");
    }
    for (i = 0; i < arrayMax (vcfEntries); i++) {
      currVcfEntry = arrp (vcfEntries,i,VcfEntry);
      for (j = 0; j < arrayMax (currVcfEntry->annotations); j++) {
        currVcfAnnotation = arrp (currVcfEntry->annotations,j,VcfAnnotation);
        for (k = 0; k < arrayMax (currVcfEntry->genotypes); k++) {
          currVcfGenotype = arrp (currVcfEntry->genotypes,k,VcfGenotype);
          if (first == 1) {
            currRow = arrayp (sampleRows,arrayMax (sampleRows),Row);
            stringPrintf (buffer,"%s\t%s",currVcfGenotype->sample,currVcfGenotype->group);
            currRow->id = hlr_strdup (string (buffer));
            for (l = 0; l < NUMELE (headers); l++) {
              currRow->values[l] = 0;
            }
          }
          else {
            currRow = arrp (sampleRows,k,Row);
          }
          if (vcf_getAllelesFromGenotype (currVcfGenotype->genotype,&allele1,&allele2)) {
            if (currVcfAnnotation->alleleNumber == allele1 || currVcfAnnotation->alleleNumber == allele2) {  
              if (strEqual (currVcfAnnotation->type,"synonymous")) {
                currRow->values[0]++;
              }
              if (strEqual (currVcfAnnotation->type,"nonsynonymous")) {
                currRow->values[1]++;
              }
              if (strEqual (currVcfAnnotation->type,"prematureStop")) {
                currRow->values[2]++;
              }
              if (strEqual (currVcfAnnotation->type,"removedStop")) {
                currRow->values[3]++;
              }
              if (strEqual (currVcfAnnotation->type,"spliceOverlap")) {
                currRow->values[4]++;
              }
              if (strEqual (currVcfAnnotation->type,"insertionFS") || 
                  strEqual (currVcfAnnotation->type,"deletionFS")) {
                currRow->values[5]++;
              }
              if (strEqual (currVcfAnnotation->type,"insertionNFS") || 
                  strEqual (currVcfAnnotation->type,"deletionNFS")) {
                currRow->values[6]++;
              }
              if (strEqual (currVcfAnnotation->type,"svOverlap")) {
                currRow->values[7]++;
              }
              if (strEqual (currVcfAnnotation->type,"insertionFS")||
                  strEqual (currVcfAnnotation->type,"deletionFS") ||
                  strEqual (currVcfAnnotation->type,"prematureStop") ||
                  strEqual (currVcfAnnotation->type,"spliceOverlap")) {
                currRow->values[8]++;
              }
              if (strEqual (currVcfAnnotation->type,"ncVariant")) {
                currRow->values[9]++;
              }
            }
          }
        }
        first = 0;
      }
    }
    vcf_freeEntries (vcfEntries);
    vcf_deInit ();
  }
  ls_destroy (ls);
  for (i = 0; i < NUMELE (headers); i++) {
    printFlags[i] = 0;
  }
  for (i = 0; i < NUMELE (headers); i++) {
    j = 0; 
    while (j < arrayMax (geneRows)) {
      currRow = arrp (geneRows,j,Row);
      if (currRow->values[i] > 0) {
        printFlags[i] = 1;
        break;
      }
      j++;
    }
  }
  fprintf (fp1,"Gene ID\tGene name\tNumber of transcripts");
  printFileHeader (fp1);
  for (i = 0; i < arrayMax (geneRows); i++) {
    currRow = arrp (geneRows,i,Row);
    fprintf (fp1,"%s",currRow->id);
    for (j = 0; j < NUMELE (headers); j++) {
      if (printFlags[j] == 1) {
        fprintf (fp1,"\t%d",currRow->values[j]);
      }
    }
    fprintf (fp1,"\n");
  }
  fclose (fp1);
  
  for (i = 0; i < NUMELE (headers); i++) {
    printFlags[i] = 0;
  }
  for (i = 0; i < NUMELE (headers); i++) {
    j = 0; 
    while (j < arrayMax (sampleRows)) {
      currRow = arrp (sampleRows,j,Row);
      if (currRow->values[i] > 0) {
        printFlags[i] = 1;
        break;
      }
      j++;
    }
  }
  fprintf (fp2,"Sample\tGroup");
  printFileHeader (fp2);
  for (i = 0; i < arrayMax (sampleRows); i++) {
    currRow = arrp (sampleRows,i,Row);
    fprintf (fp2,"%s",currRow->id);
    for (j = 0; j < NUMELE (headers); j++) {
      if (printFlags[j] == 1) {
        fprintf (fp2,"\t%d",currRow->values[j]);
      }
    }
    fprintf (fp2,"\n");
  }
  fclose (fp2);
  util_configDeInit ();
  return 0;
}


 
