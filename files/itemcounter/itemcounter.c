/****************************************************************************
 * Diablo 2 1.09 Itemcounter                                                *
 ****************************************************************************
 *  This program reads the Diablo 2 1.09 character files described in [1]   *
 *  and counts the number of items, printing the item count for every char  *
 *  and a total count. Counting includes                                    *
 *   - low quality items                                                    *
 *   - high quality items                                                   *
 *   - normal quality items                                                 *
 *   - magical items                                                        *
 *   - set items                                                            *
 *   - rare items                                                           *
 *   - uniq items                                                           *
 *   - crafted items                                                        *
 *   - Stone of Jordan (SoJ)                                                *
 *                                                                          *
 * [1]: http://www.ladderhall.com/ericjwin/109/trevin/trevinfileformat.html *
 ****************************************************************************
 *       Written by Florian 'fw' Weingarten <http://hackvalue.de/>          *
 *        This file is free software and published under the GPL            *
 *                    (GNU General Public License)                          *
 ****************************************************************************/

#include <stdio.h>
#include <stdlib.h>
#include <sys/stat.h>
#include <sys/types.h>
#include <dirent.h>
#include <string.h>
#include <errno.h>

#define CHARSAVEDIR "/home/diablo/var/charsave/"

#define ITEM_LOWQUAL	1
#define ITEM_NORMALQUAL	2
#define ITEM_HIGHQUAL	3
#define ITEM_MAGIC	4
#define ITEM_SET	5
#define ITEM_RARE	6
#define ITEM_UNIQ	7
#define ITEM_CRAFTED	8
#define ITEM_UNIQ_SOJ	122

int debug=0;

struct filebuf* readfile(char*);
struct itemlist* find_itemlist(struct filebuf*);
struct stats* parse_itemlist(struct itemlist*);
void print_stats(struct stats*, char*);
void xerror(char*, char*);
void xerrorexit(char*, char*);

struct itemlist {
	struct d2s_item *item;
	struct itemlist *next;
};

struct filebuf {
	char *buf;
	int bufsize;
};

struct stats {
	int total;
	int lowqual;
	int normalqual;
	int highqual;
	int magic;
	int set;
	int rare;
	int uniq;
	int crafted;
	int soj;
};

struct d2s_item {
	int dontcare1:32;
	int dontcare2:5;
	int simple:1;		// Bit position 37
	int dontcare3:32;
	int dontcare4:6;
	int c1:8;		// bit position 76 (37+1+32+6)
	int c2:8;
	int c3:8;
	int c4:8;
	int dontcare5:32;	// position 140
	int dontcare6:10;	// +10
	int quality:4;		// +32 = Bit position 150 (37+1+11)
	int isring:1;
	// from now on, the length is not fixed
	// if(isring==1): 3 bits for ring picture
	// ... see Trevin Beatties website
	int ringpic:3;
	int classspec:1;
	int uniqident:12;
} __attribute__((__packed__));

int main(int argc, char *argv[])
{
	struct filebuf *fb;
	struct stats overallstats;
	struct stats *s;
	DIR *dir;
	struct dirent *dirlist;
	int size;
	char *filename;

	if(argc > 1 && strcmp(argv[1], "-d") == 0) {
		debug = 1;
	}

	if((dir = opendir(CHARSAVEDIR)) == NULL) {
		xerrorexit("Error opening directory "CHARSAVEDIR, "opendir()");
	}
	
	memset(&overallstats, 0, sizeof(struct stats));

	while((dirlist = readdir(dir)) != NULL) {
		if(strcmp(dirlist->d_name, ".") == 0 || strcmp(dirlist->d_name, "..") == 0) {
			continue;
		}

		size = strlen(CHARSAVEDIR) + 1 + strlen(dirlist->d_name) + 1;
		if((filename = malloc(size)) == NULL) {
			xerrorexit("Memory allocation error", "main()");
		}
		snprintf(filename, size, "%s/%s", CHARSAVEDIR, dirlist->d_name);

		fb = readfile(filename);
		s = parse_itemlist(find_itemlist(fb));
		print_stats(s, dirlist->d_name);

		overallstats.total	+=	s->total;
		overallstats.lowqual	+=	s->lowqual;
		overallstats.normalqual	+=	s->normalqual;
		overallstats.highqual	+=	s->highqual;
		overallstats.magic	+=	s->magic;
		overallstats.set	+=	s->set;
		overallstats.rare	+=	s->rare;
		overallstats.uniq	+=	s->uniq;
		overallstats.crafted	+=	s->crafted;
		overallstats.soj	+=	s->soj;

		free(filename);
	}

	puts("");
	print_stats(&overallstats, "Total");
	puts("");

	return EXIT_SUCCESS;
}


void print_stats(struct stats *s, char *prefix)
{
	printf("%15s: %4d items, %3d low, %3d norm, %3d high, %4d magic, %4d set, %4d rare, %4d uniq, %4d crafted, %2d soj\n", \
		prefix, s->total, s->lowqual, s->normalqual, s->highqual, s->magic, s->set, s->rare, s->uniq, s->crafted, s->soj);
	
}

struct stats* parse_itemlist(struct itemlist* items)
{
	struct stats *ret = malloc(sizeof(struct stats));
	struct itemlist *ptr = items;

	// zero the struct
	memset(ret, 0, sizeof(struct stats));

	while(ptr != NULL && ptr->item != NULL) {
		
		if(debug) printf("DEBUG: ptr: %p, ptr->next: %p, ptr->item: %p\n", (void*)ptr, (void*)ptr->next, (void*)ptr->item);
		if(debug) printf("DEBUG: item type=\"%c%c%c%c\"\n", ptr->item->c1, ptr->item->c2, ptr->item->c3, ptr->item->c4);
		
		ret->total++;

		switch(ptr->item->quality) {
			case ITEM_LOWQUAL:	ret->lowqual++;		break;
			case ITEM_NORMALQUAL:	ret->normalqual++;	break;
			case ITEM_HIGHQUAL:	ret->highqual++;	break;
			case ITEM_MAGIC:	ret->magic++;		break;
			case ITEM_SET:		ret->set++;		break;
			case ITEM_RARE:		ret->rare++;		break;
			case ITEM_UNIQ:		ret->uniq++;		break;
			case ITEM_CRAFTED:	ret->crafted++;		break;
		}

		// if the item is uniq, a ring, not class specific and has the SoJ identifier...
		if(ptr->item->quality == ITEM_UNIQ && ptr->item->isring && !ptr->item->classspec && ptr->item->uniqident == ITEM_UNIQ_SOJ) {
			if(debug) puts("DEBUG: Stone of Jordan (SoJ) found");
			ret->soj++;
		}

		ptr = ptr->next;
	}

	return ret;
}

void xerror(char *message, char *cause)
{
	fprintf(stderr, "%s: %s\n", cause, message);
}

void xerrorexit(char *message, char *cause)
{
	fprintf(stderr, "%s: %s\n", cause, message);
	exit(EXIT_FAILURE);
}

struct itemlist* find_itemlist(struct filebuf* mybuf)
{
	char *buf = mybuf->buf;
	int i, gotfirst=0;
	struct itemlist *ptr = malloc(sizeof(struct itemlist));
	struct itemlist *retval = ptr;

	if(!ptr) {
		xerrorexit("Memory allocation error", "find_itemlist()");
	}

	ptr->next = NULL;
	ptr->item = NULL;

	for(i=0; i < mybuf->bufsize; i++) {
		if(i+1 < mybuf->bufsize)
		if(buf[i] == 'J' && buf[i+1] == 'M') {

			// got first JM. Itemlist starts here
			if(gotfirst == 0) {
				if(debug) printf("DEBUG: Got first JM at byte offset %d, item list starts here!\n", i);
				gotfirst = 1; continue;
			}
				
			// got last JM. Itemlist ends here
			if(i+3 < mybuf->bufsize && buf[i+2] == 0 && buf[i+3] == 0) {
				if(debug) printf("DEBUG: Got last JM at byte offset %d, item list ends here!\n", i);
				break;
			}

			// got a JM which is not the first and not the last one
			if(debug) printf("DEBUG: \tGot item JM at byte offset %d, here starts an item!\n", i);
			ptr->item = (struct d2s_item*)(buf + i);
			if(ptr->item->simple) {
				if(debug) printf("DEBUG: \t\tItem is not extended. Skipping.\n");
				ptr->item = NULL;
				continue;
			}
			ptr->next = malloc(sizeof(struct itemlist));
			if(!(ptr->next)) xerrorexit("Memory allocation error", "find_itemlist()");
			ptr->next->next = NULL;
			ptr = ptr->next;
		}
	}
	
	return retval;
}

struct filebuf* readfile(char *filename)
{
	FILE *file;
	char *buf;
	struct stat *filestruct = malloc(sizeof(struct stat));
	struct filebuf *mybuf = malloc(sizeof(struct filebuf)); 
	int bufsize, size=0;

	if(stat(filename,filestruct) == -1) {
		xerror(strerror(errno), filename);
		return NULL;
	}

	bufsize = filestruct->st_size;

	if((buf = malloc(bufsize)) == NULL) {
		xerror("Memory allocation error: buf, readfile()", filename);
		return NULL;
	}

	if((file = fopen(filename, "r")) == NULL) {
		xerror(strerror(errno), filename);
		return NULL;
	}

	while(!feof(file)) {
		size = fread(buf, 1, bufsize+1, file);
	}

	if(debug) printf("DEBUG: read %d bytes (of total %d) from %s\n", size, bufsize, filename);

	mybuf->buf = buf;
	mybuf->bufsize = bufsize;
	return mybuf;
}

