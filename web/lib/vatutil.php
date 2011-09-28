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
    
    public static function compare(GeneTranscriptEntry $a, 
                                   GeneTranscriptEntry $b)
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
}

?>