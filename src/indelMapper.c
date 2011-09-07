#include <bios/log.h>
#include <bios/format.h>
#include <bios/linestream.h>
#include <bios/stringUtil.h>
#include <bios/numUtil.h>
#include "util.h"
#include "vcf.h"
#include <ctype.h>



#define OVERLAP_NONE 0
#define OVERLAP_FULLY_CONTAINED 1
#define OVERLAP_SPLICE 2
#define OVERLAP_START 3
#define OVERLAP_END 4



static void addSubstitution (Alteration *currAlteration, char* proteinSequenceBeforeIndel, char *proteinSequenceAfterIndel, int indelOffset) 
{
  int lengthBefore,lengthAfter;
  static Stringa buffer = NULL;
  int i;
  int diff;
  int index;

  stringCreateClear (buffer,100);
  index = ((currAlteration->relativePosition - 1) / 3);
  lengthBefore = strlen (proteinSequenceBeforeIndel);
  lengthAfter = strlen (proteinSequenceAfterIndel);
  diff = abs (lengthBefore - lengthAfter);
  if (lengthBefore < lengthAfter) {
    stringPrintf (buffer,"%d_%c->",index + 1,proteinSequenceBeforeIndel[index]);
    for (i = 0; i <= diff; i++) {
      stringAppendf (buffer,"%c",proteinSequenceAfterIndel[index + i]);
    }
  }
  else if (lengthBefore > lengthAfter) {
    stringPrintf (buffer,"%d_",index + 1);
    for (i = 0; i <= diff; i++) {
      stringAppendf (buffer,"%c",proteinSequenceBeforeIndel[index + i]);
    }
    stringAppendf (buffer,"->%c",proteinSequenceAfterIndel[index]);
  }
  else {
    stringPrintf (buffer,"%d_%s->",index,subString (proteinSequenceBeforeIndel,index - 1,index + (int)ceil ((double)indelOffset / 3)));
    stringAppendf (buffer,"%s",subString (proteinSequenceAfterIndel,index - 1,index + (int)ceil ((double)indelOffset / 3)));
  } 
  currAlteration->substitution = hlr_strdup (string (buffer));
}



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
  char *proteinSequenceBeforeIndel;
  char *proteinSequenceAfterIndel;
  int numDisabledTranscripts;
  Stringa disabledTranscripts;
  int seqLength,refLength,altLength;
  char *sequenceBeforeIndel = NULL;
  int overlapMode;
  int numOverlaps;
  int sizeIndel,indelOffset;
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
      sizeIndel = abs (refLength - altLength);
      indelOffset = MAX (refLength,altLength) - 1; 
      util_clearAlterations (alterations);
      intervals = intervalFind_getOverlappingIntervals (currVcfEntry->chromosome,position,position + indelOffset);
      for (i = 0; i < arrayMax (intervals); i++) {
        currInterval = arru (intervals,i,Interval*);
        overlapMode = OVERLAP_NONE;
        numOverlaps = 0;
        for (j = 0; j < arrayMax (currInterval->subIntervals); j++) {
          currSubInterval = arrp (currInterval->subIntervals,j,SubInterval);
          overlap = rangeIntersection (position,position + indelOffset,currSubInterval->start,currSubInterval->end);
          if (currSubInterval->start <= position && (position + indelOffset) < currSubInterval->end) {
            overlapMode = OVERLAP_FULLY_CONTAINED;
            numOverlaps++;
          }
          else if (j == 0 && overlap > 0 && position < currSubInterval->start) {
            overlapMode = OVERLAP_START;
            numOverlaps++;
          }
          else if (j == (arrayMax (currInterval->subIntervals) - 1) && overlap > 0 && (position + indelOffset) >= currSubInterval->end) {
            overlapMode = OVERLAP_END;
            numOverlaps++;
          }
          else if (overlap > 0 && overlap <= indelOffset) {
            overlapMode = OVERLAP_SPLICE;
            numOverlaps++;
          }
        }
        if (overlapMode == OVERLAP_NONE) {
          continue;
        }
        currAlteration = arrayp (alterations,arrayMax (alterations),Alteration);
        if (numOverlaps > 1) {
          util_addAlteration (currAlteration,currInterval->name,"multiExonHit",currInterval,position,0);
          continue;
        }
        else if (numOverlaps == 1 && overlapMode == OVERLAP_SPLICE) {
          util_addAlteration (currAlteration,currInterval->name,"spliceOverlap",currInterval,position,0);
          continue;
        }
        else if (numOverlaps == 1 && overlapMode == OVERLAP_START) {
          util_addAlteration (currAlteration,currInterval->name,"startOverlap",currInterval,position,0);
          continue;
        }
        else if (numOverlaps == 1 && overlapMode == OVERLAP_END) {
          util_addAlteration (currAlteration,currInterval->name,"endOverlap",currInterval,position,0);
          continue;
        }
        else if (numOverlaps == 1 && overlapMode == OVERLAP_FULLY_CONTAINED && altLength > refLength) {
          if ((sizeIndel % 3) == 0) {
            util_addAlteration (currAlteration,currInterval->name,"insertionNFS",currInterval,position,0);
          }
          else {
            util_addAlteration (currAlteration,currInterval->name,"insertionFS",currInterval,position,0);
          }
        }
        else if (numOverlaps == 1 && overlapMode == OVERLAP_FULLY_CONTAINED && altLength < refLength) {
          if ((sizeIndel % 3) == 0) {
            util_addAlteration (currAlteration,currInterval->name,"deletionNFS",currInterval,position,0);
          }
          else {
            util_addAlteration (currAlteration,currInterval->name,"deletionFS",currInterval,position,0);
          }
        }
        else if (numOverlaps == 1 && overlapMode == OVERLAP_FULLY_CONTAINED && altLength == refLength) {
          util_addAlteration (currAlteration,currInterval->name,"substitution",currInterval,position,0);
        }
        else {
          die ("Unexpected type: %d %s %s %s",
               currVcfEntry->position,currVcfEntry->chromosome,
               currVcfEntry->referenceAllele,currVcfEntry->alternateAllele);
        }
        if ((sizeIndel % 3) != 0 && altLength != refLength) { 
          continue;
        }
        // Only run the remaining block of code if the indel is fully contained (insertion or deletion) AND does not cause a frameshift OR
        // if it is a substitution that is fully contained in the coding sequence
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
        strReplace (&sequenceBeforeIndel,currSeq->sequence);
        seqLength = strlen (sequenceBeforeIndel); 
        coordinates = util_getCoordinates (currInterval);
        // arraySort (coordinates,(ARRAYORDERF)util_sortCoordinatesByChromosomeAndTranscriptPosition); Array is already sorted by definition
        j = 0;
        stringClear (buffer);
        while (j < seqLength) {
          if (util_getGenomicCoordinate (coordinates,j,currVcfEntry->chromosome) == position) {
            if (altLength > refLength) {
              stringCat (buffer,textItem (alternateAlleles,h));
              j++;
              continue;
            }
            else if (altLength < refLength) {
              stringCatChar (buffer,sequenceBeforeIndel[j]);
              j = j + refLength - altLength + 1;
              continue;
            }
            else {
              stringCat (buffer,textItem (alternateAlleles,h));
              j = j + altLength;
              continue;
            }
          }
          stringCatChar (buffer,sequenceBeforeIndel[j]);
          j++;
        }
        util_destroyCoordinates (coordinates);
        proteinSequenceBeforeIndel = hlr_strdup (util_translate (currInterval,sequenceBeforeIndel));
        proteinSequenceAfterIndel = hlr_strdup (util_translate (currInterval,string (buffer)));
        addSubstitution (currAlteration,proteinSequenceBeforeIndel,proteinSequenceAfterIndel,indelOffset);
        hlr_free (proteinSequenceBeforeIndel);
        hlr_free (proteinSequenceAfterIndel);
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
        if (currAlteration->substitution[0] != '\0') {
          stringAppendf (disabledTranscripts,"%s:%s:%d_%d_%s",currAlteration->transcriptName,currAlteration->transcriptId,currAlteration->transcriptLength,currAlteration->relativePosition,currAlteration->substitution);
        }
        else if (strEqual (currAlteration->type,"multiExonHit") || strEqual (currAlteration->type,"spliceOverlap") ||
                 strEqual (currAlteration->type,"startOverlap") || strEqual (currAlteration->type,"endOverlap")) {
          stringAppendf (disabledTranscripts,"%s:%s:%d",currAlteration->transcriptName,currAlteration->transcriptId,currAlteration->transcriptLength);
        }
        else {
          stringAppendf (disabledTranscripts,"%s:%s:%d_%d",currAlteration->transcriptName,currAlteration->transcriptId,currAlteration->transcriptLength,currAlteration->relativePosition);
        }
        numDisabledTranscripts = 1;
        j = i + 1;
        while (j < arrayMax (alterations)) {
          nextAlteration = arrp (alterations,j,Alteration);
          if (strEqual (currAlteration->geneId,nextAlteration->geneId) && 
              strEqual (currAlteration->type,nextAlteration->type)) {
            if (nextAlteration->substitution[0] != '\0') {
              stringAppendf (disabledTranscripts,":%s:%s:%d_%d_%s",nextAlteration->transcriptName,nextAlteration->transcriptId,nextAlteration->transcriptLength,nextAlteration->relativePosition,nextAlteration->substitution);
            }
            else if (strEqual (nextAlteration->type,"multiExonHit") || strEqual (nextAlteration->type,"spliceOverlap") ||
                     strEqual (nextAlteration->type,"startOverlap") || strEqual (nextAlteration->type,"endOverlap")) {
              stringAppendf (disabledTranscripts,":%s:%s:%d",nextAlteration->transcriptName,nextAlteration->transcriptId,nextAlteration->transcriptLength);
            }
            else {
              stringAppendf (disabledTranscripts,":%s:%s:%d_%d",nextAlteration->transcriptName,nextAlteration->transcriptId,nextAlteration->transcriptLength,nextAlteration->relativePosition);
            }
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




