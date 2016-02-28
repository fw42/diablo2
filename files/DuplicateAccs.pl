#!/usr/bin/perl
use strict;
use warnings;

my $usersdir = "/home/diablo/var/users";
my $ignore = "admin|public-gate|public-mule";
my %data;

sub error {
	my $msg = shift;
	print STDERR "Error: $msg\n";
}

# Read the accountfile and save the passhash1, lastlogin_ip and lastlogin_owner
# in a hash of hashes: $data{username}->{$key} = $value
foreach my $accountfile (<$usersdir/*>) {

	my $username = $accountfile;
	$username =~ s|^.*/||g;

	next if($username =~ /($ignore)/);

	unless(-f $accountfile) {
		error("Skipping $accountfile. Not a regular file.");
		next;
	}

	unless(-r $accountfile) {
		error("Skipping $accountfile. Not readable.");
		next;
	}

	open(FILE, "$accountfile") or error($!);
	my @filecontent = grep {/BNET\\\\acct\\\\/} <FILE>;
	close(FILE) or error($!);

	foreach(@filecontent) {
		chomp();
		if(/^\"BNET\\\\acct\\\\(.*)\"=\"(.*)\"$/) {
			my $hashkey = $1;
			my $hashval = $2;
			next unless($hashkey =~ m/passhash1|lastlogin_ip|lastlogin_owner/);
			$data{$username}->{$hashkey} = $hashval;
		}
	}
}

sub warning {
	my $type = shift;
	my $acc = shift;
	my $compareacc = shift;
	my $value = shift;

	if($type eq "PASS") {
		print "$type: $acc and $compareacc have the same password ($value)\n";
	} elsif($type eq "IP") {
		print "$type: $acc and $compareacc have the same lastlogin IP address ($value)\n";
	} elsif($type eq "USER") {
		print "$type: $acc and $compareacc have the same lastlogin windows user ($value)\n";
	}
}

# Yes, this looks a bit weird. Why not use foreach(keys(...)). This is faster because it
# compares every pair only once. foreach(...) { foreach(..) } would compare a lot more
# already compared pairs.
my @keys = sort keys %data;
for(my $i=0; $i<=$#keys; $i++) {

	for(my $j=$i+1; $j<=$#keys; $j++) {
		
		if($data{$keys[$i]}->{'passhash1'} eq $data{$keys[$j]}->{'passhash1'}) {
			warning("PASS", $keys[$i], $keys[$j], $data{$keys[$i]}->{'passhash1'});
		}

		if($data{$keys[$i]}->{'lastlogin_ip'} eq $data{$keys[$j]}->{'lastlogin_ip'}) {
			warning("IP", $keys[$i], $keys[$j], $data{$keys[$i]}->{'lastlogin_ip'});
		}

		if($data{$keys[$i]}->{'lastlogin_owner'} eq $data{$keys[$j]}->{'lastlogin_owner'}) {
			warning("USER", $keys[$i], $keys[$j], $data{$keys[$i]}->{'lastlogin_owner'});
		}
	}

}

