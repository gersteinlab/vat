<?php
/**
 * IntervalFind implementation in PHP. IntervalFind efficiently finds intervals
 * that overlap with a query interval. The algorithm is based on containment
 * sublists. Note: The Interval format is zero-based and half-open.
 * 
 * The Interval format is a tab-delimited format as follows:
 * 
 * <pre>
 * Column:   Description:
 * 1         Name of the interval
 * 2         Chromosome 
 * 3         Strand
 * 4         Interval start
 * 5         Interval end
 * 6         Number of subintervals
 * 7         Subinterval starts (comma-delimited)
 * 8         Subinterval end (comma-delimited)
 * \endverbatim
 *
 * This is an example:
 * \verbatim
 * uc001aaw.1      chr1    +       357521  358460  1       357521  358460
 * uc001aax.1      chr1    +       410068  411702  3       410068,410854,411258    410159,411121,411702
 * uc001aay.1      chr1    -       552622  554252  3       552622,553203,554161    553066,553466,554252
 * uc001aaz.1      chr1    +       556324  557910  1       556324  557910
 * uc001aba.1      chr1    +       558011  558705  1       558011  558705
 * </pre>
 * 
 * Note in this example the intervals represent a transcripts, while the subintervals denote exons.
 * 
 * @see Alekseyenko, E.v., Lee, C.j. (2007) [Nested Containment List (NCList): 
 * A new algorithm for accelerating interval query of genome alignment and 
 * interval databases. Bioinformatics 23: 1386-1393.][ref-nclist]
 * 
 * IntervalFind was originally implemented in C and is part of the [BIOS 
 * library][ref-bios].
 * 
 * [ref-nclist]: http://bioinformatics.oxfordjournals.org/cgi/content/abstract/23/11/1386
 * [ref-bios]:   http://archive.gersteinlab.org/proj/rnaseq/doc/bios/
 * 
 * @author     David Z. Chen
 * @package    VAT
 * @copyright  (c) 2011 Gerstein Lab
 * @license    ???
 */

require_once 'util.php';
 
/**
 * Interval class
 * 
 * @property int    $source
 * @property string $name
 * @property string $chromosome
 * @property int    $strand
 * @property int    $start
 * @property int    $end
 * @property int    $sub_interval_count
 * @property array  $sub_intervals
 * 
 * @method int get_num_sub_intervals()
 * @method int get_size()
 * 
 * @static int compare()
 * @static int compare_name()
 */
 
class Interval implements Comparable {
    
    protected $_source;
    protected $_name;
    protected $_chromosome;
    protected $_strand;
    protected $_start;
    protected $_end;
    protected $_sub_interval_count;
    protected $_sub_intervals;
    
    public function __get($key)
    {
        return $this->{'_'.$key};
    }
    
    public function __set($key, $value)
    {
        $this->{'_'.$key} = $value;
    }
    
    /**
     * Returns the size of the interval
     * 
     * @return int
     */
    public function get_size()
    {
        $size = 0;
        foreach ($this->_sub_intervals as $curr_sub_interval)
        {
            $size += $curr_sub_interval->end - $curr_sub_interval->start;
        }
        
        return $size;
    }
    
    /**
     * Returns the number of sub_intervals
     * 
     * @return int
     */
    public function get_num_sub_intervals()
    {
        return count($this->_sub_intervals);
    }
    
    public static function compare(Interval $a, Interval $b)
    {
        $diff = strcmp($a->chromosome, $b->chromosome);
        if ($diff != 0)
            return $diff;
        
        $diff = $a->start - $b->start;
        if ($diff != 0)
            return $diff;
        
        return $b->end - $a->end;
    }
    
    public static function compare_name(Interval $a, Interval $b)
    {
        return strcmp($a->name, $b->name);
    }
}
 
 
/**
 * SubInterval class
 * 
 * @property int start
 * @property int end
 */

class SubInterval {
    
    protected $_start;
    protected $_end;
    
    public function __get($key)
    {
        return $this->{'_'.$key};
    }
    
    public function __set($key, $value)
    {
        $this->{'_'.$key} = $value;
    }
}


/**
 * Superinterval class
 * 
 * @property string $chromosome
 * @property int    $start
 * @property int    $end
 * @property array  @sublist
 * 
 * @static int compare()
 */

class SuperInterval implements Comparable {
    
    protected $_chromosome;
    protected $_start;
    protected $_end;
    protected $_sublist = array();
    
    public function __get($key)
    {
        return $this->{'_'.$key};
    }
    
    public function __set($key, $value)
    {
        $this->{'_'.$key} = $value;
    }
    
    public static function compare(SuperInterval $a, SuperInterval $b)
    {
        $diff = strcmp($a->chromosome, $b->chromosome);
        if ($diff != 0)
            return $diff;
        
        $diff = $a->start - $b->start;
        if ($diff != 0)
            return $diff;
        
        return $b->end - $a->end;
    }
}


/**
 * Main IntervalFind class
 * 
 * @property array $intervals
 * @property array $super_intervals
 * 
 * @method Interval parse_line()
 * @method int      get_number_of_intervals()
 * @method array    get_all_intervals()
 * @method array    get_overlapping_intervals()
 * 
 * @static array parse_file()
 */

class IntervalFind {
    
    /**
     * @var array
     */
    protected $_intervals = array();
    
    /**
     * @var array
     */
    protected $_super_intervals = array();
    
    
    /**
     * Class constructor. Analogous to the function 
     * intervalFind_addIntervalsToSearchSpace(). Adds intervals to the search
     * space. The input file file must have the Interval format (@see file
     * header).
     * 
     * @param string $file_name: file name of input file
     * @param int    $source: An integer that specifies the source. useful when
     * multiple files are used.
     */
    public function __construct($file_name, $source)
    {
        $this->_intervals = $this->_parse_file_content($file_name, $source);
    }
    
    /**
     * 
     * @param string $str
     * @return array
     */
    private function process_comma_separated_list($str)
    {
        $tokens = explode(',', $str);
        $results = array();
        
        foreach ($tokens as $token)
        {
            if ($token == "")
                continue;
            
            array_push($results, $token);
        }
        
        return $results;
    }
    
    /**
     * Parse a line in the Interval format
     * 
     * @param string $line: line in Interval format
     * @param int $source: An interval that specifies the source. This is
     * useful when multiple files are used
     * @return Interval
     */
    public function parse_line($line, $source)
    {
        $interval = new Interval();
        
        $tokens = explode("\t", $line);
        
        $interval->source             = $source;
        $interval->name               = $tokens[0];
        $interval->chromosome         = $tokens[1];
        $interval->strand             = $tokens[2];
        $interval->start              = $tokens[3];
        $interval->end                = $tokens[4];
        $interval->sub_interval_count = $tokens[5];
        
        $sub_interval_starts = $this->_process_comma_separated_list($tokens[6]);
        $sub_interval_ends   = $this->_process_comma_separated_list($tokens[7]);
        
        // FIXME: Do we need to make sure that these counts are equal to
        // $interval->sub_interval_count??????
        if (count($sub_interval_starts) != count($sub_interval_ends))
        {
            die("Unequal number of sub_interval_starts and sub_interval_ends");
            // XXX Better to throw exception
        }
        
        $interval->sub_intervals = array();
        for ($i = 0; $i < $interval->sub_interval_count; $i++)
        {
            $sub_interval = new SubInterval();
            $sub_interval->start = $sub_interval_starts[$i];
            $sub_interval->end   = $sub_interval_ends[$i];
        }
        
        return $interval;
    }
    
    /**
     * Parses a file in the Interval format
     * 
     * @param $file_name: file name
     * @param $source:
     * @return array of Intervals parsed from file
     */
    private function _parse_file_content($file_name, $source)
    {
        $fp = fopen($file_name, 'r');
        
        if ( ! $fp)
            return FALSE;
        
        $intervals = array();
        
        while (($line = fgets($fp)) !== FALSE)
        {
            if ($line == "")
                continue;
            
            $interval = $this->parse_line($line, $source);
            array_push($intervals, $interval);
        }
        
        fclose($fp);
        
        return $intervals;
    }
    
    /**
     * Returns the total number of intervals that have been added to the search
     * space
     * 
     * @return int
     */
    public function get_number_of_intervals()
    {
        return count($this->_intervals);
    }
    
    /**
     * Retrieve all the intervals that have been added to the search space
     * 
     * @return array
     */
    public function get_all_intervals()
    {
        return $this->_intervals;
    }
    
    
    /**
     * Generates super intervals from array of intervals and and stores the
     * array of super intervals in the $super_intervals field.
     */
    private function _assign_super_intervals()
    {
        usort($this->_intervals, array('Interval', 'compare'));
        $i = 0;
        while ($i < count($this->_intervals))
        {
            $curr_interval = $this->_intervals[$i];
            
            $curr_super_interval = new SuperInterval();
            $curr_super_interval->chromosome = $curr_interval->chromosome;
            $curr_super_interval->start      = $curr_interval->start;
            $curr_super_interval->end        = $curr_interval->end;
            $curr_super_interval->sublist    = array();
            
            array_push($curr_super_interval->sublist, $curr_interval);
            $j = $i + 1;
            
            while ($j < count($this->_intervals))
            {
                $next_interval = $this->_intervals[$i];
                if ($curr_interval->chromosome == $next_interval->chromosome &&
                    $curr_interval->start <= $next_interval->start &&
                    $curr_interval->end >= $next_interval->end)
                {
                    array_push($curr_super_interval->sublist, $next_interval);
                }
                else 
                {
                    break;
                }
                
                $j++;
            }
            
            $i = $j;
        }

        usort($this->_super_intervals, array('SuperInterval', 'compare'));
    }
    
    /**
     * 
     * @param array $matching_intervals
     * @param array $sublist
     * @param int $start
     * @param int $end
     */
    private function _add_intervals(&$matching_intervals, $sublist, $start, $end)
    {
        foreach ($sublist as $curr_interval)
        {
            if (range_intersection($curr_subinterval->start, $curr_subinterval->end, $start, $end) >= 0)
            {
                array_push($matching_intervals, $curr_interval);
            }
        }
        
        return $matching_intervals;
    }
    
    /**
     * Get the intervals that overlap with the query interval
     * 
     * @param string $chromosome: chromosome of query interval
     * @param int $start: Start of query interval
     * @param int $end: End of query interval
     * @return array of interlapping intervals
     */
    public function get_overlapping_intervals($chromosome, $start, $end)
    {
        $matching_intervals = array();
        
        if (empty($this->_super_intervals))
        {
            $this->_assign_super_intervals();
        }
        
        $test_super_interval = new SuperInterval();
        $test_super_interval->chromosome = $chromosome;
        $test_super_interval->start = $start;
        $test_super_interval->end = $end;
        
        $index = array_usearch($test_super_interval, $this->_super_intervals, array('SuperInterval', 'compare')); 
        $i = $index;
        
        while ($i >= 0)
        {
            $curr_super_interval = $this->_super_intervals[$i];
            if ($curr_super_interval->chromosome != $chromosome ||
                $curr_super_interval->end < $start)
                break;
            
            $matching_intervals = $this->_add_intervals($matching_intervals, $curr_super_interval->sublist, $start, $end);
            $i--;
        }
        
        $i = $index + 1;
        while ($i < count($$this->_super_intervals))
        {
            $curr_super_interval = $this->_super_intervals[$i];
            if ($curr_super_interval->chromosome != $chromosome ||
                $curr_super_interval->start > $end)
                break;
        
            $matching_intervals = $this->_add_intervals($matching_intervals, $curr_super_interval->sublist, $start, $end);
            $i++;
        }
        
        return $matching_intervals;
    }
    
     /**
     * Parse a file in the Interval format. @see file header for format.
     * 
     * @param string $file_name
     * @param int    $source
     * @return array 
     */
    public static function parse_file($file_name, $source)
    {
        return parse_file_content($file_name, $source);
    }
}
