<?php defined('VAT_SRC') or die('No direct script access');
/**
 * VAT-specific utility functions
 * 
 * @author    David Z. Chen
 * @package   VAT
 * @copyright (C) 2011-2012 Gerstein Lab
 * @license   CC BY-NC
 */

/**
* Returns an array containing gene summary information, which can be used
* by vat.php to be converted into a JSON string to be displayed as a
* table
*
* @param string $base_path
* @param string $data_set
* @param string $annotation_set
* @param string $type
* @return array
*/
function get_gene_summary($base_path, $data_set, $annotation_set, $type, $set_id)
{
    $gene_summary = array(
		'aaData'    => array(),
		'aoColumns' => array()
    );

    $file = $base_path.'/'.$data_set.'.geneSummary.txt';
    if (($fp = fopen($file, 'r')) === FALSE)
    {
        throw new Exception("Cannot open open " . $file);
    }

    $header = fgets($fp);
    $tokens = explode("\t", $header);
    foreach ($tokens as $token)
    {
        array_push($gene_summary['aoColumns'], array('sTitle' => $token));
    }
    array_push($gene_summary['aoColumns'], array('sTitle' => 'Link'));

    while (($line = fgets($fp)) !== FALSE)
    {
        $line_array = explode("\t", $line);
        $gene_id = $line_array[0];

        if ($type == "coding")
        {
            $link = sprintf("<a href=\"info.php?type=coding&dataSet=%s&annotationSet=%s&geneId=%s&setId=%s\" target=\"gene\">Link</a>",
                            $data_set, $annotation_set, $gene_id, $set_id);
            array_push($line_array, $link);
        }
        elseif ($type == "nonCoding")
        {
            $link = sprintf("<a href=\"info.php?type=nonCoding&dataSet=%s&annotationSet=%s&geneId=%s&setId=%s\" target=\"gene\">Link</a>",
                            $data_set, $annotation_set, $gene_id, $set_id);
            array_push($line_array, $link);
        }
        else
        {
            throw new InvalidArgumentException("Unknown type " . $type);
        }

        array_push($gene_summary['aaData'], $line_array);
    }
    fclose($fp);

    return $gene_summary;
}

/**
 * Returns an array containing sample summary information, which can be used
 * by vat.php to be converted into a JSON string to be displayed as a table.
 *
 * @param string $base_path
 * @param string $data_set
 * @throws Exception
 * @return array
 */
function get_sample_summary($base_path, $data_set)
{
    $sample_summary = array(
        'aaData'    => array(),
        'aoColumns' => array(),
    );

    $file = $base_path . '/' . $data_set . '.sampleSummary.txt';
    if (($fp = fopen($file, 'r')) === FALSE)
    {
        throw new Exception("Cannot open open " . $file);
    }

    $header = fgets($fp);
    $tokens = explode("\t", $header);
    foreach ($tokens as $token)
    {
        array_push($sample_summary['aoColumns'], array('sTitle' => $token));
    }

    while (($line = fgets($fp)) !== FALSE)
    {
        $tokens = explode("\t", $line);
        array_push($sample_summary['aaData'], $tokens);
    }

    fclose($fp);

    return $sample_summary;
}


/**
 * Runs the vatson executable to obtain a JSON string containing the information
 * for showing the information for a gene. Returns an associative array parsed
 * from the JSON string
 * 
 * @param string $file
 * @param string $data_set
 * @param string $annotation_set
 * @param string $gene_id
 * @param string $type
 * @param int $set_id
 * @throws Exception
 * @return array
 */
function get_info($file, $data_set, $annotation_set, $gene_id, $type, $set_id)
{
    global $vat_config;
    
    $cmd_type = ($type == "coding") ? "--gene-info" : "--noncoding-info";
    
    $cmd = sprintf("%s/vatson %s -d %s -a %s -g %s -s %s -f %s",
                   $vat_config['VAT_EXEC_DIR'],
                   $cmd_type, $data_set, $annotation_set, $gene_id, $set_id,
                   $file);
    
    $out = array();
    $json = "";
    
    $line = exec($cmd, $out, $ret);
    
    if ($ret != 0) 
    {
        throw new Exception("Error executing command " . $cmd . ": " . $line);
    }
    
    foreach ($out as $s)
    {
        $json .= $s;
    }
    
    return json_decode($json, TRUE);
}

/**
 * Runs the vatson command line executable to obtain a JSON string containing
 * the information for a genotype of a gene. Returns an associative array
 * parsed from the JSON string
 * 
 * @param string $file
 * @param string $data_set
 * @param string $gene_id
 * @param int $index
 * @param int $set_id
 * @throws Exception
 * @return array
 */
function get_genotype_info($file, $data_set, $gene_id, $index, $set_id)
{
    global $vat_config;
    
    $cmd = sprintf("%s/vatson -Y -d %s -g %s -i %s -s %s -f %s",
                   $vat_config['VAT_EXEC_DIR'], 
                   $data_set, $gene_id, $index, $set_id, $file);
    
    $out = array();
    $json = "";
    
    $line = exec($cmd, $out, $ret);
    
    if ($ret != 0) 
    {
        throw new Exception("Error executing command " . $cmd . ": " . $line);
    }
    
    foreach ($out as $s)
    {
        $json .= $s;
    }
    
    return json_decode($json, TRUE);
}

function program2type($program)
{
    if ($program == 'svMapper')
        return 'SV';
    elseif ($program == 'indelMapper')
        return 'Indel';
    elseif ($program == 'snpMapper')
        return 'SNP';
    else
        return 'Generic';
}
?>