<?php defined('VAT_SRC') or die('No direct script access.');
/**
 * Init code specific to master node
 * 
 * @author David Z. Chen
 */

/*
 * Make sure we have cluster mode turned on
 */
if ($vat_config['CLUSTER'] != 'true')
{
    die('Y U No turn on cluster support?');
}

require_once 'db.php';
require_once 'server.php';
require_once 'model.php';

/*
 * Autoload models
 */
// XXX In the future, we will do autoloading, but this suffices for now.
require_once 'model_dataset.php';

/*
 * Make sure we have S3 support enabled
 */
if ($vat_config['AWS_USE_S3'] == FALSE)
{
    die("S3 support must be enabled");
}

/*
 * Connect to MySQL
 */
$db = new DB($vat_config['MASTER_MYSQL_HOST'],
             $vat_config['MASTER_MYSQL_USER'],
             $vat_config['MASTER_MYSQL_PASS'],
             $vat_config['MASTER_MYSQL_DB']);
try
{
    $db->connect();
}
catch (Exception $e)
{
    die("Cannot connect to database");
}


/*
 * Make sure our server.json file exists in order to do load balancing
 */
if ( ! file_exists(Server::file))
{
    die('servers.json does not exist');
}

?>