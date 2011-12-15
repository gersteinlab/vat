<?php 
/**
 * PHP implementation of the [Variant Call Format][ref-vcf] developed for the 
 * 1000-Genomes Project. These classes were originally implemented as part of 
 * the [Variant Annotation Tools][ref-vat] (VAT).
 * 
 * The VCF class is the main class representing an entire VCF file. Each entry
 * is represented as a VCFEntry object. Annotations and genotype entries in
 * each VCF entry are represented respectively by the VCFAnnotation and
 * VCFGenotype classes.
 * 
 * [ref-vcf]: http://www.1000genomes.org/node/101
 * [ref-vat]: http://vat.gersteinlab.org
 * 
 * @package    VAT
 * @author     David Z. Chen
 * @copyright  (c) 2011 Gerstein Lab
 * @license    ???
 */

require_once 'util.php';
require_once 'vatutil.php';
require_once 'intervalfind.php';


/**
 * Class representing a VCF Genotype object in a VCF entry.
 * 
 * @property $genotype
 * @property $details
 * @property $group
 * @property $sample
 */

class VCFGenotype {
    
    protected $_genotype;
    protected $_details;
    protected $_group;
    protected $_sample;
    
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
 * Class representing a VCF Annotation object
 * 
 * @property $gene_id
 * @property $gene_name
 * @property $type
 * @property $fraction
 * @property $transcript_names
 * @property $transcript_ids
 * @property $transcript_details
 * @property $allele_number
 */

class VCFAnnotation {
    
    protected $_gene_id;
    protected $_gene_name;
    protected $_strand;
    protected $_type;
    protected $_fraction;
    protected $_transcript_names;
    protected $_transcript_ids;
    protected $_transcript_details;
    protected $_allele_number;
    
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
 * Class representing a single entry in a VCF file
 * 
 * @property int    $entity_id
 * @property string $chromosome
 * @property int    $position
 * @property string $id
 * @property string $reference_allele
 * @property string $alternate_allele
 * @property string $quality
 * @property string $filter
 * @property string $info
 * @property string $genotype_format
 * @property array  $genotypes
 * @property array  $annotations
 * 
 * @static int $entries
 * 
 * @method bool  has_multiple_alternate_alleles()
 * @method bool  is_invalid_entry()
 * @method array get_allele_information()
 * @method array get_alternative_alleles()
 * 
 * @static int compare()
 */

class VCFEntry implements Comparable {
    
    protected $_entity_id;
    protected $_chromosome;
    protected $_position;
    protected $_id;
    protected $_reference_allele;
    protected $_alternate_allele;
    protected $_quality;
    protected $_filter;
    protected $_info;
    protected $_genotype_format;
    protected $_genotypes = array();
    protected $_annotations = array();
    
    /**
     * Class variable containing number of entries created. This number is 
     * updated whenever a new VCFEntry object is instantiated
     * 
     * @var int
     */
    public static $entries = 0;
    
    /**
     * Class constructor
     */
    public function __construct($chromosome       = NULL,
                                $position         = NULL,
                                $id               = NULL,
                                $reference_allele = NULL,
                                $alternate_allele = NULL,
                                $quality          = NULL,
                                $filter           = NULL,
                                $info             = NULL,
                                $genotype_format  = NULL,
                                $genotypes        = NULL,
                                $annotations      = NULL)
    {
        $this->_entity_id        = self::$entries;
        $this->_chromosome       = $chromosome;
        $this->_position         = $position;
        $this->_id               = $id;
        $this->_reference_allele = $reference_allele;
        $this->_alternate_allele = $alternate_allele;
        $this->_quality          = $quality;
        $this->_filter           = $filter;
        $this->_info             = $info;
        $this->_genotype_format  = $genotype_format;
        $this->_genotypes        = $genotypes;
        $this->_annotations      = $annotations;
        
        self::$entries++;
    }
    
    
    /**
     * Comparison function compares the entity ID's of the tntries. Analog of
     * sortVcfEntryPointers() function.
     * 
     * @param VCFEntry $a
     * @param VCFEntry $b
     * @return int
     */
    public static function compare($a, $b)
    {
        return $a->entity_id - $b->entity_id;
    }
    
    /**
     * Returns true if entry has multiple alternate alleles, false otherwise.
     * 
     * @return bool
     */
    public function has_multiple_alternate_alleles()
    {
        return (strpos($this->_alternate_allele, ',') !== FALSE);
    }
    
    /**
     * Returns whether entry is a valid entry
     * 
     * @return bool
     */
    public function is_invalid_entry()
    {
        return (strpos($this->_alternate_allele, '.') !== FALSE || 
                strpos($this->_alternate_allele, '<') !== FALSE ||
                strpos($this->_alternate_allele, '>') !== FALSE ||
                strpos($this->_reference_allele, '.') !== FALSE ||
                strpos($this->_reference_allele, '<') !== FALSE ||
                strpos($this->_reference_allele, '>') !== FALSE);
    }
    
    /**
     * Getter special method
     */
    public function __get($key)
    {
        return $this->{'_'.$key};
    }
    
    
    /**
     * Setter special method
     */
    public function __set($key, $value)
    {
        $this->{'_'.$key} = $value;
    }
    
    
    /**
     * Returns an array containing the allele and total allele counts
     *  
     * @param string $group
     * @param int $allele_number
     * @return array($allele_count, $total_allele_count)
     */
    public function get_allele_information($group, $allele_number)
    {
        $allele_count = 0;
        $total_allele_count = 0;
        
        foreach ($this->_genotypes as $curr_vcf_genotype)
        {
            if ($curr_vcf_genotype->group != $group)
                continue;
            
            $total_allele_count += 2;
            list($allele1, $allele2) = VCF::get_alleles_from_genotype($curr_vcf_genotype->genotype);
            
            if ($allele1 !== FALSE && $allele2 != FALSE)
            {
                if ($allele1 == $allele_number)
                {
                    $allele_count++;
                }
                if ($allele2 == $allele_number)
                {
                    $allele_count++;
                }
            }
        }
        
        return array($allele_count, $total_allele_count);
    }
    
    /**
     * Returns array of alternate alleles
     * 
     * @return array
     */
    public function get_alternative_alleles()
    {
        $tokens = array();
        
        if (!$this->has_multiple_alternate_alleles())
        {
            array_push($tokens, $this->_alternative_allele);
        }
        else 
        {
            $copy = $this->_alternative_allele;
            $aa_tokens = explode($copy, ',');
            array_merge($tokens, $aa_tokens);
        }
        
        return $tokens;
    }
}

/**
 * VCFGene class
 * 
 * @property string $gene_id
 * @property string $gene_name
 * @property array $tanscripts
 * @property array $vcf_entries
 * 
 * @method int get_counts_for_vcf_annotation_type()
 */

class VCFGene {
    
    protected $_gene_id;
    protected $_gene_name;
    protected $_transcripts = array();
    protected $_vcf_entries = array();
    
    public function __construct($gene_id     = NULL, 
                                $gene_name   = NULL, 
                                $transcripts = NULL, 
                                $vcf_entries = NULL)
    {
        $this->_gene_id     = $gene_id;
        $this->_gene_name   = $gene_name;
        $this->_transcripts = $transcripts;
        $this->_vcf_entries = $vcf_entries;
    }
    
    public function get_counts_for_vcf_annotation_type($type)
    {
        $count = 0;
        foreach ($this->_vcf_entries as $curr_vcf_entry)
        {
            foreach ($curr_vcf_entry->annotations as $curr_vcf_annotation)
            {
                if ($this->_gene_id == $curr_vcf_annotation->gene_id &&
                    $curr_vcf_annotation->type == type)
                {
                        $count++;
                }
            }
        }
        
        return $count;
    }
    
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
 * VCF Class.
 * 
 * @property array  $comments
 * @property array  $column_headers
 * @property array  $entries
 * @property string $file_path
 * 
 * @method array  parse()
 * @method VCF    add_comment()
 * @method array  get_comments()
 * @method array  get_groups_from_column_headers()
 * @method array  get_samples_from_column_headers()
 * @method string get_column_header()
 * @method array  get_column_headers()
 * @method array  get_gene_summary
 * 
 * @static int   compare_vcf_items_gene_id()
 * @static array get_allele_information()
 */

class VCF {
    
    /**
     * VCF File comments. The metadata section of the file is contained in
     * the comments section and thus in this array.
     * @var array
     */
    protected $_comments = array();
    
    /**
     * VCF Column headers
     * @var array
     */
    protected $_column_headers = array();
    
    /**
     * Array of VCFEntries
     * @var array
     */
    protected $_entries = array();
    
    /**
     * Path to VCF file represented by instance
     * @var string
     */
    private $_file_path = NULL;
    
    /**
     * 
     * @var array
     */
    protected $_gene_intervals = array();
    
    
    /**
     * 
     * @var array
     */
    protected $_gene_transcript_entries = array();
    
    /**
     * Class constructor
     */
    public function __construct($path)
    {
        $this->_file_path = $path;
        
        $fp = fopen($path, 'r');
        
        if ( ! $fp)
            return NULL;
        
        while (($line = fgets($fp)) != FALSE)
        {
            if (strpos($line, '#CHROM') == 0)
            {
                $this->_column_headers = explode('\t', $line);
            } 
            elseif (strpos($line, '#') == 0)
            {
                array_push($this->_comments, $line);
            }
            else 
            {
                break;
            }
        }
        
        fclose($fp);
    }
    
    /**
     * Creates and populates a VCFAnnotation object given an annotation string
     * while parsing a VCF file. Called by VCF::parse().
     * 
     * @param string $str
     * @return VCFAnnotation
     */
    private function _process_annotation($str)
    {
        $tokens = explode($str, ':');
        
        $annotation = new VCFAnnotation();
        
        $annotation->allele_number = $tokens[0];
        $annotation->gene_name     = $tokens[1];
        $annotation->gene_id       = $tokens[2];
        $annotation->strand        = $tokens[3];
        $annotation->type          = $tokens[4];
        $annotation->fraction      = $tokens[5];
        
        $annotation->transcript_names   = array();
        $annotation->transcript_ids     = array();
        $annotation->transcript_details = array();
        
        for ($i = 6; $i < count($tokens); $i = $i + 3)
        {
            array_push($annotation->transcript_names, $tokens[$i]);
            array_push($annotation->transcript_ids, $tokens[$i + 1]);
            array_push($annotation->transcript_details, $tokens[$i + 2]);
        }
        
        if (count($annotation->transcript_names) != count($annotation->transcript_ids) ||
            count($annotation->transcript_ids) != count($annotation->transcript_details))
        {
            // XXX Should have better error handling
            die('Unexpected annotation format');
            // Better to throw exception
        }
        
        return $annotation;
    }

    /**
     * Creates and populates a VCFGenotype object from an input string and 
     * column index. Called by VCF::parse()
     * 
     * @param string $str
     * @param int $column_index
     * @return VCFGenotype
     */
    private function _process_genotype($str, $column_index)
    {
        $genotype = new VCFGenotype();
        
        $pos = strpos($str, ':');        
        
        if ($pos !== FALSE)
        {
            $genotype->details = substr($str, $pos + 1);
        }
        else
        {
            $genotype->details = "";
        }
        
        $genotype->genotype = $str;
        $column_header = $this->get_column_header($column_index);
        $pos = strpos($column_header, ':');
        
        if ($pos !== FALSE)
        {
            $genotype->group  = $column_header;
            $genotype->sample = substr($column_header, $pos + 1);
        }
        else
        {
            $genotype->group  = $column_header;
            $genotype->sample = $column_header;
        }
        
        return $genotype;
    }
    
    /**
     * Parses a VCF file into an array of VCFEntry objects. The file parsed
     * is the file that this instance is associated with.
     */
    public function parse()
    {
        if ( ! empty($this->_entries))
            return $this->_entries;
        
        $fp = fopen($this->_file_path, 'r');
        if ( ! $fp)
            return NULL;
        
        // Skip over comments and column headers
        while (1)
        {
            $line = fgets($fp);
            if ($line === FALSE)
                return NULL;
            
            if ( ! (strpos($line, '#CHROM') == 0 || strpos($line, '#') == 0))
                break;
        }
        
        // Process each entry
        while (($line = fgets($fp)) !== FALSE)
        {
            $curr_entry = new VCFEntry();
            $tokens = explode($line, '\t');
            
            $curr_entry->chromosome = (strstr($tokens[0], 'chr') !== FALSE)
                                    ? $tokens[0]
                                    : 'chr'.$tokens[0];
            $curr_entry->position         = $tokens[1];
            $curr_entry->id               = $tokens[2];
            $curr_entry->reference_allele = strtoupper($tokens[3]);
            $curr_entry->alternate_allele = strtoupper($tokens[4]);
            $curr_entry->quality          = $tokens[5];
            $curr_entry->filter           = $tokens[6];
            $curr_entry->info             = $tokens[7];
            
            $curr_entry->genotypes   = array();
            $curr_entry->annotations = array();
            
            if ($pos = strpos($curr_entry->info, 'VA='))
            {
                $annotations_str = substr($info, $pos + 3);
                $annotations = explode($annotations_str, ',');
                
                foreach ($annotations as $annotation)
                {
                    $curr_entry->annotations = $this->_process_annotation($annotation);
                }
            }
            
            for ($i = 8; $i < count($tokens); $i++)
            {
                if ($this->get_column_header($i) == 'FORMAT')
                {
                    $curr_entry->genotype_format = $tokens[i];
                }
                else 
                {
                    $curr_entry->genotype = $this->_process_genotype($tokens[$i], $i);
                }
            }
            
            array_push($this->_entries, $curr_entry);
        }
        
        fclose($fp);
        
        return $this->_entries;
    }


    /**
     * Adds a comment to the array of comments
     * 
     * @chainable
     * @param string $comment
     * @return VCF
     */     
    public function add_comment($comment)
    {
        array_push($this->_comments, $comment);
        return $this;
    }
    
    /**
     * Returns the array of comments
     * 
     * @return array
     */
    public function get_comments()
    {
        return $this->_comments;
    }
    
    /**
     * Returns the column header denoted by the index $column_index
     * 
     * @param int $column_index
     * @return string
     */
    public function get_column_header($column_index)
    {
        return $this->_column_headers[$column_index];
    }
    
    
    /**
     * Returns the array of all column headers
     * 
     * @return array
     */
    public function get_column_headers()
    {
        return $this->_column_headers;
    }
    
    /**
     * Returns array of groups from column headers.
     * 
     * @return array
     */
    public function get_groups_from_column_headers()
    {
        $groups = array();
        
        for ($i = 8; $i < count($this->_column_headers); $i++)
        {
            if (($header = $this->_column_headers[$i]) == 'FORMAT')
                continue;
            
            $pos = strpos($header, ':');
            if ($pos !== FALSE)
            {
                $group = substr($header, 0, $pos + 1);
            }
            else {
                $group = $header;
            }
            
            array_push($groups, $group);
        }
        
        return $groups;
    }
    
    /**
     * Returns array of samples from column headers
     * 
     * @return array
     */
    public function get_samples_from_column_headers()
    {
        $samples = array();
        
        for ($i = 8; $i < count($this->_column_headers()); $i++)
        {
            if (($header = $this->_column_headers[$i]) == 'FORMAT')
                continue;
            
            $pos = strpos($header, ':');
            if ($pos !== FALSE)
            {
                $sample = substr($header, $pos + 1);
            }
            else {
                $sample = $header;
            }
            
            array_push($samples, $sample);
        }
        
        return $samples;
    }
    
    public static function compare_vcf_items_gene_id($a, $b)
    {
        return strcmp($a['gene_id'], $b['gene_id']);
    }
    
    /**
     * Create an array of VCFGene objects with the gene summary
     * 
     * @param string $annotation_file
     * @return array of VCFGene objects
     */
    public function get_gene_summaries($annotation_file)
    {   
        if (empty($this->_gene_transcript_entries) ||
            empty($this->_gene_intervals))
        {
            $this->_gene_intervals = IntervalFind::parse_file($annotation_file, 0);
            usort($this->_gene_intervals, array('Interval', 'compare_name'));
            
            $this->_gene_transcript_entries = VAT::get_gene_transcript_entries($this->_gene_intervals);
        }
        
        $vcf_items = array();
        $vcf_genes = array();
        
        foreach ($this->_entries as $curr_vcf_entry)
        {
            foreach ($curr_vcf_entry->annotations as $curr_vcf_annotation)
            {
                $curr_vcf_item = array(
                    'gene_id'   => $curr_vcf_annotation->gene_id,
                    'gene_name' => $curr_vcf_annotation->gene_name,
                    'vcf_entry' => $curr_vcf_entry
                );
                
                array_push($vcf_items, $curr_vcf_item);
            }
        }
        
        usort($vcf_items, array('VCF', 'compare_vcf_items_gene_id'));
        
        $i = 0;
        while ($i < count($vcf_items))
        {
            $curr_vcf_item = $vcf_items[$i];
            $curr_vcf_gene = new VCFGene($curr_vcf_item['gene_id'],
                                         $curr_vcf_item['gene_name'],
                                         array(),
                                         array());
            array_push($vcf_genes, $curr_vcf_gene);
            array_push($curr_vcf_gene->vcf_entries, $curr_vcf_item['vcf_entry']);
            
            $j = $i + 1;
            
            while ($j < count($vcf_items))
            {
                $next_vcf_item = $vcf_items[$j];
                if ($curr_vcf_item['gene_id'] != $next_vcf_item['gene_id'])
                    break;
                
                array_push($curr_vcf_gene->vcf_entries, $next_vcf_item['vcf_entry']);
                
                $j++;
            }
            
            usort($curr_vcf_gene->vcf_entries, array('VCFEntry', 'compare'));
            $curr_vcf_gene->vcf_entries = array_uunique($curr_vcf_gene->vcf_entries, array('VCFEntry', 'compare'));
            
            $query_strings = VAT::get_query_strings_for_gene_id($this->_gene_transcript_entries, $curr_vcf_item['gene_id']);
            
            for ($k = 0; $k < count($query_strings); $k++)
            {
                $test_interval = new Interval();
                $test_interval->name = $query_strings[$k];
                if (($index = array_usearch($test_interval, $this->_gene_intervals, array('Interval', 'compare_name'))) === FALSE)
                {
                    die ("Expected to find interval: ".$query_strings[$k]);
                    // XXX: Better to throw exception
                }
                
                array_push($curr_vcf_gene->transcripts, $interval_find->get_interval($index));
            }
            
            $i = $j;
        }

        return $vcf_genes;
    }

    public static function get_alleles_from_genotype($genotype)
    {
        if (strstr($genotype, '.'))
            return FALSE;
        
        $pos = strpbrkpos($genotype, '|/');
        if ($pos == FALSE)
        {
            echo 'Unexpected genotype '.$genotype;
            return FALSE;
        }
        
        return array(substr($genotype, 0, $pos), substr($genotype, $pos + 1));
    }
}
?>