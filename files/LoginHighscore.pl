#!/usr/bin/perl
# use maximum number of lines to print as first argument
# if you dont, it will print every line

use integer;

my $usersdir = "/home/diablo/var/users";
my %times;
my %diffs;
my $max;

if($ARGV[0]) {
	$max = $ARGV[0];
} else {
	$max = -1;
}

foreach my $user(<$usersdir/*>) {
	next unless(-f $user && -r $user);
	open(USERFILE, "$user");
	my @data = grep(/^\"BNET\\\\acct\\\\lastlogin_time\"=\"/, <USERFILE>);
	close(USERFILE);

	foreach(@data) {
		chomp();
		$username = $user;
		$username =~ s|^.*/||g;
		m/^\"BNET\\\\acct\\\\lastlogin_time\"=\"(.*)\"$/;
		$times{$1} = $username;
	}
}

my $i=1;
foreach(sort keys %times) {
	printf "%3d  ", $i++;
	print $times{$_};
	print " " x (15 - length($times{$_}));
	print " =>  Last login: ", timeoutput($_), " ";
	print " => ", timeago(time - $_), "\n";
	last unless(--$max);
}

sub timeoutput {
	my $timestamp = shift;
	my($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($timestamp);
	$year += 1900;
	return sprintf("%4d-%02d-%02d, %02d:%02d:%02d", $year, $mon+1, $mday+1, $hour, $min, $sec);
}

sub timeago {
	my $timediff = shift;
	my $days, $hours, $minutes, $seconds;

	$days = int($timediff / (60*60*24));
	$timediff -= $days*60*60*24;

	$hours = int($timediff / (60*60));
	$timediff -= $hours*60*60;

	$minutes = int($timediff / 60);
	$timediff -= $minutes*60;

	$seconds = $timediff;

	return sprintf("%3d days, %2d hours, %2d minutes, %2d seconds ago", $days, $hours, $minutes, $seconds);
}
