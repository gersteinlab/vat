#include <bios/log.h>
#include <bios/format.h>
#include <bios/linestream.h>
#include <bios/numUtil.h>
#include "util.h"
#include "vcf.h"



int main (int argc, char *argv[])
{
  Array intervals;
  Interval *currInterval;
  SubInterval *currSubInterval;
  int refLength,altLength,offset;
  int h,i,j;
  Stringa buffer;
  Array geneTranscriptEntries;
  Texta geneTranscriptIds;
  Array alterations;
  Alteration *currAlteration,*nextAlteration;
  int numTranscripts;
  Stringa transcripts;
  VcfEntry *currVcfEntry;
  int position;
  Texta alternateAlleles;
  int flag1,flag2;
  VcfGenotype *currVcfGenotype;
 
  if (argc != 3) {
    usage ("%s <annotation.interval> <nameFeature>",argv[0]);
  }
  intervalFind_addIntervalsToSearchSpace (argv[1],0);
  geneTranscriptEntries = util_getGeneTranscriptEntries (intervalFind_getAllIntervals ());
  buffer = stringCreate (100);
  transcripts = stringCreate (100);
  alterations = arrayCreate (100,Alteration);
  vcf_init ("-");
  stringPrintf (buffer,"##INFO=<ID=VA,Number=.,Type=String,Description=\"Variant Annotation, %s, %s\">",argv[1],argv[2]);
  vcf_addComment (string (buffer));
  puts (vcf_writeMetaData ());
  puts (vcf_writeColumnHeaders ());
  while (currVcfEntry = vcf_nextEntry ()) {
    if (vcf_isInvalidEntry (currVcfEntry)) {
      continue;
    }
    flag1 = 0;
    flag2 = 0;
    position = currVcfEntry->position - 1; // make zero-based
    alternateAlleles = vcf_getAlternateAlleles (currVcfEntry);
    for (h = 0; h < arrayMax (alternateAlleles); h++) {
      refLength = strlen (currVcfEntry->referenceAllele);
      altLength = strlen (textItem (alternateAlleles,h));
      offset = MAX (refLength,altLength) - 1; 
      util_clearAlterations (alterations);
      intervals = intervalFind_getOverlappingIntervals (currVcfEntry->chromosome,position,position + offset);
      for (i = 0; i < arrayMax (intervals); i++) {
        currInterval = arru (intervals,i,Interval*);
        j = 0; 
        while (j < arrayMax (currInterval->subIntervals)) {
          currSubInterval = arrp (currInterval->subIntervals,j,SubInterval);
          if (currSubInterval->start <= position && (position + offset) < currSubInterval->end) {
            break;
          }
          j++;
        }
        if (j == arrayMax (currInterval->subIntervals)) {
          continue;
        }
        util_addAlteration (arrayp (alterations,arrayMax (alterations),Alteration),currInterval->name,argv[2],currInterval,position,0);
      }
      if (arrayMax (alterations) == 0) {
        continue;
      }
      arraySort (alterations,(ARRAYORDERF)util_sortAlterationsByGeneIdAndType);
      stringClear (buffer);
      i = 0;
      while (i < arrayMax (alterations)) {
        currAlteration = arrp (alterations,i,Alteration);
        stringAppendf (buffer,"%s%d:%s:%s:%c:%s",stringLen (buffer) == 0 ? "" : "|",h + 1,currAlteration->geneName,currAlteration->geneId,currAlteration->strand,currAlteration->type);
        stringClear (transcripts);
        stringAppendf (transcripts,"%s:%s:%d_%d",currAlteration->transcriptName,currAlteration->transcriptId,currAlteration->transcriptLength,currAlteration->relativePosition);
        numTranscripts = 1;
        j = i + 1;
        while (j < arrayMax (alterations)) {
          nextAlteration = arrp (alterations,j,Alteration);
          if (strEqual (currAlteration->geneId,nextAlteration->geneId) && 
              strEqual (currAlteration->type,nextAlteration->type)) {
            stringAppendf (transcripts,":%s:%s:%d_%d",nextAlteration->transcriptName,nextAlteration->transcriptId,nextAlteration->transcriptLength,nextAlteration->relativePosition);
            numTranscripts++;
          }
          else {
            break;
          }
          j++;
        }
        i = j;
        geneTranscriptIds = util_getTranscriptIdsForGeneId (geneTranscriptEntries,currAlteration->geneId);
        stringAppendf (buffer,":%d/%d:%s",numTranscripts,arrayMax (geneTranscriptIds),string (transcripts));
      }
      if (flag1 == 0) {
        printf ("%s\t%d\t%s\t%s\t%s\t%s\t%s\t%s;VA=",
                currVcfEntry->chromosome,currVcfEntry->position,currVcfEntry->id,
                currVcfEntry->referenceAllele,currVcfEntry->alternateAllele,
                currVcfEntry->quality,currVcfEntry->filter,currVcfEntry->info);
        flag1 = 1;
      }
      printf ("%s%s",flag2 == 1 ? "," : "",string (buffer)); 
      flag2 = 1;
    }
    if (flag1 == 1) {
      for (i = 0; i < arrayMax (currVcfEntry->genotypes); i++) {
        currVcfGenotype = arrp (currVcfEntry->genotypes,i,VcfGenotype);
        if (i == 0) {
          printf ("\t%s\t",currVcfEntry->genotypeFormat);
        }
        printf ("%s%s%s%s",currVcfGenotype->genotype,
                currVcfGenotype->details[0] != '\0' ? ":" : "",
                currVcfGenotype->details[0] != '\0' ?  currVcfGenotype->details : "",
                i < arrayMax (currVcfEntry->genotypes) - 1 ? "\t" : ""); 
      }
      puts ("");
    }
  }
  vcf_deInit ();
  return 0;
}
