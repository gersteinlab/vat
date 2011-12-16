<?php 

require_once 'lib/init.php';
require_once 'libmaster/init_master.php';
require_once 'lib/util.php';
require_once 'lib/cfio.php';
require_once 'lib/vat.php';
require_once 'libmaster/balancer.php';

$fatal_error = array();

$cfio = new CFIO();

$title           = NULL;
$description     = NULL;
$annotation_file = NULL;
$program         = NULL;
$process         = TRUE;
$set_id          = -1;


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
    if ($name == "" || $description == "")
    {
        array_push($fatal_error, "Name or description not set");
    }
    
    $model = Model::factory('dataset');
    
    $model->set('title', $title)
          ->set('description', $description)
          ->set('annotation_file', $annotation_file)
          ->set('variant_type', $program)
          ->set('raw_filename', $uploaded_file)
          ->set('status', SET_STATUS_RAW_UPLOADED);
    
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

if (empty($fatal_error) && $process == TRUE)
{

    $request_body = array(
        'set_id'          => $set_id,
        'annotation-file' => $annotation_file,
        'program'         => $program,
        'raw_filename'    => $uploaded_file
    );
    
    $url = 'http://' . $server->get_address() . '/vat_process_api.php?set_id=' . $set_id;
    
    $request = new RESTTxRequest($url, 'GET', $request_body);
    
    try
    {
        $request->execute();
    }
    catch (Exception $e)
    {
        array_push($fatal_error, "Error executing request: " . $e->getMessage());
    }
}




?>