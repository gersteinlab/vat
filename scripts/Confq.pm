#!/usr/bin/env perl
# Perl configuration file parser module for a simple key-value configuration
# file format
#
# @author David Z. Chen
# @package VAT
 

package Confq;

use strict;
use warnings;

sub new {
	my $proto = shift;
	my $class = ref($proto) || $proto;
	my $self = {};
	
	$self->{PATH} = "";
	$self->{CONFIG} = {};
	bless($self, $class);

	if (@_) {
		if (!$self->read(shift)) {
			return undef;
		}
	}

	return $self;
}

sub read {
	my $self = shift;
	my $path = shift;

	unless (open(CONFFILE, "<", $path)) {
		print "Cannot open file ", $path, "\n";
		
		return undef;
	}

	$self->{PATH} = $path;

	my $n = 1;
	while (<CONFFILE>) {
		if (!$self->parse($_)) {
			print "Syntax error at line ", $n, "\n";
			return 0;
		}
		$n++;
	}
	
	close(CONFFILE);
	
	return 1;
}

sub trim {
	my $string = shift;
	$string =~ s/^\s+//;
	$string =~ s/\s+$//;
	return $string;
}

sub ltrim {
	my $string = shift;
	$string =~ s/^\s+//;
	return $string;
}

sub rtrim {
	my $string = shift;
	$string =~ s/\s+$//;
	return $string;
}

sub parse { 
	my $self = shift;
	my $line = trim(shift);

	if (substr ($line, 0, 2) =~ /\/\//) {
		return 1;
	} elsif ($line =~ /\w+\s*(\".*\"|\'.*\'|[^\s\"\']+)/) {

		my ($key, $value) = split(/\s/, $line, 2);

		$key = trim($key);
		$value = trim($value);
		
		my $rsqi = rindex($value, "'");
		my $lsqi = index($value, "'");
		my $rdqi = rindex($value, "\"");
		my $ldqi = index($value, "\"");

		if ($lsqi == 0) {
			if ($rsqi < 0 || $rsqi == $lsqi) {
				return 0;
			}
				
			$value = substr($value, $lsqi + 1, $rsqi - $lsqi - 1);
		} elsif ($ldqi == 0) {
			if ($ldqi < 0 || $rdqi == $ldqi) {
				return 0;
			}

			$value = substr($value, $ldqi + 1, $rdqi - $ldqi - 1);
		}
		
        #print $key . " " . $value . "\n";
		$self->{CONFIG}{$key} = $value;

		return 1;
	} elsif (!$line) {
		return 1;
	} else {
		return 0;
	}
}


sub get { 
	my $self = shift;
	my $key  = shift;

	return $self->{CONFIG}{$key};
}

1;
