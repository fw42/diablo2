#include <stdio.h>
#include <stdlib.h>
#include <time.h>
#include <errno.h>
#include <string.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <dirent.h>

#define CHARINFODIR "/home/diablo/var/charinfo"
#define CHARSAVEDIR "/home/diablo/var/charsave"

/* taken from d2cs_d2dbs_character.h and setup.h of PvPGN 1.08rc2 */
#define MAX_CHARNAME_LEN   16
#define MAX_ACCTNAME_LEN   16
#define MAX_REALMNAME_LEN  32

typedef struct
{
	int		magicword;      /* static for check */
	int		version;        /* charinfo file version */
	int		create_time;    /* character creation time */
	int		last_time;      /* character last access time */
	int		checksum;
	int		total_play_time; /* total in game play time */
	int		reserved[6];
	unsigned char	charname[MAX_CHARNAME_LEN];
	unsigned char	account[MAX_ACCTNAME_LEN];
	unsigned char	realmname[MAX_REALMNAME_LEN];
} t_d2charinfo_header;

struct charsavedata {
	char level;
	char* dead;
	char* class;
};

// files are all exactly 192 bytes
#define STRUCTSIZE 192

void read_charinfo_file(char*, int, char*);
struct charsavedata* read_charsave_file(char*);

void fehler(char *msg, char *src)
{
	fprintf(stderr,"%s (%s)\n", msg, src);
	exit(EXIT_FAILURE);
}

int main(void)
{
	DIR *dir=NULL, *subdir=NULL;
	char *filename;
	char *subdirname;
	struct dirent *dirlist, *subdirlist;
	
	dir = opendir(CHARINFODIR);
	
	while((dirlist = readdir(dir)) != NULL) {
		if(strcmp(dirlist->d_name, ".") == 0 || strcmp(dirlist->d_name, "..") == 0) {
			continue;
		}

		subdirname = malloc(strlen(CHARINFODIR) + 1 + strlen(dirlist->d_name) + 1);
		sprintf(subdirname, "%s/%s", CHARINFODIR, dirlist->d_name);

		subdir = opendir(subdirname);

		while((subdirlist = readdir(subdir)) != NULL) {

			if(strcmp(subdirlist->d_name, ".") == 0 || strcmp(subdirlist->d_name, "..") == 0) {
				continue;
			}
			
			filename = malloc(strlen(subdirname) + 1 + strlen(subdirlist->d_name) + 1);
			sprintf(filename, "%s/%s", subdirname, subdirlist->d_name);
			read_charinfo_file(filename, 0, subdirlist->d_name);
			free(filename);
			
		}
		
		free(subdirname);
	}

	return(EXIT_SUCCESS);
}

struct charsavedata* read_charsave_file(char *filename)
{
	FILE *file=NULL;
	char *buf;
	int bufsize=0;
	int size=0;
	struct stat filestruct;
	struct charsavedata *data = malloc(sizeof(struct charsavedata));

	stat(filename, &filestruct);
	bufsize = filestruct.st_size;
	buf = malloc(bufsize);

	if((file = fopen(filename, "r")) == NULL) {
		fehler(strerror(errno), filename);
	}

	while(!feof(file)) {
		size = fread(buf, 1, bufsize, file);
	}
	
	data->level = buf[43];
	data->class = NULL;
	data->dead  = NULL;
	
	switch(buf[40])
	{
		case 0:	data->class = malloc(strlen("Amazone") + 1);
			strcpy(data->class, "Amazone");
			break;
		case 1:	data->class = malloc(strlen("Sorc") + 1);
			strcpy(data->class, "Sorc");
			break;
		case 2:	data->class = malloc(strlen("Nec") + 1);
			strcpy(data->class, "Nec");
			break;
		case 3:	data->class = malloc(strlen("Paladin") + 1);
			strcpy(data->class, "Paladin");
			break;
		case 4:	data->class = malloc(strlen("Barb") + 1);
			strcpy(data->class, "Barb");
			break;
	}

	// bit[3] gesetzt
	if(buf[36] & 0x8) {
		data->dead = malloc(strlen("dead")+1);
		strcpy(data->dead, "dead");
	} else {
		data->dead = malloc(strlen("alive")+1);
		strcpy(data->dead, "alive");
	}
	
	free(buf);

	return data;
}

void read_charinfo_file(char *filename, int mode, char *filenameshort)
{
	FILE *file=NULL;
	t_d2charinfo_header *charinfo;
	char *buf, *charsave;
	struct charsavedata* data;
	int size=0;

	buf = malloc(STRUCTSIZE);
	
	if((file = fopen(filename, "r")) == NULL) {
		fehler(strerror(errno), filename);
	}

	while(!feof(file)) {
		size = fread(buf, 1, STRUCTSIZE+1, file);
	}		

	charinfo = (t_d2charinfo_header*)buf;
	
	charsave = malloc(strlen(CHARSAVEDIR) + 1 + strlen(filenameshort) + 1);
	sprintf(charsave, "%s/%s", CHARSAVEDIR, filenameshort);
	data = read_charsave_file(charsave);
	free(charsave);
	
	if(mode == 0) {
		if(data->level > 1) {
			printf("%s %s %d %s %s\n", charinfo->account, charinfo->charname, data->level, data->class, data->dead);
		}
	}

	free(buf);
}

