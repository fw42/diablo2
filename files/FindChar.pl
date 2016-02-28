#!/usr/bin/perl
use strict;
use warnings;

my $charinfodir = "/home/diablo/var/charinfo";;

if(scalar @ARGV == 0) {
	print "Usage: $0 <charnames1> [charname2] [charname3] ...\n";
	exit -1;
}

foreach(@ARGV) {
	char_to_acc($_);
}

sub char_to_acc {
	my $search = shift;
	my $count = 0;
	foreach my $dir(<$charinfodir/*>) {
		next unless(-d $dir);
		foreach my $char(<$dir/*>) {
			next unless(-f $char);
			
			if(cuttoslash($char) eq "\L$search") {
				print "Found $search on user account *" . cuttoslash($dir) . " ($char)\n";
				$count++;
			}

			if($count > 1) {
				print "Huh!? Duplicate character found!\n";
			}
		}
	}
	print "$search not found\n" if(!$count);
}

sub cuttoslash {
	my $s = shift;
	$s =~ s|^.*/||g;
	return $s;
}
