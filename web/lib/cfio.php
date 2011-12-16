<?php defined('VAT_SRC') or die('No direct script access');
/**
 * VAT I/O layer PHP implementation
 * 
 * @author    David Z. Chen
 * @package   vat/web
 * @copyright (c) 2011 Gerstein Lab
 */

require_once 'aws/sdk.class.php';

/**
 * 
 * Enter description here ...
 * @author dzc
 *
 */
class CFIO {
    
    /**
     * Instance of Amazon S3 library
     * @var AmazonS3
     */
    protected $_s3 = NULL;
    
    /**
     * Data set ID associated with current instance
     * @var int
     */
    protected $_set_id;
    
    /**
     * Locally unique ID assigned to current instance
     * @var int
     */
    protected $_proc_id;
    
    /**
     * Working directory
     * @var string
     */
    protected $_working_dir;
    
    /**
     * Class constructor. Sets current process ID and initializes S3 object.
     */
    public function __construct()
    {
        global $vat_config;
        
        do 
        {
            $this->_proc_id = rand();
            @$test = mkdir($vat_config['WEB_DATA_WORKING_DIR'] . '/' . $this->_proc_id);
        } while ($test === FALSE);
        
        $this->_set_id = $this->_proc_id;
        $this->_working_dir = $vat_config['WEB_DATA_WORKING_DIR'] . '/' . $this->_proc_id;
        $this->_s3 = new AmazonS3();
    }
    
    /**
     * Returns current set ID 
     * 
     * @return int
     */
    public function get_set_id()
    {
        return $this->_set_id;
    }
    
    /**
     * Return current process ID
     * 
     * @return int
     */
    public function get_proc_id()
    {
        return $this->_proc_id;
    }
    
    /**
    * Return current process ID
    *
    * @return int
    */
    public function get_working_dir()
    {
        return $this->_working_dir;
    }
    
    /**
     * Attempts to set a set ID for current instance.
     * 
     * @param unknown_type $set_id
     * @throws InvalidArgumentException
     */
    public function set_set_id($set_id)
    {   
        $this->_set_id = $set_id;
    }
    
    /**
     * Retrieves one or more data files from AWS_S3_DATA_BUCKET if S3 support is
     * enabled or from WEB_DATA_DIR if S3 support is disabled 
     * 
     * @param array $objects
     * @param int $set_id
     * @throws InvalidArgumentException
     * @throws Exception 
     */
    public function get_data($objects, $set_id = -1)
    {
        global $vat_config;
        
        $use_set_id = ($set_id != -1) ? $set_id : $this->_set_id;
        
        if ( ! is_array($objects)) 
        {
            throw new InvalidArgumentException("Objects parameter must be array");
        }
        
        assert(file_exists($this->_working_dir));

        if ($vat_config['AWS_USE_S3'] == 'true') 
        {
            foreach ($objects as $object) 
            {
                if ($object == "raw") 
                {
                    $object_name = sprintf("%d/vat.%d.raw.vcf", $use_set_id, $use_set_id);
                    $dst_file    = sprintf("%s/vat.%d.raw.vcf", $this->_working_dir, $use_set_id);
                } 
                else if ($object == "svmapper") 
                {
                    $object_name = sprintf("%d/vat.%d.vcf", $use_set_id, $use_set_id);
                    $dst_file    = sprintf("%s/vat.%d.vcf", $this->_working_dir, $use_set_id);
                } 
                else if ($object == "bgzip") 
                {
                    $object_name = sprintf("%d/vat.%d.vcf.gz", $use_set_id, $use_set_id);
                    $dst_file    = sprintf("%s/%d/vat.%d.vcf.gz", $this->_working_dir, $use_set_id);
                } 
                else if ($object == "tabix") 
                {
                    $object_name = sprintf("%d/vat.%d.vcf.gz.tbi", $use_set_id, $use_set_id);
                    $dst_file    = sprintf("%s/vat.%d.vcf.gz.tbi", $this->_working_dir, $use_set_id);
                } 
                else if ($object == "gene_summary") 
                {
                    $object_name = sprintf("%d/vat.%d.geneSummary.txt", $use_set_id, $use_set_id);
                    $dst_file    = sprintf("%s/vat.%d.geneSummary.txt", $this->_working_dir, $use_set_id);
                } 
                else if ($object == "sample_summary") 
                {
                    $object_name = sprintf("%d/vat.%d.sampleSummary.txt", $use_set_id, $use_set_id);
                    $dst_file    = sprintf("%s/vat.%d.sampleSummary.txt", $this->_working_dir, $use_set_id);
                } 
                else 
                {
                    continue;
                }
                
                $response = $this->_s3->get_object($vat_config['AWS_S3_DATA_BUCKET'], $object_name, array('fileDownload' => $dst_file));
                
                if ($response->isOK() === FALSE) 
                {
                    throw new Exception("Cannot get object " . $object_name . " from bucket " . $vat_config['AWS_S3_DATA_BUCKET']);
                }
            }
        } 
        else 
        {
            foreach ($objects as $object) 
            {
                if ($object == "raw") 
                {
                    $src_file = sprintf("%s/%d/vat.%d.raw.vcf", $vat_config['WEB_DATA_DIR'],  $use_set_id, $use_set_id);
                    $dst_file = sprintf("%s/vat.%d.raw.vcf", $this->_working_dir, $use_set_id);
                } 
                else if ($object == "svmapper") 
                {
                    $src_file = sprintf("%s/%d/vat.%d.vcf", $vat_config['WEB_DATA_DIR'], $use_set_id, $use_set_id);
                    $dst_file = sprintf("%s/vat.%d.vcf", $this->_working_dir, $use_set_id);
                } 
                else if ($object == "bgzip") 
                {
                    $src_file = sprintf("%s/%d/vat.%d.vcf.gz", $vat_config['WEB_DATA_DIR'], $use_set_id, $use_set_id);
                    $dst_file = sprintf("%s/vat.%d.vcf.gz", $this->_working_dir, $use_set_id);
                } 
                else if ($object == "tabix") 
                {
                    $src_file = sprintf("%s/%d/vat.%d.vcf.gz.tbi", $vat_config['WEB_DATA_DIR'], $use_set_id, $use_set_id);
                    $dst_file = sprintf("%s/vat.%d.vcf.gz.tbi", $this->_working_dir, $use_set_id);
                } 
                else if ($object == "gene_summary") 
                {
                    $src_file = sprintf("%s/%d/vat.%d.geneSummary.txt", $vat_config['WEB_DATA_DIR'], $use_set_id, $use_set_id);
                    $dst_file = sprintf("%s/vat.%d.geneSummary.txt", $this->_working_dir, $use_set_id);
                } 
                else if ($object == "sample_summary") 
                {
                    $src_file = sprintf("%s/%d/vat.%d.sampleSummary.txt", $vat_config['WEB_DATA_DIR'], $use_set_id, $use_set_id);
                    $dst_file = sprintf("%s/vat.%d.sampleSummary.txt", $this->_working_dir, $use_set_id);
                } 
                else 
                {
                    continue;
                }
                
                if (copy($src_file, $dst_file) === FALSE) 
                {
                    throw new Exception("Cannot copy " . $src_file . " to " . $dst_file . ".");
                }
            }
        }
    }
    
    /**
     * Retrieves the VCF file for a gene subset from AWS_S3_DATA_BUCKET if S3
     * support is enabled or from WEB_DATA_DIR if S3 support is disabled
     * 
     * @param string $gene_id
     * @param int $set_id
     * @throws Exception if copy is unsuccessful
     */
    public function get_gene_data($gene_id, $set_id = -1)
    {
        global $vat_config;
        
        $use_set_id = ($set_id != -1) ? $set_id : $this->_set_id;
        
        assert(file_exists($this->_working_dir));

        $gene_dir = sprintf("%s/vat.%d", $this->_working_dir, $use_set_id);
        if ( ! file_exists($gene_dir)) 
        {
            if (mkdir($gene_dir) === FALSE) 
            {
                throw new Exception("Cannot create directory " . $gene_dir);    
            }
        }
        
        if ($vat_config['AWS_USE_S3'] == 'true') 
        {
            $object_name = sprintf("%d/vat.%d/%s.vcf", $use_set_id, $use_set_id, $gene_id);
            $dst_file    = sprintf("%s/vat.%d/%s.vcf", $this->_working_dir, $use_set_id, $gene_id);
            
            $response = $this->_s3->get_object($vat_config['AWS_S3_DATA_BUCKET'], $object_name, array('fileDownload' => $dst_file));
            
            if ($response->isOK() === FALSE) 
            {
                throw new Exception("Cannot get object " . $object_name . " from bucket " . $vat_config['AWS_S3_DATA_BUCKET']);
            }
        } 
        else 
        {
            $src_file = sprintf("%s/%d/vat.%d/%s.vcf", $vat_config['VAT_DATA_DIR'], $use_set_id, $use_set_id, $gene_id);
            $dst_file = sprintf("%s/vat.%d/%s.vcf", $this->_working_dir, $use_set_id, $gene_id);
            
            if (copy($src_file, $dst_file) === FALSE) 
            {
                throw new Exception("Cannot copy " . $src_file . " to " . $dst_file . ".");
            }
        }
    }
    
    /**
     * Retrieves a raw VCF file from AWS_S3_RAW_BUCKET if S3 support is enabled
     * or from WEB_DATA_RAW_DIR if S3 support is disabled
     * 
     * @param string $filename
     * @throws Exception if copy or upload is unsuccessful
     */
    public function get_raw($filename)
    {
        global $vat_config;
        
        assert(file_exists($this->_working_dir));
        
        if ($vat_config['AWS_USE_S3'] == 'true') 
        {
            $object_name = $filename;
            $dst_file = sprintf("%s/%s", $this->_working_dir, $filename);
            
            $response = $this->_s3->get_object($vat_config['AWS_S3_RAW_BUCKET'], $object_name, array('fileDownload' => $dst_file));
            
            if ($response->isOK() === FALSE) 
            {
                throw new Exception("Cannot get object " . $object_name . " from bucket " . $vat_config['AWS_S3_RAW_BUCKET']);
            }
        } 
        else 
        {
            $src_file = sprintf("%s/%s", $vat_config['WEB_DATA_RAW_DIR'], $filename);
            $dst_file = sprintf("%s/%s", $this->_working_dir, $filename);
            
            if (copy($src_file, $dst_file) === FALSE) 
            {
                throw new Exception("Cannot copy " . $src_file . " to " . $dst_file . ".");
            }
        }
    }
    
    /**
     * If S3 support is enabled, uploads set of data files to AWS_S3_DATA_BUCKET
     * with the current set ID prepended to the key names. If S3 support is
     * disabled, copies the set of data files to WEB_DATA_DIR.  
     * 
     * @throws Exception if upload or copy is unsuccessful
     */
    public function push_data()
    {
        global $vat_config;
        
        $set_id = $this->_set_id;
        
        if ($vat_config['AWS_USE_S3'] == 'true') 
        {
            $bucket = $vat_config['AWS_S3_DATA_BUCKET'];
            
            // Raw
            $file_name = sprintf("%s/vat.%d.raw.vcf", $this->_working_dir, $set_id);
            if ( ! file_exists($file_name)) 
            {
                throw new Exception("File " . $file_name . " expected but does not exist.");
            }
            $object_name = sprintf("%d/vat.%d.raw.vcf", $set_id, $set_id);
            $this->_s3->batch()->create_object($bucket, $object_name, array('fileUpload' => $file_name));
            
            // svMapper
            $file_name = sprintf("%s/vat.%d.vcf", $this->_working_dir, $set_id);
            if ( ! file_exists($file_name)) 
            {
                throw new Exception("File " . $file_name . " expected but does not exist.");
            }
            $object_name = sprintf("%d/vat.%d.vcf", $set_id, $set_id);
            $this->_s3->batch()->create_object($bucket, $object_name, array('fileUpload' => $file_name));
            
            // bgzip
            $file_name = sprintf("%s/vat.%d.vcf.gz", $this->_working_dir, $set_id);
            if ( ! file_exists($file_name)) 
            {
                throw new Exception("File " . $file_name . " expected but does not exist.");
            }
            $object_name = sprintf("%d/vat.%d.vcf.gz", $set_id, $set_id);
            $this->_s3->batch()->create_object($bucket, $object_name, array('fileUpload' => $file_name));
            
            // tabix
            $file_name = sprintf("%s/vat.%d.vcf.gz.tbi", $this->_working_dir, $set_id);
            if ( ! file_exists($file_name)) 
            {
                throw new Exception("File " . $file_name . " expected but does not exist.");
            }
            $object_name = sprintf("%d/vat.%d.vcf.gz.tbi", $set_id, $set_id);
            $this->_s3->batch()->create_object($bucket, $object_name, array('fileUpload' => $file_name));
            
            // geneSummary
            $file_name = sprintf("%s/vat.%d.geneSummary.txt", $this->_working_dir, $set_id);
            if ( ! file_exists($file_name)) 
            {
                throw new Exception("File " . $file_name . " expected but does not exist.");
            }
            $object_name = sprintf("%d/vat.%d.geneSummary.txt", $set_id, $set_id);
            $this->_s3->batch()->create_object($bucket, $object_name, array('fileUpload' => $file_name));
            
            // sampleSummary
            $file_name = sprintf("%s/vat.%d.sampleSummary.txt", $this->_working_dir, $set_id);
            if ( ! file_exists($file_name)) 
            {
                throw new Exception("File " . $file_name . " expected but does not exist.");
            }
            $object_name = sprintf("%d/vat.%d.sampleSummary.txt", $set_id, $set_id);
            $this->_s3->batch()->create_object($bucket, $object_name, array('fileUpload' => $file_name));
            
            // gene subsets and images
            $dir_name = sprintf("%s/vat.%d", $this->_working_dir, $set_id);
            if ( ! file_exists($dir_name)) 
            {
                throw new Exception("Directory " . $dir_name . "expected but does not exist");
            }
            $it = new RecursiveDirectoryIterator($dir_name);
            foreach ($it as $file) 
            {
                $parts = explode(DIRECTORY_SEPARATOR, $file);
                $basename = array_pop($parts);
                
                if ($it->isDir()) 
                {
                    assert($basename == "." || $basename == "..");
                    continue;
                }
                
                $object_name = sprintf("%d/vat.%d/%s", $set_id, $set_id, $basename);
                $this->_s3->batch()->create_object($bucket, $object_name, array('fileUpload' => $file));
            }
            
            $response = $this->_s3->batch()->send();
            if ($response->areOK() === FALSE) 
            {
                throw new Exception("push_data to bucket " . $bucket . " unsuccessful");
            }
        } 
        else 
        {
            if (mkdir($vat_config['WEB_DATA_DIR'] . "/" . $set_id) == FALSE) 
            {
                throw new Exception("Cannot mkdir directory " . $vat_config['WEB_DATA_DIR'] . '/' . $set_id);
            }
            
            // raw
            $src_file = sprintf("%s/vat.%d.raw.vcf", $this->_working_dir, $set_id);
            if ( ! file_exists($src_file)) 
            {
                throw new Exception("File " . $src_file . " expected but does not exist.");
            }
            $dst_file = sprintf("%s/%d/vat.%d.raw.vcf", $vat_config['WEB_DATA_DIR'], $set_id, $set_id);
            if (copy($src_file, $dst_file) === FALSE) 
            {
                throw new Exception("Cannot move " . $src_file . " to " . $dst_file . ".");
            }
            
            // svMapper
            $src_file = sprintf("%s/vat.%d.vcf", $this->_working_dir, $set_id);
            if ( ! file_exists($src_file)) 
            {
                throw new Exception("File " . $src_file . " expected but does not exist.");
            }
            $dst_file = sprintf("%s/%d/vat.%d.vcf", $vat_config['WEB_DATA_DIR'], $set_id, $set_id);
            if (copy($src_file, $dst_file) === FALSE) 
            {
                throw new Exception("Cannot move " . $src_file . " to " . $dst_file . ".");
            }
            
            // bgzip
            $src_file = sprintf("%s/vat.%d.vcf.gz", $this->_working_dir, $set_id);
            if ( ! file_exists($src_file)) 
            {
                throw new Exception("File " . $src_file . " expected but does not exist.");
            }
            $dst_file = sprintf("%s/%d/vat.%d.vcf.gz", $vat_config['WEB_DATA_DIR'], $set_id, $set_id);
            if (copy($src_file, $dst_file) === FALSE) 
            {
                throw new Exception("Cannot move " . $src_file . " to " . $dst_file . ".");
            }
            
            // tabix
            $src_file = sprintf("%s/vat.%d.vcf.gz.tbi", $this->_working_dir, $set_id);
            if ( ! file_exists($src_file)) 
            {
                throw new Exception("File " . $src_file . " expected but does not exist.");
            }
            $dst_file = sprintf("%s/%d/vat.%d.vcf.gz.tbi", $vat_config['WEB_DATA_DIR'], $set_id, $set_id);
            if (copy($src_file, $dst_file) === FALSE) 
            {
                throw new Exception("Cannot move " . $src_file . " to " . $dst_file . ".");
            }
            
            // geneSummary
            $src_file = sprintf("%s/vat.%d.geneSummary.txt", $this->_working_dir, $set_id);
            if ( ! file_exists($src_file)) 
            {
                throw new Exception("File " . $src_file . " expected but does not exist.");
            }
            $dst_file = sprintf("%s/%d/vat.%d.geneSummary.txt", $vat_config['WEB_DATA_DIR'], $set_id, $set_id);
            if (copy($src_file, $dst_file) === FALSE) 
            {
                throw new Exception("Cannot move " . $src_file . " to " . $dst_file . ".");
            }
            
            // sampleSummary
            $src_file = sprintf("%s/vat.%d.sampleSummary.txt", $this->_working_dir, $set_id);
            if ( ! file_exists($src_file)) 
            {
                throw new Exception("File " . $src_file . " expected but does not exist.");
            }
            $dst_file = sprintf("%s/%d/vat.%d.sampleSummary.txt", $vat_config['WEB_DATA_DIR'], $set_id, $set_id);
            if (copy($src_file, $dst_file) === FALSE) 
            {
                throw new Exception("Cannot move " . $src_file . " to " . $dst_file . ".");
            }
            
            // gene subsets and images
            if (mkdir($vat_config['WEB_DATA_DIR'] . '/' . $set_id . '/vat.' . $set_id) === FALSE) 
            {
                throw new Exception("Cannot mkdir image directory for ". $set_id);
            }
            $dir_name = sprintf("%s/vat.%d", $this->_working_dir, $set_id);
            if ( ! file_exists($src_file)) 
            {
                throw new Exception("File " . $src_file . " expected but does not exist.");
            }
            $it = new RecursiveDirectoryIterator($dir_name);
            foreach ($it as $file) 
            {
                $parts = explode(DIRECTORY_SEPARATOR, $file);
                $basename = array_pop($parts);
                
                if ($it->isDir()) 
                {
                    assert($basename == "." || $basename == "..");
                    continue;
                }
                
                $dst_name = sprintf("%s/%d/vat.%d/%s", $vat_config['WEB_DATA_DIR'], $set_id, $set_id, $basename);
                
                if (copy($file, $dst_name) === FALSE) 
                {
                    throw new Exception("Cannot move " . $src_file . " to " . $dst_file);
                }
            }
        }
    }
    
    /**
     * Puts an uploaded raw VCF file from the working directory to the
     * AWS_S3_RAW_BUCKET if S3 support is enabled or copies to WEB_DATA_RAW_DIR
     * if S3 support is disabled.
     * 
     * @param string $filename
     * @throws Exception if upload or copy is unsuccessful
     */
    public function push_raw($filename)
    {
        global $vat_config;
        
        if ($vat_config['AWS_USE_S3'] == 'true') 
        {
            $object_name = $filename;
            $src_file = sprintf("%s/%s", $this->_working_dir, $filename);
            
            $response = $this->_s3->create_object($vat_config['AWS_S3_RAW_BUCKET'], 
                $object_name, array('fileUpload' => $src_file));
            
            if ($response->isOk() === FALSE) 
            {
                throw new Exception("Cannot put file " . $src_file . " to bucket " . $vat_config['AWS_S3_RAW_BUCKET']);
            }
        } 
        else 
        {
            $src_file = sprintf("%s/%s", $this->_working_dir, $filename);
            $dst_file = sprintf("%s/%d/%s", $vat_config['WEB_DATA_RAW_DIR'], $this->_set_id, $filename);
            
            if (copy($src_file, $dst_file) === FALSE) 
            {
                throw new Exception("Cannot copy " . $src_file . " to " . $dst_file . ".");
            }
        }
    }
    
    /**
     * Deletes a raw VCF file from AWS_S3_RAW_BUCKET if S3 support is enabled
     * or from WEB_DATA_RAW_DIR if S3 support is disabled 
     * 
     * @param unknown_type $filename
     * @throws Exception
     */
    public function delete_raw($filename)
    {
        global $vat_config;
        
        if ($vat_config['AWS_USE_S3'] == 'true') 
        {
            if ($this->_s3->if_object_exists($vat_config['AWS_S3_RAW_BUCKET'], $filename)) 
            {
                $response = $this->_s3->delete_object($vat_config['AWS_S3_RAW_BUCKET'], $filename);
                if ($response->isOK() === FALSE) 
                {
                    throw new Exception("Cannot delete raw file " . $filename . " from " . $vat_config['AWS_S3_RAW_BUCKET']);
                }
            }
        } 
        else 
        {
            $path = $vat_config['WEB_DATA_RAW_DIR'] . '/' . $filename;
            if (file_exists($path)) 
            {
                if (unlink($path) === NULL) 
                {
                    throw new Exception("Cannot delete raw file " . $path);
                }
            }
        }
    }
    
    /**
     * Deletes the working directory for the current process
     * 
     * @throws Exception
     */
    public function clear_working()
    {
        global $vat_config;
        
        if (cleardir($vat_config['WEB_DATA_WORKING_DIR'] . '/' . $this->_proc_id) === FALSE) {
            throw new Exception("Cannot delete working directory");
        }
    }
    
    /**
     * 
     * Enter description here ...
     */
    public function get_list_raw()
    {
        global $vat_config;
    }
    
    /**
     * 
     * Enter description here ...
     */
    public function get_list_data()
    {
        global $vat_config;
        
    }
    
    /**
     * Destructor
     */
    public function __destruct()
    {
        global $vat_config;
        
        rrmdir($this->_working_dir);
        
        if ($vat_config['AWS_USE_S3'] == TRUE) {
            $this->_s3 = NULL;
        }
        
    }
}

?>
