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

static void _upload_form ()
{
    puts ("<html>");
    puts ("<head>");
    html_printGenericStyleSheet (12);
    puts ("<title>VAT</title>\n");
    puts ("</head>");
    puts ("<body>");
    puts ("<h4><center>[<a href=\"http://vat.gersteinlab.org\">VAT Main Page</a>]</center></h4>");
    puts ("<h1>Variation Annotation Tool (VAT)</h1>");
    printf ("<form action=\"%s/vat_process_cgi\" method=\"post\" ENCTYPE=\"multipart/form-data\">\n", util_getConfigValue ("WEB_URL_CGI"));
    puts ("<br>");
    puts ("<b>VCF file upload</b> (Examples<sup>&Dagger;</sup>: "
          "[<a href=\"http://homes.gersteinlab.org/people/lh372/VAT/1000genomes_pilot_snps.sample.vcf\">SNPs</a>] "
          "[<a href=\"http://homes.gersteinlab.org/people/lh372/VAT/1000genomes_pilot_indels.sample.vcf\">Indels</a>] "
          "[<a href=\"http://homes.gersteinlab.org/people/lh372/VAT/1000genomes_pilot_svs.sample.vcf\">SVs</a>]):&nbsp;&nbsp;");
    puts ("<input type=\"file\" name=\"upFile\">");
    puts ("<br><br><br>");
    puts ("<b>Variant type</b>:&nbsp;&nbsp;");
    puts ("<select name=\"variantType\">");
    puts ("<option value=\"snpMapper\" checked=\"checked\">SNPs</option>");
    puts ("<option value=\"indelMapper\">Indels</option>");
    puts ("<option value=\"svMapper\">SVs</option>");
    puts ("</select>");
    puts ("<br><br><br>");
    puts ("<b>Annotation file</b>:&nbsp;&nbsp;");
    puts ("<select name=\"annotationFile\">");
    puts ("<option value=\"gencode3b\">GENCODE (version 3b; hg18)</option>");
    puts ("<option value=\"gencode3c\">GENCODE (version 3c; hg19)</option>");
    puts ("<option value=\"gencode4\">GENCODE (version 4; hg19)</option>");
    puts ("<option value=\"gencode5\">GENCODE (version 5; hg19)</option>");
    puts ("<option value=\"gencode6\">GENCODE (version 6; hg19)</option>");
    puts ("<option value=\"gencode7\">GENCODE (version 7; hg19)</option>");
    puts ("</select>");
    puts ("<br><br><br>");
    puts ("<input type=\"checkbox\" name=\"process\" value=\"yes\" checked=\"checked\"> Process after upload");
    puts ("<br><br><br>");
    puts ("<input type=\"submit\" value=\"Submit\">");
    puts ("<input type=\"reset\" value=\"Reset\">");
    puts ("</form>");
    puts ("<br><br><br><br><br><br><br>");
    puts ("_________________<br>");
    puts ("<fn><sup>&Dagger;</sup> - The example files were obtained from the <a href=\"http://www.1000genomes.org\">1000 Genomes Pilot Project</a>. The genome coordinates are based on hg18.</fn>");
    puts ("</body>");
    puts ("</html>");
    fflush (stdout);
}


int main (int argc, char *argv[])
{
    cgiInit ();
    cgiHeader("text/html");
    util_configInit ("VAT_CONFIG_FILE");

    _upload_form ();

    fflush (stdout);

    util_configDeInit ();
    return 0;
}




