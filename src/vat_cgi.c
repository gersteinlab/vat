#include <bios/log.h>
#include <bios/format.h>
#include <bios/linestream.h>
#include <bios/intervalFind.h>
#include <bios/html.h>
#include "util.h"
#include "vcf.h"



static void geneSummary2json (char *dataSet, char *annotationSet)
{
  static Stringa buffer = NULL;
  LineStream ls;
  char *line;
  char *header;
  WordIter w;
  char *token;
  static char *geneId = NULL;
  int first;

  stringCreateClear (buffer,100);
  stringPrintf (buffer,"%s/%s.geneSummary.txt",util_getConfigValue ("WEB_DATA_DIR"),dataSet);
  ls = ls_createFromFile (string (buffer));
  header = hlr_strdup (ls_nextLine (ls));
  puts ("\"aaData\": [ ");
  while (line = ls_nextLine (ls)) {
    printf ("[");
    first = 1;
    w = wordIterCreate (line,"\t",0);
    while (token = wordNext (w)) {
      if (first == 1) {
        strReplace (&geneId,token);
        first = 0;
      }
      printf ("\"%s\",",token);
    }
    printf ("\"<a href=%s/vat_cgi?mode=showGene&dataSet=%s&annotationSet=%s&geneId=%s target=gene>Link</a>\"],\n",
            util_getConfigValue ("WEB_URL_CGI"),dataSet,annotationSet,geneId);
    wordIterDestroy (w);
  }
  puts ("],");
  puts ("\"aoColumns\": [");
  w = wordIterCreate (header,"\t",0);
  while (token = wordNext (w)) {
    printf ("{\"sTitle\": \"%s\"},",token);
  }
  printf ("{\"sTitle\": \"Link\"}");
  puts ("]");
  wordIterDestroy (w);
  ls_destroy (ls);
}



static void sampleSummary2json (char *dataSet)
{
  static Stringa buffer = NULL;
  LineStream ls;
  char *line;
  char *header;
  WordIter w;
  char *token;

  stringCreateClear (buffer,100);
  stringPrintf (buffer,"%s/%s.sampleSummary.txt",util_getConfigValue ("WEB_DATA_DIR"),dataSet);
  ls = ls_createFromFile (string (buffer));
  header = hlr_strdup (ls_nextLine (ls));
  puts ("\"aaData\": [ ");
  while (line = ls_nextLine (ls)) {
    printf ("[");
    w = wordIterCreate (line,"\t",0);
    while (token = wordNext (w)) {
      printf ("\"%s\",",token);
    }
    wordIterDestroy (w);
    printf ("],\n");
  }
  puts ("],");
  puts ("\"aoColumns\": [");
  w = wordIterCreate (header,"\t",0);
  while (token = wordNext (w)) {
    printf ("{\"sTitle\": \"%s\"},",token);
  }
  puts ("]");
  wordIterDestroy (w);
  ls_destroy (ls);
}



static void processData (char *dataSet, char *annotationSet)
{
  puts ("<html>");
  puts ("<head>");
  html_printGenericStyleSheet (12);
  puts ("<meta charset=\"utf-8\">");
  puts ("<style type=\"text/css\" media=\"screen\">");
  puts ("  @import url(http://www.datatables.net/release-datatables/media/css/demo_table.css);");
  puts ("</style>");
  puts ("<script type=\"text/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js\"></script>");
  puts ("<script type=\"text/javascript\" language=\"javascript\" src=\"http://www.datatables.net/release-datatables/media/js/jquery.dataTables.js\"></script>"); 
  puts ("<script type=\"text/javascript\" charset=\"utf-8\">"); 
  puts ("			$(document).ready(function() {");
  puts ("                             $('#ex1').html ( '<table border=1 cellpadding=2 align=center id=gene class=display></table>' )");
  puts ("				$('#gene').dataTable( {");
  puts ("                                  \"bProcessing\": true,");
  puts ("                                  \"iDisplayLength\": 25,");
  puts ("                                  \"bStateSave\": true,");
  puts ("                                  \"sPaginationType\": \"full_numbers\",");
  geneSummary2json (dataSet,annotationSet);
  puts ("                             } );");
  puts ("                             $('#ex2').html ( '<table border=1 cellpadding=2 align=center id=sample class=display></table>' )");
  puts ("				$('#sample').dataTable( {");
  puts ("                                  \"bProcessing\": true,");
  puts ("                                  \"iDisplayLength\": 25,");
  puts ("                                  \"bStateSave\": true,");
  puts ("                                  \"sPaginationType\": \"full_numbers\",");
  sampleSummary2json (dataSet);
  puts ("                             } );");
  puts ("			} );");
  puts ("</script>");
  puts ("<title>VAT</title>\n");
  puts ("</head>");
  puts ("<body>");
  printf ("<h1><center>Results: %s</center></h1><br>\n",dataSet);
  printf ("<h3><center>Gene summary based on %s annotation set</center></h3>\n",annotationSet);
  puts ("<div id=ex1></div>");
  puts ("<br><br>");
  puts ("<center>");
  printf ("[<a href=%s/%s.vcf.gz target=external>Download compressed VCF file with annotated variants</a>]",util_getConfigValue ("WEB_DATA_URL"),dataSet);
  puts ("&nbsp;&nbsp;&nbsp;");
  printf ("[<a href=%s/%s.geneSummary.txt target=external>View tab-delimited gene summary file</a>]",util_getConfigValue ("WEB_DATA_URL"),dataSet);
  puts ("</center>");
  puts ("<br><br><br><br><br><br>");
  puts ("<h3><center>Sample summary</center></h3>");
  puts ("<div id=ex2></div>");
  puts ("<br><br>");
  puts ("<center>");
  printf ("[<a href=%s/%s.sampleSummary.txt target=external>View tab-delimited sample summary file</a>]",util_getConfigValue ("WEB_DATA_URL"),dataSet);
  puts ("</center>");
  puts ("</body>");
  puts ("</html>");
  fflush (stdout);  
}



static char* hyperlinkId (char *id)
{
  static Stringa buffer = NULL;
  Texta tokens;
  int i;

  stringCreateClear (buffer,1000);
  if (!strstr (id,"rs")) {
    return id;
  }
  tokens = textFieldtokP (id,";");
  for (i = 0; i < arrayMax (tokens); i++) {
    if (strstr (textItem (tokens,i),"rs")) {
      stringAppendf (buffer,"<a href=http://www.ncbi.nlm.nih.gov/SNP/snp_ref.cgi?rs=%s target=external>%s</a>",textItem (tokens,i) + 2,textItem (tokens,i));
    }
    else {
      stringAppendf (buffer,"%s",textItem (tokens,i));
    }
    stringCat (buffer,i < arrayMax (tokens) - 1 ? ";" : "");
  }
  textDestroy (tokens);
  return string (buffer);
}




static void showGeneInformation (char *dataSet, char *annotationSet, char *geneId)
{
  static Stringa buffer = NULL;
  Array vcfEntries;
  Array vcfGenes;
  VcfGene *currVcfGene;
  VcfEntry *currVcfEntry;
  VcfAnnotation *currVcfAnnotation;
  int i,j,k;
  Interval *currInterval;
  char *thisGeneId,*transcriptId,*geneName,*transcriptName;
  Texta groups;
  int alleleCount0,totalAlleleCount0;
  int alleleCount1,totalAlleleCount1;
  int start,end;
  
  puts ("<html>");
  puts ("<head>");
  html_printGenericStyleSheet (12);
  puts ("<title>VAT</title>\n");
  puts ("</head>");
  puts ("<body>");

  stringCreateClear (buffer,100);
  stringPrintf (buffer,"%s/%s/%s.vcf",util_getConfigValue ("WEB_DATA_DIR"),dataSet,geneId);
  vcf_init (string (buffer));
  vcfEntries = vcf_parse ();
  groups = vcf_getGroupsFromColumnHeaders ();
  vcf_deInit ();
  stringPrintf (buffer,"%s/%s.interval",util_getConfigValue ("WEB_DATA_DIR"),annotationSet);
  vcfGenes = vcf_getGeneSummaries (vcfEntries,string (buffer));
  i = 0; 
  while (i < arrayMax (vcfGenes)) {
    currVcfGene = arrp (vcfGenes,i,VcfGene);
    if (strEqual (currVcfGene->geneId,geneId)) {
      break;
    }
    i++;
  }
  if (i == arrayMax (vcfGenes)) {
    die ("Unable to find %s in %s!",geneId,dataSet);
  }
  currInterval = arru (currVcfGene->transcripts,0,Interval*);
  start = currInterval->start;
  end = currInterval->end;
  for (i = 1; i < arrayMax (currVcfGene->transcripts); i++) {
    currInterval = arru (currVcfGene->transcripts,i,Interval*);
    if (start > currInterval->start) {
      start = currInterval->start;
    }
    if (end < currInterval->end) {
      end = currInterval->end;
    }
  }

  printf ("<h1><center>%s: gene summary for <font color=red>%s</font> [%s]</center></h1><br>\n",dataSet,currVcfGene->geneName,geneId);
  printf ("<h3><center>External links:</h3>\n");
  puts ("<center><b>");
  printf ("[<a href=http://genome.ucsc.edu/cgi-bin/hgTracks?clade=mammal&org=human&db=hg18&position=%s:%d-%d target=external>UCSC genome browser</a>]&nbsp;&nbsp;&nbsp;\n",currInterval->chromosome,start - 1000,end + 1000);
  printf ("[<a href=http://may2009.archive.ensembl.org/Homo_sapiens/Gene/Summary?g=%s target=external>Ensembl genome browser</a>]&nbsp;&nbsp;&nbsp;\n",geneId);
  printf ("[<a href=http://www.genecards.org/cgi-bin/carddisp.pl?gene=%s target=external>Gene Cards</a>]&nbsp;&nbsp;&nbsp;\n",currVcfGene->geneName);
  puts ("</b></center>");
  puts ("<br><br><br>");

  printf ("<h3><center>Transcript summary based on %s annotation set</center></h3>\n",annotationSet);
  puts ("<table border=1 cellpadding=2 align=center width=95%>");
  puts ("<thead>");
  puts ("<tr>");
  puts ("<th>Transcript name</th>");
  puts ("<th>Transcript ID</th>");
  puts ("<th>Chromosome</th>");
  puts ("<th>Strand</th>");
  puts ("<th>Start</th>");
  puts ("<th>End</th>");
  puts ("<th>Number of exons</th>");
  puts ("<th>Transcript length</th>");
  puts ("</tr>");
  puts ("</thead>");
  puts ("<tbody>"); 

  thisGeneId = NULL;
  transcriptId = NULL;
  geneName = NULL;
  transcriptName = NULL;
  for (i = 0; i < arrayMax (currVcfGene->transcripts); i++) {
    currInterval = arru (currVcfGene->transcripts,i,Interval*);
    util_processTranscriptLine (currInterval->name,&thisGeneId,&transcriptId,&geneName,&transcriptName);
    puts ("<tr align=center>");
    printf ("<td>%s</td>",transcriptName);
    printf ("<td>%s</td>",transcriptId);
    printf ("<td>%s</td>",currInterval->chromosome);
    printf ("<td>%c</td>",currInterval->strand);
    printf ("<td>%d</td>",currInterval->start);
    printf ("<td>%d</td>",currInterval->end);
    printf ("<td>%d</td>",arrayMax (currInterval->subIntervals));
    printf ("<td>%d</td>",intervalFind_getSize (currInterval));
    puts ("</tr>");
  }
  puts ("</tbody>");
  puts ("</table>");
  puts ("<br><br><br>"); 
  puts ("<h3><center>Graphical representation of genetic variants</center></h3>");
  printf ("<center><img src=%s/%s/%s.png></center>\n",util_getConfigValue ("WEB_DATA_URL"),dataSet,geneId);
  puts ("<br><br>");
  printf ("<center><img src=%s/%s/legend.png></center>\n",util_getConfigValue ("WEB_DATA_URL"),dataSet);
  puts ("<br><br><br>");
  puts ("<h3><center>Detailed summary of variants</center></h3>");
  puts ("<table border=1 cellpadding=2 align=center width=95%>");
  puts ("<thead>");
  puts ("<tr>");
  puts ("<th rowspan=2>Chromosome</th>");
  puts ("<th rowspan=2>Position</th>");
  puts ("<th rowspan=2>Reference allele</th>");
  puts ("<th rowspan=2>Alternate allele</th>");
  puts ("<th rowspan=2>Identifier</th>");
  puts ("<th rowspan=2>Type</th>");
  puts ("<th rowspan=2>Fraction of transcripts affected</th>");
  puts ("<th rowspan=2>Transcripts</th>");
  puts ("<th rowspan=2>Transcript details</th>");
  i = 0; 
  while (i < arrayMax (currVcfGene->vcfEntries)) {
    currVcfEntry = arru (currVcfGene->vcfEntries,i,VcfEntry*);
    if (arrayMax (currVcfEntry->genotypes) > 0) {
      break;
    }
    i++;
  }
  if (i < arrayMax (currVcfGene->vcfEntries)) {
    printf ("<th colspan=%d>Alternate allele frequencies</th>",arrayMax (groups));
    puts ("<th rowspan=2>Genotypes</th>");
    puts ("</tr>");
    puts ("<tr>");
    for (i = 0; i < arrayMax (groups); i++) {
      printf ("<th>%s</th>\n",textItem (groups,i));
    }
  }
  puts ("</tr>");
  puts ("</thead>");
  puts ("<tbody>"); 

  for (i = 0; i < arrayMax (currVcfGene->vcfEntries); i++) {
    currVcfEntry = arru (currVcfGene->vcfEntries,i,VcfEntry*);
    if (vcf_hasMultipleAlternateAlleles (currVcfEntry)) {
      continue;
    }
    for (j = 0; j < arrayMax (currVcfEntry->annotations); j++) {
      currVcfAnnotation = arrp (currVcfEntry->annotations,j,VcfAnnotation);
      if (strEqual (currVcfGene->geneId,currVcfAnnotation->geneId)) {
        puts ("<tr align=center>");
        printf ("<td>%s</td>\n",currVcfEntry->chromosome);
        printf ("<td>%d</td>\n",currVcfEntry->position);
        printf ("<td>%s</td>\n",currVcfEntry->referenceAllele);
        printf ("<td>%s</td>\n",currVcfEntry->alternateAllele);
        printf ("<td>%s</td>\n",hyperlinkId (currVcfEntry->id));
        printf ("<td>%s</td>\n",currVcfAnnotation->type);
        printf ("<td>%s</td>\n",currVcfAnnotation->fraction);
        puts ("<td>");
        for (k = 0; k < arrayMax (currVcfAnnotation->transcriptIds); k++) {
          printf ("%s%s",
                  textItem (currVcfAnnotation->transcriptIds,k),
                  k < arrayMax (currVcfAnnotation->transcriptIds) - 1 ? "<br>" : "");
        }
        puts ("</td>");
        puts ("<td>");
        for (k = 0; k < arrayMax (currVcfAnnotation->transcriptDetails); k++) {
          printf ("%s%s",
                  textItem (currVcfAnnotation->transcriptDetails,k),
                  k < arrayMax (currVcfAnnotation->transcriptDetails) - 1 ? "<br>" : "");
        }
        puts ("</td>");
        for (k = 0; k < arrayMax (groups); k++) {
          puts ("<td>");
          vcf_getAlleleInformation (currVcfEntry,textItem (groups,k),0,&alleleCount0,&totalAlleleCount0);
          vcf_getAlleleInformation (currVcfEntry,textItem (groups,k),1,&alleleCount1,&totalAlleleCount1);
          if (alleleCount0 == 0 && alleleCount1 == 0) {
            printf ("N/A\n");
          }
          else {
            printf ("%.3f\n",(double)alleleCount1 / totalAlleleCount1);
          }
          puts ("</td>");
        }
        printf ("<td><a href=%s/vat_cgi?mode=showGenotypes&dataSet=%s&geneId=%s&index=%d target=genotypes>Link</a></td>\n",
                util_getConfigValue ("WEB_URL_CGI"),dataSet,geneId,i);
        puts ("</tr>");
      } 
    }
  }
  puts ("</tbody>");
  puts ("</table>");
  puts ("</body>");
  puts ("</html>");
  fflush (stdout);  
}



static void showGenotypes (char *dataSet, char *geneId, int index)
{
  static Stringa buffer = NULL;
  Array vcfEntries;
  VcfEntry *currVcfEntry;
  VcfGenotype *currVcfGenotype;
  int i,j;
  Texta groups;
  int alleleCount0,totalAlleleCount0;
  int alleleCount1,totalAlleleCount1;
 
  puts ("<html>");
  puts ("<head>");
  html_printGenericStyleSheet (12);
  puts ("<title>VAT</title>\n");
  puts ("</head>");
  puts ("<body>");

  stringCreateClear (buffer,100);
  stringPrintf (buffer,"%s/%s/%s.vcf",util_getConfigValue ("WEB_DATA_DIR"),dataSet,geneId);
  vcf_init (string (buffer));
  vcfEntries = vcf_parse ();
  groups = vcf_getGroupsFromColumnHeaders ();
  vcf_deInit ();
  if (index >= arrayMax (vcfEntries)) {
    die ("Invalid index");
  }
  currVcfEntry = arrp (vcfEntries,index,VcfEntry);
  puts ("<h3><center>Variant summary</center></h3>");
  puts ("<table border=1 cellpadding=2 align=center width=95%>");
  puts ("<thead>");
  puts ("<tr>");
  puts ("<th rowspan=2>Chromosome</th>");
  puts ("<th rowspan=2>Position</th>");
  puts ("<th rowspan=2>Reference allele</th>");
  puts ("<th rowspan=2>Alternate allele</th>");
  puts ("</tr>");
  puts ("</thead>");
  puts ("<tbody>"); 
  puts ("<tr align=center>");
  printf ("<td>%s</td>\n",currVcfEntry->chromosome);
  printf ("<td>%d</td>\n",currVcfEntry->position);
  printf ("<td>%s</td>\n",currVcfEntry->referenceAllele);
  printf ("<td>%s</td>\n",currVcfEntry->alternateAllele);
  puts ("</td>");
  puts ("</tbody>");
  puts ("</table>");
  puts ("<br><br>");

  puts ("<h3><center>Genotype information</center></h3>");
  puts ("<table border=1 cellpadding=2 align=center width=95%>");
  puts ("<thead>");
  puts ("<tr>");
  for (i = 0; i < arrayMax (groups); i++) {
    printf ("<th>%s</th>\n",textItem (groups,i));
  }
  puts ("</tr>");
  puts ("</thead>");
  puts ("<tbody>"); 
  puts ("<tr align=center>");
  for (i = 0; i < arrayMax (groups); i++) {
    puts ("<td>");
    vcf_getAlleleInformation (currVcfEntry,textItem (groups,i),0,&alleleCount0,&totalAlleleCount0);
    vcf_getAlleleInformation (currVcfEntry,textItem (groups,i),1,&alleleCount1,&totalAlleleCount1);
    if (alleleCount0 == 0 && alleleCount1 == 0) {
      printf ("RefCount = %d<br>AltCount = %d\n",totalAlleleCount0,0);
    }
    else {
      printf ("RefCount = %d<br>AltCount = %d\n",alleleCount0,alleleCount1);
    }
    puts ("</td>");
  }
  puts ("</tr>");
  puts ("<tr align=center valign=top>");
  for (i = 0; i < arrayMax (groups); i++) {
    puts ("<td>");
    for (j = 0; j < arrayMax (currVcfEntry->genotypes); j++) {
      currVcfGenotype = arrp (currVcfEntry->genotypes,j,VcfGenotype);
      if (strEqual (textItem (groups,i),currVcfGenotype->group)) { 
        printf ("%s: %s<br>",currVcfGenotype->sample,currVcfGenotype->genotype);
      }
    }  
    puts ("</td>");
  }
  puts ("</tr>");
  puts ("</tbody>");
  puts ("</table>");
  puts ("</body>");
  puts ("</html>");
  fflush (stdout);  
}



static void cleanUpData (void)
{
  static Stringa buffer = NULL;
  
  stringCreateClear (buffer,100);
  
  puts ("<html>");
  puts ("<head>");
  html_printGenericStyleSheet (12);
  puts ("<title>VAT</title>\n");
  puts ("</head>");
  puts ("<body>");

  stringPrintf (buffer,"rm -rf %s/vat.*",util_getConfigValue ("WEB_DATA_DIR"));
  hlr_system (string (buffer),0);
  puts ("Done deleting temporary files...");
  puts ("</body>");
  puts ("</html>");
  fflush (stdout);  
}



int main (int argc, char *argv[]) 
{
  char *queryString;
  int first;
  Stringa item = stringCreate (20);
  Stringa value = stringCreate (20);
  char *iPtr,*vPtr;
  char *mode = NULL;
  char *dataSet = NULL;
  char *geneId = NULL;
  char *annotationSet = NULL;
  int index;

  cgiInit();
  cgiHeader("text/html");
  util_configInit ("VAT_CONFIG_FILE");
  queryString = cgiGet2Post();
  if (queryString[0] == '\0') {
    warn ("Wrong URL");
    fflush (stdout);
    return 0;
  }
  first = 1;
  cgiGetInit ();
  while (cgiGetNextPair (&first,item,value)) {
    iPtr = string (item);
    vPtr = string (value);
    if (strEqual (iPtr,"mode")) {
      strReplace (&mode,vPtr);
    }
    else if (strEqual (iPtr,"dataSet")) {
      strReplace (&dataSet,vPtr);
    }
    else if (strEqual (iPtr,"annotationSet")) {
      strReplace (&annotationSet,vPtr);
    }
    else if (strEqual (iPtr,"geneId")) {
      strReplace (&geneId,vPtr);
    }
    else if (strEqual (iPtr,"index")) {
      index = atoi (vPtr);
    }
    else {
      die ("Unexpected inputs: '%s' '%s'\n",iPtr,vPtr); 
    }
  }
  if (strEqual (mode,"process")) {
    processData (dataSet,annotationSet);
  }
  else if (strEqual (mode,"showGene")) {
    showGeneInformation (dataSet,annotationSet,geneId);
  }
  else if (strEqual (mode,"showGenotypes")) {
    showGenotypes (dataSet,geneId,index);
  }
  else if (strEqual (mode,"cleanUp")) {
    cleanUpData ();
  }
  else {
    die ("Unknown mode: %s",mode);
  }
  fflush (stdout);
  util_configDeInit ();
  return 0;
}


   
