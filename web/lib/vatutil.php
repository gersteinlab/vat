<?php
/**
 * Various utility classes for VAT.
 * 
 * @package    VAT
 * @author     David Z. Chen
 * @copyright  (c) 2011 Gerstein Lab
 * @license    ???
 */

require_once 'util.php';

/**
 * Class representing a gene transcript entry
 * 
 * @property string $gene_id
 * @property string $transcript_id
 * @property string $gene_name
 * @property string $transcript_name
 * 
 * @static int compare()
 */
class GeneTranscriptEntry implements Comparable {

    protected $_gene_id;
    protected $_transcript_id;
    protected $_gene_name;
    protected $_transcript_name;
    
    public function __construct($gene_id         = NULL,
                                $transcript_id   = NULL,
                                $gene_name       = NULL,
                                $transcript_name = NULL)
    {
        $this->_gene_id         = $gene_id;
        $this->_transcript_id   = $transcript_id;
        $this->_gene_name       = $gene_name;
        $this->_transcript_name = $transcript_name;
    }
    
    /**
     * Compares two GeneTranscriptEntries by Gene ID
     * 
     * @param GeneTranscriptEntry $a
     * @param GeneTranscriptEntry $b
     */
    public static function compare($a, $b)
    {
        return strcmp($a->gene_id, $b->gene_id);
    }
}


/**
 * Utility helper class for VAT. These could be declared as standalone functions
 * but by convention, are declared as static functions of a helper class.
 * 
 * @static array get_gene_transcript_entries()
 * @static array process_transcript_line()
 * @static array get_query_strings_for_gene_id()
 * @static array get_gene_summary()
 * @static array get_sample_summary()
 * @static string hyperlink_id()
 */
class VAT {
    
    /**
     * Takes an array of intervals and creates from the intervals an array of
     * GeneTranscriptEntry objects
     * 
     * @param array<Interval> $intervals
     * @return array<GeneTranscriptEntry>
     */
    public static function get_gene_transcript_entries($intervals)
    {
        $gene_transcript_entries = array();
        
        foreach ($intervals as $curr_interval)
        {
            list($gene_id, 
                 $transcript_id, 
                 $gene_name, 
                 $transcript_name) = VAT::process_transcript_line($curr_interval->name);
            
            $curr_entry = new GeneTranscriptEntry($gene_id, 
                                                  $transcript_id, 
                                                  $gene_name, 
                                                  $transcript_name);
            array_push($gene_transcript_entries, $curr_entry);
        }
        usort($gene_transcript_entries, array('GeneTranscriptEntry', 'compare'));
        
        return $gene_transcript_entries;
    }
    
    /**
     * Takes a transcript line string and tokenizes it and returns a 4-tuple
     * of (gene ID, transcript ID, gene name, transcript name)
     * 
     * @param string $transcript_line
     * @return array
     */
    public static function process_transcript_line($transcript_line)
    {
        
        $tokens = explode('|', $transcript_line);
        if (count($tokens) != 4)
        {
            die('Unexpected interval name: '.$transcript_line
               .'\nRequire: geneId|transcriptId|geneName|transcriptName');
            // XXX: Better to throw exception
        }
        
        return array($tokens[0], $tokens[1], $tokens[2], $tokens[3]);
    }
    
    /**
     * 
     */
    public static function get_query_strings_for_gene_id($gene_transcript_entries, $gene_id)
    {
        $query_strings = array();
        
        $test_entry = new GeneTranscriptEntry();
        $test_entry->gene_id = $gene_id;
        
        if (($index = array_usearch($test_entry, $gene_transcript_entries, array('GeneTranscriptEntry', 'compare'))) == FALSE)
        {
            echo "Expected to find geneId ".$gene_id;
            return FALSE;
        }
        
        $i = $index;
        while ($i < count($gene_transcript_entries))
        {
            $curr_entry = $gene_transcript_entries[$i];
            
            if ($curr_entry->gene_id == $gene_id)
            {
                $query_string = sprintf("%s|%s|%s|%s", 
                                        $curr_entry->gene_id,
                                        $curr_entry->transcript_id,
                                        $curr_entry->gene_name,
                                        $curr_entry->transcript_name);
                array_push($query_strings, $query_string);
            }
            else
            {
                break;
            }
            
            $i++;
        }
        
        $i = $index - 1;
        while ($i > 0)
        {
            $curr_entry = $gene_transcript_entries[$i];
            if ($curr_entry->gene_id == $gene_id)
            {
                $query_string = sprintf("%s|%s|%s|%s",
                                        $curr_entry->gene_id,
                                        $curr_entry->transcript_id,
                                        $curr_entry->gene_name,
                                        $curr_entry->transcript_name);
                array_push($query_strings, $query_string);
            }
            else 
            {
                break;
            }
            
            $i--;
        }
        
        return $query_strings;
    }
    
    
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
    public static function get_gene_summary($base_path, $data_set, $annotation_set, $type)
    {   
        $gene_summary = array(
                'aaData'    => array(),
                'aoColumns' => array()
        );
        
        $file = $base_path.'/'.$data_set.'.geneSummary.txt';
        if (($fp = fopen($file, 'r')) === FALSE)
        {
            echo "Cannot open open ". $file;
            return NULL;
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
                $link = '<a href="vat.php?mode=showGene&dataSet='.$data_set
                .'&annotationSet='.$annotation_set.'&geneId='.$gene_id.'"'
                .' target="gene">Link</a>';
                array_push($line_array, $link);
            }
            elseif ($type == "nonCoding")
            {
                $link = '<a href="vat.php?mode=showNonCoding&dataSet='.$data_set
                .'&annotationSet='.$annotation_set.'&geneId='.$gene_id.'"'
                .'target="gene">Link</a>';
                array_push($line_array, $link);
            }
            else
            {
                echo "Unknown type ".$type;
                return NULL;
                // XXX Better to throw exception
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
     * @return array
     */
    public static function get_sample_summary($base_path, $data_set)
    {
        $sample_summary = array(
            'aaData'    => array(),
            'aoColumns' => array(),
        );
    
        $file = $base_path.'/'.$data_set.'.sampleSummary.txt';
        if (($fp = fopen($file, 'r')) === FALSE)
        {
            return NULL;
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
     * Creates a hyperlink to the NIH SNP tool
     * 
     * @param unknown_type $id
     * @return string
     */
    public static function hyperlink_id($id)
    {
        if (strstr($id, 'rs') === FALSE)
        return $id;
    
        $tokens = explode(';', $id);
        $str = "";
        for ($i = 0; $i < count($tokens); $i++)
        {
            if (strstr($tokens[$i]) !== FALSE)
            {
                $str .= '<a href="http://www.ncbi.nlm.nih.gov/SNP/snp_ref.cgi?rs='
                .substr($tokens[$i], 2).'" target="external">'.$tokens[$i].'</a>';
            }
            else
            {
                $str .= $tokens[$i];
            }
    
            $str .= ($i < count($tokens) - 1) ? ';' : '';
        }
    
        return $str;
    }
}

?>