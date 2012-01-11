<?php

require_once 'lib/init.php';
require_once 'libmaster/init_master.php';
require_once 'lib/util.php';
require_once 'lib/rest.php';
require_once 'lib/cfio.php';
require_once 'lib/vat.php';

$fatal_error = array();

$cfio = new CFIO();

/**
* Process the uploaded file, validate form, and copy uploaded file into
* working directory
*
* @return bool
*/
function handle_upload($working_dir)
{
    global $fatal_error;
    global $vat_config;
    global $uploaded_file;

    $file_name = sanitize_file_name($_FILES['upFile']['name']);

    if (($retval = copy($_FILES['upFile']['tmp_name'], $working_dir . "/" . $file_name)) === FALSE)
    {
        array_push($fatal_error, "Cannot copy uploaded file " . $file_name);
        return FALSE;
    }

    $uploaded_file = $file_name;

    return TRUE;
}

function decompress_files($working_dir)
{
    global $uploaded_file;
    global $fatal_error;
    
    if (mime_content_type($working_dir . '/' . $uploaded_file) != 'application/x-gzip')
    {
        array_push($fatal_error, "Upload file must be a .tar.gz archive");
        return FALSE;
    }
    
    $cmd = "tar -zxvf " . $working_dir . "/" . $uploaded_file . " -C " . $working_dir;
    exec($cmd, $output, $retval);
    
    if ($retval != 0)
    {
        array_push($fatal_error, "Error extracting archive: " . $output);
        return FALSE;
    }
    
    $basename = basename($uploaded_file, '.tar.gz');
   
    if ( ! file_exists($working_dir . '/' . $basename . '.raw.vcf'))
    {
        $fh = fopen($working_dir . '/' . $basename . '.raw.vcf', 'w');
        if ($fh === FALSE) {
            array_push($fatal_error, "Cannot touch raw VCF file");
            return FALSE;
        }
        fclose($fh);
    }
    
    if ( ! file_exists($working_dir . '/' . $basename . '.vcf'))
    {
        $fh = fopen($working_dir . '/' . $basename . '.vcf', 'w');
        if ($fh === FALSE) {
            array_push($fatal_error, "Cannot touch VCF file");
            return FALSE;
        }
        fclose($fh);
    }
    
    if ( ! file_exists($working_dir . '/' . $basename . '.sampleSummary.txt'))
    {
        array_push($fatal_error, "Sample summary file not found in archive");
        return FALSE;
    }
    if ( ! file_exists($working_dir . '/' . $basename . '.geneSummary.txt'))
    {
        array_push($fatal_error, "Gene summary file not found in archive");
        return FALSE;
    }
    if ( ! file_exists($working_dir . '/' . $basename . '.vcf.gz'))
    {
        array_push($fatal_error, "Gzipped VCF file not found in archive");
        return FALSE;
    }
    if ( ! file_exists($working_dir . '/' . $basename . '.vcf.gz.tbi'))
    {
        array_push($fatal_error, "Tabix indexed file not found in archive");
        return FALSE;
    }
    if ( ! file_exists($working_dir . '/' . $basename) || 
         ! is_dir($working_dir . '/' . $basename))
    {
        array_push($fatal_error, "Image/gene subset directory not found in archive");
        return FALSE;
    }
    
    return TRUE;
}

function rename_files($working_dir, $set_id)
{
    global $fatal_error;
    global $uploaded_file;
    
    $basename = basename($uploaded_file, '.tar.gz');
    
    $files = array(
        $working_dir . '/' . $basename . '.raw.vcf' => $working_dir . '/vat.' . $set_id . '.raw.vcf',
        $working_dir . '/' . $basename . '.vcf' => $working_dir . '/vat.' . $set_id . '.vcf',
        $working_dir . '/' . $basename . '.vcf.gz' => $working_dir . '/vat.' . $set_id . '.vcf.gz',
        $working_dir . '/' . $basename . '.vcf.gz.tbi' => $working_dir . '/vat.' . $set_id . '.vcf.gz.tbi',
        $working_dir . '/' . $basename . '.sampleSummary.txt' => $working_dir . '/vat.' . $set_id . '.sampleSummary.txt',
        $working_dir . '/' . $basename . '.geneSummary.txt' => $working_dir . '/vat.' . $set_id . '.geneSummary.txt',
        $working_dir . '/' . $basename  => $working_dir . '/vat.' . $set_id
    );
    
    foreach ($files as $src => $dst) 
    {
        if (rename($src, $dst) === FALSE) 
        {
            array_push($fatal_error, "Cannot rename $src to $dst");
            return FALSE;
        }
    }
    
    return TRUE;
}

$title           = NULL;
$description     = NULL;
$annotation_file = NULL;
$program         = NULL;
$process         = TRUE;
$set_id          = -1;
$uploaded_file   = NULL;
$model           = NULL;

/*
* Display error if max file size set by web server configuration has been
* exceeded by the upload file
*/
if (empty($_POST) && empty($_FILES) && isset($_SERVER['REQUEST_METHOD']) &&
    $_SERVER['REQUEST_METHOD'] == 'POST')
{
    $poids_max = ini_get('post_max_size');
    array_push($fatal_error, "Max upload file size " . $poids_max . " exceeded. Please configure your php.ini to allow uploads of larger files.");
}

if ( ! empty($_POST))
{
    $working_dir = $cfio->get_working_dir();
    
    $upload_success = handle_upload($working_dir);
    if ($upload_success == FALSE)
    {
        array_push($fatal_error, "Upload unsuccessful");
    }
    
    $title           = $_POST['title'];
    $description     = $_POST['description'];
    $annotation_file = $_POST['annotationFile'];
    $program         = $_POST['variantType'];
    
    // XXX We will need better validation, ideally on the client side.
    if ($title == "" || $description == "")
    {
        array_push($fatal_error, "Name or description not set");
    }
    
    $model = Model::factory('dataset');
    
    $model->set('title', $title);
    $model->set('description', $description);
    $model->set('annotation_file', $annotation_file);
    $model->set('variant_type', $program);
    $model->set('raw_filename', $uploaded_file);
    $model->set('status', SET_STATUS_RAW_UPLOADED);
    $model->save();
    
    $cfio->set_set_id($model->get('id'));
    $set_id = $model->get('id');
}
else
{
    array_push($fatal_error, "Nothing to do");
}

if (empty($fatal_error))
{
    if (decompress_files($working_dir) === TRUE) 
    {
        rename_files($working_dir, $set_id);
    }
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
        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
        <script>!window.jQuery && document.write('<script src="js/jquery-1.4.4.min.js"><\/script>')</script>
        <script src="js/bootstrap-dropdown.js"></script>
 
    </head>
    <body>
        <div class="topbar">
            <div class="fill">
                <div class="container">
                    <a class="brand" href="index.php">VAT</a>
                    <ul class="nav">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="upload.php">Upload</a></li>
                        <li class="dropdown" data-dropdown="dropdown">
                            <a href="#" class="dropdown-toggle">Documentation</a>
                            <ul class="dropdown-menu">
                                <li><a href="installation.php">Installing</a></li>
                                <li><a href="formats.php">Data formats</a></li>
                                <li><a href="programs.php">List of programs</a></li>
                                <li><a href="workflow.php">Example workflow</a></li>
                                <li class="divider"></li>
                                <li><a href="documentation.php">All documentation</a></li>
                            </ul>
                        </li>
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
    <? foreach ($fatal_error as $error): ?>
					<li><? echo $error; ?></li>
	<? endforeach; ?>
				</ul>
			</div>
<? else: ?>
			<div class="row">
                <div class="span1"><strong>1</strong></div>
                <div class="span10">Decompressing and saving file</div>
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
	    $cfio->push_data();
	}
	catch (Exception $e)
	{
	    ?>
			<script type="text/javascript" charset="utf-8">
			document.getElementById("proc-0-throbber").style.visibility = "hidden";
			document.getElementById("proc-0-error-label").style.visibility = "visible";
            </script>
	    <?
	    die();
	}

	assert($model != NULL);
	$model->set('status', SET_STATUS_COMPLETE);
    $model->save();
	?>
			<hr />
			<script type="text/javascript" charset="utf-8">
            document.getElementById("proc-0-throbber").style.visibility = "hidden";
            document.getElementById("proc-0-done-label").style.visibility = "visible";
            </script>
			
			<div class="span16">
				<div class="well">
					<a class="btn primary" href="summary.php?dataSet=vat.<? echo $cfio->get_set_id(); ?>&setId=<? echo $cfio->get_set_id(); ?>&annotationSet=<? echo $annotation_file; ?>&type=coding">View results</a>
					<a class="btn" href="upload.php">Back</a>
				</div>
			</div>
<? endif; ?>

			<footer>
                <p>&copy; Gerstein Lab 2011</p>
            </footer>
		</div><!-- /container -->
	</body>
</html>