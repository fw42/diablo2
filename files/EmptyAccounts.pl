#!/usr/bin/perl
use warnings;
use strict;

my $charinfodir = "/home/diablo/var/charinfo";

foreach my $account(<$charinfodir/*>) {

	my $chars=0;
	next unless(-d $account);

	foreach my $char(<$account/*>) {
		next unless(-f $char);
		$chars++;
	}

	unless($chars) {
		my $path = $account;
		$account =~ s|^.*/||g;
		print "User account *$account does not contain any characters ($path)\n";
	}

}
