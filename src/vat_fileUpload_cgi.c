#include <bios/log.h>
#include <bios/format.h>
#include <bios/linestream.h>
#include <bios/html.h>
#include <sys/types.h>
#include <unistd.h>
#include <stdlib.h>

#include "util.h"
#include "vcf.h"
#include "cfio.h"



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
  char *vcfFile;
  int pid;

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
    puts ("<h4><center>[<a href=http://vat.gersteinlab.org>VAT Main Page</a>]</center></h4>");
    puts ("<h1>Variation Annotation Tool (VAT)</h1>");
    printf ("<form action=%s/vat_fileUpload_cgi?process method=post ENCTYPE=\"multipart/form-data\">\n", util_getConfigValue ("WEB_URL_CGI"));
    puts ("<br>");
    puts ("<b>VCF file upload</b> (Examples<sup>&Dagger;</sup>: [<a href=http://homes.gersteinlab.org/people/lh372/VAT/1000genomes_pilot_snps.sample.vcf>SNPs</a>] [<a href=http://homes.gersteinlab.org/people/lh372/VAT/1000genomes_pilot_indels.sample.vcf>Indels</a>] [<a href=http://homes.gersteinlab.org/people/lh372/VAT/1000genomes_pilot_svs.sample.vcf>SVs</a>]):&nbsp;&nbsp;");
    puts ("<input type=file name=upFile>");
    puts ("<br><br><br>");
    puts ("<b>Variant type</b>:&nbsp;&nbsp;");
    puts ("<select name=variantType>");
    puts ("<option value=snpMapper checked>SNPs</option>");
    puts ("<option value=indelMapper>Indels</option>");
    puts ("<option value=svMapper>SVs</option>");
    puts ("</select>");
    puts ("<br><br><br>");
    puts ("<b>Annotation file</b>:&nbsp;&nbsp;");
    puts ("<select name=annotationFile>");
    puts ("<option value=gencode3b>GENCODE (version 3b; hg18)</option>");
    puts ("<option value=gencode3c>GENCODE (version 3c; hg19)</option>");
    puts ("<option value=gencode4>GENCODE (version 4; hg19)</option>");
    puts ("<option value=gencode5>GENCODE (version 5; hg19)</option>");
    puts ("<option value=gencode6>GENCODE (version 6; hg19)</option>");
    puts ("<option value=gencode7>GENCODE (version 7; hg19)</option>");
    puts ("</select>");
    puts ("<br><br><br>");
    puts ("<input type=submit value=Submit>");
    puts ("<input type=reset value=Reset>");
    puts ("</form>");
    puts ("<br><br><br><br><br><br><br>");
    puts ("_________________<br>");
    puts ("<fn><sup>&Dagger;</sup> - The example files were obtained from the <a href=http://www.1000genomes.org>1000 Genomes Pilot Project</a>. The genome coordinates are based on hg18.</fn>");
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
    pid = getpid();

    if (cfio_init () < 0) {
      die ("Could not initialize file system\n");
    }

    puts ("<html>");
    puts ("<head>");
    html_printGenericStyleSheet (12);
    puts ("<title>VAT</title>\n");
    puts ("</head>");
    puts ("<body>");

    puts ("<center>");
    puts ("<h1>");
    printf ("Processing uploaded data <img src=%s/processing.gif id=processing>\n",
            util_getConfigValue ("WEB_STATIC_URL"));
    puts ("</h1>");
  
    vcfFile = NULL;
    cgiMpInit();  
    while (cgiMpNext (item, value, fileName, contentType)) {
      if (strEqual (string (item), "upFile")) {
        vcfFile = hlr_strdup (string (value));
      }
      else if (strEqual (string (item), "variantType")) {
        program = hlr_strdup (string (value));
      }
      else if (strEqual (string (item), "annotationFile")) {
        annotationFile = hlr_strdup (string (value));
      }
      else {
        die ("Unknown item/value pair: %s/%s", string (item), string (value));
      }
    }
    cgiMpDeinit ();

    if (vcfFile[0] == '\0') {
      puts ("<script type=\"text/javascript\" charset=\"utf-8\">"); 
      puts ("document.getElementById(\"processing\").style.visibility = \"hidden\"");
      puts ("</script>");
      die ("No VCF file uploaded!");
    }
    puts ("<br><br><br>");
    puts ("Step [1/6]: Writing file... ");
    fflush (stdout);

    stringPrintf (cmd, "mkdir %s/%d", util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);
    hlr_system (string (cmd), 0);

    stringClear (cmd);
    stringPrintf (cmd,"mkdir %s/%d/vat.%d", util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid);
    hlr_system (string (cmd), 0);

    stringPrintf (buffer,"%s/%d/vat.%d.raw.vcf", util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid);
    fp = fopen (string (buffer),"w");
    if (fp == NULL) {
      die ("Unable to open file: %s", string (buffer));
    }
    fprintf (fp,"%s",vcfFile);
    fclose (fp);

    printf ("<img src=%s/check.png height=15 width=15><br><br><br>\n",
            util_getConfigValue ("WEB_STATIC_URL"));
    puts ("Step [2/6]: Annotating variants... ");
    fflush (stdout);

    if (strEqual (program,"svMapper")) {
      stringPrintf (cmd,"%s/%s %s/%s.interval < %s/%d/vat.%d.raw.vcf > %s/%d/vat.%d.vcf",
                    util_getConfigValue ("VAT_EXEC_DIR"), program,
                    util_getConfigValue ("WEB_DATA_REFERENCE_DIR"), annotationFile,
                    util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid,
                    util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid);
    }
    else {
      fflush (stdout);
      stringPrintf (cmd,"%s/%s %s/%s.interval %s/%s.fa < %s/%d/vat.%d.raw.vcf > %s/%d/vat.%d.vcf",
                    util_getConfigValue ("VAT_EXEC_DIR"), program,
                    util_getConfigValue ("WEB_DATA_REFERENCE_DIR"), annotationFile,
                    util_getConfigValue ("WEB_DATA_REFERENCE_DIR"), annotationFile,
                    util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid,
                    util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid);
    }
    hlr_system (string (cmd),0);

    printf ("<img src=%s/check.png height=15 width=15><br><br><br>\n",
            util_getConfigValue ("WEB_STATIC_URL"));
    puts ("Step [3/6]: Indexing files... ");
    fflush (stdout);

    stringPrintf (cmd,"%s/bgzip -c %s/%d/vat.%d.vcf > %s/%d/vat.%d.vcf.gz",
                  util_getConfigValue ("TABIX_DIR"),
                  util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid,
                  util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid);
    hlr_system (string (cmd),0);

    stringPrintf (cmd,"%s/tabix -p vcf %s/%d/vat.%d.vcf.gz",
                  util_getConfigValue ("TABIX_DIR"),
                  util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid);
    hlr_system (string (cmd),0);

    printf ("<img src=%s/check.png height=15 width=15><br><br><br>\n",
            util_getConfigValue ("WEB_STATIC_URL"));
    puts ("Step [4/6]: Creating variant summay file... ");
    fflush (stdout);
  
    stringPrintf (cmd,"%s/vcfSummary %s/%d/vat.%d.vcf.gz %s/%s.interval",
                  util_getConfigValue ("VAT_EXEC_DIR"),
                  util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid,
                  util_getConfigValue ("WEB_DATA_REFERENCE_DIR"), annotationFile);
    hlr_system (string (cmd),0);
   
    printf ("<img src=%s/check.png height=15 width=15><br><br><br>\n",
            util_getConfigValue ("WEB_STATIC_URL"));
    puts ("Step [5/6]: Generating images... ");
    fflush (stdout);
 
    stringPrintf (cmd,"%s/vcf2images %s/%d/vat.%d.vcf.gz %s/%s.interval %s/%d/vat.%d",
                  util_getConfigValue ("VAT_EXEC_DIR"),
                  util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid,
                  util_getConfigValue ("WEB_DATA_REFERENCE_DIR"), annotationFile,
                  util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid);
    hlr_system (string (cmd),0);

    printf ("<img src=%s/check.png height=15 width=15><br><br><br>\n",
            util_getConfigValue ("WEB_STATIC_URL"));
    puts ("Step [6/6]: Subsetting file... ");
    fflush (stdout);

    stringPrintf (cmd,"%s/vcfSubsetByGene %s/%d/vat.%d.vcf.gz %s/%s.interval %s/%d/vat.%d",
                  util_getConfigValue ("VAT_EXEC_DIR"),
                  util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid,
                  util_getConfigValue ("WEB_DATA_REFERENCE_DIR"), annotationFile,
                  util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid);
    hlr_system (string (cmd),0);
   
    printf ("<img src=%s/check.png height=15 width=15><br><br><br>\n",
            util_getConfigValue ("WEB_STATIC_URL"));

    puts ("Syncing files... ");
    fflush (stdout);
    if (cfio_push_raw (pid) != 0) {
      die ("Failed to sync files\n");
    }
    if (cfio_clear_working (pid) != 0) {
      die ("Failed to clear working directory files\n");
    }

    printf ("<img src=%s/check.png height=15 width=15><br><br><br>\n",
            util_getConfigValue ("WEB_STATIC_URL"));

    printf ("[<a href=%s/vat_cgi?mode=process&dataSet=vat.%d&annotationSet=%s&type=coding>View results</a>]\n",
            util_getConfigValue ("WEB_URL_CGI"), pid, annotationFile);

    puts ("<script type=\"text/javascript\" charset=\"utf-8\">"); 
    puts ("document.getElementById(\"processing\").style.visibility = \"hidden\"");
    puts ("</script>");
    puts ("</center>");
    puts ("</body>");
    puts ("</html>");
    cfio_deinit();
  }

  fflush (stdout);


  util_configDeInit ();
  return 0;
}




