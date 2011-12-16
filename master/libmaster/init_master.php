<?php defined('VAT_SRC') or die('No direct script access.');

require_once 'model.php';

// XXX In the future, we will do autoloading, but this suffices for now.
require_once 'model_dataset.php';

if ($vat_config['AWS_USE_S3'] == 'false')
{
    die("S3 support must be enabled");
}

?>