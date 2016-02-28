/*******************************************************************************
 * charcounter.c - written by Florian 'fw' Weingarten <http://hackvalue.de/>   *
 *  This software reads 1.09d character files from a directory and counts the  *
 *  number of hardcore chars (dead and alive) and the number of softcore chars *
 *******************************************************************************/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <dirent.h>
#include <errno.h>
#include <sys/types.h>
#include <sys/stat.h>

// Change me
#define CHARSAVEDIR "/home/diablo/var/charsave"

// Dont change me
#define STATUSOFFSET 36
#define STATUSSIZE 1

struct charstatus {
	int unknown:2;
	int hardcore:1;
	int died:1;		// set if died in the past, never gets 0 again, not even in SC
	int unknown_two:1;
	int expansion:1;
	int unknown_three:2;
} __attribute__((__packed__));

struct stats {
	unsigned int softcore;
	unsigned int hardcore_alive;
	unsigned int hardcore_dead;
} theStats;

void printstats()
{
	puts("        Hardcore   Softcore    Totals");
	puts("      +----------+----------+----------+");
	printf("Alive | %8d | %8d | %8d |\n", theStats.hardcore_alive, theStats.softcore, theStats.hardcore_alive + theStats.softcore);
	printf(" Dead | %8d |        - | %8d |\n", theStats.hardcore_dead, theStats.hardcore_dead);
	printf("Total | %8d | %8d | %8d |\n", theStats.hardcore_alive + theStats.hardcore_dead, theStats.softcore, theStats.hardcore_alive + theStats.hardcore_dead + theStats.softcore);
	puts("      +----------+----------+----------+");
}

int readstatsfromchar(char *filename)
{
	FILE *file;
	char buf[STATUSOFFSET + STATUSSIZE];
	struct stat *filestruct = malloc(sizeof(struct stat));
	int tmp;
	struct charstatus *status;

	if(chdir(CHARSAVEDIR) == -1) {
		fprintf(stderr, "%s\n", strerror(errno));
		return EXIT_FAILURE;
	}

	if(stat(filename,filestruct) == -1) {
		fprintf(stderr, "Error reading file %s\n", filename);
		return EXIT_FAILURE;
	}

	if((tmp = filestruct->st_size) < STATUSOFFSET + STATUSSIZE) {
		fprintf(stderr, "Invalid file %s (%d bytes < %d bytes)\n", filename, tmp, STATUSOFFSET+STATUSSIZE);
		return EXIT_FAILURE;
	}

	if((file = fopen(filename, "r")) == NULL) {
		fprintf(stderr, "%s\n", strerror(errno));
		return EXIT_FAILURE;
	}

	if((tmp = fread(buf, 1, STATUSOFFSET + STATUSSIZE, file)) < STATUSOFFSET+STATUSSIZE) {
		fprintf(stderr, "Error reading file. Could not read enough bytes (read %d, need %d)\n", tmp, STATUSOFFSET+STATUSSIZE);
		return EXIT_FAILURE;
	}

	status = (struct charstatus*)&(buf[STATUSOFFSET]);

	if(status->hardcore && status->died)
		theStats.hardcore_dead++;
	else if(status->hardcore && !status->died)
		theStats.hardcore_alive++;
	else if(!status->hardcore)
		theStats.softcore++;

	free(filestruct);
	return EXIT_SUCCESS;
}

int main()
{
	struct dirent *dirlist;
	DIR *dir;

	// zero the stats
	memset(&theStats, 0, sizeof(struct stats));

	if((dir = opendir(CHARSAVEDIR)) == NULL) {
		fputs("Error opening directory "CHARSAVEDIR, stderr);
		return EXIT_FAILURE;
	}

	while((dirlist = readdir(dir)) != NULL) {
		if(!strcmp(dirlist->d_name, ".") || !strcmp(dirlist->d_name, "..")) continue;
		readstatsfromchar(dirlist->d_name);
	}

	printstats();

	return EXIT_SUCCESS;
}
