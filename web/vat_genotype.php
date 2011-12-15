<?php 

require_once 'lib/config.php';
require_once 'lib/util.php';
require_once 'lib/cfio.php';
require_once 'lib/vat.php';

/* ---------------------------------------------------------------------------
 * Globals
 */

$fatal_error = array();

$cfio = new CFIO;

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

$cfio->set_set_id($_GET['setId']);

if (empty($fatal_error))
{
    $data_set = $_GET['dataSet'];
    $set_id   = $_GET['setId'];
    $index    = $_GET['index'];
    $gene_id  = $_GET['geneId'];
    
    try
    {
        $cfio->get_gene_data($gene_id);
    }
    catch (Exception $e)
    {
        array_push($fatal_error, "Cannot get VCF file for gene " . $gene_id);
    }
    
    $file = $cfio->get_working_dir() . '/vat.' . $set_id . '/' . $gene_id . '.vcf';
    
    try
    {
        $info = get_genotype_info($file, $data_set, $gene_id, $index, $set_id);
    }
    catch (Exception $e)
    {
        array_push($fatal_error, "Cannot get info for genotype " . $gene_id . " " . $index);
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
 
    </head>
    <body>
        <div class="topbar">
            <div class="fill">
                <div class="container-fluid">
                    <a class="brand" href="index.php">VAT</a>
                    <ul class="nav">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="vat_upload.php">Upload</a></li>
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