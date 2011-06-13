#include <bios/log.h>
#include <bios/format.h>
#include <bios/linestream.h>
#include <bios/intervalFind.h>
#include <bios/common.h>
#include "vcf.h"
#include "util.h"



static LineStream lsVcf = NULL;
static Texta comments = NULL;
static Texta columnHeaders = NULL;



static void vcf_doInit (void)
{
  char *line;
  
  comments = textCreate (100);
  ls_bufferSet (lsVcf,1);
  while (line = ls_nextLine (lsVcf)) {
    if (strStartsWithC (line,"#CHROM")) {
      columnHeaders = textFieldtokP (line,"\t");
    }
    else if (line[0] == '#') {
      textAdd (comments,line);
    }
    else {
      ls_back (lsVcf,1);
      break;
    }
  }
}



void vcf_init (char *fileName)
{
  lsVcf = ls_createFromFile (fileName);
  vcf_doInit ();
}



void vcf_initFromPipe (char *command)
{
  lsVcf = ls_createFromPipe (command);
  vcf_doInit ();
}



void vcf_deInit (void) 
{
  ls_destroy (lsVcf);
  textDestroy (comments);
  textDestroy (columnHeaders);
}



static char* vcf_getColumnHeader (int columnIndex) 
{
  return textItem (columnHeaders,columnIndex);
}



void vcf_addComment (char *comment)
{
  textAdd (comments,comment);
}



Texta vcf_getColumnHeaders (void)
{
  return columnHeaders;
}



static void vcf_freeEntry (VcfEntry *currEntry, int freePointer)
{
  int i;
  VcfGenotype *currVcfGenotype;
  VcfAnnotation *currVcfAnnotation;

  if (currEntry == NULL) {
    return;
  }
  hlr_free (currEntry->chromosome);
  hlr_free (currEntry->id);
  hlr_free (currEntry->referenceAllele);
  hlr_free (currEntry->alternateAllele);
  hlr_free (currEntry->quality);
  hlr_free (currEntry->filter);
  hlr_free (currEntry->info);
  hlr_free (currEntry->genotypeFormat);
  for (i = 0; i < arrayMax (currEntry->genotypes); i++) {
    currVcfGenotype = arrp (currEntry->genotypes,i,VcfGenotype);
    hlr_free (currVcfGenotype->genotype);
    hlr_free (currVcfGenotype->details);
    hlr_free (currVcfGenotype->group);
    hlr_free (currVcfGenotype->sample);
  }
  arrayDestroy (currEntry->genotypes);
  for (i = 0; i < arrayMax (currEntry->annotations); i++) {
    currVcfAnnotation = arrp (currEntry->annotations,i,VcfAnnotation);
    hlr_free (currVcfAnnotation->geneId);
    hlr_free (currVcfAnnotation->geneName);
    hlr_free (currVcfAnnotation->type);
    hlr_free (currVcfAnnotation->fraction);
    textDestroy (currVcfAnnotation->transcriptNames);
    textDestroy (currVcfAnnotation->transcriptIds);
    textDestroy (currVcfAnnotation->transcriptDetails);
  }
  arrayDestroy (currEntry->annotations);
  if (freePointer) {
    freeMem (currEntry);
    currEntry = NULL;
  }
}



void vcf_freeEntries (Array vcfEntries)
{
  int i;
  VcfEntry *currEntry;

  for (i = 0; i < arrayMax (vcfEntries); i++) {
    currEntry = arrp (vcfEntries,i,VcfEntry);
    vcf_freeEntry (currEntry,0);
  }
  arrayDestroy (vcfEntries);
}



static void vcf_processAnnotation (char *str, VcfAnnotation *currVcfAnnotation)
{
  Texta tokens;
  int i;

  tokens = textStrtokP (str,":");
  currVcfAnnotation->alleleNumber = atoi (textItem (tokens,0));
  currVcfAnnotation->geneName = hlr_strdup (textItem (tokens,1));
  currVcfAnnotation->geneId = hlr_strdup (textItem (tokens,2));
  currVcfAnnotation->strand = textItem (tokens,3)[0];
  currVcfAnnotation->type = hlr_strdup (textItem (tokens,4));
  currVcfAnnotation->fraction = hlr_strdup (textItem (tokens,5));
  currVcfAnnotation->transcriptNames = textCreate (10);
  currVcfAnnotation->transcriptIds = textCreate (10);
  currVcfAnnotation->transcriptDetails = textCreate (10);
  for (i = 6; i < arrayMax (tokens); i = i + 3) {
    textAdd (currVcfAnnotation->transcriptNames,textItem (tokens,i));
    textAdd (currVcfAnnotation->transcriptIds,textItem (tokens,i + 1));
    textAdd (currVcfAnnotation->transcriptDetails,textItem (tokens,i + 2));
  }
  if (arrayMax (currVcfAnnotation->transcriptNames) != arrayMax (currVcfAnnotation->transcriptIds) ||
      arrayMax (currVcfAnnotation->transcriptIds) != arrayMax (currVcfAnnotation->transcriptDetails)) {
    die ("Unexpected annotation format");
  }
  textDestroy (tokens);
}



static void vcf_processGenotype (char *str, int columnIndex, VcfGenotype *currVcfGenotype)
{
  char *pos;
  static char *columnHeader = NULL;

  pos = strchr (str,':');
  if (pos != NULL) {
    *pos = '\0';
    currVcfGenotype->details = hlr_strdup (pos + 1);
  }
  else {
    currVcfGenotype->details = hlr_strdup ("");
  }
  currVcfGenotype->genotype = hlr_strdup (str);
  strReplace (&columnHeader,vcf_getColumnHeader (columnIndex));
  pos = strchr (columnHeader,':');
  if (pos != NULL) {
    *pos = '\0';
    currVcfGenotype->group = hlr_strdup (columnHeader);
    currVcfGenotype->sample = hlr_strdup (pos + 1);
  }
  else {
    currVcfGenotype->group = hlr_strdup (columnHeader);
    currVcfGenotype->sample = hlr_strdup (columnHeader);
  }
}



static void vcf_processNextEntry (VcfEntry **thisEntry)
{
  static Stringa buffer = NULL;
  VcfEntry *currEntry;
  char *line;
  Texta tokens;
  Texta annotations;
  int i,j;
  char *pos;

  stringCreateClear (buffer,100);
  line = ls_nextLine (lsVcf);
  if (line == NULL) {
    *thisEntry = NULL;
    return;
  }
  currEntry = *thisEntry;
  tokens = textFieldtok (line,"\t");
  if (strstr (textItem (tokens,0),"chr")) {
    currEntry->chromosome = hlr_strdup (textItem (tokens,0));
  }
  else {
    stringPrintf (buffer,"chr%s",textItem (tokens,0));
    currEntry->chromosome = hlr_strdup (string (buffer));
  }
  currEntry->position = atoi (textItem (tokens,1));
  currEntry->id = hlr_strdup (textItem (tokens,2));
  currEntry->referenceAllele = hlr_strdup (textItem (tokens,3));
  toupperStr (currEntry->referenceAllele);
  currEntry->alternateAllele = hlr_strdup (textItem (tokens,4));
  toupperStr (currEntry->alternateAllele);
  currEntry->quality = hlr_strdup (textItem (tokens,5));
  currEntry->filter = hlr_strdup (textItem (tokens,6));
  currEntry->info = hlr_strdup (textItem (tokens,7));
  currEntry->genotypes = arrayCreate (10,VcfGenotype);
  currEntry->annotations = arrayCreate (10,VcfAnnotation);
  if (pos = strstr (currEntry->info,"VA=")) {
    annotations = textFieldtokP (pos + 3,",");
    for (j = 0; j < arrayMax (annotations); j++) {
      vcf_processAnnotation (textItem (annotations,j),arrayp (currEntry->annotations,arrayMax (currEntry->annotations),VcfAnnotation));
    }
    textDestroy (annotations);
  }
  for (i = 8; i < arrayMax (tokens); i++) {
    if (strEqual (vcf_getColumnHeader (i),"FORMAT")) {
      currEntry->genotypeFormat = hlr_strdup (textItem (tokens,i));
    }
    else {
      vcf_processGenotype (textItem (tokens,i),i,arrayp (currEntry->genotypes,arrayMax (currEntry->genotypes),VcfGenotype));
    }
  }
  textDestroy (tokens);
}



VcfEntry* vcf_nextEntry (void) 
{
  static VcfEntry *currEntry = NULL;

  vcf_freeEntry (currEntry,1);
  AllocVar (currEntry);  
  vcf_processNextEntry (&currEntry); 
  return currEntry;
}



Array vcf_parse (void)
{
  Array entries;
  VcfEntry *currEntry;
  int count;
  
  count = 0;
  entries = arrayCreate (100000,VcfEntry);
  while (1) {
    currEntry = arrayp (entries,arrayMax (entries),VcfEntry);
    vcf_processNextEntry (&currEntry); 
    if (currEntry == NULL) {
      arraySetMax (entries,count);
      break;
    }
    count++;
  }
  return entries;
}



int vcf_isInvalidEntry (VcfEntry *currEntry) 
{
  if (strchr (currEntry->alternateAllele,'.') || strchr (currEntry->alternateAllele,'<') || strchr (currEntry->alternateAllele,'>')) {
    return 1;
  }
  if (strchr (currEntry->referenceAllele,'.') || strchr (currEntry->referenceAllele,'<') || strchr (currEntry->referenceAllele,'>')) {
    return 1;
  }
  return 0;
}



int vcf_hasMultipleAlternateAlleles (VcfEntry *currEntry)
{
  if (strchr (currEntry->alternateAllele,',')) {
    return 1;
  }
  return 0;
} 



Texta vcf_getAlternateAlleles (VcfEntry *currEntry)
{
  static Texta tokens = NULL;
  WordIter w;
  char *item;
  char *copy = NULL;

  textCreateClear (tokens,10);
  if (vcf_hasMultipleAlternateAlleles (currEntry) == 0) {
    textAdd (tokens,currEntry->alternateAllele);
  }
  else {
    strReplace (&copy,currEntry->alternateAllele);
    w = wordIterCreate (copy,",",0);
    while (item = wordNext (w)) {
      textAdd (tokens,item);
    }
    wordIterDestroy (w);
  }
  return tokens;
}
    


char* vcf_writeEntry (VcfEntry *currEntry)
{
  static Stringa buffer = NULL;
  int i;
  VcfGenotype *currVcfGenotype;

  stringCreateClear (buffer,100);
  stringPrintf (buffer,"%s\t%d\t%s\t%s\t%s\t%s\t%s\t%s",
                currEntry->chromosome,
                currEntry->position,
                currEntry->id,
                currEntry->referenceAllele,
                currEntry->alternateAllele,
                currEntry->quality,
                currEntry->filter,
                currEntry->info);
  for (i = 0; i < arrayMax (currEntry->genotypes); i++) {
    currVcfGenotype = arrp (currEntry->genotypes,i,VcfGenotype);
    if (i == 0) {
      stringAppendf (buffer,"\t%s\t",currEntry->genotypeFormat);
    }
    stringAppendf (buffer,"%s%s%s%s",currVcfGenotype->genotype,
                   currVcfGenotype->details[0] != '\0' ? ":" : "",
                   currVcfGenotype->details[0] != '\0' ?  currVcfGenotype->details : "",
                   i < arrayMax (currEntry->genotypes) - 1 ? "\t" : ""); 
  }
  return string (buffer);
}

 

char* vcf_writeMetaData (void)
{
  static Stringa buffer = NULL;
  int i;

  stringCreateClear (buffer,100);
  for (i = 0; i < arrayMax (comments); i++) {
    stringAppendf (buffer,"%s%s",
                   textItem (comments,i),
                   i < arrayMax (comments) - 1 ? "\n" : "");
  }
  return string (buffer);
}



char* vcf_writeColumnHeaders (void)
{
  static Stringa buffer = NULL;
  int i;
  
  stringCreateClear (buffer,100);
  for (i = 0; i < arrayMax (columnHeaders); i++) {
    stringAppendf (buffer,"%s%s",
                   textItem (columnHeaders,i),
                   i < arrayMax (columnHeaders) - 1 ? "\t" : "");
  }
  return string (buffer);
}



typedef struct {
  char *geneId;
  char *geneName;
  VcfEntry *vcfEntry;
} VcfItem;



static int sortIntervalsByName (Interval *a, Interval *b) 
{
  return strcmp (a->name,b->name);
}



static int sortVcfItmesByGeneId (VcfItem *a, VcfItem *b)
{
  return strcmp (a->geneId,b->geneId);
}



static int sortVcfEntryPointers (VcfEntry **a, VcfEntry **b) 
{
  return *a - *b;
}



Array vcf_getGeneSummaries (Array vcfEntries, char *annotationFile)
{
  static Array intervals;
  VcfEntry *currVcfEntry;
  static Array geneTranscriptEntries;
  static Array vcfItems;
  VcfItem *currVcfItem,*nextVcfItem;
  Texta queryStrings;
  Interval testInterval;
  int index;
  static Stringa buffer = NULL;
  static Array vcfGenes;
  VcfGene *currVcfGene;
  int i,j,k;
  VcfAnnotation *currVcfAnnotation;
  static int first = 1;

  stringCreateClear (buffer,100);
  if (first == 1) {
    vcfItems = arrayCreate (100000,VcfItem);
    vcfGenes = arrayCreate (25000,VcfGene);
    intervals = intervalFind_parseFile (annotationFile,0);
    arraySort (intervals,(ARRAYORDERF)sortIntervalsByName);
    geneTranscriptEntries = util_getGeneTranscriptEntries (intervals);
    first = 0;
  }
  else {
    arrayClear (vcfItems);
    for (i = 0; i < arrayMax (vcfGenes); i++) {
      currVcfGene = arrp (vcfGenes,i,VcfGene);
      arrayDestroy (currVcfGene->transcripts);
      arrayDestroy (currVcfGene->vcfEntries);
    }
    arrayClear (vcfGenes);
  }
  for (i = 0; i < arrayMax (vcfEntries); i++) {
    currVcfEntry = arrp (vcfEntries,i,VcfEntry);
    for (j = 0; j < arrayMax (currVcfEntry->annotations); j++) {
      currVcfAnnotation = arrp (currVcfEntry->annotations,j,VcfAnnotation);
      currVcfItem = arrayp (vcfItems,arrayMax (vcfItems),VcfItem);
      currVcfItem->geneId = currVcfAnnotation->geneId;
      currVcfItem->geneName = currVcfAnnotation->geneName;
      currVcfItem->vcfEntry = currVcfEntry;
    }
  }
  arraySort (vcfItems,(ARRAYORDERF)sortVcfItmesByGeneId);
  i = 0;
  while (i < arrayMax (vcfItems)) {
    currVcfItem = arrp (vcfItems,i,VcfItem);
    currVcfGene = arrayp (vcfGenes,arrayMax (vcfGenes),VcfGene);
    currVcfGene->transcripts = arrayCreate (10,Interval*);
    currVcfGene->vcfEntries = arrayCreate (10,VcfEntry*);
    currVcfGene->geneId = currVcfItem->geneId;
    currVcfGene->geneName = currVcfItem->geneName;
    array (currVcfGene->vcfEntries,arrayMax (currVcfGene->vcfEntries),VcfEntry*) = currVcfItem->vcfEntry;
    j = i + 1;
    while (j < arrayMax (vcfItems)) {
      nextVcfItem = arrp (vcfItems,j,VcfItem);
      if (!strEqual (currVcfItem->geneId,nextVcfItem->geneId)) {
        break;
      }
      array (currVcfGene->vcfEntries,arrayMax (currVcfGene->vcfEntries),VcfEntry*) = nextVcfItem->vcfEntry;
      j++;
    }
    arraySort (currVcfGene->vcfEntries,(ARRAYORDERF)sortVcfEntryPointers);
    arrayUniq (currVcfGene->vcfEntries,NULL,(ARRAYORDERF)sortVcfEntryPointers);
    queryStrings = util_getQueryStringsForGeneId (geneTranscriptEntries,currVcfItem->geneId);
    for (k = 0; k < arrayMax (queryStrings); k++) {
      testInterval.name = hlr_strdup (textItem (queryStrings,k));
      if (!arrayFind (intervals,&testInterval,&index,(ARRAYORDERF)sortIntervalsByName)) {
        die ("Expected to find interval: %s",textItem (queryStrings,k));
      }
      array (currVcfGene->transcripts,arrayMax (currVcfGene->transcripts),Interval*) = arrp (intervals,index,Interval);
      hlr_free (testInterval.name);
    }
    i = j;
  }
  return vcfGenes;
}



int vcf_getCountsForVcfAnnotationType (VcfGene *currVcfGene, char *type)
{
  VcfEntry *currVcfEntry;
  int i,j;
  VcfAnnotation *currVcfAnnotation;
  int count;

  count = 0;
  for (i = 0; i < arrayMax (currVcfGene->vcfEntries); i++) {
    currVcfEntry = arru (currVcfGene->vcfEntries,i,VcfEntry*);
    for (j = 0; j < arrayMax (currVcfEntry->annotations); j++) {
      currVcfAnnotation = arrp (currVcfEntry->annotations,j,VcfAnnotation);
      if (strEqual (currVcfGene->geneId,currVcfAnnotation->geneId) &&
          strEqual (currVcfAnnotation->type,type)) {
        count++;
      }
    }
  }
  return count;
}



Texta vcf_getGroupsFromColumnHeaders (void)
{
  static Texta groups = NULL;
  int i;
  char *pos;
  static char *copy = NULL;

  textCreateClear (groups,100);
  for (i = 8; i < arrayMax (columnHeaders); i++) {
    if (strEqual (textItem (columnHeaders,i),"FORMAT")) {
      continue;
    }
    strReplace (&copy,textItem (columnHeaders,i));
    pos = strchr (copy,':');
    if (pos != NULL) {
      *pos = '\0';
    }
    textAdd (groups,copy);
  }
  textUniqKeepOrder (groups);
  return groups;
}



Texta vcf_getSamplesFromColumnHeaders (void)
{
  static Texta samples = NULL;
  int i;
  char *pos;
  static char *copy = NULL;

  textCreateClear (samples,100);
  for (i = 8; i < arrayMax (columnHeaders); i++) {
    if (strEqual (textItem (columnHeaders,i),"FORMAT")) {
      continue;
    }
    strReplace (&copy,textItem (columnHeaders,i));
    pos = strchr (copy,':');
    if (pos != NULL) {
      copy = pos + 1;
    }
    textAdd (samples,copy);
  }
  return samples;
}



int vcf_getAllelesFromGenotype (char *genotype, int *allele1, int *allele2)
{
  char *pos;
  static char *copy = NULL;

  if (strchr (genotype,'.')) {
    return 0;
  }
  strReplace (&copy,genotype);
  pos = strpbrk (copy,"|/");
  if (pos == NULL) {
    warn ("Unexpected genotype: %s",genotype);
    return 0;
  }
  *pos = '\0';
  *allele1 = atoi (copy);
  *allele2 = atoi (pos + 1);
  return 1;
}



void vcf_getAlleleInformation (VcfEntry *currVcfEntry, char *group, int alleleNumber, int *alleleCount, int *totalAlleleCount)
{
  VcfGenotype *currVcfGenotype;
  int i;
  int allele1,allele2;
 
  *alleleCount = 0;
  *totalAlleleCount = 0;
  for (i = 0; i < arrayMax (currVcfEntry->genotypes); i++) {
    currVcfGenotype = arrp (currVcfEntry->genotypes,i,VcfGenotype);
    if (!strEqual (currVcfGenotype->group,group)) {
      continue;
    }
    *totalAlleleCount = *totalAlleleCount + 2;
    if (vcf_getAllelesFromGenotype (currVcfGenotype->genotype,&allele1,&allele2)) {
      if (allele1 == alleleNumber) {
        (*alleleCount)++;
      }
      if (allele2 == alleleNumber) {
        (*alleleCount)++;
      }
    }
  }
}
