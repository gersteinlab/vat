<?php 
/**
 * Web-based upload program and driver for the [Variant Annotation Tool][ref-vat].
 * This script processes the upload and runs the uploaded file through the
 * VAT pipeline.
 * 
 * [ref-vat]: http://vat.gersteinlab.org
 * 
 * @package    VAT
 * @author     David Z. Chen
 * @copyright  (c) 2011 Gerstein Lab
 * @license    ???
 */

require_once 'lib/config.php';
require_once 'lib/util.php';
require_once 'lib/vatutil.php';

if ($vat_config['USE_S3'] === TRUE):
require_once 'lib/s3.php';
endif;


/**
 * Globals
 */
define('VAT_UPLOAD_DEBUG', FALSE);

$values = array();
$errors = array(
    'upFile' => "",
);
$fatal_error = array();
$uploaded_file = "";


/*
 * Display error if max file size set by web server configuration has been
 * exceeded by the uploaded file.
 * TODO: Add in documentation information about upload file size for PHP
 */
if (empty($_POST) && empty($_FILES) && 
    isset($_SERVER['REQUEST_METHOD']) && 
    $_SERVER['REQUEST_METHOD'] == 'POST')
{   
    $poids_max = ini_get('post_max_size');
    echo "Max upload file size " . $poids_max . " exceeded. Please configure "
        ."your php.ini to allow uploads of larger files.\n";
}

/**
 * Validates submitted upload form
 * 
 * @return bool
 */
function validate_form()
{
    global $errors;
    
    $success = TRUE;
    
    if ($_FILES['upFile']['tmp_name'] == '')
    {
        $errors['upFile'] = 'No VCF file uploaded';
        $success = FALSE;
    }
    
    return $success;
}


/**
 * Sanitize uploaded filename
 *
 * @param string $file_name
 * @return string
 */
function sanitize_file_name($file_name)
{
    $file_name = stripslashes($file_name);
    $file_name = str_replace("'", "", $file_name);
    
    return $file_name;
}

/**
 * Process the uploaded file, validate form, and copy uploaded file into
 * working directory
 * 
 * @return bool
 */
function handle_upload()
{
    global $fatal_error, $uploaded_file, $vat_config;
    
    $file_name = sanitize_file_name($_FILES['upFile']['name']);
    
    if (($retval = validate_form()) === FALSE)
    {
        return FALSE;
    }
    
    if (($retval = copy($_FILES['upFile']['tmp_name'], $vat_config['WEB_DATA_DIR']."/".$file_name)) === FALSE)
    {
        array_push($fatal_error, "Cannot copy uploaded file ".$file_name);
        return FALSE;
    }
    
    $uploaded_file = $file_name;
    
    return TRUE;
}

/**
 * Step 1/6: Write files
 * 
 * @param int $proc_id
 * @param string $vcf_file
 * @return bool
 */
function write_files($proc_id, $vcf_file)
{
    global $vat_config, $uploaded_file, $fatal_error;
    
if (VAT_UPLOAD_DEBUG === TRUE):
    echo "mkdir ".$vat_config['WEB_DATA_DIR'].'/vat.'.$proc_id."\n";
endif;

    if ( ! mkdir($vat_config['WEB_DATA_DIR'].'/vat.'.$proc_id))
    {
        array_push($fatal_error, "Could not mkdir directory for file");
        return FALSE;
    }
    
    if (($retval = rename($vat_config['WEB_DATA_DIR'].'/'.$uploaded_file, 
                          $vat_config['WEB_DATA_DIR'].'/vat.'.$proc_id.".raw.vcf")) === FALSE)
    {
        return FALSE;
    }
    
    return TRUE;
}

/**
 * Step 2/6: Annotating variants
 * 
 * @param int $proc_id
 * @param string $program
 * @param string $annotation_file
 * @return bool 
 */
function annotate_variants($proc_id, $program, $annotation_file)
{
    global $vat_config, $fatal_error;
    
    if ($program == 'svMapper')
    {
        $cmd = sprintf('%s/%s %s/%s.interval < %s/vat.%d.raw.vcf > %s/vat.%d.vcf 2>&1',
                       $vat_config['VAT_EXEC_DIR'], $program, 
                       $vat_config['WEB_DATA_DIR'], $annotation_file,
                       $vat_config['WEB_DATA_DIR'], $proc_id,
                       $vat_config['WEB_DATA_DIR'], $proc_id);
    }
    else
    {
        $cmd = sprintf('%s/%s %s/%s.interval %s/%s.fa < %s/vat.%d.raw.vcf > %s/vat.%d.vcf 2>&1',
                       $vat_config['VAT_EXEC_DIR'], $program,
                       $vat_config['WEB_DATA_DIR'], $annotation_file,
                       $vat_config['WEB_DATA_DIR'], $annotation_file,
                       $vat_config['WEB_DATA_DIR'], $proc_id,
                       $vat_config['WEB_DATA_DIR'], $proc_id);
    }
    
    
    exec($cmd, $output, $retval);
    
if (VAT_UPLOAD_DEBUG === TRUE):
    echo $cmd . " $retval\n";
    var_dump($output);
endif;
    
    return TRUE;
}

/**
 * Step 3/6: Index files
 * 
 * @param int $proc_id
 * @return bool
 */
function index_files($proc_id)
{
    global $vat_config, $fatal_error;
    
    $cmd = sprintf('%s/bgzip -c %s/vat.%d.vcf > %s/vat.%d.vcf.gz 2>&1',
                   $vat_config['TABIX_DIR'], 
                   $vat_config['WEB_DATA_DIR'], $proc_id,
                   $vat_config['WEB_DATA_DIR'], $proc_id);
    
    exec($cmd, $output, $retval);
    
if (VAT_UPLOAD_DEBUG === TRUE):
    echo $cmd . " $retval<br />\n";
    var_dump($output);
endif;
    
    $cmd = sprintf('%s/tabix -p vcf %s/vat.%d.vcf.gz 2>&1',
                   $vat_config['TABIX_DIR'],
                   $vat_config['WEB_DATA_DIR'], $proc_id);
   
    exec($cmd, $output, $retval);
    
if (VAT_UPLOAD_DEBUG === TRUE):
    echo $cmd . " $retval\n";
    var_dump($output);
endif;
    
    return TRUE;
}


/**
 * Step 4/6: Create summary files
 * 
 * @param int $proc_id
 * @param string $annotation_file
 * @return bool
 */
function create_summary_file($proc_id, $annotation_file)
{
    global $vat_config, $fatal_error;
    
    $cmd = sprintf('%s/vcfSummary %s/vat.%d.vcf.gz %s/%s.interval 2>&1',
                   $vat_config['VAT_EXEC_DIR'],
                   $vat_config['WEB_DATA_DIR'], $proc_id,
                   $vat_config['WEB_DATA_DIR'], $annotation_file);

    exec($cmd, $output, $retval);
    
if (VAT_UPLOAD_DEBUG === TRUE):
    echo $cmd . " $retval\n";
    var_dump($output);
endif;
    
    return TRUE;
}

/**
 * Steph 5/6: Generate images
 * 
 * @param int $proc_id
 * @param string $annotation_file
 * @return bool
 */
function generate_images($proc_id, $annotation_file)
{
    global $vat_config, $fatal_error;
    
    $cmd = sprintf('%s/vcf2images %s/vat.%d.vcf.gz %s/%s.interval %s/vat.%d 2>&1',
                   $vat_config['VAT_EXEC_DIR'],
                   $vat_config['WEB_DATA_DIR'], $proc_id,
                   $vat_config['WEB_DATA_DIR'], $annotation_file,
                   $vat_config['WEB_DATA_DIR'], $proc_id);

    exec($cmd, $output, $retval);
    
if (VAT_UPLOAD_DEBUG === TRUE):
    echo $cmd . " $retval\n";
    var_dump($output);
endif;
    
    return TRUE;
}

/**
 * Step 6/6: Subset file
 * 
 * @param int $param_id
 * @param string $annotation_file
 * @return bool
 */
function subset_file($proc_id, $annotation_file)
{
    global $vat_config, $fatal_error;
    
    $cmd = sprintf('%s/vcfSubsetByGene %s/vat.%d.vcf.gz %s/%s.interval %s/vat.%d 2>&1',
                   $vat_config['VAT_EXEC_DIR'],
                   $vat_config['WEB_DATA_DIR'], $proc_id,
                   $vat_config['WEB_DATA_DIR'], $annotation_file,
                   $vat_config['WEB_DATA_DIR'], $proc_id);
    
    exec($cmd, $output, $retval);
    
if (VAT_UPLOAD_DEBUG === TRUE):
    echo $cmd . " $retval\n";
    var_dump($output);
endif;
    
    return TRUE;
}

/**
 * Saves results to Amazon S3 bucket
 * 
 * @param string $proc_id
 * @return bool
 */
function s3_push($proc_id)
{
    assert($vat_config['VAT_EXEC_FIR'] === TRUE);
    
    
    
    return TRUE;
}

?>

<html>
<head>
	<title>VAT</title>
	<link rel="stylesheet" href="css/style.css" />
</head>
<body>
<? flush_buffers(); ?>


<? $upload_success = FALSE; ?>
<? if ($_POST): ?>
	<? $upload_success = handle_upload(); ?>
<? endif; ?>

<? if ($_POST && $upload_success): ?>
	<center>
		<h1>Processing uploaded data <img src="image/processing.gif" id="processing"></h1>
		<? 
		$vcf_file        = $uploaded_file;
		$program         = $_POST['variantType'];
		$annotation_file = $_POST['annotationFile'];
		$proc_id         = rand();
		
if (VAT_UPLOAD_DEBUG === TRUE):
		$pid = posix_getpid();
		
		echo $pid;
endif;
		?>
		
		
		Step [1/6]: Writing file...
		<? flush_buffers(); ?>
		<? if (($ret = write_files($proc_id, $vcf_file)) === FALSE): ?>
		    <span class="error">Writing file failed</span>
		    <? die(); ?>
		<? endif; ?>
		<img src="image/check.png" height="15" width="15"><br><br><br>
		
		Step [2/6]: Annotating variants...
		<? flush_buffers(); ?>
		<? if (($ret = annotate_variants($proc_id, $program, $annotation_file)) === FALSE): ?>
		    <span class="error">Annotating variants failed</span>
		    <? die(); ?>
		<? endif; ?>
		<img src="image/check.png" height="15" width="15"><br><br><br>
		
		Step [3/6]: Indexing files...
		<? flush_buffers(); ?>
		<? if (($ret = index_files($proc_id)) === FALSE): ?>
		    <span class="error">Indexing files failed</span>
		    <? die(); ?>
		<? endif; ?>
		<img src="image/check.png" height="15" width="15"><br><br><br>
		
		Step [4/6]: Creating variant summay file...
		<? flush_buffers(); ?>
		<? if (($ret = create_summary_file($proc_id, $annotation_file)) === FALSE): ?>
		    <span class="error">Creating variant summay file failed</span>
		    <? die(); ?>
		<? endif; ?>
		<img src="image/check.png" height="15" width="15"><br><br><br>
		
		Step [5/6]: Generating images...
		<? flush_buffers(); ?>
		<? if (($ret = generate_images($proc_id, $annotation_file)) === FALSE): ?>
		    <span class="error">Generating images failed</span>
		    <? die(); ?>
		<? endif; ?>
		<img src="image/check.png" height="15" width="15"><br><br><br>
		
		Step [6/6]: Subsetting file...
		<? flush_buffers(); ?>
		<? if (($ret = subset_file($proc_id, $annotation_file)) === FALSE): ?>
		    <span class="error">Subsetting file failed</span>
		    <? die(); ?>
		<? endif; ?>
		<img src="image/check.png" height="15" width="15"><br><br><br>
		
		<? if ($vat_config['USE_S3'] === TRUE): ?>
		    <? flush_buffers(); ?>
		Saving results to S3...
			<? if (($ret = s3_push($proc_id)) === FALSE): ?>  
				<? die(); ?>
			<? endif; ?>
		<img src="image/check.png" height="15" width="15"><br><br><br>
		<? endif; ?>
		
		<a href="vat.php?mode=process&dataSet=vat.<? echo $proc_id; ?>&annotationSet=<? echo $annotation_file; ?>&type=coding">View results</a>
		
		<script type="text/javascript" charset="utf-8"> 
    	document.getElementById("processing").style.visibility = "hidden";
    	</script>
	</center>
	
<? else: ?>

	<h4><center>[<a href=http://vat.gersteinlab.org>VAT Main Page</a>]</center></h4>
	<h1>Variation Annotation Tool (VAT)</h1>
	
	<? if ( ! empty($fatal_error)): ?>
	<h3>Errors found</h3>
	<ul>
		<? foreach ($fatal_error as $error): ?>
		<li><span class="error"><? echo $error; ?></span></li>
		<? endforeach; ?>
	</ul>
	<? endif ?>
	
	<form action="vat_upload.php" method="POST" enctype="multipart/form-data">
		<br />
		<b>VCF file upload</b> 
		(Examples<sup>&Dagger;</sup>: [<a href="http://homes.gersteinlab.org/people/lh372/VAT/1000genomes_pilot_snps.sample.vcf">SNPs</a>] [<a href="http://homes.gersteinlab.org/people/lh372/VAT/1000genomes_pilot_indels.sample.vcf">Indels</a>] [<a href="http://homes.gersteinlab.org/people/lh372/VAT/1000genomes_pilot_svs.sample.vcf">SVs</a>]):&nbsp;&nbsp;
		
		<input type="file" name="upFile" /><? echo $errors['upFile']; ?>
		<br><br><br>
		<b>Variant type</b>:&nbsp;&nbsp;
		<select name="variantType">
			<option value="snpMapper" selected="selected">SNPs</option>
			<option value="indelMapper">Indels</option>
			<option value="svMapper">SVs</option>
		</select>
		<br><br><br>
		<b>Annotation file</b>:&nbsp;&nbsp;
		<select name="annotationFile">
			<option value="gencode3b">GENCODE (version 3b; hg18)</option>
			<option value="gencode3c">GENCODE (version 3c; hg19)</option>
			<option value="gencode4">GENCODE (version 4; hg19)</option>
			<option value="gencode5">GENCODE (version 5; hg19)</option>
			<option value="gencode6">GENCODE (version 6; hg19)</option>
			<option value="gencode7">GENCODE (version 7; hg19)</option>
		</select>
		<br><br><br>
		<input type="submit" value="Submit" />
		<input type="reset" value="Reset" />
	</form>
	<br /><br /><br /><br /><br /><br /><br />_________________<br />
	<fn>
		<sup>&Dagger;</sup> - The example files were obtained from the <a href="http://www.1000genomes.org">1000 Genomes Pilot Project</a>. The genome coordinates are based on hg18.
	</fn>
	
<? endif; ?>

</body>
</html>