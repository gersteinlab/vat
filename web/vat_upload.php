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
require_once 'lib/aws/sdk.class.php';

/**
 * Globals
 */
$values = array();
$errors = array(
    'upFile' => "",
);
$fatal_error = "";

/**
 * 
 */
function validate_form($files)
{
    global $errors;
    
    $success = TRUE;
    
    if ($_FILES['uploadFile']['tmp_name'] == '')
    {
        $errors['upFile'] = 'No VCF file uploaded';
        $success = FALSE;
    }
    
    return $success;
}

/**
 * 
 */
function write_files($proc_id, $vcf_file)
{
    global $vat_config, $fatal_error;
    
    if (!mkdir($vat_config['WEB_DATA_DIR'].'vat.'.$proc_id))
    {
        $fatal_error = "Could not mkdir directory for file";
        return FALSE;
    }
    
    $fp = fopen($vat_config['WEB_DATA_DIR'].'/vat.'.$proc_id.'.raw.vcf', 'w');
    if (!$fp)
    {
        $fatal_error = "Could not open raw file to write";
        return FALSE;
    }
    
    fprintf($fp, $s, $vcf_file);
    fclose($fp);
    
    return TRUE;
}

/**
 * 
 */
function annotate_variants($proc_id, $program, $annotation_file)
{
    global $vat_config, $fatal_error;
    
    if ($program == 'svMapper')
    {
        $cmd = sprintf('%s/%s %s/%s.interval < %s/vat.%d.raw.vcf > %s/vat.%d.vcf',
                       $vat_config['WEB_DATA_DIR'], $program, 
                       $vat_config['WEB_DATA_DIR'], $annotation_file,
                       $vat_config['WEB_DATA_DIR'], $proc_id,
                       $vat_config['WEB_DATA_DIR'], $proc_id);
    }
    else
    {
        $cmd = sprintf('%s/%s %s/%s.interval %s/%s.fa < %s/vat.%d.raw.vcf > %s/vat.%d.vcf',
                       $vat_config['WEB_DATA_DIR'], $program,
                       $vat_config['WEB_DATA_DIR'], $annotation_file,
                       $vat_config['WEB_DATA_DIR'], $annotation_file,
                       $vat_config['WEB_DATA_DIR'], $proc_id,
                       $vat_config['WEB_DATA_DIR'], $proc_id);
    }
    
    system($cmd);
}

/**
 * 
 */
function index_files($proc_id)
{
    global $vat_config, $fatal_error;
    
    $cmd = sprintf('%s/bgzip -c %s/vat.%d.vcf > %s/vat.%d.vcf.gz',
                   $vat_config['WEB_DATA_DIR'], 
                   $vat_config['WEB_DATA_DIR'], $proc_id,
                   $vat_config['WEB_DATA_DIR'], $proc_id);
    system($cmd);
    
    $cmd = sprintf('%s/tabix -p vcf %s/vat.%d.vcf.gz',
                   $vat_config['WEB_DATA_DIR'],
                   $vat_config['WEB_DATA_DIR'], $proc_id);
    system($cmd);
}


/**
 * 
 */
function create_summary_file($proc_id, $annotation_file)
{
    global $vat_config, $fatal_error;
    
    $cmd = sprintf('%s/vcfSummary %s/vat.%d.vcf.gz %s/%s.interval',
                   $vat_config['WEB_DATA_DIR'],
                   $vat_config['WEB_DATA_DIR'], $proc_id,
                   $vat_config['WEB_DATA_DIR'], $annotation_file);
    system($cmd);
}

/**
 * 
 */
function generate_images($proc_id, $annotation_file)
{
    global $vat_config, $fatal_error;
    
    $cmd = sprintf('%s/vcf2images %s/vat.%d.vcf.gz %s/%s.interval %s/vat.%d',
                   $vat_config['WEB_DATA_DIR'],
                   $vat_config['WEB_DATA_DIR'], $proc_id,
                   $vat_config['WEB_DATA_DIR'], $annotation_file,
                   $vat_config['WEB_DATA_DIR'], $proc_id);
    system($cmd);
    
}

/**
 * 
 */
function subset_file()
{
    global $vat_config, $fatal_error;
    
    $cmd = sprintf('%s/vcfSubsetByGene %s/vat.%d.vcf.gz %s/%s.interval %s/vat.%d',
                   $vat_config['WEB_DATA_DIR'],
                   $vat_config['WEB_DATA_DIR'], $proc_id,
                   $vat_config['WEB_DATA_DIR'], $annotation_file,
                   $vat_config['WEB_DATA_DIR'], $proc_id);
    system($cmd);
}

?>

<html>
<head>
	<title>VAT</title>
	<link rel="stylesheet" href="css/style.css" />
</head>
<body>

<? if ($_POST): ?>
	<? $upload_success = validate_form(); ?>
<? endif; ?>

<? if ($upload_success): ?>
	<center>
		<h1>Processing uploaded data <img src="image/processing.gif" id="processing"></h1>
		<? 
		$vcf_file        = $_POST['upFile'];
		$program         = $_POST['variantType'];
		$annotation_file = $_POST['annotationFile'];
		$proc_id         = posix_getpid();                                                    // FIXME!!!!!
		?>
		
		Step [1/6]: Writing file...
		<? flush(); ?>
		<? if (!($ret = write_file($proc_id, $vcf_file))): ?>
		    <span class="error">Writing file failed</span>
		    <? die(); ?>
		<? endif; ?>
		<img src="image/check.png" height="15" width="15"><br><br><br>
		
		<h3>Step [2/6]: Annotating variants...</h3>
		<? flush(); ?>
		<? if (!($ret = annotate_variants($proc_id, $program, $annotation_file))): ?>
		    <span class="error">Annotating variants failed</span>
		    <? die(); ?>
		<? endif; ?>
		<img src="image/check.png" height="15" width="15"><br><br><br>
		
		<h3>Step [3/6]: Indexing files...</h3>
		<? flush(); ?>
		<? if (!($ret = index_files($proc_id))): ?>
		    <span class="error">Indexing files failed</span>
		    <? die(); ?>
		<? endif; ?>
		<img src="image/check.png" height="15" width="15"><br><br><br>
		
		<h3>Step [4/6]: Creating variant summay file...</h3>
		<? flush(); ?>
		<? if (!($ret = create_summary_file($proc_id, $annotation_file))): ?>
		    <span class="error">Creating variant summay file failed</span>
		    <? die(); ?>
		<? endif; ?>
		<img src="image/check.png" height="15" width="15"><br><br><br>
		
		<h3>Step [5/6]: Generating images...</h3>
		<? flush(); ?>
		<? if (!($ret = generate_images($proc_id, $annotation_file))): ?>
		    <span class="error">Generating images failed</span>
		    <? die(); ?>
		<? endif; ?>
		<img src="image/check.png" height="15" width="15"><br><br><br>
		
		<h3>Step [6/6]: Subsetting file...</h3>
		<? flush(); ?>
		<? if (!($ret = subset_file())): ?>
		    <span class="error">Subsetting file failed</span>
		    <? die(); ?>
		<? endif; ?>
		<img src="image/check.png" height="15" width="15"><br><br><br>
		
		<a href="vat.php?mode=process&dataSet=vat.<? echo $procId; ?>&annotationSet=<? echo $annotationFile; ?>&type=coding">View results</a>
		
		<script type="text/javascript" charset="utf-8"> 
    	document.getElementById("processing").style.visibility = "hidden";
    	</script>
	</center>
	
<? else: ?>

	<h4><center>[<a href=http://vat.gersteinlab.org>VAT Main Page</a>]</center></h4>
	<h1>Variation Annotation Tool (VAT)</h1>
	<form action="vat_upload.php?process" method="POST" enctype="multipart/form-data">
		<br />
		<b>VCF file upload</b> 
		(Examples<sup>&Dagger;</sup>: [<a href="http://homes.gersteinlab.org/people/lh372/VAT/1000genomes_pilot_snps.sample.vcf">SNPs</a>] [<a href="http://homes.gersteinlab.org/people/lh372/VAT/1000genomes_pilot_indels.sample.vcf">Indels</a>] [<a href="http://homes.gersteinlab.org/people/lh372/VAT/1000genomes_pilot_svs.sample.vcf">SVs</a>]):&nbsp;&nbsp;
		
		<input type="file" name="upFile" /><? echo $errors['upFile']; ?>
		<br><br><br>
		<b>Variant type</b>:&nbsp;&nbsp;
		<select name=variantType>
			<option value=snpMapper checked>SNPs</option>
			<option value=indelMapper>Indels</option>
			<option value=svMapper>SVs</option>
		</select>
		<br><br><br>
		<b>Annotation file</b>:&nbsp;&nbsp;
		<select name=annotationFile>
			<option value=gencode3b>GENCODE (version 3b; hg18)</option>
			<option value=gencode3c>GENCODE (version 3c; hg19)</option>
			<option value=gencode4>GENCODE (version 4; hg19)</option>
			<option value=gencode5>GENCODE (version 5; hg19)</option>
			<option value=gencode6>GENCODE (version 6; hg19)</option>
			<option value=gencode7>GENCODE (version 7; hg19)</option>
		</select>
		<br><br><br>
		<input type="submit" value="Submit" />
		<input type="reset" value="Reset" />
	</form>
	<br /><br /><br /><br /><br /><br /><br />_________________<br />
	<fn>
		<sup>&Dagger;</sup> - The example files were obtained from the <a href=http://www.1000genomes.org>1000 Genomes Pilot Project</a>. The genome coordinates are based on hg18.
	</fn>
	
<? endif; ?>

</body>
</html>