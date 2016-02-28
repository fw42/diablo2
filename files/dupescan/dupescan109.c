/**********************************************************************
 * dupescan109 - Diablo II 1.09d Dupescanner                          *
 *  written by Florian 'fw' Weingarten <http://hackvalue.de/>         *
 *                                                                    *
 * See README for additional information on implementation and usage. *
 *                                                                    *
 *    This piece of software is distributed under the terms of the    *
 *    GNU General Public License (GPL). The complete license comes    *
 *                     with this file package.                        *
 **********************************************************************
 * CHANGELOG:                                                         *
 *  - Aug 13 2006: fixed a possible heap overflow if the number of    *
 *    items which is told us by the JM header does not match the      *
 *    actually following JMs (items), added a realloc if buffer is    *
 *    too small
 *  - Aug 10 2006: fixed a possible misinterpretation of JM in the    *
 *    binary file which lead to a possible memory allocation error    *
 *    and added some additional debug information                     *
 **********************************************************************/

#define DEFAULT_DIRNAME "/home/diablo/var/charsave"

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>
#include <string.h>
#include <dirent.h>
#include <getopt.h>

#define COLOROK "\033[32;;32m"
#define COLORERROR "\033[31;;31m"
#define COLORCLOSE "\033[0;;0m"
#define COLORDEBUG "\033[33;;33m"
#define COLORDUPE COLORERROR

struct fingerprintcontainer {
	int first:7;
	int data:32;
	int last:1;
} __attribute__((__packed__));

typedef struct fingerprintcontainer fpc;
fpc *fpcptr;

struct itemtypecontainer {
	int first:4;
	int one:8;
	int two:8;
	int three:8;
	int four:8;
	int last:4;
} __attribute__((__packed__));

typedef struct itemtypecontainer itc;
itc *itcptr;

struct character {
	char* charname;
	char* filename;
	struct item* firstitem;
};

struct item {
	int number;
	int base;
	int length;
	char* itemtype;
	unsigned long fingerprint;
	struct item* next;
};

int debug=0, verbose=0, overalldupes=0;
char *me;

void fehler(char*, char*);
struct character* parsefile(char *);
void dumpchar(struct character*);
void char_compare(struct character*, struct character*);

void usage(void)
{
	puts("");
	puts("Diablo II 1.09 Dupe Scanner");
	puts("===========================");
	puts(" -s[directory]\tScan files in directory for duplicate items");
	printf("\t\t(default directory=%s)\n", DEFAULT_DIRNAME);
	puts("   -v\t\tBe verbose. Print the itemlist with fingerprints for each char");
	puts("   -d\t\tDebug Mode. Print additional debugging information (-d includes -v)");
	puts("   -q\t\tQuiet Mode. Just do the scanning, no status crap");
	puts("");
	puts(" -c[charfile]\tShow the itemlist of just one single character and dont do any dupe scanning");
	puts("   -d\t\tDebug Mode. Print additional debugging information");
	puts("");
	
	exit(EXIT_SUCCESS);
}

int main(int argc, char **argv)
{
	char *dirname=NULL, *filename=NULL, c, mode=0;
	DIR* dir;
	struct dirent* dirlist;
	int filecount=0, charcount=0, size, i, j;
	struct character **charlist, *singlechar;

	me = argv[0];

	dirname = malloc(strlen(DEFAULT_DIRNAME)+1);
	strcpy(dirname, DEFAULT_DIRNAME);
	
	while((c = getopt(argc, argv, "vdsq::c:h")) != -1) {
		switch(c)
		{
			case 's':
				if(optarg != NULL) {
					free(dirname);
					dirname = malloc(strlen(optarg)+1);
					strcpy(dirname, optarg);
				}
				if(mode == 0)
					mode=c;
				break;
			case 'v':
				if(verbose == 0)
					verbose=1;
				break;
			case 'd':
				debug=verbose=1;
				break;
			case 'c':
				if(mode == 0) {
					mode=c;
					if(optarg != NULL) {
						filename = malloc(strlen(optarg)+1);
						strcpy(filename, optarg);
					}
				}
				break;
			case 'q':
				if(verbose == 0)
					verbose = -1;
				break;
			default:
				usage();
				break;
		}
	}

	if(argc == 1) {
		usage();
	}
	
	if(mode == 0) {
		puts("You have to use -c or -s!");
		free(dirname);
		exit(EXIT_SUCCESS);
	}
	
	if(mode == 's') {
		if((dir = opendir(dirname)) == NULL) {
			fehler(strerror(errno), dirname);
		}
	
		while((dirlist = readdir(dir)) != NULL) {
			filecount++;
		}		

		rewinddir(dir);
		charlist = malloc(filecount * sizeof(struct character*));

		while((dirlist = readdir(dir)) != NULL) {
			if(strcmp(dirlist->d_name, ".") != 0 && strcmp(dirlist->d_name, "..") != 0) {
				size = strlen(dirname) + strlen(dirlist->d_name) + 1 + 1;
				filename = malloc(size);
				snprintf(filename, size, "%s/%s", dirname, dirlist->d_name);
				charlist[charcount] = parsefile(filename);
				if(charlist[charcount] != NULL) {
					if(verbose==1) dumpchar(charlist[charcount]);
					charcount++;
				}
				free(filename);
			}
		}

		printf("\nChecking for Dupes in %d files...\n", charcount-1);
	
		// compare chars
		for(i=0; i<charcount-1; i++) {
			for(j=i+1; j<charcount; j++) {
				char_compare(charlist[i], charlist[j]);
			}
		}
		
		if(overalldupes==0) {
			printf("%sOK%s: No dupes were found.\n\n", COLOROK, COLORCLOSE);
		} else {
			printf("%sDUPES%s: Found %d dupes.\n\n", COLORERROR, COLORCLOSE, overalldupes);
		}

	} else if(mode == 'c') {
		singlechar = parsefile(filename);
		if(singlechar != NULL) {
			puts("");
			dumpchar(singlechar);
		}
		free(filename);
	}

	return EXIT_SUCCESS;
}

void char_compare(struct character* one, struct character* two)
{
	struct item *itemone, *itemtwo;

	if((itemone = one->firstitem) == NULL) {
		return;
	}

	while(itemone != NULL) {
		itemtwo = two->firstitem;
		while(itemtwo != NULL) {
			if(itemone->fingerprint == itemtwo->fingerprint && itemone->fingerprint != 0) {
				// not really sure about that. ibk and tbk seem to be tp and id books
				// not sure why they seem to be extended items either.. just ignore them for now
				if(strcmp(itemone->itemtype, "ibk ") == 0 || strcmp(itemone->itemtype, "tbk ") == 0) {
					itemtwo = itemtwo->next;
					continue;
				}
				printf("%sPOSSIBLE DUPE:%s \"%s\" and \"%s\" both have an item of type \"%s\" with fingerprint 0x%lx\n", COLORDUPE, COLORCLOSE, one->charname, two->charname, itemone->itemtype, itemone->fingerprint);
				overalldupes++;
			}
			itemtwo = itemtwo->next;
		}
		itemone = itemone->next;
	}

}

void dumpchar(struct character* theChar)
{
	struct item* ptr;
	
	if((ptr = theChar->firstitem) == NULL) {
		return;
	}
	
	printf("%s has the following extended items\n", theChar->charname);

	while(ptr != NULL) {
		if(ptr->fingerprint != 0) {
			if(debug) printf(" Adr: %p, next: %p,", (void*)ptr, (void*)ptr->next);
			printf(" Number: %3d, Base: %4d, Length: %3d, Fingerprint: 0x%08lx, Item type: %s\n", ptr->number, ptr->base, ptr->length, ptr->fingerprint, ptr->itemtype);
		}
		ptr = ptr->next;
	}

	puts("");
}

struct character* parsefile(char *filename)
{
	FILE *file;
	char *buf, *data;
	int size, i, bufsize, jmcount=0;
	short *itemcount=NULL, myitemcount=0;
	struct stat *filestruct = malloc(sizeof(struct stat));
	int itemlist_base=-1, itemnumber=0;
	struct character* theChar = malloc(sizeof(struct character));
	struct item** theItemlist = NULL;

	size = strlen(filename)+1;
	if((theChar->filename = malloc(size)) == NULL)
		fehler("Memory allocation error", filename);
	strncpy(theChar->filename, filename, size);
	
	// 15 bytes for charname + nullbyte
	theChar->charname = malloc(16);

	theChar->firstitem = NULL;
	
	if(stat(filename, filestruct) == -1) {
		fehler(strerror(errno), filename);
	}
	
	bufsize = filestruct->st_size;
	if((buf = malloc(bufsize)) == NULL) {
		fehler("Unable to allocate memory for file buffer", filename);
	}
	
	if((file = fopen(filename, "r")) == NULL) {
		fehler(strerror(errno), filename);
	}
	
	while(!feof(file)) {
		size = fread(buf, 1, bufsize+1, file);
		if(debug) printf("%sDEBUG:%s read %d bytes of file \"%s\"\n", COLORDEBUG, COLORCLOSE, size, filename);
	}

	strncpy(theChar->charname, &buf[20], 16);

	if((int)buf[4] != 92) {
		if(verbose != -1)
			printf("%sError%s: %s is not a valid v1.09 character file or he has never been in a game (File version is %d, should be 92)\n", COLORERROR, COLORCLOSE, filename, buf[4]);
		if(verbose==1) puts("");
		free(filestruct);
		free(theChar->filename);
		free(theChar->charname);
		free(theChar);
		free(buf);
		if(debug) puts("");
		return NULL;
	}

	if(verbose != -1)
		printf("%sOK%s: Read %4d bytes of character data for \"%s\" from \"%s\"...\n", COLOROK, COLORCLOSE, size, theChar->charname, theChar->filename);

	// check if bit 3 (fourth) is set (0x8 = 1000)
	if(buf[36] & 0x8) {
		if(verbose != -1)
			printf("%sError%s: %s (%s) is not alive.. skipping..\n", COLORERROR, COLORCLOSE, theChar->charname, theChar->filename);
		if(verbose==1) puts("");
		free(filestruct);
		free(theChar->filename);
		free(theChar->charname);
		free(theChar);
		free(buf);
		if(debug) puts("");
		return NULL;
	}

	for(i=0; i<bufsize-1; i++) {
		if(buf[i] == 'J' && buf[i+1] == 'M') {
			jmcount++;
		
			if(itemcount != NULL) {
				if(itemnumber > *itemcount) {
					if(debug) printf("%sDEBUG%s: itemnumber is larger than expected (%d>%d). Enlarging buffer.\n", COLORDEBUG, COLORCLOSE, itemnumber, *itemcount);
					theItemlist = realloc(theItemlist, (itemnumber+1) * sizeof(struct item*));
					if(theItemlist == NULL) {
						fehler("Memory allocation error (realloc())", filename);
					}
				}
			}
		
			if(itemlist_base == -1) {
				if(i+5 >= bufsize || buf[i+4] != 'J' || buf[i+5] != 'M') {
					if(debug) printf("%sDEBUG:%s %s: Found first JM at buf[%d] but it is not followed by another JM, so its not the itemlist base! Skipping.\n", COLORDEBUG, COLORCLOSE, filename, i);
					continue;
				}
				itemcount = (short*)&buf[i+2];
				if(debug) printf("%sDEBUG:%s %s: Found first JM: Itemlist base is buf[%d], following %hd items (JM 0x%x 0x%x)\n", COLORDEBUG, COLORCLOSE, filename, i, *itemcount, buf[i+2], buf[i+3]);
				itemlist_base = i;
				if((theItemlist = malloc((*itemcount+1) * sizeof(struct item*))) == NULL) {
					fehler("Memory allocation error", filename);
				}
			} else if(buf[i+2] == 0 && buf[i+3] == 0) {
					if(*itemcount != 0) {
						if(itemnumber>0) {
							theItemlist[itemnumber-1]->length = (i) - theItemlist[itemnumber-1]->base;
							if(debug) printf("%sDEBUG:%s %s: Found itembase %2d at buf[%04d], length: %2d\n", COLORDEBUG, COLORCLOSE, filename, itemnumber-1, theItemlist[itemnumber-1]->base, theItemlist[itemnumber-1]->length);
						}
					}
					if(debug) printf("%sDEBUG:%s %s: Found last JM: Itemlist ends at buf[%d]\n", COLORDEBUG, COLORCLOSE, filename, i-1);
					break;
			} else {
//				printf("unknown: %x\n", buf[2] >> 4);
				data = &buf[i];
				itcptr = (itc*)&data[9];
				theItemlist[itemnumber] = malloc(sizeof(struct item));
				theItemlist[itemnumber]->itemtype = malloc(5);
				sprintf(theItemlist[itemnumber]->itemtype, "%c%c%c%c", itcptr->one, itcptr->two, itcptr->three, itcptr->four);
				theItemlist[itemnumber]->number = itemnumber;
				theItemlist[itemnumber]->next = NULL;
				theItemlist[itemnumber]->fingerprint = 0;
				theItemlist[itemnumber]->base = i;
				theItemlist[itemnumber]->length = 0;
				if(itemnumber == 0) {
					theChar->firstitem = theItemlist[0];
				} else {
					myitemcount++;
					theItemlist[itemnumber-1]->length = theItemlist[itemnumber]->base - theItemlist[itemnumber-1]->base;
					theItemlist[itemnumber-1]->next = theItemlist[itemnumber];
				
					if(debug) {
						printf("%sDEBUG:%s %s: Found itembase %2d at buf[%04d], length: %2d\n", COLORDEBUG, COLORCLOSE, filename, itemnumber-1, theItemlist[itemnumber-1]->base, theItemlist[itemnumber-1]->length);
					}
				}
				itemnumber++;
			}
		}

	}

	
	// if the char does not have any items: set the first item to zero
	if(jmcount == 0 && itemcount == NULL) {
		theChar->firstitem = NULL;
	}

	for(i=0; i < myitemcount; i++) {
		// standard items are 14 bytes long, everything thats longer is an extended item
		// 19: dunno
		// 20: -
		// 21: itemtype=box (maybe cube?)
		// 22: key
		// 23: ibk, tbk (identify/townportal??)
		// HACK: again, not really sure about that stuff here.. we just ignore everything
		// that is shorter than 23 bytes for now
		if(theItemlist[i]->length >= 23) {
		
			// read the bitstructure at offset 13
			data = &buf[theItemlist[i]->base];
			fpcptr = (fpc*)&data[13];
			theItemlist[i]->fingerprint = fpcptr->data;
			
			if(debug) printf("%sDEBUG:%s Item %3d is an extended item and therefore has a Fingerprint (0x%lx) (type=%s)\n", COLORDEBUG, COLORCLOSE, i, theItemlist[i]->fingerprint, theItemlist[i]->itemtype);
		}
	}

	if(debug) printf("%sDEBUG:%s End of file reached for %s\n", COLORDEBUG, COLORCLOSE, filename);

//	if(theItemlist != NULL) free(theItemlist);
	free(filestruct);
	free(buf);
	return theChar;
}

void fehler(char *fehler, char *filename)
{
	fprintf(stderr, "%s: %s (%s)\n", me, fehler, filename);
	fputs("Exiting.", stderr);
	exit(EXIT_FAILURE);
}

