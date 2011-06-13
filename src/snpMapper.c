#include <bios/log.h>
#include <bios/format.h>
#include <bios/linestream.h>
#include <bios/stringUtil.h>
#include "util.h"
#include "vcf.h"
#include <ctype.h>



static void addSubstitution (Alteration *currAlteration, char* proteinSequenceBeforeSNP, char *proteinSequenceAfterSNP) 
{
  int lengthBefore,lengthAfter;
  static Stringa buffer = NULL;
  int i;
  int index;

  stringCreateClear (buffer,100);
  lengthBefore = strlen (proteinSequenceBeforeSNP);
  lengthAfter = strlen (proteinSequenceAfterSNP);
  index = ((currAlteration->relativePosition - 1) / 3);
  if (lengthBefore != lengthAfter) {
    die ("Expected same length: %d, %d",lengthBefore,lengthAfter);
  }
  if (strEqual (currAlteration->type,"synonymous")) {
    stringPrintf (buffer,"%d_%c->%c",
                  index + 1,
                  proteinSequenceBeforeSNP[index],
                  proteinSequenceAfterSNP[index]);
    currAlteration->substitution = hlr_strdup (string (buffer));
    return;
  }
  i = 0;
  while (i < lengthBefore) {
    if (proteinSequenceBeforeSNP[i] != proteinSequenceAfterSNP[i]) {
      stringPrintf (buffer,"%d_%c->%c",
                    index + 1,
                    proteinSequenceBeforeSNP[i],
                    proteinSequenceAfterSNP[i]);
      break;
    }
    i++;
  }
  currAlteration->substitution = hlr_strdup (string (buffer));
}



static int detectSpliceJunctionAlterations (Interval *currInterval, int position)
{
  SubInterval *currSubInterval;
  int i;

  if (arrayMax (currInterval->subIntervals) == 1) {
    return 0;
  }
  for (i = 0; i < arrayMax (currInterval->subIntervals); i++) {
    currSubInterval = arrp (currInterval->subIntervals,i,SubInterval);
    if (i == 0 && 
        currSubInterval->end <= position && 
        position < (currSubInterval->end + 2)) {
      return 1;
    }
    else if (i == (arrayMax (currInterval->subIntervals) - 1) &&
             (currSubInterval->start - 2) <= position && 
             position < currSubInterval->start) {
      return 1;
    }
    else if ((i > 0 && i < (arrayMax (currInterval->subIntervals) - 1)) &&
             (((currSubInterval->start - 2) <= position && 
               position < currSubInterval->start) ||
              (currSubInterval->end <= position && 
               position < (currSubInterval->end + 2)))) {
      return 1;
    }
  }
  return 0;
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
  int numStopsBeforeSNP;
  int numStopsAfterSNP;
  Array geneTranscriptEntries;
  Texta geneTranscriptIds;
  Array alterations;
  Alteration *currAlteration,*nextAlteration;
  char *proteinSequenceBeforeSNP;
  char *proteinSequenceAfterSNP;
  int numDisabledTranscripts;
  Stringa disabledTranscripts;
  char *sequenceBeforeSNP = NULL;
  char *sequenceAfterSNP = NULL;
  int transcriptCoordinate;
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
      if (strlen (currVcfEntry->referenceAllele) != 1 ||
          strlen (textItem (alternateAlleles,h)) != 1) {
        warn ("Unexpected alleles: ref=%s, alt=%s",currVcfEntry->referenceAllele,textItem (alternateAlleles,h));
        continue;
      }
      intervals = intervalFind_getOverlappingIntervals (currVcfEntry->chromosome,position,position);
      util_clearAlterations (alterations);
      for (i = 0; i < arrayMax (intervals); i++) {
        currInterval = arru (intervals,i,Interval*);
        if (detectSpliceJunctionAlterations (currInterval,position)) {
          util_addAlteration (arrayp (alterations,arrayMax (alterations),Alteration),
                              currInterval->name,"spliceOverlap",currInterval,position,0);
          continue;
        }
        j = 0; 
        while (j < arrayMax (currInterval->subIntervals)) {
          currSubInterval = arrp (currInterval->subIntervals,j,SubInterval);
          if (currSubInterval->start <= position && position < currSubInterval->end) {
            break;
          }
          j++;
        }
        if (j == arrayMax (currInterval->subIntervals)) {
          /*
          util_addAlteration (arrayp (alterations,arrayMax (alterations),Alteration),
                              currInterval->name,"intronic",currInterval,position,0);
          */
          continue;
        }
        currAlteration = arrayp (alterations,arrayMax (alterations),Alteration);
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
        transcriptCoordinate = util_getTranscriptCoordinate (coordinates,position,currVcfEntry->chromosome);       
        strReplace (&sequenceBeforeSNP,currSeq->sequence);
        if (sequenceBeforeSNP[transcriptCoordinate] != currVcfEntry->referenceAllele[0]) {
          warn ("Reference allele different from reference");
        }
        strReplace (&sequenceAfterSNP,currSeq->sequence);
        sequenceAfterSNP[transcriptCoordinate] = textItem (alternateAlleles,h)[0];
        util_destroyCoordinates (coordinates);
        proteinSequenceBeforeSNP = hlr_strdup (util_translate (currInterval,sequenceBeforeSNP));
        proteinSequenceAfterSNP = hlr_strdup (util_translate (currInterval,sequenceAfterSNP));
        numStopsBeforeSNP = countChars (proteinSequenceBeforeSNP,'*');
        numStopsAfterSNP = countChars (proteinSequenceAfterSNP,'*');
        if (numStopsAfterSNP > numStopsBeforeSNP) {
          util_addAlteration (currAlteration,currInterval->name,"prematureStop",currInterval,position,0);
        }
        else if (numStopsAfterSNP < numStopsBeforeSNP) {
          util_addAlteration (currAlteration,currInterval->name,"removedStop",currInterval,position,0);
        }
        else if (strEqual (proteinSequenceBeforeSNP,proteinSequenceAfterSNP)) {
          util_addAlteration (currAlteration,currInterval->name,"synonymous",currInterval,position,0);
        }
        else if (!strEqual (proteinSequenceBeforeSNP,proteinSequenceAfterSNP)) {
          util_addAlteration (currAlteration,currInterval->name,"nonsynonymous",currInterval,position,0);
        }
        addSubstitution (currAlteration,proteinSequenceBeforeSNP,proteinSequenceAfterSNP);
        hlr_free (proteinSequenceBeforeSNP);
        hlr_free (proteinSequenceAfterSNP);
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
        if (strEqual (currAlteration->type,"spliceOverlap")) {
          stringAppendf (disabledTranscripts,"%s:%s:%d",currAlteration->transcriptName,currAlteration->transcriptId,currAlteration->transcriptLength);
        }
        else {
          stringAppendf (disabledTranscripts,"%s:%s:%d_%d_%s",currAlteration->transcriptName,currAlteration->transcriptId,currAlteration->transcriptLength,currAlteration->relativePosition,currAlteration->substitution);
        }
        numDisabledTranscripts = 1;
        j = i + 1;
        while (j < arrayMax (alterations)) {
          nextAlteration = arrp (alterations,j,Alteration);
          if (strEqual (currAlteration->geneId,nextAlteration->geneId) && 
              strEqual (currAlteration->type,nextAlteration->type)) {
            if (strEqual (currAlteration->type,"spliceOverlap")) {
              stringAppendf (disabledTranscripts,":%s:%s:%d",nextAlteration->transcriptName,nextAlteration->transcriptId,nextAlteration->transcriptLength);
            }
            else {
              stringAppendf (disabledTranscripts,":%s:%s:%d_%d_%s",nextAlteration->transcriptName,nextAlteration->transcriptId,nextAlteration->transcriptLength,nextAlteration->relativePosition,nextAlteration->substitution);               
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

