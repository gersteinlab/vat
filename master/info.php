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

if ( ! isset($_GET['annotationSet']))
{
    array_push($fatal_error, "Annotation set not set");
}
if ( ! isset($_GET['type']))
{
    array_push($fatal_error, "Type not set");
}
if ( ! isset($_GET['geneId']))
{
    array_push($fatal_error, "geneId not set");
}
if ( ! isset($_GET['dataSet']))
{
    array_push($fatal_error, "Data set not set");
}
if ( ! isset($_GET['setId']))
{
    array_push($fatal_error, "Set ID not set");
}
if ($_GET['type'] != "coding" && $_GET['type'] != "nonCoding")
{
    array_push($fatal_error, "Invalid type: " . $_GET['type']);
}

if (empty($fatal_error))
{
    $data_set       = $_GET['dataSet'];
    $annotation_set = $_GET['annotationSet'];
    $type           = $_GET['type'];
    $gene_id        = $_GET['geneId'];
    $set_id         = $_GET['setId'];
   
    $server = Server::get(1);
    
    $url = sprintf("http://%s/info_api.php?annotation_set=%s&type=%s&set_id=%s&gene_id=%s",
                   $server['address'], $annotation_set, $type, $set_id, $gene_id);
    
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
       
        $info = $response_body['info'];
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
	                <h2>External links</h2>
	                <ul>
	                    <li><a href="<? echo $info['links']['genomeBrowser']; ?>" target="external">UCSC genome browser</a></li>
	                    <li><a href="<? echo $info['links']['ensemblGenomeBrowser']; ?>" target="external">Ensembl genome browser</a></li>
    <? if ($type == 'coding'): ?>
	                    <li><a href="<? echo $info['links']['geneCards']; ?>" target="external">Gene Cards</a></li>
	<? endif; ?>
	                </ul>
                </div>
            </div>
            
            <div class="content">
                <div class="page-header">
                    <h1><? echo $info['dataSet'] ?>: gene summary for <font color="red"><? echo $info['geneName']; ?></font> [<? echo $info['geneId']; ?>]</h1>
                </div>
                    <h3>Transcript summary based on <? echo $info['annotationSet']; ?> annotation set</h3>
                    <table class="bordered-table zebra-striped">
                        <thead>
                            <tr>
                                <th>Transcript name</th>
                                <th>Transcript ID</th>
                                <th>Chromosome</th>
                                <th>Strand</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Number of exons</th>
                                <th>Transcript length</th>
                            </tr>
                        </thead>
                        <tbody>
	<? foreach ($info['transcriptSummary'] as $transcript_line): ?>
                            <tr>
                                <td><? echo $transcript_line['transcriptName']; ?></td>
                                <td><? echo $transcript_line['transcriptId']; ?></td>
                                <td><? echo $transcript_line['chromosome']; ?></td>
                                <td><? echo $transcript_line['strand']; ?></td>
                                <td><? echo $transcript_line['start']; ?></td>
                                <td><? echo $transcript_line['end']; ?></td>
                                <td><? echo $transcript_line['numExons']; ?></td>
                                <td><? echo $transcript_line['length']; ?></td>
                            </tr>
	<? endforeach; ?>
					    </tbody>
                    </table>
            
	<? if ($type == 'coding'): ?>
                    <h2>Graphical representation of genetic variants</h2>
                    <center>
                    <p>
                        <img src="<? echo $info['variantsImage']; ?>" />
                    </p>
                    <p>
                        <img src="<? echo $info['legendImage']; ?>">
                    </p>
                    </center>
    <? else: ?>
    				<h2>Graphical representation of genetic variants</h2>
    				<h3>Reference</h3>
                    <center>
                    <p>
                        <embed src="<? echo $info['secondaryStructureRefImage']; ?>" height="450px" width="1000px">
                    </p>
                    </center>
                    <h3>Variants</h3>
                    <center>
                    <p>
                        <embed src="<? echo $info['secondaryStructureVarImage']; ?>" height="450px" width="1000px">
                    </p>
                    </center>
    <? endif; ?>

                    <h2>Detailed summary of variants</h2>
                    <table class="bordered-table zebra-striped">
						<thead>
						    <tr>
						        <th rowspan="2">Chr</th>
						        <th rowspan="2">Position</th>
						        <th rowspan="2">Ref. allele</th>
						        <th rowspan="2">Alt. allele</th>
						        <th rowspan="2">Identifier</th>
						        <th rowspan="2">Type</th>
						        <th rowspan="2">Fraction of transcripts affected</th>
						        <th rowspan="2">Transcripts</th>
						        <th rowspan="2">Transcript details</th>
						        <th colspan="3" style="border-bottom: 1px solid #d0d0d0;">Alternate allele frequencies</th>
						        <th rowspan=2>Genotypes</th>
						    </tr>
						    <tr>
	<? $i = 0; ?>
	<? foreach ($info['alternateAlleles'] as $allele): ?>
		<? if ($i == 0): ?>
						        <th style="border-left: 1px solid #d0d0d0;"><? echo $allele; ?></th>
		<? else: ?>
						        <th><? echo $allele; ?></th>
		<? endif; ?>
    <? endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
	<? foreach ($info['variantSummary'] as $entry): ?>
                            <tr>
								<td><? echo $entry['chromosome']; ?></td>
								<td><? echo $entry['position']; ?></td>
								<td><? echo $entry['referenceAllele']; ?></td>
								<td><? echo $entry['alternateAllele']; ?></td>
								<td><? echo $entry['identifier']; ?></td>
								<td><? echo $entry['type']; ?></td>
								<td><? echo $entry['fraction']; ?></td>
								<td>
		<? foreach ($entry['transcriptIds'] as $transcriptId): ?>
									<? echo $transcriptId; ?><br />
		<? endforeach; ?>
								</td>
								<td>
		<? foreach ($entry['transcriptDetails'] as $transcriptDetail): ?>
									<? echo $transcriptDetail; ?><br />
		<? endforeach; ?>
								</td>
		<? foreach ($entry['alternateAlleleFreqs'] as $allele_freq): ?>
								<td><? echo $allele_freq; ?></td>
		<? endforeach; ?>
		<? 
		$genotypes_link = sprintf("genotype.php?dataSet=%s&setId=%s&geneId=%s&index=%s",  
		                          $data_set, 
		                          $set_id,
		                          $gene_id,
		                          $entry['index']); 
		?>
								<td><a href="<? echo $genotypes_link; ?>" target="genotypes">Link</a></td>
                            </tr>
	<? endforeach; ?>
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
    
        

