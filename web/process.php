<?php


require_once 'lib/init.php';
require_once 'lib/util.php';
require_once 'lib/cfio.php';

$vat_upload_debug = FALSE;

/* ---------------------------------------------------------------------------
 * Globals
 */

/**
 * Array to hold fatal errors. Errors displayed and no action performed if array
 * is non-empty 
 * @var array
 */
$fatal_error = array();

/**
 * Name of uploaded file
 * @var string
 */
$upload_file = NULL;

/* ---------------------------------------------------------------------------
 * Functions
 */

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
function handle_upload($working_dir)
{
    global $fatal_error, $uploaded_file, $vat_config;

    $file_name = sanitize_file_name($_FILES['upFile']['name']);

    if (($retval = copy($_FILES['upFile']['tmp_name'], $working_dir . "/" . $file_name)) === FALSE)
    {
        array_push($fatal_error, "Cannot copy uploaded file " . $file_name);
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
function write_files($working_dir, $set_id, $vcf_file)
{
    global $vat_config, $uploaded_file, $fatal_error, $vat_upload_debug;

if ($vat_upload_debug === TRUE):
    echo "<pre>\n";
    echo "mkdir " . $working_dir . '/vat.' . $set_id."\n";
    echo "</pre>\n";
endif;

    if ( ! mkdir($working_dir . '/vat.' . $set_id))
    {
        array_push($fatal_error, "Could not mkdir directory for file");
        return FALSE;
    }

    if (($retval = rename($working_dir . '/' . $uploaded_file,
                          $working_dir . '/vat.' . $set_id . ".raw.vcf")) === FALSE)
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
function annotate_variants($working_dir, $set_id, $program, $annotation_file)
{
    global $vat_config, $fatal_error, $vat_upload_debug;

    if ($program == 'svMapper')
    {
        $cmd = sprintf('%s/%s %s/%s.interval < %s/vat.%d.raw.vcf > %s/vat.%d.vcf 2>&1',
                       $vat_config['VAT_EXEC_DIR'], $program,
                       $vat_config['WEB_DATA_REFERENCE_DIR'], $annotation_file,
                       $working_dir, $set_id,
                       $working_dir, $set_id);
    }
    else
    {
        $cmd = sprintf('%s/%s %s/%s.interval %s/%s.fa < %s/vat.%d.raw.vcf > %s/vat.%d.vcf 2>&1',
                       $vat_config['VAT_EXEC_DIR'], $program,
                       $vat_config['WEB_DATA_REFERENCE_DIR'], $annotation_file,
                       $vat_config['WEB_DATA_REFERENCE_DIR'], $annotation_file,
                       $working_dir, $set_id,
                       $working_dir, $set_id);
    }


    exec($cmd, $output, $retval);

if ($vat_upload_debug === TRUE):
    echo "<pre>\n";
    echo $cmd . " $retval\n";
    var_dump($output);
    echo "</pre>\n";
endif;

    return $retval == 0;
}

/**
 * Step 3/6: Index files
 *
 * @param int $proc_id
 * @return bool
 */
function index_files($working_dir, $set_id)
{
    global $vat_config, $fatal_error, $vat_upload_debug;

    $cmd = sprintf('%s/bgzip -c %s/vat.%d.vcf > %s/vat.%d.vcf.gz 2>&1',
                   $vat_config['TABIX_DIR'],
                   $working_dir, $set_id,
                   $working_dir, $set_id);

    exec($cmd, $output, $retval);

if ($vat_upload_debug === TRUE):
    echo "<pre>\n";
    echo $cmd . " $retval<br />\n";
    var_dump($output);
    echo "</pre>\n";
endif;

    if ($retval != 0) 
    {
        return FALSE;
    }

    $cmd = sprintf('%s/tabix -p vcf %s/vat.%d.vcf.gz 2>&1',
                   $vat_config['TABIX_DIR'],
                   $working_dir, $set_id);
     
    exec($cmd, $output, $retval);

if ($vat_upload_debug === TRUE):
    echo "<pre>\n";
    echo $cmd . " $retval\n";
    var_dump($output);
    echo "</pre>\n";
endif;

    return $retval == 0;
}


/**
 * Step 4/6: Create summary files
 *
 * @param int $proc_id
 * @param string $annotation_file
 * @return bool
 */
function create_summary_file($working_dir, $set_id, $annotation_file)
{
    global $vat_config, $fatal_error, $vat_upload_debug;

    $cmd = sprintf('%s/vcfSummary %s/vat.%d.vcf.gz %s/%s.interval 2>&1',
                   $vat_config['VAT_EXEC_DIR'],
                   $working_dir, $set_id,
                   $vat_config['WEB_DATA_REFERENCE_DIR'], $annotation_file);

    exec($cmd, $output, $retval);

if ($vat_upload_debug === TRUE):
    echo "<pre>\n";
    echo $cmd . " $retval\n";
    var_dump($output);
    echo "</pre>\n";
endif;

    return $retval == 0;
}

/**
 * Steph 5/6: Generate images
 *
 * @param int $proc_id
 * @param string $annotation_file
 * @return bool
 */
function generate_images($working_dir, $set_id, $annotation_file)
{
    global $vat_config, $fatal_error, $vat_upload_debug;

    $cmd = sprintf('%s/vcf2images %s/vat.%d.vcf.gz %s/%s.interval %s/vat.%d 2>&1',
                   $vat_config['VAT_EXEC_DIR'],
                   $working_dir, $set_id,
                   $vat_config['WEB_DATA_REFERENCE_DIR'], $annotation_file,
                   $working_dir, $set_id);

    exec($cmd, $output, $retval);

if ($vat_upload_debug === TRUE):
    echo "<pre>\n";
    echo $cmd . " $retval\n";
    var_dump($output);
    echo "</pre>\n";
endif;

    return $retval == 0;
}

/**
 * Step 6/6: Subset file
 *
 * @param int $param_id
 * @param string $annotation_file
 * @return bool
 */
function subset_file($working_dir, $set_id, $annotation_file)
{
    global $vat_config, $fatal_error, $vat_upload_debug;

    $cmd = sprintf('%s/vcfSubsetByGene %s/vat.%d.vcf.gz %s/%s.interval %s/vat.%d 2>&1',
                   $vat_config['VAT_EXEC_DIR'],
                   $working_dir, $set_id,
                   $vat_config['WEB_DATA_REFERENCE_DIR'], $annotation_file,
                   $working_dir, $set_id);

    exec($cmd, $output, $retval);

if ($vat_upload_debug === TRUE):
    echo "<pre>\n";
    echo $cmd . " $retval\n";
    var_dump($output);
    echo "</pre>\n";
endif;

    return $retval == 0;
}

/* ---------------------------------------------------------------------------
 * Controller section
 */

$cfio = new CFIO();

$upload_success = FALSE;
$annotation_file = NULL;
$program = NULL;
$process = TRUE;
$working_dir = NULL;

// Display error if max file size set by web server configuration has been 
// exceeded by the upload file
if (empty($_POST) && empty($_FILES) && isset($_SERVER['REQUEST_METHOD']) &&
    $_SERVER['REQUEST_METHOD'] == 'POST')
{
    $poids_max = ini_get('post_max_size');
    array_push($fatal_error, "Max upload file size " . $poids_max . " exceeded. Please configure your php.ini to allow uploads of larger files.");
}

if ( ! empty($_POST))
{
    $working_dir = $cfio->get_working_dir();
    
    // Invariant: handle_upload() sets $uploaded_file global variable
    $upload_success = handle_upload($working_dir);
    $annotation_file = $_POST['annotationFile'];
    $program = $_POST['variantType'];
    $process = isset($_POST['process']) ? TRUE : FALSE;
}
else if ( ! empty($_GET)) 
{
    if ( ! isset($_GET['inFile'])) 
    {
        array_push($fatal_error, "No input file specified");
    }
    if ( ! isset($_GET['annotationFile'])) 
    {
        array_push($fatal_error, "Annotation file not specified");
    }
    if ( ! isset($_GET['variantType'])) 
    {
        array_push($fatal_error, "Variant type not specified");
    }
    if ( isset($_GET['setId']))
    {
        $cfio->set_set_id($_GET['setId']);
    }
    
    if (empty($fatal_error))
    {
        try
        {
            $cfio->get_raw($_GET['inFile']);
        }
        catch (Exception $e)
        {
            array_push($fatal_error, "Could not get input file " . $_GET['inFile'] . ": " . $e->getMessage());
        }
        
        assert(file_exists($vat_config['WEB_DATA_WORKING_DIR'] . '/' . $cfio->get_proc_id()));
        
        $working_dir = $cfio->get_working_dir();
        $uploaded_file = $_GET['inFile'];
        $annotation_file = $_GET['annotationFile'];
        $program = $_GET['variantType'];
        $process = TRUE;
    }
}
else
{
    array_push($fatal_error, "Nothing to do");
}

?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>VAT - Variant Annotation Tool</title>
        <meta name="description" content="Variant annotation tool cloud service">
        <meta name="author" content="Gerstein Lab">
        
        <!-- HTML5 shim for IE 6-8 support -->
        <!--[if lt IE 9]>
            <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->
        
        <!-- Styles -->
        <link href="css/bootstrap.css" rel="stylesheet">
        <style type="text/css">
            body {
                padding-top: 60px;
            }
        </style>
        
        <!-- Fav and touch icons -->
        <link rel="shortcut icon" href="images/favicon.ico">
        <link rel="apple-touch-icon" href="images/apple-touch-icon.png">
        <link rel="apple-touch-icon" sizes="72x72" href="images/apple-touch-icon-72x72.png">
        <link rel="apple-touch-icon" sizes="114x114" href="images/apple-touch-icon-114x114.png">
 
    </head>
    <body>
        <div class="topbar">
            <div class="fill">
                <div class="container">
                    <a class="brand" href="index.php">VAT</a>
                    <ul class="nav">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="upload.php">Upload</a></li>
                        <li><a href="documentation.php">Documentation</a></li>
                        <li><a href="download.php">Download</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="container">
        
            <div class="page-header">
                <h1>Processing</h1>
            </div>

<? if ( ! empty($fatal_error)): ?>
            <div class="span16">
				<h2><font color="red">Errors found</font></h2>
				<ul>
    <? foreach ($fatal_errors as $error): ?>
					<li><? echo $error; ?></li>
	<? endforeach; ?>
				</ul>
			</div>
<? else: ?>
    <? if ($process === FALSE): ?>
			<div class="row">
                <div class="span1"><strong>1</strong></div>
                <div class="span10">Saving file</div>
                <div class="span5">
                	<img src="image/throbber.gif" id="proc-0-throbber" />
                	<span id="proc-0-done-label" style="visibility:hidden;" class="label success">DONE</span>
                	<span id="proc-0-error-label" style="visibility:hidden;" class="label important">FAILED</span>
                </div>
            </div>
	    
        <?php
        flush_buffers();
		try
		{
		    $cfio->push_raw($uploaded_file);
		}
		catch (Exception $e)
		{
        ?>
            <script type="text/javascript" charset="utf-8">
			document.getElementById("proc-0-throbber").style.visibility = "hidden";
			document.getElementById("proc-0-error-label").style.visibility = "visible";
            </script>
            <? die(); ?>
        <?
		}
        ?>
            <hr />
            <script type="text/javascript" charset="utf-8">
            document.getElementById("proc-0-throbber").style.visibility = "hidden";
            document.getElementById("proc-0-done-label").style.visibility = "visible";
            </script>
        	<div class="span16">
                <div class="well">
                    <a class="btn" href="upload.php">Back</a>
                    <a class="btn primary" href="process.php?inFile=<? echo $uploaded_file; ?>&annotationFile=<? echo $annotation_file; ?>&variantType=<? echo $program; ?>">Process file</a>
                </div>
            </div>

    <? else: ?>

            <div class="row">
                <div class="span1"><strong>1</strong></div>
                <div class="span10">Writing file</div>
                <div class="span5">
                	<img src="image/throbber.gif" id="proc-1-throbber" />
                	<span id="proc-1-done-label" style="visibility:hidden;" class="label success">DONE</span>
                	<span id="proc-1-error-label" style="visibility:hidden;" class="label important">FAILED</span>
                </div>
            </div>
        <? flush_buffers(); ?>
        <? if (write_files($working_dir, $cfio->get_set_id(), $uploaded_file) === FALSE): ?>
            <script type="text/javascript" charset="utf-8">
			document.getElementById("proc-1-throbber").style.visibility = "hidden";
			document.getElementById("proc-1-error-label").style.visibility = "visible";
            </script>
            <? die(); ?>
        <? endif; ?>
            <hr />
            <script type="text/javascript" charset="utf-8">
            document.getElementById("proc-1-throbber").style.visibility = "hidden";
            document.getElementById("proc-1-done-label").style.visibility = "visible";
            </script>
            
            <div class="row">
                <div class="span1"><strong>2</strong></div>
                <div class="span10">Annotating variants</div>
                <div class="span5">
                	<img src="image/throbber.gif" id="proc-2-throbber" />
                	<span id="proc-2-done-label" style="visibility:hidden;" class="label success">DONE</span>
                	<span id="proc-2-error-label" style="visibility:hidden;" class="label important">FAILED</span>
                </div>
            </div>
            <? flush_buffers(); ?>
            <? if (annotate_variants($working_dir, $cfio->get_set_id(), $program, $annotation_file) === FALSE): ?>
            <script type="text/javascript" charset="utf-8">
			document.getElementById("proc-2-throbber").style.visibility = "hidden";
			document.getElementById("proc-2-error-label").style.visibility = "visible";
            </script>
            <? die(); ?>
            <? endif; ?>
            <hr />
            <script type="text/javascript" charset="utf-8">
            document.getElementById("proc-2-throbber").style.visibility = "hidden";
            document.getElementById("proc-2-done-label").style.visibility = "visible";
            </script>
            
            <div class="row">
                <div class="span1"><strong>3</strong></div>
                <div class="span10">Indexing files</div>
                <div class="span5">
                	<img src="image/throbber.gif" id="proc-3-throbber" />
                	<span id="proc-3-done-label" style="visibility:hidden;" class="label success">DONE</span>
                	<span id="proc-3-error-label" style="visibility:hidden;" class="label important">FAILED</span>
                </div>
            </div>
            <? flush_buffers(); ?>
            <? if (index_files($working_dir, $cfio->get_set_id()) === FALSE): ?>
            <script type="text/javascript" charset="utf-8">
			document.getElementById("proc-3-throbber").style.visibility = "hidden";
			document.getElementById("proc-3-error-label").style.visibility = "visible";
            </script>
            <? die(); ?>
            <? endif; ?>
            <hr />
            <script type="text/javascript" charset="utf-8">
            document.getElementById("proc-3-throbber").style.visibility = "hidden";
            document.getElementById("proc-3-done-label").style.visibility = "visible";
            </script>
            
            <div class="row">
                <div class="span1"><strong>4</strong></div>
                <div class="span10">Creating variant summary file</div>
                <div class="span5">
                	<img src="image/throbber.gif" id="proc-4-throbber" />
                	<span id="proc-4-done-label" style="visibility:hidden;" class="label success">DONE</span>
                	<span id="proc-4-error-label" style="visibility:hidden;" class="label important">FAILED</span>
                </div>
            </div>
            <? flush_buffers(); ?>
            <? if (create_summary_file($working_dir, $cfio->get_set_id(), $annotation_file) === FALSE): ?>
            <script type="text/javascript" charset="utf-8">
			document.getElementById("proc-4-throbber").style.visibility = "hidden";
			document.getElementById("proc-4-error-label").style.visibility = "visible";
            </script>
            <? die(); ?>
            <? endif; ?>
            <hr />
            <script type="text/javascript" charset="utf-8">
            document.getElementById("proc-4-throbber").style.visibility = "hidden";
            document.getElementById("proc-4-done-label").style.visibility = "visible";
            </script>
            
            <div class="row">
                <div class="span1"><strong>5</strong></div>
                <div class="span10">Generating images</div>
                <div class="span5">
                	<img src="image/throbber.gif" id="proc-5-throbber" />
                	<span id="proc-5-done-label" style="visibility:hidden;" class="label success">DONE</span>
                	<span id="proc-5-error-label" style="visibility:hidden;" class="label important">FAILED</span>
                </div>
            </div>
            <? flush_buffers(); ?>
            <? if (generate_images($working_dir, $cfio->get_set_id(), $annotation_file) === FALSE): ?>
            <script type="text/javascript" charset="utf-8">
			document.getElementById("proc-5-throbber").style.visibility = "hidden";
			document.getElementById("proc-5-error-label").style.visibility = "visible";
            </script>
            <? die(); ?>
            <? endif; ?>
            <hr />
            <script type="text/javascript" charset="utf-8">
            document.getElementById("proc-5-throbber").style.visibility = "hidden";
            document.getElementById("proc-5-done-label").style.visibility = "visible";
            </script>
            
            <div class="row">
                <div class="span1"><strong>6</strong></div>
                <div class="span10">Subseting file</div>
                <div class="span5">
                	<img src="image/throbber.gif" id="proc-6-throbber" />
                	<span id="proc-6-done-label" style="visibility:hidden;" class="label success">DONE</span>
                	<span id="proc-6-error-label" style="visibility:hidden;" class="label important">FAILED</span>
                </div>
            </div>
            <? flush_buffers(); ?>
            <? if (subset_file($working_dir, $cfio->get_set_id(), $annotation_file) === FALSE): ?>
            <script type="text/javascript" charset="utf-8">
			document.getElementById("proc-6-throbber").style.visibility = "hidden";
			document.getElementById("proc-6-error-label").style.visibility = "visible";
            </script>
            <? die(); ?>
            <? endif; ?>
            <hr />
            <script type="text/javascript" charset="utf-8">
            document.getElementById("proc-6-throbber").style.visibility = "hidden";
            document.getElementById("proc-6-done-label").style.visibility = "visible";
            </script>
            
            <div class="row">
                <div class="span1"><strong>7</strong></div>
                <div class="span10">Syncing files</div>
                <div class="span5">
                	<img src="image/throbber.gif" id="proc-7-throbber" />
                	<span id="proc-7-done-label" style="visibility:hidden;" class="label success">DONE</span>
                	<span id="proc-7-error-label" style="visibility:hidden;" class="label important">FAILED</span>
                </div>
            </div>
            <? flush_buffers(); ?>
            <?php 
            try
            {
                $cfio->push_data();
                $cfio->delete_raw($uploaded_file);
            }
            catch (Exception $e)
            {
            ?>
            <script type="text/javascript" charset="utf-8">
			document.getElementById("proc-7-throbber").style.visibility = "hidden";
			document.getElementById("proc-7-error-label").style.visibility = "visible";
            </script>
            <? die(); ?>
            <? 
            } 
            ?>
            <hr />
            <script type="text/javascript" charset="utf-8">
            document.getElementById("proc-7-throbber").style.visibility = "hidden";
            document.getElementById("proc-7-done-label").style.visibility = "visible";
            </script>

            <div class="span16">
                <div class="well">
                    <a class="btn primary" href="summary.php?dataSet=vat.<? echo $cfio->get_set_id(); ?>&setId=<? echo $cfio->get_set_id(); ?>&annotationSet=<? echo $annotation_file; ?>&type=coding">View results</a>
                    <a class="btn" href="upload.php">Back</a>
                </div>
            </div>
	<? endif; ?>    
<? endif; ?>
            

            <footer>
                <p>&copy; Gerstein Lab 2011</p>
            </footer>
        </div><!-- /container -->
    </body>
</html>
