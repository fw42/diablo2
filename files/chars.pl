#!/usr/bin/perl
# Florian Weingarten <flo@hackvalue.de>
# 01.07.2006

my $dir = "/home/diablo/var/charinfo";

my $datum = localtime;
my $charstotal = 0;
my $accountstotal = 0;

print "Diablo 2 Server Account/Userlist\r\n";
print " -- $datum\r\n\r\n";

while(<$dir/*>) {
	$accountname = $_;
	$accountname =~ s/^$dir\///;

	print $accountname,"\r\n";
	$accountstotal++;
	
	while(<$_/*>) {
		s|^$dir/$accountname/||;
		print "\t$_\r\n";
		$charstotal++;
	}
	
	print "\r\n";
}

print "\r\nTotal of $accountstotal accounts and $charstotal characters.\r\n";
