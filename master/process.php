<?php 

require_once 'lib/init.php';
require_once 'libmaster/init_master.php';
require_once 'lib/util.php';
require_once 'lib/rest.php';
require_once 'lib/cfio.php';
require_once 'lib/vat.php';

$fatal_error = array();

$cfio = new CFIO();

$title           = NULL;
$description     = NULL;
$annotation_file = NULL;
$program         = NULL;
$process         = TRUE;
$set_id          = -1;
$uploaded_file   = NULL;

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

/*
 * We're processing a form submission. Therefore, we are creating a new data
 * set. Create a new model for dataset, populate the fields, and save to the
 * database and obtain the new ID for the dataset. Whether or not we process
 * the file depends on if "process uploaded file" was checked.
 */ 
if ( ! empty($_POST))
{
    $working_dir = $cfio->get_working_dir();
    
    $upload_success = handle_upload($working_dir);
    
    $title           = $_POST['title'];
    $description     = $_POST['description'];
    $annotation_file = $_POST['annotationFile'];
    $program         = $_POST['variantType'];
    $process         = isset($_POST['process']) ? TRUE : FALSE;
    
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
    
    $set_id = $model->get('id');
}

/*
 * We are not processing a form submission and have a data set ID specified
 * in the GET query string. In this case, we are definitely processing the
 * raw VCF file specified.
 */
else if ( ! empty($_GET))
{
    if ( ! isset($_GET['id']))
    {
        array_push($fatal_error, "No input file specified");
    }
    
    $set_id = $_GET['id'];
    
    $model = Model::factory('dataset', $set_id);
    
    if ($model == NULL)
    {
        array_push($fatal_error, "Data set does not exist");
    }
    elseif ($model->get('status') > SET_STATUS_RAW_UPLOADED)
    {
        array_push($fatal_error, "Data set is already being processed");
    }
    
    $title           = $model->get('title');
    $annotation_file = $model->get('annotation_file');
    $program         = $model->get('variant_type');
    $uploaded_file   = $model->get('raw_filename');
    $process         = TRUE;

}
else
{
    array_push($fatal_error, "Nothing to do");
}

/*
 * Make the request.
 */
if (empty($fatal_error) && $process == TRUE)
{

    $request_body = array(
        'set_id'          => $set_id,
        'annotation_file' => $annotation_file,
        'variant_type'    => $program,
        'raw_filename'    => $uploaded_file
    );
    
    $server = Server::get(2);
    
    $url = 'http://' . $server['address'] . '/process_api.php';
    
    $request = new RESTTxRequest($url, 'POST', $request_body);
    
    try
    {
        $request->execute();
    }
    catch (InvalidArgumentException $ie)
    {
        array_push($fatal_error, "Invalid argument exception: " . $ie->getMessage());
    }
    catch (Exception $e)
    {
        array_push($fatal_error, "Error executing request: " . $e->getMessage());
    }
    
    $response_info = $request->get_response_info();
    if ($response_info['http_code'] != 200)
    {
        array_push($fatal_error, "API responded with error code " . $response_info['http_code']);
    }
    //var_dump($request);
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
 
 		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script> 
    	<script>!window.jQuery && document.write('<script src="js/jquery-1.4.4.min.js"><\/script>')</script> 
    	<script src="js/jquery.json-2.2.min.js" type="text/javascript"></script>
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
    <? foreach ($fatal_error as $error): ?>
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
                    <a class="btn primary" href="process.php?id=<? echo $set_id; ?>">Process file</a>
                </div>
            </div>

    <? else: ?>
    		<script>
    	    function update_statuses() {
    		    $.ajax({
    		        type: 'GET',
    		        url: 'http://<? echo $vat_config['MASTER_ADDRESS'] ?>/dataset_api.php?id=<? echo $set_id; ?>',
    		        dataType: 'application/json',
    		        success: function(reply) {
    		            reply = $.evalJSON(reply);
    		            var state = reply.dataset.status;

                        for (var i = 1; i <= state; i++) {
                            $('#proc-' + i + '-throbber').css({"visibility" : "hidden"});
                            $('#proc-' + i + '-done-label').css({"visibility" : "visible"});
                        }

    		            if (state < 7) {
                            var next = parseInt(state) + 1;
    		                $('#proc-' + next + '-throbber').css({"visibility" : "visible"}); 
    		                setTimeout(update_statuses, 1000);
    		            } else {
        		            $('#wait-btn').css({"visibility" : "hidden"});
                            $('#done-btn-back').css({"visibility" : "visible"});
                            $('#done-btn-view').css({"visibility" : "visible"});
    		            }
    		        }
                });
    		}

    		update_statuses();
    		</script>

            <div class="row">
                <div class="span1"><strong>1</strong></div>
                <div class="span10">Writing file</div>
                <div class="span5">
                	<img src="image/throbber.gif" id="proc-1-throbber" />
                	<span id="proc-1-done-label" style="visibility:hidden;" class="label success">DONE</span>
                	<span id="proc-1-error-label" style="visibility:hidden;" class="label important">FAILED</span>
                </div>
            </div>
            <hr />
            <div class="row">
                <div class="span1"><strong>2</strong></div>
                <div class="span10">Annotating variants</div>
                <div class="span5">
                	<img src="image/throbber.gif" id="proc-2-throbber" style="visibility:hidden;" />
                	<span id="proc-2-done-label" style="visibility:hidden;" class="label success">DONE</span>
                	<span id="proc-2-error-label" style="visibility:hidden;" class="label important">FAILED</span>
                </div>
            </div>
            <hr />
            <div class="row">
                <div class="span1"><strong>3</strong></div>
                <div class="span10">Indexing files</div>
                <div class="span5">
                	<img src="image/throbber.gif" id="proc-3-throbber" style="visibility:hidden;" />
                	<span id="proc-3-done-label" style="visibility:hidden;" class="label success">DONE</span>
                	<span id="proc-3-error-label" style="visibility:hidden;" class="label important">FAILED</span>
                </div>
            </div>
            <hr />
            <div class="row">
                <div class="span1"><strong>4</strong></div>
                <div class="span10">Creating variant summary file</div>
                <div class="span5">
                	<img src="image/throbber.gif" id="proc-4-throbber" style="visibility:hidden;" />
                	<span id="proc-4-done-label" style="visibility:hidden;" class="label success">DONE</span>
                	<span id="proc-4-error-label" style="visibility:hidden;" class="label important">FAILED</span>
                </div>
            </div>
            <hr />
            <div class="row">
                <div class="span1"><strong>5</strong></div>
                <div class="span10">Generating images</div>
                <div class="span5">
                	<img src="image/throbber.gif" id="proc-5-throbber" style="visibility:hidden;" />
                	<span id="proc-5-done-label" style="visibility:hidden;" class="label success">DONE</span>
                	<span id="proc-5-error-label" style="visibility:hidden;" class="label important">FAILED</span>
                </div>
            </div>
            <hr />
            <div class="row">
                <div class="span1"><strong>6</strong></div>
                <div class="span10">Subseting file</div>
                <div class="span5">
                	<img src="image/throbber.gif" id="proc-6-throbber" style="visibility:hidden;" />
                	<span id="proc-6-done-label" style="visibility:hidden;" class="label success">DONE</span>
                	<span id="proc-6-error-label" style="visibility:hidden;" class="label important">FAILED</span>
                </div>
            </div>
            <hr />
            <div class="row">
                <div class="span1"><strong>7</strong></div>
                <div class="span10">Syncing files</div>
                <div class="span5">
                	<img src="image/throbber.gif" id="proc-7-throbber" style="visibility:hidden;" />
                	<span id="proc-7-done-label" style="visibility:hidden;" class="label success">DONE</span>
                	<span id="proc-7-error-label" style="visibility:hidden;" class="label important">FAILED</span>
                </div>
            </div>
	   <hr />
            <div class="span16">
                <div class="well">
                    <a id="done-btn-view" style="visibility:hidden" class="btn primary" href="summary.php?dataSet=vat.<? echo $set_id; ?>&setId=<? echo $set_id; ?>&annotationSet=<? echo $annotation_file; ?>&type=coding">View results</a>
                    <a id="done-btn-back" style="visibility:hidden" class="btn" href="upload.php">Back</a>
                    <button id="wait-btn" class="btn" disabled>Please wait...</button>
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
