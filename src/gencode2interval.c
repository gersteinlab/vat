#include <bios/log.h>
#include <bios/format.h>
#include <bios/linestream.h>



// <seqname> <source> <feature> <start> <end> <score> <strand> <frame> [attributes] [comments] 



typedef struct {
  char* chromosome;
  char* source;
  char* feature;
  int start;
  int end;
  char* score;
  char* strand;
  int frame;
  char* group;
  char* geneId;
  char* transcriptId;
  char* transcriptType;
  char* geneType;
  char* geneName;
  char* transcriptName;
} GtfEntry;



static int sortGtfEntries (GtfEntry *a, GtfEntry *b)
{
  int diff;

  diff = strcmp (a->geneId,b->geneId);
  if (diff != 0) {
    return diff;
  }
  diff = strcmp (a->transcriptId,b->transcriptId);
  if (diff != 0) {
    return diff;
  }
  return a->start - b->start;
} 



static int hasFeature (Array items, char *feature) 
{
  int i;
  GtfEntry *thisGtfEntry;

  for (i = 0; i < arrayMax (items); i++) {
    thisGtfEntry = arru (items,i,GtfEntry*);
    if (strEqual (thisGtfEntry->feature,feature)) {
      return 1;
    }
  }
  return 0;
}



static void writeAnnotation (char *geneId, char *transcriptId, char *geneName,char *transcriptName, char *chromosome, char* strand, Array items)
{
  int i;
  static Array starts = NULL; 
  static Array ends = NULL;
  GtfEntry *thisGtfEntry;
  int hasStart;
  int hasStop;

  if (starts == NULL) {
    starts = arrayCreate (100,int);
  }
  else {
    arrayClear (starts);
  }
  if (ends == NULL) {
    ends = arrayCreate (100,int);
  }
  else {
    arrayClear (ends);
  }
  for (i = 0; i < arrayMax (items); i++) {
    thisGtfEntry = arru (items,i,GtfEntry*);
    if (strEqual (thisGtfEntry->feature,"CDS") || strEqual (thisGtfEntry->feature,"exon")) {
      array (starts,arrayMax (starts),int) = thisGtfEntry->start - 1; // convert to zero-based intervals
      array (ends,arrayMax (ends),int) = thisGtfEntry->end;
    }
  }
  if (arrayMax (starts) != arrayMax (ends)) {
    die ("Unequal number of starts and ends");
  }
  if (strEqual (chromosome,"chrMT")) {
    chromosome[4] = '\0';
  }
  hasStart = hasFeature (items,"start_codon");
  hasStop = hasFeature (items,"stop_codon");
  if (strand[0] == '+') {
    if (hasStart == 0) {
      arru (starts,0,int) = arru (starts,0,int) + arru (items,0,GtfEntry*)->frame;
    }
    if (hasStop == 1) {
      arru (ends,arrayMax (ends) - 1,int) = arru (ends,arrayMax (ends) - 1,int) + 3;
    }
  }
  else if (strand[0] == '-') {
    if (hasStart == 0) {
      arru (ends,arrayMax (ends) - 1,int) = arru (ends,arrayMax (ends) - 1,int) - arru (items,arrayMax (items) - 1,GtfEntry*)->frame;
    }
    if (hasStop == 1) {
      arru (starts,0,int) = arru (starts,0,int) - 3;
    }
  }
  else {
    die ("Unexpected strand: %c",strand[0]);
  }
  printf ("%s|%s|%s|%s\t%s\t%s\t",geneId,transcriptId,geneName,transcriptName,chromosome,strand);
  printf ("%d\t%d\t%d\t",arru (starts,0,int),arru (ends,arrayMax (ends) - 1,int),arrayMax (starts));
  for (i = 0; i < arrayMax (starts); i++) {
    printf ("%d%s",arru (starts,i,int),i < arrayMax (starts) - 1 ? "," : "\t");
  }
  for (i = 0; i < arrayMax (ends); i++) {
    printf ("%d%s",arru (ends,i,int),i < arrayMax (ends) - 1 ? "," : "\n");
  }
}



static char* getAttribute (Texta tokens, char *attributeName)
{
  int i;
  char *pos1,*pos2;

  i = 0; 
  while (i < arrayMax (tokens)) {
    if (strstr (textItem (tokens,i),attributeName)) {
      pos1 = strchr (textItem (tokens,i),'"');
      pos2 = strrchr (textItem (tokens,i),'"');
      if (pos1 == NULL || pos2 == NULL) {
        die ("Unexpected token: %s",textItem (tokens,i));
      } 
      *pos2 = '\0';
      return (pos1 + 1);
    }
    i++;
  }
  die ("Expected to find attribute: %s",attributeName);
  return NULL;
}



int main (int argc, char *argv[]) 
{
  LineStream ls;
  char* line;
  WordIter w;
  GtfEntry *currGtfEntry,*nextGtfEntry;
  Array gtfEntries;
  int i,j;
  Texta tokens;
  Array items;
 
  gtfEntries = arrayCreate (10000,GtfEntry);
  ls = ls_createFromFile ("-");
  while (line = ls_nextLine (ls)) {
    if (line[0] == '#') continue;
    w = wordIterCreate (line,"\t",0);
    currGtfEntry = arrayp (gtfEntries,arrayMax (gtfEntries),GtfEntry);
    currGtfEntry->chromosome = hlr_strdup (wordNext (w));
    currGtfEntry->source = hlr_strdup (wordNext (w));
    currGtfEntry->feature = hlr_strdup (wordNext (w));
    currGtfEntry->start = atoi (wordNext (w));
    currGtfEntry->end = atoi (wordNext (w));
    currGtfEntry->score = hlr_strdup (wordNext (w));
    currGtfEntry->strand = hlr_strdup (wordNext (w));
    currGtfEntry->frame = atoi (wordNext (w));
    currGtfEntry->group = hlr_strdup (wordNext (w));
    tokens = textStrtokP(currGtfEntry->group,";");
    currGtfEntry->geneId = hlr_strdup (getAttribute (tokens,"gene_id"));
    currGtfEntry->transcriptId = hlr_strdup (getAttribute (tokens,"transcript_id"));
    currGtfEntry->transcriptType = hlr_strdup (getAttribute (tokens,"transcript_type"));
    currGtfEntry->geneType = hlr_strdup (getAttribute (tokens,"gene_type"));
    currGtfEntry->geneName = hlr_strdup (getAttribute (tokens,"gene_name"));
    currGtfEntry->transcriptName = hlr_strdup (getAttribute (tokens,"transcript_name"));
    textDestroy (tokens);
    wordIterDestroy (w);
  }
  ls_destroy (ls);
  arraySort (gtfEntries,(ARRAYORDERF)sortGtfEntries);
 
  items = arrayCreate (100000,GtfEntry*);
  i = 0;
  while (i < arrayMax (gtfEntries)) {
    currGtfEntry = arrp (gtfEntries,i,GtfEntry);
    arrayClear (items);
    array (items,arrayMax (items),GtfEntry*) = currGtfEntry;
    j = i + 1;
    while (j < arrayMax (gtfEntries)) {
      nextGtfEntry = arrp (gtfEntries,j,GtfEntry);
      if (strEqual (currGtfEntry->geneId,nextGtfEntry->geneId) &&
          strEqual (currGtfEntry->transcriptId,nextGtfEntry->transcriptId)) {
        array (items,arrayMax (items),GtfEntry*) = nextGtfEntry;
      }
      else { 
        break;
      }
      j++;
    }
    i = j;
    writeAnnotation (currGtfEntry->geneId,currGtfEntry->transcriptId,currGtfEntry->geneName,currGtfEntry->transcriptName,
                     currGtfEntry->chromosome,currGtfEntry->strand,items);
  }
  return 0;
}
