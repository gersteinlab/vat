#ifndef DEF_UTIL_H
#define DEF_UTIL_H



#include <bios/fasta.h>
#include <bios/intervalFind.h>



typedef struct {
  char *geneId;
  char *transcriptId;
  char *geneName;
  char *transcriptName;
} GeneTranscriptEntry;



typedef struct {
  char* chromosome;
  int genomic;
  int transcript;
} Coordinate; 



typedef struct {
  char *geneId;
  char *transcriptId;
  char *geneName;
  char *transcriptName;
  char strand;
  char *type;
  int transcriptLength;
  int relativePosition;
  char *substitution;
} Alteration;



extern Array util_getGeneTranscriptEntries (Array intervals);
extern void util_destroyGeneTranscriptEntries (Array geneTranscriptEntries);
extern Texta util_getTranscriptIdsForGeneId (Array geneTranscriptEntries, char *geneId);
extern Texta util_getQueryStringsForGeneId (Array geneTranscriptEntries, char *geneId);
extern void util_processTranscriptLine (char *transcriptLine, char **geneId, char **transcriptId, char **geneName, char **transcriptName);
extern char* util_translate (Interval *currInterval, char *sequence);
extern int util_sortSequencesByName (Seq *a, Seq *b);
extern int util_sortCoordinatesByChromosomeAndGenomicPosition (Coordinate *a, Coordinate *b);
extern int util_sortCoordinatesByChromosomeAndTranscriptPosition (Coordinate *a, Coordinate *b);
extern Array util_getCoordinates (Interval *transcript);
extern void util_destroyCoordinates (Array coordinates);
extern int util_getGenomicCoordinate (Array coordinates, int transcriptCoordinate, char* chromosome);
extern int util_getTranscriptCoordinate (Array coordinates, int genomicCoordinate, char* chromosome);
extern int util_sortAlterationsByGeneIdAndType (Alteration *a, Alteration *b);
extern void util_addAlteration (Alteration *currAlteration, char *fullTranscriptName, char *type, Interval *currInterval, int position);
extern void util_clearAlterations (Array alterations);
extern void util_configInit (char *nameConfigFileEnvironmentVariable);
extern char* util_getConfigValue (char *parameterName);
extern void util_configDeInit (void);



#endif
