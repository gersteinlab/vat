/**
 *
 */

#include <config.h>
#include <stdio.h>
#include <getopt.h>
#include <bios/log.h>
#include <bios/format.h>
#include <bios/linestream.h>
#include <bios/intervalFind.h>

#include "util.h"
#include "vcf.h"

static const char *short_options = "GNYd:a:g:i:s:f:";
static const struct option long_options[] = {
    { "gene-info",      no_argument,       0, 'G' },
    { "noncoding-info", no_argument,       0, 'N' },
    { "genotypes",      no_argument,       0, 'Y' },
    { "data-set",       required_argument, 0, 'd' },
    { "annotation-set", required_argument, 0, 'a' },
    { "gene-id",        required_argument, 0, 'g' },
    { "index",          required_argument, 0, 'i' },
    { "set-id",         required_argument, 0, 's' },
    { "file",           required_argument, 0, 'f' },
    { 0, 0, 0, 0 }
};

enum {
    MODE_GENE_INFO = 0,
    MODE_NONCODING_INFO,
    MODE_GENOTYPE
};

static char *_hyperlinkId (char *id)
{
    static Stringa buffer = NULL;
    Texta tokens;
    int i;

    stringCreateClear (buffer,1000);

    if (!strstr (id,"rs")) {
        return id;
    }

    tokens = textFieldtokP (id, ";");

    for (i = 0; i < arrayMax (tokens); i++) {
        if (strstr (textItem (tokens,i),"rs")) {
            stringAppendf (buffer,"<a href=http://www.ncbi.nlm.nih.gov/SNP/snp_ref.cgi?rs=%s target=external>%s</a>",
                           textItem (tokens,i) + 2,textItem (tokens,i));
        } else {
            stringAppendf (buffer,"%s",textItem (tokens,i));
        }
        stringCat (buffer,i < arrayMax (tokens) - 1 ? ";" : "");
    }

    textDestroy (tokens);
    return string (buffer);
}


static void _showInformation (char *file,
                               char *dataSet,
                               char *annotationSet,
                               char *geneId,
                               char *type,
                               int setId)
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

    stringCreateClear (buffer,100);

    vcf_init (file);
    vcfEntries = vcf_parse ();
    groups = vcf_getGroupsFromColumnHeaders ();
    vcf_deInit ();

    stringPrintf (buffer,"%s/%s.interval", util_getConfigValue ("WEB_DATA_REFERENCE_DIR"), annotationSet);
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
        die ("Unable to find %s in %s!", geneId, dataSet);
    }

    currInterval = arru (currVcfGene->transcripts, 0, Interval*);
    start = currInterval->start;
    end = currInterval->end;

    for (i = 1; i < arrayMax (currVcfGene->transcripts); i++) {
        currInterval = arru (currVcfGene->transcripts, i, Interval*);
        if (start > currInterval->start) {
            start = currInterval->start;
        }
        if (end < currInterval->end) {
            end = currInterval->end;
        }
    }

    printf (
        "{\n"
        "\"dataSet\": \"%s\",\n"
        "\"geneName\": \"%s\",\n"
        "\"geneId\": \"%s\",\n"
        "\"annotationSet\": \"%s\",\n"
        "\"type\": \"%s\",\n",
        dataSet,
        currVcfGene->geneName,
        geneId,
        annotationSet,
        type
    );
    printf (
        "\"links\": {\n"
        "\t\"genomeBrowser\": \"http://genome.ucsc.edu/cgi-bin/hgTracks?clade=mammal&org=human&db=hg18&position=%s:%d-%d\",\n"
        "\t\"ensemblGenomeBrowser\": \"http://may2009.archive.ensembl.org/Homo_sapiens/Gene/Summary?g=%s\",\n",
        currInterval->chromosome, start - 1000, end + 1000,
        geneId
    );

    if (strEqual (type, "coding")) {
        printf (
            "\t\"geneCards\": \"http://www.genecards.org/cgi-bin/carddisp.pl?gene=%s\"\n",
            currVcfGene->geneName
        );
    } else {
        printf (
            "\t\"geneCards\": null\n"
        );
    }

    printf (
        "},\n"
        "\"transcriptSummary\": [\n"
    );

    thisGeneId = NULL;
    transcriptId = NULL;
    geneName = NULL;
    transcriptName = NULL;

    for (i = 0; i < arrayMax (currVcfGene->transcripts); i++) {
        currInterval = arru (currVcfGene->transcripts,i,Interval*);
        util_processTranscriptLine (currInterval->name, &thisGeneId, &transcriptId, &geneName, &transcriptName);

        printf (
            "\t{\n"
            "\t\t\"transcriptName\": \"%s\",\n"
            "\t\t\"transcriptId\":   \"%s\",\n"
            "\t\t\"chromosome\":     \"%s\",\n"
            "\t\t\"strand\":         \"%c\",\n"
            "\t\t\"start\":          %d,\n"
            "\t\t\"end\":            %d,\n"
            "\t\t\"numExons\":       %d,\n"
            "\t\t\"length\":         %d\n"
            "\t}%s\n",
            transcriptName,
            transcriptId,
            currInterval->chromosome,
            currInterval->strand,
            currInterval->start,
            currInterval->end,
            arrayMax (currInterval->subIntervals),
            intervalFind_getSize (currInterval),
            (i < arrayMax (currVcfGene->transcripts) - 1) ? "," : ""
        );
    }

    printf (
        "],\n"
    );

    if (strEqual (type,"coding")) {
        printf (
            "\"variantsImage\": \"%s/%d/%s/%s.png\",\n"
            "\"legendImage\": \"%s/%d/%s/legend.png\",\n"
            "\"secondaryStructureRefImage\": null,\n"
            "\"secondaryStructureVarImage\": null,\n",
            util_getConfigValue ("WEB_DATA_URL"), setId, dataSet, geneId,
            util_getConfigValue ("WEB_DATA_URL"), setId, dataSet
        );
    } else if (strEqual (type,"nonCoding")) {
        printf (
            "\"variantsImage\": null,\n"
            "\"legendImage\": null,\n"
            "\"secondaryStructureRefImage\": \"%s/%d/%s/%s_ref.svg\",\n"
            "\"secondaryStructureVarImage\": \"%s/%d/%s/%s_alt.svg\",\n",
            util_getConfigValue ("WEB_DATA_URL"), setId, dataSet, geneId,
            util_getConfigValue ("WEB_DATA_URL"), setId, dataSet, geneId
        );
    } else {
        die ("Unknown type: %s",type);
    }

    i = 0;
    while (i < arrayMax (currVcfGene->vcfEntries)) {
        currVcfEntry = arru (currVcfGene->vcfEntries,i,VcfEntry*);
        if (arrayMax (currVcfEntry->genotypes) > 0) {
            break;
        }
        i++;
    }

    printf (
        "\"alternateAlleles\": [\n"
    );
    if (i < arrayMax (currVcfGene->vcfEntries)) {
        for (i = 0; i < arrayMax (groups); i++) {
            printf (
                "\t\"%s\"%s\n",
                textItem (groups, i),
                (i < arrayMax (groups) - 1) ? "," : ""
            );
        }
    }
    printf (
        "],\n"
    );

    printf (
        "\"variantSummary\": [\n"
    );

    int first = 1;
    for (i = 0; i < arrayMax (currVcfGene->vcfEntries); i++) {
        currVcfEntry = arru (currVcfGene->vcfEntries,i,VcfEntry*);
        if (vcf_hasMultipleAlternateAlleles (currVcfEntry))
            continue;

        for (j = 0; j < arrayMax (currVcfEntry->annotations); j++) {
            currVcfAnnotation = arrp (currVcfEntry->annotations,j,VcfAnnotation);
            if (!strEqual (currVcfGene->geneId,currVcfAnnotation->geneId))
                continue;

            if (first == 0)
                printf (",\n");

            first = 0;

            printf (
                "\t{\n"
                "\t\t\"chromosome\":      \"%s\",\n"
                "\t\t\"position\":        %d,\n"
                "\t\t\"referenceAllele\": \"%s\",\n"
                "\t\t\"alternateAllele\": \"%s\",\n"
                "\t\t\"identifier\":      \"%s\",\n"
                "\t\t\"type\":            \"%s\",\n"
                "\t\t\"fraction\":        \"%s\",\n",
                currVcfEntry->chromosome,
                currVcfEntry->position,
                strlen (currVcfEntry->referenceAllele) > 50 ? "Length > 50 nucleotides" : currVcfEntry->referenceAllele,
                strlen (currVcfEntry->alternateAllele) > 50 ? "Length > 50 nucleotides" : currVcfEntry->alternateAllele,
                _hyperlinkId (currVcfEntry->id),
                currVcfAnnotation->type,
                currVcfAnnotation->fraction
            );

            printf (
                "\t\t\"transcriptIds\": [\n"
            );
            for (k = 0; k < arrayMax (currVcfAnnotation->transcriptIds); k++) {
                printf (
                    "\t\t\t\"%s\"%s\n",
                    textItem (currVcfAnnotation->transcriptIds, k),
                    (k < arrayMax (currVcfAnnotation->transcriptIds) - 1) ? "," : ""
                );
            }
            printf (
                "\t\t],\n"
                "\t\t\"transcriptDetails\": [\n"
            );
            for (k = 0; k < arrayMax (currVcfAnnotation->transcriptDetails); k++) {
                printf (
                    "\t\t\t\"%s\"%s\n",
                    textItem (currVcfAnnotation->transcriptDetails, k),
                    (k < arrayMax (currVcfAnnotation->transcriptDetails) - 1) ? "," : ""
                );
            }
            printf (
                "\t\t],\n"
                "\t\t\"alternateAlleleFreqs\": [\n"
            );

            for (k = 0; k < arrayMax (groups); k++) {
                vcf_getAlleleInformation (currVcfEntry, textItem (groups, k), 0, &alleleCount0, &totalAlleleCount0);
                vcf_getAlleleInformation (currVcfEntry, textItem (groups, k), 1, &alleleCount1, &totalAlleleCount1);
                if (alleleCount0 == 0 && alleleCount1 == 0) {
                    printf (
                        "\t\t\t\"N/A\"%s\n",
                        (k < arrayMax (groups) - 1) ? "," : ""
                    );
                } else {
                    printf (
                        "\t\t\t\"%.3f\"%s\n",
                        (double) alleleCount1 / totalAlleleCount1,
                        (k < arrayMax(groups) - 1) ? "," : ""
                    );
                }
            }
            printf (
                "\t\t],\n"
                "\t\t\"index\": %d\n",
                i
            );
            printf (
                "\t}"
            );
        }
    }

    printf (
        "]\n"
        "}\n"
    );
    fflush (stdout);
}

static void _showGeneInformation (char *file, char *dataSet, char *annotationSet,
                                  char *geneId, int setId)
{
    _showInformation (file, dataSet, annotationSet, geneId, "coding", setId);
}



static void _showNonCodingInformation (char *file, char *dataSet, char *annotationSet,
                                       char *geneId, int setId)
{
    _showInformation (file, dataSet, annotationSet, geneId, "nonCoding", setId);
}

static void _showGenotypes (char *file, char *dataSet, char *geneId, int index, int setId)
{
    static Stringa buffer = NULL;
    Array vcfEntries;
    VcfEntry *currVcfEntry;
    VcfGenotype *currVcfGenotype;
    int i, j;
    Texta groups;
    int alleleCount0, totalAlleleCount0;
    int alleleCount1, totalAlleleCount1;

    stringCreateClear (buffer, 100);

    vcf_init (file);
    vcfEntries = vcf_parse ();
    groups = vcf_getGroupsFromColumnHeaders ();
    vcf_deInit ();

    if (index >= arrayMax (vcfEntries)) {
        die ("Invalid index");
    }

    currVcfEntry = arrp (vcfEntries,index,VcfEntry);
    printf(
        "{\n"
    );

    printf (
        "\"chromosome\":      \"%s\",\n"
        "\"position\":        %d,\n"
        "\"referenceAllele\": \"%s\",\n"
        "\"alternateAllele\": \"%s\",\n",
        currVcfEntry->chromosome,
        currVcfEntry->position,
        currVcfEntry->referenceAllele,
        currVcfEntry->alternateAllele
    );

    printf (
        "\"groups\": [\n"
    );
    for (i = 0; i < arrayMax (groups); i++) {
        printf (
            "\t\"%s\"%s\n",
            textItem (groups, i),
            (i < arrayMax (groups) - 1) ? "," : ""
        );
    }
    printf (
        "],\n"
        "\"alleleGroupHeaders\": [\n"
    );

    for (i = 0; i < arrayMax (groups); i++) {
        vcf_getAlleleInformation (currVcfEntry, textItem (groups, i), 0, &alleleCount0, &totalAlleleCount0);
        vcf_getAlleleInformation (currVcfEntry, textItem (groups, i), 1, &alleleCount1, &totalAlleleCount1);

        if (alleleCount0 == 0 && alleleCount1 == 0) {
            printf (
                "\t{ \"refCount\": %d, \"altCount\": %d }%s\n",
                totalAlleleCount0,
                0,
                (i < arrayMax (groups) - 1) ? "," : ""
            );
        } else {
            printf (
                "\t{ \"refCount\": %d, \"altCount\": %d }%s\n",
                alleleCount0,
                alleleCount1,
                (i < arrayMax (groups) - 1) ? "," : ""
            );
        }
    }

    printf (
        "],\n"
        "\"alleleGroups\": [\n"
    );
    int first;
    for (i = 0; i < arrayMax (groups); i++) {
        printf (
            "\t[\n"
        );

        first = 1;
        for (j = 0; j < arrayMax (currVcfEntry->genotypes); j++) {
            currVcfGenotype = arrp (currVcfEntry->genotypes, j, VcfGenotype);
            if (strEqual (textItem (groups, i), currVcfGenotype->group)) {

                if (first == 0)
                    printf(",\n");

                first = 0;

                printf (
                    "\t\t{ \"sample\": \"%s\", \"genotype\": \"%s\" }",
                    currVcfGenotype->sample,
                    currVcfGenotype->genotype
                );
            }
        }
        printf (
            "\t]%s\n",
            (i < arrayMax (groups) - 1) ? "," : ""
        );
    }

    printf (
        "]\n"
        "}\n"
    );
}


static void _usage()
{
    printf(
        "Usage: vatson [OPTION]... [VAR=VALUE]\n"
        "There are three modes that the vatson command line tool can be run:\n"
        "  -G --gene-info        Get transcript and variant info for a gene\n"
        "  -N --noncoding-info   Get noncoding information\n"
        "  -Y --genotype         Get genotype information for a gene's variants\n"
        "\n"
        "Some input files must be specified:\n"
        "  -d --data-set         Name of data set\n"
        "  -a --annotation-set   Name of annotation set\n"
        "  -g --gene-id          Gene ID\n"
        "  -i --index            Index of genotype for displaying genotype information\n"
        "  -s --set-id           Set ID of file. Yes, this is redundant with data-set. Fix later\n"
        "  -f --file             Input file"
        "The output is in JSON which is easily parsable and used such as by the PHP web apps.\n"
    );
}

int main (int argc, char *argv[])
{
    int option_index = 0;
    int c;
    int mode = -1;
    char *dataSet = NULL;
    char *geneId = NULL;
    char *annotationSet = NULL;
    char *type = NULL;
    char *file = NULL;
    int setId = -1;
    int index = -1;

    util_configInit ("VAT_CONFIG_FILE");

    do {
        c = getopt_long (argc, argv, short_options, long_options, &option_index);

        if (c == -1)
            break;

        switch (c) {
        case 'G':
            mode = MODE_GENE_INFO;
            break;
        case 'N':
            mode = MODE_NONCODING_INFO;
            break;
        case 'Y':
            mode = MODE_GENOTYPE;
            break;
        case 'd':
            strReplace (&dataSet, optarg);
            break;
        case 'a':
            strReplace (&annotationSet, optarg);
            break;
        case 'g':
            strReplace (&geneId, optarg);
            break;
        case 'i':
            index = atoi (optarg);
            break;
        case 's':
            setId = atoi (optarg);
            break;
        case 'f':
            strReplace (&file, optarg);
            break;
        case '?':
            break;
        default:
            _usage ();
            exit (-1);
        }
    } while (c != -1);

    switch (mode) {
    case MODE_GENE_INFO:
        if (annotationSet == NULL || dataSet == NULL || geneId == NULL || file == NULL || setId == -1) {
            _usage ();
            exit (-2);
        }
        _showGeneInformation (file, dataSet, annotationSet, geneId, setId);
        break;
    case MODE_NONCODING_INFO:
        if (annotationSet == NULL || dataSet == NULL || geneId == NULL || file == NULL || setId == -1) {
            _usage ();
            exit (-2);
        }

        _showNonCodingInformation (file, dataSet, annotationSet, geneId, setId);
        break;
    case MODE_GENOTYPE:
        if (dataSet == NULL || geneId == NULL || index == -1 || setId == -1) {
            _usage ();
            exit (-2);
        }

        _showGenotypes (file, dataSet, geneId, index, setId);
        break;
    default:
        _usage ();
        exit (-2);
    }

    util_configDeInit ();

    return EXIT_SUCCESS;
}
