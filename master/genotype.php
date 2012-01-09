<?php 

require_once 'lib/init.php';
require_once 'libmaster/init_master.php';
require_once 'lib/util.php';
require_once 'lib/rest.php';
require_once 'lib/vat.php';

/* ---------------------------------------------------------------------------
 * Globals
 */

$fatal_error = array();

$info = NULL;

/* ---------------------------------------------------------------------------
 * Controller section
 */

if ( ! isset($_GET['geneId']))
{
    array_push($fatal_error, "geneId not set");
}
if ( ! isset($_GET['setId']))
{
    array_push($fatal_error, "setId not set");
}
if ( ! isset($_GET['index']))
{
    array_push($fatal_error, "index not set");
}
if ( ! isset($_GET['dataSet']))
{
    array_push($fatal_error, "dataSet not set");
}

if (empty($fatal_error))
{
    $data_set = $_GET['dataSet'];
    $set_id   = $_GET['setId'];
    $index    = $_GET['index'];
    $gene_id  = $_GET['geneId'];
    
    $server = Server::get(1);
    
    $url = sprintf("http://%s/genotype_api.php?gene_id=%s&set_id=%s&index=%s",
                   $server['address'], $gene_id, $set_id, $index);
    
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
       
        $info = $response_body['genotype_info'];
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
                <div class="container-fluid">
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
        
        <div class="container-fluid">
<? if ( ! empty($fatal_error)): ?>
			<div class="sidebar">
				<div class="well">
					<h2>External links</h2>
					<p>
						Disabled
					</p>
				</div>
			</div>
			<div class="content">
				<div class="page-header">
					<h1>Gene summary</h1>
				</div>
				<h3>Errors found</h3>
				<ul>
	<? foreach ($fatal_error as $error): ?>
					<li><? echo $error; ?></li>
	<? endforeach; ?>
				</ul>
			</div>
<? else: ?>
            
            <div class="sidebar">
                <div class="well">
                    <h3>Variant summary</h3>
                    <table class="bordered-table">
                        <tr>
                            <td><strong>Chromosome</strong></td>
                            <td><? echo $info['chromosome']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Position</strong></td>
                            <td><? echo $info['position']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Reference allele</strong></td>
                            <td><? echo $info['referenceAllele']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Alternate allele</strong></td>
                            <td><? echo $info['alternateAllele']; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="content">
                <div class="page-header">
                    <h1><? echo $data_set; ?>: <font color=red><? echo $gene_id; ?></font> Genotype <? echo $index; ?></h1>
                </div>
                
                <h2>Genotype information</h2>
                <table class="bordered-table">
                    <thead>
                        <tr>
	<? foreach ($info['groups'] as $group): ?>
                            <th><? echo $group; ?></th>
	<? endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
    <? foreach ($info['alleleGroupHeaders'] as $allele_group_header): ?>
                            <td>RefCount = <? echo $allele_group_header['refCount']; ?><br>AltCount = <? echo $allele_group_header['altCount']; ?></td>
    <? endforeach; ?>
                        </tr>
                        <tr>
    <? foreach ($info['alleleGroups'] as $group): ?>
                            <td>
		<? foreach ($group as $item): ?>
                                <? echo $item['sample']; ?>: <? echo $item['genotype']; ?><br />
        <? endforeach; ?>
                            </td>
    <? endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
<? endif; ?>
            <footer>
                <p>&copy; Gerstein Lab 2011</p>
            </footer>
        </div><!-- /container-fluid -->
    </body>
</html>