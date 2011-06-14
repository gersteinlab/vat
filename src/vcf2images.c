#include <bios/log.h>
#include <bios/format.h>
#include <bios/linestream.h>
#include <bios/numUtil.h>
#include <bios/bits.h>
#include "util.h"
#include "vcf.h"
#include "gd.h"
#include "gdfontmb.h"
#include "gdfonts.h"
#include "gdfontt.h"



#define IMAGE_WIDTH 1000
#define MARGIN_TOP 10
#define MARGIN_BOTTOM 10
#define MARGIN_LEFT 50
#define MARGIN_RIGHT 50
#define SIZE_ANNOTATION 10
#define FRACTION_INTRONIC 0.3
#define WIDTH_TICK 2



#define LEGEND_MARGIN 15



static char *types[] = {"spliceOverlap","synonymous","nonsynonymous","prematureStop","removedStop","insertionNFS","insertionFS","deletionNFS","deletionFS","svOverlap"};



typedef struct {
  int genomic;
  int pixel;
} ImageCoordinate; 



typedef struct {
  int pixelStart;
  int pixelEnd;
  int color;
} TickMark;



static Array generateColors (gdImagePtr im)
{
  Array colors;

  colors = arrayCreate (NUMELE (types),int);
  array (colors,arrayMax (colors),int) = gdImageColorAllocate (im,255,0,0);
  array (colors,arrayMax (colors),int) = gdImageColorAllocate (im,255,165,0);
  array (colors,arrayMax (colors),int) = gdImageColorAllocate (im,255,255,0);
  array (colors,arrayMax (colors),int) = gdImageColorAllocate (im,50,205,50);
  array (colors,arrayMax (colors),int) = gdImageColorAllocate (im,0,255,0);
  array (colors,arrayMax (colors),int) = gdImageColorAllocate (im,64,224,208);
  array (colors,arrayMax (colors),int) = gdImageColorAllocate (im,0,0,255);
  array (colors,arrayMax (colors),int) = gdImageColorAllocate (im,0,0,128);
  array (colors,arrayMax (colors),int) = gdImageColorAllocate (im,160,32,240);
  array (colors,arrayMax (colors),int) = gdImageColorAllocate (im,205,133,65);
  if (arrayMax (colors) != NUMELE (types)) {
    die ("Number of colors and types do not match!");
  }
  return colors;
}



static int sortImageCoordinates (ImageCoordinate *a, ImageCoordinate *b)
{
  return a->genomic - b->genomic;
}



static Array generateImageCoordinateSystem (Array intervalPointers, int width)
{
  Interval *currInterval;
  int min,max;
  int k,l,m;
  Bits *bits;
  int bitCount;
  SubInterval *currExon;
  Array imageCoordinates;
  ImageCoordinate *currImageCoordinate;
  Array exonStarts,exonEnds;
  int compositeTranscriptLength;
  int intronSize;
  int totalExonSize;
  int exonSize;
  GraphCoordTrans gct;
  int start;

  exonStarts = arrayCreate (100,int);
  exonEnds = arrayCreate (100,int);
  currInterval = arru (intervalPointers,0,Interval*);
  min = currInterval->start;
  max = currInterval->end;
  for (k = 1; k < arrayMax (intervalPointers); k++) {
    currInterval = arru (intervalPointers,k,Interval*);
    if (currInterval->start < min) {
      min = currInterval->start;
    }
    if (currInterval->end > max) {
      max = currInterval->end;
    }
  }
  bitCount = max - min + 1;
  bits = bitAlloc (bitCount);
  for (k = 0; k < arrayMax (intervalPointers); k++) {
    currInterval = arru (intervalPointers,k,Interval*);
    for (l = 0; l < arrayMax (currInterval->subIntervals); l++) {
      currExon = arrp (currInterval->subIntervals,l,SubInterval);
      for (m = currExon->start; m <= currExon->end; m++) {
        bitSetOne (bits,m - min);
      }
    }
  }
  if (bitReadOne (bits,0) == 1) {
    array (exonStarts,arrayMax (exonStarts),int) = min;
  }
  for (k = min; k < max; k++) {
    if (bitReadOne (bits,k - min) == 1 &&
        bitReadOne (bits,k - min + 1) == 0) {
      array (exonEnds,arrayMax (exonEnds),int) = k;
    }
    if (bitReadOne (bits,k - min) == 0 &&
        bitReadOne (bits,k - min + 1) == 1) {
      array (exonStarts,arrayMax (exonStarts),int) = k + 1;
    }
  }
  if (bitReadOne (bits,bitCount - 1) == 1) {
    array (exonEnds,arrayMax (exonEnds),int) = max;
  }
  bitFree (&bits);
  if (arrayMax (exonStarts) != arrayMax (exonEnds)) {
    die ("exonStarts and exonEnds must have the same number of elements");
  }
  
  compositeTranscriptLength = 0;
  for (k = 0; k < arrayMax (exonStarts); k++) {
    compositeTranscriptLength += (arru (exonEnds,k,int) - arru (exonStarts,k,int));
  }
  
  intronSize = (double)width * FRACTION_INTRONIC / (arrayMax (exonStarts) - 1);
  totalExonSize = width - (intronSize * (arrayMax (exonStarts) - 1));
  imageCoordinates = arrayCreate (10000,ImageCoordinate);
  start = 0;
  for (k = 0; k < arrayMax (exonStarts); k++) {
    exonSize = (double)(arru (exonEnds,k,int) - arru (exonStarts,k,int)) / compositeTranscriptLength * totalExonSize;
    gct = gr_ct_create ((double)arru (exonStarts,k,int),(double)arru (exonEnds,k,int),start,start + exonSize);
    for (l = arru (exonStarts,k,int); l < arru (exonEnds,k,int); l++) {     
      currImageCoordinate = arrayp (imageCoordinates,arrayMax (imageCoordinates),ImageCoordinate);
      currImageCoordinate->genomic = l;
      currImageCoordinate->pixel = gr_ct_toPix (gct,l);
    }
    gr_ct_destroy (gct);
    start += (exonSize + intronSize);
  }
  arraySort (imageCoordinates,(ARRAYORDERF)sortImageCoordinates);
  arrayDestroy (exonStarts);
  arrayDestroy (exonEnds);
  return imageCoordinates;
}



static int genomicPosition2pixel (Array imageCoordinates, int genomicCoordinate) 
{
  ImageCoordinate testImageCoordinate;
  int index;
 
  testImageCoordinate.genomic = genomicCoordinate;
  if (!arrayFind (imageCoordinates,&testImageCoordinate,&index,(ARRAYORDERF)sortImageCoordinates)) {
    die ("Expected to find imageCoordinate: %d",genomicCoordinate); 
  }
  return arrp (imageCoordinates,index,ImageCoordinate)->pixel;
}



static int type2color (Array colors, char *type) 
{
  int i;

  i = 0; 
  while (i < NUMELE (types)) {
    if (strEqual (types[i],type)) {
      return arru (colors,i,int);
    }
    i++;
  }
  if (i == NUMELE (types)) {
    die ("Unexpected type: %s",type);
  }
  return -1;
}



static int getSplicePixel (Interval *currInterval, VcfEntry *currVcfEntry, Array imageCoordinates)
{
  SubInterval *currSubInterval;
  int i;
  int refLength,altLength,size;

  refLength = strlen (currVcfEntry->referenceAllele);
  altLength = strlen (currVcfEntry->alternateAllele);
  size = MAX (refLength,altLength);
  i = 0; 
  while (i < arrayMax (currInterval->subIntervals)) {
    currSubInterval = arrp (currInterval->subIntervals,i,SubInterval);
    if (rangeIntersection (currVcfEntry->position - 1,currVcfEntry->position - 1 + size,currSubInterval->start - 2,currSubInterval->start) > 0) {
      return genomicPosition2pixel (imageCoordinates,currSubInterval->start);
    }
    if (rangeIntersection (currVcfEntry->position - 1,currVcfEntry->position - 1 + size,currSubInterval->end - 1,currSubInterval->end + 2) > 0) {
      return genomicPosition2pixel (imageCoordinates,currSubInterval->end - 1);
    }
    i++;
  }
  die ("Expected to locate splice: %d %s %s\n%s",
       currVcfEntry->position,currVcfEntry->referenceAllele,currVcfEntry->alternateAllele,
       intervalFind_writeInterval (currInterval));
  return -1;
}



static Array getTickMarks (Interval *currInterval, char *transcriptId, VcfGene *currVcfGene, 
                           Array imageCoordinates, Array colors)
{
  VcfEntry *currVcfEntry;
  VcfAnnotation *currVcfAnnotation;
  int i,j,k;
  static Array transcriptVcfEntryPointers = NULL;
  static Array tickMarks = NULL;
  SubInterval *currSubInterval;
  int start,end,position;
  TickMark *currTickMark;
  static Texta types = NULL;
  int count;

  if (transcriptVcfEntryPointers == NULL) {
    transcriptVcfEntryPointers = arrayCreate (100,VcfEntry*);
  }
  else {
    arrayClear (transcriptVcfEntryPointers);
  }
  if (tickMarks == NULL) {
    tickMarks = arrayCreate (100,TickMark);
  }
  else {
    arrayClear (tickMarks); 
  }
  textCreateClear (types,100);
  for (i = 0; i < arrayMax (currVcfGene->vcfEntries); i++) {
    currVcfEntry = arru (currVcfGene->vcfEntries,i,VcfEntry*);
    if (vcf_hasMultipleAlternateAlleles (currVcfEntry)) {
      continue;
    }
    count = 0;
    for (j = 0; j < arrayMax (currVcfEntry->annotations); j++) {
      currVcfAnnotation = arrp (currVcfEntry->annotations,j,VcfAnnotation);
      if (strEqual (currVcfGene->geneId,currVcfAnnotation->geneId)) {
        k = 0; 
        while (k < arrayMax (currVcfAnnotation->transcriptIds)) {
          if (strEqual (textItem (currVcfAnnotation->transcriptIds,k),transcriptId)) {
            break;
          }
          k++;
        }
        if (k == arrayMax (currVcfAnnotation->transcriptIds)) {
          continue;
        }
        if (strEqual (currVcfAnnotation->type,"spliceOverlap")) {
          currTickMark = arrayp (tickMarks,arrayMax (tickMarks),TickMark);
          currTickMark->pixelStart = getSplicePixel (currInterval,currVcfEntry,imageCoordinates);
          currTickMark->pixelEnd = currTickMark->pixelStart + WIDTH_TICK - 1;
          currTickMark->color = type2color (colors,currVcfAnnotation->type);
          continue;
        }
        if (strEqual (currVcfAnnotation->type,"multiExonHit") ||
            strEqual (currVcfAnnotation->type,"startOverlap") ||
            strEqual (currVcfAnnotation->type,"endOverlap") ||
            strEqual (currVcfAnnotation->type,"svOverlap")) {
          continue;
        }
        array (transcriptVcfEntryPointers,arrayMax (transcriptVcfEntryPointers),VcfEntry*) = currVcfEntry;
        textAdd (types,currVcfAnnotation->type);
        count++;
      }
    }
    if (count > 1) {
      warn ("More than one trancripts per event:\n%s",vcf_writeEntry (currVcfEntry));
    }
  }
  for (i = 0; i < arrayMax (currInterval->subIntervals); i++) {
    currSubInterval = arrp (currInterval->subIntervals,i,SubInterval);
    start = genomicPosition2pixel (imageCoordinates,currSubInterval->start);
    end = genomicPosition2pixel (imageCoordinates,currSubInterval->end - 1);
    for (j = 0; j < arrayMax (transcriptVcfEntryPointers); j++) {
      currVcfEntry = arru (transcriptVcfEntryPointers,j,VcfEntry*);
      position = genomicPosition2pixel (imageCoordinates,currVcfEntry->position - 1);
      if (start <= position && position <= end) {
         currTickMark = arrayp (tickMarks,arrayMax (tickMarks),TickMark);
         currTickMark->pixelStart = position;
         currTickMark->pixelEnd = currTickMark->pixelStart + WIDTH_TICK - 1;
         currTickMark->color = type2color (colors,textItem (types,j));
      }
    }
  }
  return tickMarks;
}



static Array getSVTickMarks (Interval *currInterval, char *transcriptId, VcfGene *currVcfGene, 
                             Array imageCoordinates, Array colors)
{
  VcfEntry *currVcfEntry;
  VcfAnnotation *currVcfAnnotation;
  int i,j,k;
  static Array transcriptVcfEntryPointers = NULL;
  static Array tickMarks = NULL;
  SubInterval *currSubInterval;
  int start,end,position;
  TickMark *currTickMark;
  int count;
  int sizeSV;

  if (transcriptVcfEntryPointers == NULL) {
    transcriptVcfEntryPointers = arrayCreate (100,VcfEntry*);
  }
  else {
    arrayClear (transcriptVcfEntryPointers);
  }
  if (tickMarks == NULL) {
    tickMarks = arrayCreate (100,TickMark);
  }
  else {
    arrayClear (tickMarks); 
  }
  for (i = 0; i < arrayMax (currVcfGene->vcfEntries); i++) {
    currVcfEntry = arru (currVcfGene->vcfEntries,i,VcfEntry*);
    if (vcf_hasMultipleAlternateAlleles (currVcfEntry)) {
      continue;
    }
    count = 0;
    for (j = 0; j < arrayMax (currVcfEntry->annotations); j++) {
      currVcfAnnotation = arrp (currVcfEntry->annotations,j,VcfAnnotation);
      if (strEqual (currVcfGene->geneId,currVcfAnnotation->geneId)) {
        k = 0; 
        while (k < arrayMax (currVcfAnnotation->transcriptIds)) {
          if (strEqual (textItem (currVcfAnnotation->transcriptIds,k),transcriptId)) {
            break;
          }
          k++;
        }
        if (k == arrayMax (currVcfAnnotation->transcriptIds)) {
          continue;
        }
        if (!strEqual (currVcfAnnotation->type,"svOverlap")) {
          continue;
        }
        array (transcriptVcfEntryPointers,arrayMax (transcriptVcfEntryPointers),VcfEntry*) = currVcfEntry;
        count++;
      }
    }
    if (count > 1) {
      warn ("More than one trancripts per event:\n%s",vcf_writeEntry (currVcfEntry));
    }
  }
  for (i = 0; i < arrayMax (currInterval->subIntervals); i++) {
    currSubInterval = arrp (currInterval->subIntervals,i,SubInterval);
    start = genomicPosition2pixel (imageCoordinates,currSubInterval->start);
    end = genomicPosition2pixel (imageCoordinates,currSubInterval->end - 1);
    for (j = 0; j < arrayMax (transcriptVcfEntryPointers); j++) {
      currVcfEntry = arru (transcriptVcfEntryPointers,j,VcfEntry*);
      sizeSV = strlen (currVcfEntry->referenceAllele) - strlen (currVcfEntry->alternateAllele); // this is just for deletions
      if (rangeIntersection (currSubInterval->start,currSubInterval->end,currVcfEntry->position,currVcfEntry->position + sizeSV) > 0) {
         currTickMark = arrayp (tickMarks,arrayMax (tickMarks),TickMark);
         if ((currVcfEntry->position - 1) < currSubInterval->start) {
           currTickMark->pixelStart = start;
         }
         else {
           currTickMark->pixelStart = genomicPosition2pixel (imageCoordinates,currVcfEntry->position - 1);
         }
         if ((currVcfEntry->position - 1 + sizeSV) < (currSubInterval->end - 1)) {
           currTickMark->pixelEnd = genomicPosition2pixel (imageCoordinates,currVcfEntry->position - 1 + sizeSV);
         }
         else {
           currTickMark->pixelEnd = end;
         }
         currTickMark->color = type2color (colors,"svOverlap");
      }
    }
  }
  return tickMarks;
}



static void drawAnnotations (gdImagePtr im, VcfGene *currVcfGene, Array imageCoordinates, 
                             int width, int baseColor, Array colors)
{
  int i,j;
  Interval *currInterval;
  SubInterval *prevSubInterval,*currSubInterval;
  int yCoordinate;
  static Stringa buffer = NULL;
  char *geneId,*transcriptId,*geneName,*transcriptName;
  Array tickMarks;
  TickMark *currTickMark;

  geneId = NULL;
  transcriptId = NULL;
  geneName = NULL;
  transcriptName = NULL;
  stringCreateClear (buffer,100);
  for (i = 0; i < arrayMax (currVcfGene->transcripts); i++) {
    currInterval = arru (currVcfGene->transcripts,i,Interval*);
    yCoordinate = i * 2 * SIZE_ANNOTATION;
    for (j = 1; j < arrayMax (currInterval->subIntervals); j++) {
      prevSubInterval = arrp (currInterval->subIntervals,j - 1,SubInterval);
      currSubInterval = arrp (currInterval->subIntervals,j,SubInterval);
      gdImageLine (im,
                   MARGIN_LEFT + genomicPosition2pixel (imageCoordinates,prevSubInterval->end - 1),
                   MARGIN_TOP + yCoordinate + SIZE_ANNOTATION / 2,
                   MARGIN_LEFT + genomicPosition2pixel (imageCoordinates,currSubInterval->start),
                   MARGIN_TOP + yCoordinate + SIZE_ANNOTATION / 2,
                   baseColor);
    }
  }
  for (i = 0; i < arrayMax (currVcfGene->transcripts); i++) {
    currInterval = arru (currVcfGene->transcripts,i,Interval*);
    util_processTranscriptLine (currInterval->name,&geneId,&transcriptId,&geneName,&transcriptName);
    yCoordinate = i * 2 * SIZE_ANNOTATION;
    for (j = 0; j < arrayMax (currInterval->subIntervals); j++) {
      currSubInterval = arrp (currInterval->subIntervals,j,SubInterval);
      gdImageFilledRectangle (im,
                              MARGIN_LEFT + genomicPosition2pixel (imageCoordinates,currSubInterval->start),
                              MARGIN_TOP + yCoordinate,
                              MARGIN_LEFT + genomicPosition2pixel (imageCoordinates,currSubInterval->end - 1),
                              MARGIN_TOP + yCoordinate + SIZE_ANNOTATION,
                              baseColor);
    }
    tickMarks = getSVTickMarks (currInterval,transcriptId,currVcfGene,imageCoordinates,colors);
    for (j = 0; j < arrayMax (tickMarks); j++) {
      currTickMark = arrp (tickMarks,j,TickMark);
      gdImageFilledRectangle (im,
                              MARGIN_LEFT + currTickMark->pixelStart,
                              MARGIN_TOP + yCoordinate,
                              MARGIN_LEFT + currTickMark->pixelEnd,
                              MARGIN_TOP + yCoordinate + SIZE_ANNOTATION,
                              currTickMark->color);
    }
    tickMarks = getTickMarks (currInterval,transcriptId,currVcfGene,imageCoordinates,colors);
    for (j = 0; j < arrayMax (tickMarks); j++) {
      currTickMark = arrp (tickMarks,j,TickMark);
      gdImageFilledRectangle (im,
                              MARGIN_LEFT + currTickMark->pixelStart,
                              MARGIN_TOP + yCoordinate,
                              MARGIN_LEFT + currTickMark->pixelEnd,
                              MARGIN_TOP + yCoordinate + SIZE_ANNOTATION,
                              currTickMark->color);
    }
  }
}



static void generateLegend (char *path) 
{
  gdImagePtr im;
  static Stringa buffer = NULL;
  FILE *pngout;
  int black;
  int i;
  Array colors;
  int pixels;

  stringCreateClear (buffer,100);
  pixels = 0;
  for (i = 0; i < NUMELE (types); i++) {
    stringPrintf (buffer," %s ",types[i]);  
    pixels += (stringLen (buffer) * gdFontGetMediumBold ()->w);
  }
  im = gdImageCreate (pixels + 2 * LEGEND_MARGIN,3 * LEGEND_MARGIN + 2 * gdFontGetMediumBold ()->h);
  gdImageColorAllocate (im,140,140,140);
  black = gdImageColorAllocate (im,0,0,0);
  colors = generateColors (im);
  stringPrintf (buffer,"LEGEND FOR VARIATION TYPES:");
  gdImageString (im,
                 gdFontGetMediumBold (),
                 im->sx / 2 - (stringLen (buffer) * gdFontGetMediumBold ()->w / 2),
                 LEGEND_MARGIN,
                 (unsigned char*)string (buffer),
                 black);
  pixels = 0;
  for (i = 0; i < NUMELE (types); i++) {
    stringPrintf (buffer," %s ",types[i]);  
    gdImageString (im,
                   gdFontGetMediumBold (),
                   LEGEND_MARGIN + pixels,
                   LEGEND_MARGIN + gdFontGetMediumBold ()->h + 10,
                   (unsigned char*)types[i],
                   type2color (colors,types[i]));
    pixels += (stringLen (buffer) * gdFontGetMediumBold ()->w);
  }
  arrayDestroy (colors);
  stringPrintf (buffer,"%s/legend.png",path);
  pngout = fopen (string (buffer),"wb");
  gdImagePng (im,pngout);
  fclose (pngout);
  gdImageDestroy (im);
}



int main (int argc, char *argv[])
{
  Array vcfEntries;
  Array vcfGenes;
  VcfGene *currVcfGene;
  gdImagePtr im;
  FILE *pngout;
  int black,white;
  Array imageCoordinates;
  Array colors;
  Stringa buffer;
  int i;
  LineStream ls;
  char *chromosome;

  if (argc != 4) {
    usage ("%s <file.vcf.gz> <gencode.interval> <outputDir>",argv[0]);
  }
  util_configInit ("VAT_CONFIG_FILE");
  buffer = stringCreate (100);
  stringPrintf (buffer,"%s/tabix -l %s",util_getConfigValue ("TABIX_DIR"),argv[1]);
  ls = ls_createFromPipe (string (buffer));
  while (chromosome = ls_nextLine (ls)) {
    stringPrintf (buffer,"%s/tabix -h %s %s",util_getConfigValue ("TABIX_DIR"),argv[1],chromosome);
    vcf_initFromPipe (string (buffer));
    vcfEntries = vcf_parse ();
    vcfGenes = vcf_getGeneSummaries (vcfEntries,argv[2]);
    for (i = 0; i < arrayMax (vcfGenes); i++) {
      currVcfGene = arrp (vcfGenes,i,VcfGene);
      im = gdImageCreate (IMAGE_WIDTH,MARGIN_TOP + MARGIN_BOTTOM + SIZE_ANNOTATION * 2 * arrayMax (currVcfGene->transcripts) - SIZE_ANNOTATION);
      white = gdImageColorAllocate (im,255,255,255);
      black = gdImageColorAllocate (im,0,0,0);
      colors = generateColors (im);
      imageCoordinates = generateImageCoordinateSystem (currVcfGene->transcripts,IMAGE_WIDTH - MARGIN_LEFT - MARGIN_RIGHT);    
      drawAnnotations (im,currVcfGene,imageCoordinates,IMAGE_WIDTH - MARGIN_LEFT - MARGIN_RIGHT,black,colors);
      arrayDestroy (colors);
      stringPrintf (buffer,"%s/%s.png",argv[3],currVcfGene->geneId);
      pngout = fopen (string (buffer),"w");
      if (pngout == NULL) {
        die ("Unable to open file: %s",string (buffer)); 
      }
      gdImagePng (im,pngout);
      fclose (pngout);
      gdImageDestroy (im);
      arrayDestroy (imageCoordinates);
    }
    vcf_freeEntries (vcfEntries);
    vcf_deInit ();
  }
  ls_destroy (ls);
  generateLegend (argv[3]);
  stringDestroy (buffer);
  util_configDeInit ();
  return 0;
}




