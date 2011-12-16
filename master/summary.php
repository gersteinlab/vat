<?php 

require_once 'lib/init.php';
require_once 'lib/util.php';
require_once 'lib/rest.php';
require_once 'lib/vat.php';

/**
 * Array to hold fatal errors. 
 * @var array
 */
$fatal_error = array();

$gene_summary = array();
$sample_summary = array();


if ( ! isset($_GET['annotationSet'])) 
{
    array_push($fatal_error, "Annotation set not set");
}
if ( ! isset($_GET['type']))
{
    array_push($fatal_error, "Type not set");
}
if ( ! isset($_GET['dataSet']))
{
    array_push($fatal_error, "Data set not set");
}
if ( ! isset($_GET['setId']))
{
    array_push($fatal_error, "Set ID not set");
}

if (empty($fatal_error))
{
    $data_set       = $_GET['dataSet'];
    $annotation_set = $_GET['annotationSet'];
    $type           = $_GET['type'];
    $set_id         = $_GET['setId'];
    
    /// XXX XXX XXX ADD BALANCER SERVER SELECTION
    $url = sprintf("http://128.36.220.24/summary_api.php?annotation_set=%s&set_id=%s&type=%s",
                   $annotation_set, $set_id, $type);
    
    $request = new RESTTxRequest($url, 'GET', array());

    
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
        array_push($fatal_error, "Error executing request " . $e->getMessage());
    }
    
    $response_info = $request->get_response_info();
    if ($response_info['http_code'] != 200)
    {
        array_push($fatal_error, "API responded with error code " . $response_info['http_code']);
    }
    else
    {
        $response_body = json_decode($request->get_response_body(), TRUE);
       
        $gene_summary = $response_body['gene_summary'];
        $sample_summary = $response_body['sample_summary'];
    }
    
}

/* ---------------------------------------------------------------------------
 * View section
 */
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
        
        <style type="text/css" media="screen">
		    @import url("css/demo_table_jui.css");
		    @import url("css/smoothness/jquery-ui-1.8.4.custom.css");
        </style>
        
        <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
        <script type="text/javascript" language="javascript" src="js/jquery.dataTables.js"></script>
        <script type="text/javascript" charset="utf-8">
            $(document).ready(function() {
                $('#ex1').html ('<table id="gene" class="zebra-striped"></table>');
                $('#gene').dataTable( 
                    <? echo json_format(json_encode($gene_summary)); ?>
                );
                $('#ex2').html ( '<table id="sample" class="zebra-striped"></table>' );
                $('#sample').dataTable(
                    <? echo json_format(json_encode($sample_summary)); ?>
                );
            });
        </script>
        <title>VAT</title>

    </head>
    <body>
        <div class="topbar">
            <div class="fill">
                <div class="container-fluid">
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
    
        <div class="container-fluid">
            <div class="sidebar">
                <div class="well">
                <h3>Data files</h3>
                <ul>
                    <li><a href="<? echo $vat_config['WEB_DATA_URL']; ?>/<? echo $set_id; ?>/<? echo $data_set; ?>.vcf.gz" target="external">Download compressed VCF file with annotated variants</a></li>
                    <li><a href="<? echo $vat_config['WEB_DATA_URL']; ?>/<? echo $set_id; ?>/<? echo $data_set; ?>.geneSummary.txt" target="external">View tab-delimited gene summary file</a></li>
                    <li><a href="<? echo $vat_config['WEB_DATA_URL']; ?>/<? echo $set_id; ?>/<? echo $data_set; ?>.sampleSummary.txt" target="external">View tab-delimited sample summary file</a></li>
                </ul>
                </div>
            </div>
            
            <div class="content">
                <div class="page-header">
                    <h1>Results: <? echo $data_set; ?></h1>
                </div>
                
                <h2>Gene summary based on gencode3b annotation set</h2>
                <div id="ex1"></div>
                <p></p>
            
                <h2>Sample summary</h2>
                <div id="ex2"></div>
            </div>
            
            <footer>
                <p>&copy; Gerstein Lab 2011</p>
            </footer>
        </div>

    </body>
</html>
