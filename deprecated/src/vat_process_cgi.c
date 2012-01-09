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

static void _process (char *inFile)
{
    Stringa item;
    Stringa value;
    Stringa fileName;
    Stringa contentType;
    FILE *fp;
    Stringa buffer;
    Stringa buffer2;
    Stringa cmd;
    char *program;
    char *annotationFile;
    char *vcfFile;
    int pid;
    int process;

    process = 0;
    item = stringCreate (20);
    value = stringCreate (20);
    fileName = stringCreate (20);
    contentType = stringCreate (20);
    buffer = stringCreate (20);
    buffer2 = stringCreate (20);
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

    if (inFile == NULL) {
        cgiMpInit();
        while (cgiMpNext (item, value, fileName, contentType)) {
            if (strEqual (string (item), "upFile")) {
                vcfFile = hlr_strdup (string (value));
            } else if (strEqual (string (item), "variantType")) {
                program = hlr_strdup (string (value));
            } else if (strEqual (string (item), "annotationFile")) {
                annotationFile = hlr_strdup (string (value));
            } else if (strEqual (string (item), "process")) {
                process = 1;
            } else {
                die ("Unknown item/value pair: %s/%s", string (item), string (value));
            }
        }
        cgiMpDeinit ();
    } else {
        if (cfio_get_raw (inFile) != 0) {
            die ("Cannot get input file %s\n", inFile);
        }
    }

    if (inFile == NULL && vcfFile[0] == '\0') {
        puts ("<script type=\"text/javascript\" charset=\"utf-8\">");
        puts ("document.getElementById(\"processing\").style.visibility = \"hidden\"");
        puts ("</script>");
        die ("No VCF file uploaded!");
    }

    puts ("<br><br><br>");
    puts ("Step [1/%d]: Writing file... ", (process == 1) ? 2 : 7);
    fflush (stdout);

    stringPrintf (cmd, "mkdir %s/%d",
                  util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid);
    hlr_system (string (cmd), 0);

    stringClear (cmd);
    stringPrintf (cmd,"mkdir %s/%d/vat.%d",
                  util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid);
    hlr_system (string (cmd), 0);

    stringPrintf (buffer,"%s/%d/vat.%d.raw.vcf",
                  util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid);

    if (inFile == NULL) {
        fp = fopen (string (buffer), "w");
        if (fp == NULL) {
            die ("Unable to open file: %s", string (buffer));
        }
        fprintf (fp,"%s",vcfFile);
        fclose (fp);
    } else {
        stringPrintf (buffer2, "%s/%s", util_getConifgValue ("WEB_DATA_WORKING_DIR"), inFile);
        if (shell_mv (string (buffer2), string (buffer)) != 0) {
            die ("Cannot rename file %s\n", string (buffer));
        }
    }

    if (process == 0) {
        assert (inFile == NULL);

        if (cfio_push_raw (string (buffer)) != 0) {
            die ("Cannot push file %s\n", string (buffer));
        }
        // TODO
        //if ()
    }

    printf ("<img src=%s/check.png height=15 width=15><br><br><br>\n",
            util_getConfigValue ("WEB_STATIC_URL"));
    puts ("Step [2/7]: Annotating variants... ");
    fflush (stdout);

    if (strEqual (program,"svMapper")) {
        stringPrintf (cmd,"%s/%s %s/%s.interval < %s/%d/vat.%d.raw.vcf > %s/%d/vat.%d.vcf",
                      util_getConfigValue ("VAT_EXEC_DIR"), program,
                      util_getConfigValue ("WEB_DATA_REFERENCE_DIR"), annotationFile,
                      util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid,
                      util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid);
    } else {
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
    puts ("Step [3/7]: Indexing files... ");
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
    puts ("Step [4/7]: Creating variant summay file... ");
    fflush (stdout);

    stringPrintf (cmd,"%s/vcfSummary %s/%d/vat.%d.vcf.gz %s/%s.interval",
                 util_getConfigValue ("VAT_EXEC_DIR"),
                 util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid,
                 util_getConfigValue ("WEB_DATA_REFERENCE_DIR"), annotationFile);
    hlr_system (string (cmd),0);

    printf ("<img src=%s/check.png height=15 width=15><br><br><br>\n",
           util_getConfigValue ("WEB_STATIC_URL"));
    puts ("Step [5/7]: Generating images... ");
    fflush (stdout);

    stringPrintf (cmd,"%s/vcf2images %s/%d/vat.%d.vcf.gz %s/%s.interval %s/%d/vat.%d",
                 util_getConfigValue ("VAT_EXEC_DIR"),
                 util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid,
                 util_getConfigValue ("WEB_DATA_REFERENCE_DIR"), annotationFile,
                 util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid);
    hlr_system (string (cmd),0);

    printf ("<img src=%s/check.png height=15 width=15><br><br><br>\n",
           util_getConfigValue ("WEB_STATIC_URL"));
    puts ("Step [6/7]: Subsetting file... ");
    fflush (stdout);

    stringPrintf (cmd,"%s/vcfSubsetByGene %s/%d/vat.%d.vcf.gz %s/%s.interval %s/%d/vat.%d",
                 util_getConfigValue ("VAT_EXEC_DIR"),
                 util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid,
                 util_getConfigValue ("WEB_DATA_REFERENCE_DIR"), annotationFile,
                 util_getConfigValue ("WEB_DATA_WORKING_DIR"), pid, pid);
    hlr_system (string (cmd),0);

    printf ("<img src=%s/check.png height=15 width=15><br><br><br>\n",
           util_getConfigValue ("WEB_STATIC_URL"));

    puts ("Step [7/7]: Syncing files... ");
    fflush (stdout);
    if (cfio_push_data (pid) != 0) {
        die ("Failed to sync files\n");
    }
    if (cfio_clear_working (pid) != 0) {
        die ("Failed to clear working directory files\n");
    }

    printf ("<img src=%s/check.png height=15 width=15><br><br><br>\n",
           util_getConfigValue ("WEB_STATIC_URL"));

    printf ("[<a href=%s/vat_cgi?mode=process&dataSet=vat.%d&setId=%d&annotationSet=%s&type=coding>View results</a>]\n",
           util_getConfigValue ("WEB_URL_CGI"), pid, pid, annotationFile);

    puts ("<script type=\"text/javascript\" charset=\"utf-8\">");
    puts ("document.getElementById(\"processing\").style.visibility = \"hidden\"");
    puts ("</script>");
    puts ("</center>");
    puts ("</body>");
    puts ("</html>");

    cfio_deinit();

    stringDestroy (item);
    stringDestroy (value);
    stringDestroy (fileName);
    stringDestroy (contentType);
    stringDestroy (buffer);
    stringDestroy (buffer2);
    stringDestroy (cmd);
}

int main (int argc, char *argv[])
{
    cgiInit ();
    cgiHeader("text/html");
    util_configInit ("VAT_CONFIG_FILE");

    int first;
    Stringa item = stringCreate (20);
    Stringa value = stringCreate (20);
    char *iPtr, *vPtr;
    char *inFile = NULL;

    first = 1;
    cgiGetInit ();

    while (cgiGetNextPair (&first, item, value)) {
        iPtr = string (item);
        vPtr = string (value);

        if (strEqual(iPtr, "inFile")) {
            inFile = strReplace (&inFile, vPtr);
        }
    }

    _process (inFile);


    fflush (stdout);

    util_configDeInit ();
    return 0;
}




