#!/usr/bin/perl
use strict;
use warnings;

my $cmd = "ps aux | grep -E \"^diablo\"";
my ($user, $pid, $cpu_percentage, $memory_percentage, $vsz, $rss, $tty, $stat, $start, $time, $command);
my $first=1;
my %data;

# Format variables
my ($f_name, $f_cpu, $f_mem, $f_vsz, $f_rss, $f_pids);

# Sum variables
my ($s_cpu, $s_mem, $s_vsz, $s_rss) = 0;

format TABLE =
 | @>>>> | @>>>>> | @>>>>> | @>>>>>>>>> | @>>>>>>>>> | @<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< |
$f_name, $f_cpu, $f_mem, $f_vsz, $f_rss, $f_pids
.

open(STREAM, "$cmd |");

while(<STREAM>) {
	s/\s+/ /g;
	($user, $pid, $cpu_percentage, $memory_percentage, $vsz, $rss, $tty, $stat, $start, $time, $command) = split(" ", $_, 11);

	if($command =~ /(d2cs|d2dbs|bnetd)/) {
		$data{$1}{'pid'} = $pid;
		$data{$1}{'cpu'} = $cpu_percentage;
		$data{$1}{'memory'} = $memory_percentage;
		$data{$1}{'vsz'} = $vsz;
		$data{$1}{'rss'} = $rss;
	} elsif($command =~ /(D2GS|wineserver|wine\.log)/) {
		if($first) {
			$data{'d2gs'}{'pid'} = $pid;
			$data{'d2gs'}{'cpu'} = $cpu_percentage;
			$data{'d2gs'}{'memory'} = $memory_percentage;
			$data{'d2gs'}{'vsz'} = $vsz;
			$data{'d2gs'}{'rss'} = $rss;
			$first=0;
		} else {
			$data{'d2gs'}{'pid'} .= " $pid";
			$data{'d2gs'}{'cpu'} += $cpu_percentage;
			$data{'d2gs'}{'memory'} += $memory_percentage;
			$data{'d2gs'}{'vsz'} += $vsz;
			$data{'d2gs'}{'rss'} += $rss;
		}
	}

}

$~ = "TABLE";

print "\n";
print " +-------+--------+--------+------------+------------+------------------------------------------+\n";
($f_name, $f_cpu, $f_mem, $f_vsz, $f_rss, $f_pids) = ("Proc", "CPU%", "MEM%", "VSZ", "RSS", "PIDS");
write;
print " +-------+--------+--------+------------+------------+------------------------------------------+\n";

foreach my $proc (sort(keys(%data))) {
	$f_name	= $proc;
	$f_cpu	= $data{$proc}{'cpu'};
	$f_mem	= $data{$proc}{'memory'};
	$f_vsz	= $data{$proc}{'vsz'};
	$f_rss	= $data{$proc}{'rss'};
	$f_pids	= $data{$proc}{'pid'};

	$s_cpu	+= $f_cpu;
	$s_mem	+= $f_mem;
	$s_vsz	+= $f_vsz;
	$s_rss	+= $f_rss;

	$f_mem	= format_number($f_mem);
	$f_cpu	= format_number($f_cpu);
	$f_vsz	= suffix_shorten_number($f_vsz);
	$f_rss	= suffix_shorten_number($f_rss);
	write;

}

print " +-------+--------+--------+------------+------------+------------------------------------------+\n";
$f_name	= "Total";
$f_cpu	= $s_cpu;
$f_mem	= $s_mem;
$f_vsz	= $s_vsz;
$f_rss	= $s_rss;
$f_pids	= "-";

$f_cpu	= format_number($f_cpu);
$f_mem	= format_number($f_mem);

$f_vsz	= suffix_shorten_number($f_vsz);
$f_rss	= suffix_shorten_number($f_rss);

write;
print " +-------+--------+--------+------------+------------+------------------------------------------+\n";

close(STREAM);

print "\n";
print "RSS: Physical memory allocated for the process\n";
print "VSZ: Virtual memory allocated for the process (including shared memory)\n";
print "\n";

sub format_number {
	my $num = shift;
	return sprintf("%3.2f", $num);
}

sub suffix_shorten_number {
	my $num = shift;
	
	my $suffix = 0;
	while($num >= 1024) {
		$num /= 1024;
		$suffix++;
	}
	if($suffix == 0) {
		$suffix = "K";
	} elsif($suffix == 1) {
		$suffix = "M";
	} elsif($suffix == 2) {
		$suffix = "G";
	}
	
	$num = sprintf("%.02f %s", $num, $suffix);

	return $num;
}
