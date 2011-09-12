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

require 'interval.php';


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
 * @property $chromosome
 * @property $position
 * @property $id
 * @property $reference_allele
 * @property $alternate_allele
 * @property $quality
 * @property $filter
 * @property $info
 * @property $genotype_format
 * @property array $genotypes
 * @property array $annotations
 * 
 * @method bool has_multiple_alternate_alleles()
 * @method bool is_invalid_entry()
 * @method get_allele_information()
 */

class VCFEntry {
    
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
    
    public function has_multiple_alternate_alleles()
    {
        return (strpos($this->_alternate_allele, ',') !== FALSE);
    }
    
    public function is_invalid_entry()
    {
        return (strpos($this->_alternate_allele, '.') !== FALSE || 
                strpos($this->_alternate_allele, '<') !== FALSE ||
                strpos($this->_alternate_allele, '>') !== FALSE ||
                strpos($this->_reference_allele, '.') !== FALSE ||
                strpos($this->_reference_allele, '<') !== FALSE ||
                strpos($this->_reference_allele, '>') !== FALSE);
    }
    
    public function __get($key)
    {
        return $this->{'_'.$key};
    }
    
    public function __set($key, $value)
    {
        $this->{'_'.$key} = $value;
    }
    
    public function get_allele_information()
    {
        
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
            //echo 'Unexpected annotation format';
            //return NULL;
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
        
        $fp = fopen($this->_file_path);
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
                    $curr_entry->genotype = $this->_process_genotype();
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
        
        for ($i = 8; $i < count($this->_column_headers()); $i++)
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
    
    /**
     * 
     */
    public function get_gene_summaries($annotation_file)
    {
        if (empty($this->_gene_transcript_entries) ||
            empty($this->_gene_intervals))
        {
            $intervals = Interval::parse_file($annotation_file);                        // FIXME: Implement
            // FIXME: sort $intervals
            
            $gene_transcript_entries = Util::get_gene_Transcript_entries($intervals);   // FIXME: Implement
        }
        
        $vcf_items = array();
        
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
        
        // FIXME: Sort $curr_vcf_items
        $vcf_genes = array();
        
        $i = 0;
        while ($i < count($vcf_items))
        {
            $curr_vcf_item = $vcf_items[$i];
            $curr_vcf_gene = array(
                'transcripts' => array(),
                'vcf_entries' => array(),
                'gene_id'     => $curr_vcf_item['gene_id'],
                'gene_name'   => $curr_vcf_item['gene_name']
            );
            array_push($vcf_genes, $curr_vcf_gene);
            array_push($curr_vcf_gene['vcf_entries'], $curr_vcf_item['vcf_entry']);
            
            $j = $i + 1;
            
            while ($j < count($vcf_items))
            {
                $next_vcf_item = $vcf_items[$j];
                if ($curr_vcf_item['gene_id'] != $next_vcf_item['gene_id'])
                    break;
                
                array_push($curr_vcf_gene['vcf_entries'], $next_vcf_item['vcf_entry']);
                
                $j++;
            }
            
            // FIXME: sort $curr_vcf_gene['vcf_entries']
            // FIXME: uniq $curr_vcf_gene['vcf_entries']
            
            $query_strings = Util::get_query_strings_for_gene_id($gene_transcript_entries, $curr_vcf_item['gene_id']);
            
            for ($k = 0; $k < count($query_strings); $k++)
            {
                $test_interval = new Interval();
                $test_interval->name = $query_strings[$k];
                if (array_search($needle, $haystack))
                {
                    
                }
            }
            
            $i = $j;
        }

        return $vcf_genes;
    }
    
}
?>