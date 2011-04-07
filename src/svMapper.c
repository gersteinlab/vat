#include <bios/log.h>
#include <bios/format.h>
#include <bios/linestream.h>
#include <bios/stringUtil.h>
#include <bios/numUtil.h>
#include "util.h"
#include "vcf.h"
#include <ctype.h>




int main (int argc, char *argv[])
{
  Array intervals;
  Interval *currInterval;
  SubInterval *currSubInterval;
  int h,i,j;
  Array seqs;
  Seq *currSeq,testSeq;
  int index;
  Stringa buffer;
  Array geneTranscriptEntries;
  Texta geneTranscriptIds;
  Array alterations;
  Alteration *currAlteration,*nextAlteration;
  int numDisabledTranscripts;
  Stringa disabledTranscripts;
  int refLength,altLength;
  int offset;
  int overlap;
  Array coordinates;
  VcfEntry *currVcfEntry;
  VcfGenotype *currVcfGenotype;
  int position;
  Texta alternateAlleles;
  int flag1,flag2;
  
  if (argc != 3) {
    usage ("%s <annotation.interval> <annotation.fa>",argv[0]);
  }
  intervalFind_addIntervalsToSearchSpace (argv[1],0);
  geneTranscriptEntries = util_getGeneTranscriptEntries (intervalFind_getAllIntervals ());
  seq_init ();
  fasta_initFromFile (argv[2]);
  seqs = fasta_readAllSequences (0);
  fasta_deInit ();
  arraySort (seqs,(ARRAYORDERF)util_sortSequencesByName); 
  buffer = stringCreate (100);
  disabledTranscripts = stringCreate (100);
  alterations = arrayCreate (100,Alteration);
  vcf_init ("-");
  stringPrintf (buffer,"##INFO=<ID=VA,Number=.,Type=String,Description=\"Variant Annotation, %s\">",argv[1]);
  vcf_addComment (string (buffer));
  puts (vcf_writeMetaData ());
  puts (vcf_writeColumnHeaders ());
  while (currVcfEntry = vcf_nextEntry ()) {
    if (vcf_isInvalidAlternateAllele (currVcfEntry)) {
      continue;
    }
    flag1 = 0;
    flag2 = 0;
    position = currVcfEntry->position - 1; // make zero-based
    alternateAlleles = vcf_getAlternateAlleles (currVcfEntry);
    for (h = 0; h < arrayMax (alternateAlleles); h++) {
      refLength = strlen (currVcfEntry->referenceAllele);
      altLength = strlen (textItem (alternateAlleles,h));
      if (refLength > altLength) {
        offset = refLength - altLength; // just for deletions
      }
      else {
        continue;
      }
      util_clearAlterations (alterations);
      intervals = intervalFind_getOverlappingIntervals (currVcfEntry->chromosome,position,position + offset);
      for (i = 0; i < arrayMax (intervals); i++) {
        currInterval = arru (intervals,i,Interval*);
        j = 0; 
        while (j < arrayMax (currInterval->subIntervals)) {
          currSubInterval = arrp (currInterval->subIntervals,j,SubInterval);
          overlap = rangeIntersection (position,position + offset,currSubInterval->start,currSubInterval->end);
          if (overlap > 0) {
            break;
          }
          j++;
        }
        if (j == arrayMax (currInterval->subIntervals)) {
          continue;
        }
       
        stringPrintf (buffer,"%s|%s|%c|",currInterval->name,currInterval->chromosome,currInterval->strand);
        for (j = 0; j < arrayMax (currInterval->subIntervals); j++) {
          currSubInterval = arrp (currInterval->subIntervals,j,SubInterval);
          stringAppendf (buffer,"%d|%d%s",currSubInterval->start,currSubInterval->end,j < arrayMax (currInterval->subIntervals) - 1 ? "|" : "");
        }
        testSeq.name = hlr_strdup (string (buffer));
        if (!arrayFind (seqs,&testSeq,&index,(ARRAYORDERF)util_sortSequencesByName)) {
          die ("Expected to find %s in seqs",string (buffer));
        }
        hlr_free (testSeq.name);
        currSeq = arrp (seqs,index,Seq);
        coordinates = util_getCoordinates (currInterval);
        // arraySort (coordinates,(ARRAYORDERF)util_sortCoordinatesByChromosomeAndTranscriptPosition); Array is already sorted by definition
    
        stringClear (buffer);
        for (j = 0; j < arrayMax (currInterval->subIntervals); j++) {
          currSubInterval = arrp (currInterval->subIntervals,j,SubInterval);
          overlap = rangeIntersection (position,position + offset,currSubInterval->start,currSubInterval->end);
          if (overlap > 0) {
            ;
          }
          else {
            ;
          }
        }
        util_destroyCoordinates (coordinates);
        currAlteration = arrayp (alterations,arrayMax (alterations),Alteration);
        util_addAlteration (currAlteration,currInterval->name,"SV",currInterval,position);
      }
      if (arrayMax (alterations) == 0) {
        continue;
      }
      arraySort (alterations,(ARRAYORDERF)util_sortAlterationsByGeneIdAndType);
      stringClear (buffer);
      i = 0;
      while (i < arrayMax (alterations)) {
        currAlteration = arrp (alterations,i,Alteration);
        stringAppendf (buffer,"%s%d:%s:%s:%c:%s",stringLen (buffer) == 0 ? "" : ",",h + 1,currAlteration->geneName,currAlteration->geneId,currAlteration->strand,currAlteration->type);
        stringClear (disabledTranscripts);
        stringAppendf (disabledTranscripts,"%s:%s:%d",currAlteration->transcriptName,currAlteration->transcriptId,currAlteration->transcriptLength);
        numDisabledTranscripts = 1;
        j = i + 1;
        while (j < arrayMax (alterations)) {
          nextAlteration = arrp (alterations,j,Alteration);
          if (strEqual (currAlteration->geneId,nextAlteration->geneId) && 
              strEqual (currAlteration->type,nextAlteration->type)) {
            stringAppendf (disabledTranscripts,":%s:%s:%d",nextAlteration->transcriptName,nextAlteration->transcriptId,nextAlteration->transcriptLength);
            numDisabledTranscripts++;
          }
          else {
            break;
          }
          j++;
        }
        i = j;
        geneTranscriptIds = util_getTranscriptIdsForGeneId (geneTranscriptEntries,currAlteration->geneId);
        stringAppendf (buffer,":%d/%d:%s",numDisabledTranscripts,arrayMax (geneTranscriptIds),string (disabledTranscripts));
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




