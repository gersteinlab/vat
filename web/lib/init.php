<?php
/**
 * Initialization code for VAT. Sets some global constants, error reporting,
 * and parses the configuration file. A VAT configuration file named vat.conf
 * is required to exist in the web root.
 *
 * @package    VAT
 * @author     David Z. Chen
 * @copyright  (c) 2011 Gerstein Lab
 * @license    CC BY-NC
 */

define('CONFIG_FILE_PATH', 'vat.conf');
define('CONFIG_COMMENT_DELIM', '//');
define('VAT_SRC', TRUE);

require_once 'constants.php';

/* 
 * Turn on all error reporting
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

/**
 * Opens a VAT configuration file and parses the key/value pairs into an 
 * associatve array
 *
 * @param string $path
 * @return array
 */
function config_parse($path)
{
    $config = array();
    
    $fp = fopen($path, 'r');
    if ( ! $fp)
        die("Cannot open configuration file");
    
    $n = 0;
    while (($line = fgets($fp)) !== FALSE)
    {
        $n++;
        $line = trim($line);
        if ($line == "")
            continue;
        
        $pos = strpos($line, CONFIG_COMMENT_DELIM);
        if ($pos === 0)
            continue;
        
        $tokens = explode(' ', $line);
        
        if (count($tokens) != 2)
        {
            echo "Config file syntax error on line " . $n . "\n";
            return FALSE;
        }
        
        $key = $tokens[0];
        
        if ($tokens[1] == "true" || $tokens[1] == "false")
        {
            $value = ($tokens[1] == "true") ? TRUE : FALSE;
        } 
        else 
        {
            $value = $tokens[1];
        }
        
        $config[$key] = $value;
    }
    
    fclose($fp);
    
    return $config;
}

if ( ! file_exists(CONFIG_FILE_PATH))
{
    die('Config file vat.conf not found in web root');
}

putenv("VAT_CONFIG_FILE=" . CONFIG_FILE_PATH);
$vat_config = config_parse(CONFIG_FILE_PATH);

?>