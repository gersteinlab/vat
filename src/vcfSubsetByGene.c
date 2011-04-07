#include <bios/log.h>
#include <bios/format.h>
#include <bios/linestream.h>
#include "util.h"
#include "vcf.h"
#include <stdio.h>



int main (int argc, char *argv[])
{
  LineStream ls;
  char *chromosome;
  Array vcfEntries;
  Array vcfGenes;
  VcfGene *currVcfGene;
  int i,j;
  Stringa buffer;
  FILE *fp;
  VcfEntry *currVcfEntry;
  
  if (argc != 4) {
    usage ("%s <file.vcf.gz> <gencode.interval> <outputDir>",argv[0]);
  }
  util_configInit ("VAT_CONFIG_FILE");
  buffer = stringCreate (100);
  stringPrintf (buffer,"%s/tabix -l %s",util_getConfigValue ("TABIX_DIR"),argv[1]);
  ls = ls_createFromPipe (string (buffer));
  while (chromosome = ls_nextLine (ls)) {
    stringPrintf (buffer,"%s/tabix -h %s %s",util_getConfigValue ("TABIX_DIR"),argv[1],chromosome);
    vcf_initFromPipe (string (buffer));
    vcfEntries = vcf_parse ();
    vcfGenes = vcf_getGeneSummaries (vcfEntries,argv[2]);
    for (i = 0; i < arrayMax (vcfGenes); i++) {
      currVcfGene = arrp (vcfGenes,i,VcfGene);
      stringPrintf (buffer,"%s/%s.vcf",argv[3],currVcfGene->geneId);
      fp = fopen (string (buffer),"w");
      if (fp == NULL) {
        die ("Unable to open file: %s",string (buffer));
      }
      fprintf (fp,"%s\n",vcf_writeMetaData ());
      fprintf (fp,"%s\n",vcf_writeColumnHeaders ());
      for (j = 0; j < arrayMax (currVcfGene->vcfEntries); j++) {
        currVcfEntry = arru (currVcfGene->vcfEntries,j,VcfEntry*);
        fprintf (fp,"%s\n",vcf_writeEntry (currVcfEntry));
      }
      fclose (fp);
    }
    vcf_freeEntries (vcfEntries);
    vcf_deInit ();
  }
  ls_destroy (ls);
  util_configDeInit ();
  return 0;
}


 
