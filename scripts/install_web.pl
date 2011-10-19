#!/usr/bin/env perl

use strict;
use warnings;

use FindBin;

use lib "$FindBin::Bin";

use Confq;
use Cwd;

use constant FILES => "src/vat_cgi src/vat_fileUpload_cgi web/*";

my $path = getcwd() . "/default.vatrc";
my $config = Confq->new($path);

if (!$config) {
	print "Error installing web components\n";
	exit -1;
}

my $cmd = "cp -r " . FILES . " " . $config->get('WEB_DIR_CGI');

print $cmd, "\n";
system($cmd);
