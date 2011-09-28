<?php

define('CONFIG_FILE_PATH', '');
define('CONFIG_COMMENT_DELIM', '//');

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
        
        $conf = substr($line, 0, $pos + 1);
        list($key, $value) = explode(' ', $conf, 2);
        if ($key === FALSE || $value === FALSE)
        {
            fprintf(STDERR, "Config file syntax error on line %d\n", $n);
            return FALSE;
        }
        
        $config[$key] = $value;
    }
    
    fclose($fp);
    
    return $config;
}

$vat_config = config_parse(CONFIG_FILE_PATH);

?>