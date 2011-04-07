#include <bios/log.h>
#include <bios/format.h>
#include <bios/linestream.h>
#include <bios/html.h>
#include "util.h"
#include "vcf.h"
#include <sys/types.h>
#include <unistd.h>
#include <stdlib.h>



int main (int argc, char *argv[]) 
{
  Stringa item;
  Stringa value;
  Stringa fileName;
  Stringa contentType;
  FILE *fp;
  Stringa buffer;
  Stringa cmd;
  char *program;
  char *annotationFile;
  
  cgiInit ();
  cgiHeader("text/html");
  util_configInit ("VAT_CONFIG_FILE");
  if (argc < 2) {
    puts ("<html>");
    puts ("<head>");
    html_printGenericStyleSheet (12);
    puts ("<title>VAT</title>\n");
    puts ("</head>");
    puts ("<body>");
    puts ("<h1>Variation Annotation Tool (VAT)</h1>");
    printf ("<form action=%s/vat_fileUpload_cgi?process method=post ENCTYPE=\"multipart/form-data\">\n",util_getConfigValue ("WEB_URL_CGI"));
    puts ("<br>");
    puts ("VCF file upload:&nbsp;&nbsp;");
    puts ("<input type=file name=upFile>");
    puts ("<br><br><br>");
    puts ("Variant type:&nbsp;&nbsp;");
    puts ("<select name=variantType>");
    puts ("<option value=snpMapper checked>SNPs</option>");
    puts ("<option value=indelMapper>Indels</option>");
    puts ("</select>");
    puts ("<br><br><br>");
    puts ("Annotation file:&nbsp;&nbsp;");
    puts ("<select name=annotationFile>");
    puts ("<option value=gencode3b>GENCODE (version 3b; hg18)</option>");
    puts ("<option value=gencode3c>GENCODE (version 3c; hg19)</option>");
    puts ("</select>");
    puts ("<br><br><br>");
    puts ("<input type=submit value=Submit>");
    puts ("<input type=reset value=Reset>");
    puts ("</form>");
    puts ("</body>");
    puts ("</html>");
    fflush (stdout);  
  }
  else {
    item = stringCreate (20);
    value = stringCreate (20);
    fileName = stringCreate (20);
    contentType = stringCreate (20);
    buffer = stringCreate (20);
    cmd = stringCreate (20);

    puts ("<html>");
    puts ("<head>");
    html_printGenericStyleSheet (12);
    puts ("<title>VAT</title>\n");
    puts ("</head>");
    puts ("<body>");

    puts ("<center>");
    puts ("<h1>");
    printf ("Processing uploaded data <img src=%s/processing.gif id=processing>\n",util_getConfigValue ("WEB_DATA_URL"));
    puts ("</h1>");
    puts ("<br><br><br>");
    puts ("Step [1/6]: Writing file... ");
    fflush (stdout);

    stringPrintf (cmd,"mkdir %s/vat.%d",util_getConfigValue ("WEB_DATA_DIR"),getpid ());
    hlr_system (string (cmd),0);
 
    stringPrintf (buffer,"%s/vat.%d.raw.vcf",util_getConfigValue ("WEB_DATA_DIR"),getpid ());
    fp = fopen (string (buffer),"w");
    if (fp == NULL) {
      die ("Unable to open file: %s",string (buffer));
    }
    cgiMpInit();  
    while (cgiMpNext (item,value,fileName,contentType)) {
      if (strEqual (string (item),"upFile")) {
        fprintf (fp,"%s",string (value));
      }
      else if (strEqual (string (item),"variantType")) {
        program = hlr_strdup (string (value));
      }
      else if (strEqual (string (item),"annotationFile")) {
        annotationFile = hlr_strdup (string (value));
      }
      else {
        die ("Unknown item/value pair: %s/%s",string (item),string (value));
      }
    }
    cgiMpDeinit ();
    fclose (fp);

    printf ("<img src=%s/check.png height=15 width=15><br><br><br>\n",util_getConfigValue ("WEB_DATA_URL"));
    puts ("Step [2/6]: Annotating variants... ");
    fflush (stdout);

    stringPrintf (cmd,"%s/%s %s/%s.interval %s/%s.fa < %s/vat.%d.raw.vcf > %s/vat.%d.vcf",
                  util_getConfigValue ("WEB_DATA_DIR"),program,util_getConfigValue ("WEB_DATA_DIR"),annotationFile,util_getConfigValue ("WEB_DATA_DIR"),annotationFile,
                  util_getConfigValue ("WEB_DATA_DIR"),getpid (),util_getConfigValue ("WEB_DATA_DIR"),getpid ());
    hlr_system (string (cmd),0);

    printf ("<img src=%s/check.png height=15 width=15><br><br><br>\n",util_getConfigValue ("WEB_DATA_URL"));
    puts ("Step [3/6]: Indexing files... ");
    fflush (stdout);

    stringPrintf (cmd,"%s/bgzip -c %s/vat.%d.vcf > %s/vat.%d.vcf.gz",
                  util_getConfigValue ("WEB_DATA_DIR"),util_getConfigValue ("WEB_DATA_DIR"),getpid (),util_getConfigValue ("WEB_DATA_DIR"),getpid ());
    hlr_system (string (cmd),0);

    stringPrintf (cmd,"%s/tabix -p vcf %s/vat.%d.vcf.gz",
                  util_getConfigValue ("WEB_DATA_DIR"),util_getConfigValue ("WEB_DATA_DIR"),getpid ());
    hlr_system (string (cmd),0);

    printf ("<img src=%s/check.png height=15 width=15><br><br><br>\n",util_getConfigValue ("WEB_DATA_URL"));
    puts ("Step [4/6]: Creating variant summay file... ");
    fflush (stdout);
  
    stringPrintf (cmd,"%s/vcfSummary %s/vat.%d.vcf.gz %s/%s.interval",
                  util_getConfigValue ("WEB_DATA_DIR"),util_getConfigValue ("WEB_DATA_DIR"),getpid (),util_getConfigValue ("WEB_DATA_DIR"),annotationFile);    
    hlr_system (string (cmd),0);
   
    printf ("<img src=%s/check.png height=15 width=15><br><br><br>\n",util_getConfigValue ("WEB_DATA_URL"));
    puts ("Step [5/6]: Generating images... ");
    fflush (stdout);
 
    stringPrintf (cmd,"%s/vcf2images %s/vat.%d.vcf.gz %s/%s.interval %s/vat.%d",
                  util_getConfigValue ("WEB_DATA_DIR"),util_getConfigValue ("WEB_DATA_DIR"),getpid (),util_getConfigValue ("WEB_DATA_DIR"),annotationFile,util_getConfigValue ("WEB_DATA_DIR"),getpid ());    
    hlr_system (string (cmd),0);

    printf ("<img src=%s/check.png height=15 width=15><br><br><br>\n",util_getConfigValue ("WEB_DATA_URL"));
    puts ("Step [6/6]: Subsetting file... ");
    fflush (stdout);

    stringPrintf (cmd,"%s/vcfSubsetByGene %s/vat.%d.vcf.gz %s/%s.interval %s/vat.%d",
                  util_getConfigValue ("WEB_DATA_DIR"),util_getConfigValue ("WEB_DATA_DIR"),getpid (),util_getConfigValue ("WEB_DATA_DIR"),annotationFile,util_getConfigValue ("WEB_DATA_DIR"),getpid ());   
    hlr_system (string (cmd),0);
   
    printf ("<img src=%s/check.png height=15 width=15><br><br><br>\n",util_getConfigValue ("WEB_DATA_URL"));
    printf ("[<a href=%s/vat_cgi?mode=process&dataSet=vat.%d&annotationSet=%s>View results</a>]\n",util_getConfigValue ("WEB_URL_CGI"),getpid (),annotationFile);

    puts ("<script type=\"text/javascript\" charset=\"utf-8\">"); 
    puts ("document.getElementById(\"processing\").style.visibility = \"hidden\"");
    puts ("</script>");
    puts ("</center>");
    puts ("</body>");
    puts ("</html>");
  }
  fflush (stdout);
  util_configDeInit ();
  return 0;
}




