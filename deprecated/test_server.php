<?php 


require_once 'lib/init.php';
require_once 'libmaster/init_master.php';

$server = Server::get(1);

echo "<pre>\n";
var_dump($server);
echo "</pre>\n";

?>