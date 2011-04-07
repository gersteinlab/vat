#include <bios/log.h>
#include <bios/format.h>
#include <bios/linestream.h>
#include "util.h"
#include "vcf.h"



typedef struct {
  char *sample;
  char *group;
} Group;



static Array readGroups (char *file)
{
  LineStream ls;
  char *line;
  WordIter w;
  Array groups;
  Group *currGroup;

  groups = arrayCreate (100,Group);
  ls = ls_createFromFile (file);
  while (line = ls_nextLine (ls)) {
    currGroup = arrayp (groups,arrayMax (groups),Group);
    w = wordIterCreate (line,"\t",0);
    currGroup->sample = hlr_strdup (wordNext (w));
    currGroup->group = hlr_strdup (wordNext (w));
    wordIterDestroy (w);
  }
  ls_destroy (ls);
  return groups;
}



int main (int argc, char *argv[])
{
  Texta columnHeaders;
  Array groups;
  int i,j;
  Group *currGroup;

  if (argc != 3) {
    usage ("%s <oldHeader.vcf> <groups.txt>",argv[0]);
  }
  vcf_init (argv[1]);
  puts (vcf_writeMetaData ());
  groups = readGroups (argv[2]);
  columnHeaders = vcf_getColumnHeaders ();
  for (i = 0; i < arrayMax (columnHeaders); i++) {
    if (i < 9) {
      printf ("%s\t",textItem (columnHeaders,i));
    }
    else {
      j = 0; 
      while (j < arrayMax (groups)) {
        currGroup = arrp (groups,j,Group);
        if (strEqual (currGroup->sample,textItem (columnHeaders,i))) {
          break;
        }
        j++;
      }
      if (j == arrayMax (groups)) {
        die ("Unable to find group for sample: %s",textItem (columnHeaders,i));
      }
      printf ("%s:%s%s",currGroup->group,textItem (columnHeaders,i),i < arrayMax (columnHeaders) - 1 ? "\t" : "\n");
    }
  }
  vcf_deInit ();
  return 0;
}
