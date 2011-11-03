<?php 

require_once "../lib/intervalfind.php";

//$file = '/var/www/data/gencode3b.interval';
$file = '/var/www/data/test.interval';

$gene_intervals = IntervalFind::parse_file($file);

echo count($gene_intervals)."<br />\n";

echo "memory usage: ".memory_get_usage()."<br />\n";

system('wc '.$file);

?>