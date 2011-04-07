#include <bios/log.h>
#include <bios/format.h>
#include <bios/linestream.h>
#include "util.h"
#include <stdlib.h>



void util_processTranscriptLine (char *transcriptLine, char **geneId, char **transcriptId, char **geneName, char **transcriptName)
{
  Texta tokens;

  tokens = textStrtokP (transcriptLine,"|");
  if (arrayMax (tokens) != 4) {
    die ("Unexpected interval name: %s\nRequire: geneId|transcriptId|geneName|transcriptName");
  }
  strReplace (geneId,textItem (tokens,0));
  strReplace (transcriptId,textItem (tokens,1));
  strReplace (geneName,textItem (tokens,2));
  strReplace (transcriptName,textItem (tokens,3));
  textDestroy (tokens);
}



static int util_sortGeneTranscriptEntriesByGeneId (GeneTranscriptEntry *a,  GeneTranscriptEntry *b)
{
  return strcmp (a->geneId,b->geneId);
}



Array util_getGeneTranscriptEntries (Array intervals) 
{
  Interval *currInterval;
  GeneTranscriptEntry *currEntry;
  Array geneTranscriptEntries;
  char *geneId,*transcriptId,*geneName,*transcriptName;
  int i;
  
  geneId = NULL;
  transcriptId = NULL;
  geneName = NULL;
  transcriptName = NULL;
  geneTranscriptEntries = arrayCreate (50000,GeneTranscriptEntry);
  for (i = 0; i < arrayMax (intervals); i++) {
    currInterval = arrp (intervals,i,Interval);
    util_processTranscriptLine (currInterval->name,&geneId,&transcriptId,&geneName,&transcriptName);
    currEntry = arrayp (geneTranscriptEntries,arrayMax (geneTranscriptEntries),GeneTranscriptEntry);
    currEntry->geneId = hlr_strdup (geneId);
    currEntry->transcriptId = hlr_strdup (transcriptId);
    currEntry->geneName = hlr_strdup (geneName);
    currEntry->transcriptName = hlr_strdup (transcriptName);
  }
  arraySort (geneTranscriptEntries,(ARRAYORDERF)util_sortGeneTranscriptEntriesByGeneId);
  return geneTranscriptEntries;
}



void util_destroyGeneTranscriptEntries (Array geneTranscriptEntries)
{
  int i;
  GeneTranscriptEntry *currEntry;

  for (i = 0; i < arrayMax (geneTranscriptEntries); i++) {
    currEntry = arrp (geneTranscriptEntries,i,GeneTranscriptEntry);
    hlr_free (currEntry->geneId);
    hlr_free (currEntry->transcriptId);
    hlr_free (currEntry->geneName);
    hlr_free (currEntry->transcriptName);
  }
  arrayDestroy (geneTranscriptEntries);
}



Texta util_getTranscriptIdsForGeneId (Array geneTranscriptEntries, char *geneId)
{
  static Texta transcriptIds = NULL;
  int index;
  GeneTranscriptEntry *currEntry,testEntry;
  int i;

  textCreateClear (transcriptIds,100);
  testEntry.geneId = hlr_strdup (geneId);
  if (!arrayFind (geneTranscriptEntries,&testEntry,&index,(ARRAYORDERF)util_sortGeneTranscriptEntriesByGeneId)) {
    die ("Expected to find geneId : %s",geneId);
  }
  hlr_free (testEntry.geneId);
  i = index;
  while (i < arrayMax (geneTranscriptEntries)) {
    currEntry = arrp (geneTranscriptEntries,i,GeneTranscriptEntry);
    if (strEqual (currEntry->geneId,geneId)) {
      textAdd (transcriptIds,currEntry->transcriptId);
    }
    else {
      break;
    }
    i++;
  }
  i = index - 1;
  while (i > 0) {
    currEntry = arrp (geneTranscriptEntries,i,GeneTranscriptEntry);
    if (strEqual (currEntry->geneId,geneId)) {
      textAdd (transcriptIds,currEntry->transcriptId);
    }
    else {
      break;
    }
    i--;
  }
  return transcriptIds;
}



Texta util_getQueryStringsForGeneId (Array geneTranscriptEntries, char *geneId)
{
  static Texta queryStrings = NULL;
  static Stringa buffer = NULL;
  int index;
  GeneTranscriptEntry *currEntry,testEntry;
  int i;

  stringCreateClear (buffer,1000);
  textCreateClear (queryStrings,100);
  testEntry.geneId = hlr_strdup (geneId);
  if (!arrayFind (geneTranscriptEntries,&testEntry,&index,(ARRAYORDERF)util_sortGeneTranscriptEntriesByGeneId)) {
    die ("Expected to find geneId : %s",geneId);
  }
  hlr_free (testEntry.geneId);
  i = index;
  while (i < arrayMax (geneTranscriptEntries)) {
    currEntry = arrp (geneTranscriptEntries,i,GeneTranscriptEntry);
    if (strEqual (currEntry->geneId,geneId)) {
      stringPrintf (buffer,"%s|%s|%s|%s",currEntry->geneId,currEntry->transcriptId,currEntry->geneName,currEntry->transcriptName);
      textAdd (queryStrings,string (buffer));
    }
    else {
      break;
    }
    i++;
  }
  i = index - 1;
  while (i > 0) {
    currEntry = arrp (geneTranscriptEntries,i,GeneTranscriptEntry);
    if (strEqual (currEntry->geneId,geneId)) {
      stringPrintf (buffer,"%s|%s|%s|%s",currEntry->geneId,currEntry->transcriptId,currEntry->geneName,currEntry->transcriptName);
      textAdd (queryStrings,string (buffer));
    }
    else {
      break;
    }
    i--;
  }
  return queryStrings;
}



char* util_translate (Interval *currInterval, char *sequence)
{
  if (currInterval->strand == '-') {
    seq_reverseComplement (sequence,strlen (sequence));
  }
  return seq_dnaTranslate (sequence,0);
}



int util_sortSequencesByName (Seq *a, Seq *b)
{
  return strcmp (a->name,b->name);
}



int util_sortCoordinatesByChromosomeAndGenomicPosition (Coordinate *a, Coordinate *b)
{
  int diff;
  
  diff = strcmp (a->chromosome,b->chromosome);
  if (diff != 0) {
    return diff;
  }
  return a->genomic - b->genomic;
}



int util_sortCoordinatesByChromosomeAndTranscriptPosition (Coordinate *a, Coordinate *b)
{
  int diff;
  
  diff = strcmp (a->chromosome,b->chromosome);
  if (diff != 0) {
    return diff;
  }
  return a->transcript - b->transcript;
}



Array util_getCoordinates (Interval *transcript)
{
  int i,j,k;
  SubInterval *currExon;
  Coordinate *currCoordinate;
  Array coordinates;

  coordinates = arrayCreate (10000,Coordinate);
  k = 0;
  for (i = 0; i < arrayMax (transcript->subIntervals); i++) {
    currExon = arrp (transcript->subIntervals,i,SubInterval);
    for (j = currExon->start; j < currExon->end; j++) {
      currCoordinate = arrayp (coordinates,arrayMax (coordinates),Coordinate);
      currCoordinate->genomic = j;
      currCoordinate->transcript = k;
      currCoordinate->chromosome = hlr_strdup (transcript->chromosome);
      k++;
    }
  }
  return coordinates;
}



void util_destroyCoordinates (Array coordinates)
{
  Coordinate *currCoordinate;
  int i;

  for (i = 0; i < arrayMax (coordinates); i++) {
    currCoordinate = arrp (coordinates,i,Coordinate);
    hlr_free (currCoordinate->chromosome);
  }
  arrayDestroy (coordinates);
}



int util_getGenomicCoordinate (Array coordinates, int transcriptCoordinate, char* chromosome) 
{
  Coordinate testCoordinate;
  int index;
 
  testCoordinate.transcript = transcriptCoordinate;
  testCoordinate.chromosome = hlr_strdup (chromosome);
  if (!arrayFind (coordinates,&testCoordinate,&index,(ARRAYORDERF)util_sortCoordinatesByChromosomeAndTranscriptPosition)) {
    die ("Expected to find coordinate: %s %d",chromosome,transcriptCoordinate); 
  }
  hlr_free (testCoordinate.chromosome);
  return arrp (coordinates,index,Coordinate)->genomic;
}



int util_getTranscriptCoordinate (Array coordinates, int genomicCoordinate, char* chromosome) 
{
  Coordinate testCoordinate;
  int index;
 
  testCoordinate.genomic = genomicCoordinate;
  testCoordinate.chromosome = hlr_strdup (chromosome);
  if (!arrayFind (coordinates,&testCoordinate,&index,(ARRAYORDERF)util_sortCoordinatesByChromosomeAndGenomicPosition)) {
    die ("Expected to find coordinate: %s %d",chromosome,genomicCoordinate); 
  }
  hlr_free (testCoordinate.chromosome);
  return arrp (coordinates,index,Coordinate)->transcript;
}



static void util_addRelativePosition (Alteration *currAlteration, Interval *currInterval, int position)
{
  SubInterval *currSubInterval;
  int i,j;

  currAlteration->relativePosition = 0;
  for (i = 0; i < arrayMax (currInterval->subIntervals); i++) {
    currSubInterval = arrp (currInterval->subIntervals,i,SubInterval);
    for (j = currSubInterval->start; j < currSubInterval->end; j++) {
      currAlteration->relativePosition++;
      if (j == position) {
        if (currInterval->strand == '+') {
          return;
        }
        else if (currInterval->strand == '-') {
          currAlteration->relativePosition = currAlteration->transcriptLength - currAlteration->relativePosition + 1;
          return;
        }
        else {
          die ("Unexpected strand");
        }
      }
    }
  }
  currAlteration->relativePosition = -1;
}



int util_sortAlterationsByGeneIdAndType (Alteration *a, Alteration *b)
{
  int diff;

  diff = strcmp (a->geneId,b->geneId);
  if (diff != 0) {
    return diff;
  }
  return strcmp (a->type,b->type);
}



void util_addAlteration (Alteration *currAlteration, char *fullTranscriptName, char *type, Interval *currInterval, int position) 
{
  char *geneId,*transcriptId,*geneName,*transcriptName;
  static Stringa buffer = NULL;

  stringCreateClear (buffer,100);
  geneId = NULL;
  transcriptId = NULL;
  geneName = NULL;
  transcriptName = NULL;
  util_processTranscriptLine (fullTranscriptName,&geneId,&transcriptId,&geneName,&transcriptName);
  currAlteration->geneId = hlr_strdup (geneId);
  currAlteration->transcriptId = hlr_strdup (transcriptId);
  currAlteration->geneName = hlr_strdup (geneName);
  currAlteration->transcriptName = hlr_strdup (transcriptName);
  currAlteration->type = hlr_strdup (type);
  currAlteration->strand = currInterval->strand;
  currAlteration->transcriptLength = intervalFind_getSize (currInterval);
  util_addRelativePosition (currAlteration,currInterval,position);
  currAlteration->substitution = hlr_strdup ("");
}



void util_clearAlterations (Array alterations)
{
  int i;
  Alteration *currAlteration;
  
  for (i = 0; i < arrayMax (alterations); i++) {
    currAlteration = arrp (alterations,i,Alteration);
    hlr_free (currAlteration->geneId);
    hlr_free (currAlteration->transcriptId);
    hlr_free (currAlteration->geneName);
    hlr_free (currAlteration->transcriptName);
    hlr_free (currAlteration->type);
    hlr_free (currAlteration->substitution);
  }
  arrayClear (alterations); 
}



typedef struct {
  char* name;
  char* value;
} Pair;



static Array pairs = NULL;



void util_configInit (char *nameConfigFileEnvironmentVariable)
{
  Pair *currPair;
  LineStream ls;
  char *configurationFileName;
  char *line;
  WordIter w;

  configurationFileName = getenv (nameConfigFileEnvironmentVariable);
  if (configurationFileName == NULL) {
    die ("Unable to find environment variable: %s",nameConfigFileEnvironmentVariable);
  }
  pairs = arrayCreate (10,Pair);
  ls = ls_createFromFile (configurationFileName);
  while (line = ls_nextLine (ls)) {
    if (line[0] == '\0' || line[0] == '/') {
      continue;
    }
    w = wordIterCreate (line," \t",1);
    currPair = arrayp (pairs,arrayMax (pairs),Pair);
    currPair->name = hlr_strdup (wordNext (w));
    currPair->value = hlr_strdup (wordNext (w));
    wordIterDestroy (w);
  }
  ls_destroy (ls);
}



char* util_getConfigValue (char *parameterName)
{
  int i;
  Pair *currPair;

  for (i = 0; i < arrayMax (pairs); i++) {
    currPair = arrp (pairs,i,Pair);
    if (strEqual (currPair->name,parameterName)) {
      return currPair->value;
    }
  }
  die ("Unable to find parameterName in configuration file: %s",parameterName);
  return NULL;
}



void util_configDeInit (void)
{
  int i;
  Pair *currPair;
  
  for (i = 0; i < arrayMax (pairs); i++) {
    currPair = arrp (pairs,i,Pair);
    hlr_free (currPair->name);
    hlr_free (currPair->value);
  }
  arrayDestroy (pairs);
}
