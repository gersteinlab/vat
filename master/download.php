<?php 

require_once 'lib/init.php';

$vat_config['VAT_RELEASES_URL'] = 'https://s3.amazonaws.com/vat-releases';
$vat_config['VAT_ANNOTATION_URL'] = 'https://s3.amazonaws.com/vat-annotation';

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
                        <li class="active"><a href="download.php">Download</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="container">
        	<div class="page-header">
        		<h1>Download</h1>
        	</div>
        	
        	<div class="row">
        		<div class="span16">
            		<h2>Dependences</h2>
            		<p>
            			The following packages are required by VAT:
            		</p>
            		<ul>
						<li><strong><a href="http://www.gnu.org/software/gsl/" target="_blank">GNU Scientific Library (GSL)</a></strong> - version-1.14; required for libBIOS, which is a general C library</li>
						<li><strong><a href="http://www.libgd.org/Main_Page" target="_blank">GD library</a></strong> - The GD library is used to create an image for each gene model and its associated variants (version-2.0.35; required by VAT).</li>
						<li><strong><a href="http://samtools.sourceforge.net/tabix.shtml" target="_blank">Tabix</a></strong> - Tabix (version-0.2.3) is a generic tool that indexes position-sorted files in tab-delimited formats to facilitate fast retrieval (download). These tools are utilized by VAT. Note: these executables must be part of the PATH.</li>
						<li><strong><a href="http://hgwdev.cse.ucsc.edu/~kent/exe/linux/blatSuite.34.zip" target="_blank">BlatSuite</a></strong> - BLAT and a collection of utility programs. These tools are utilized by VAT. Note: these executables must be part of the PATH.</li>
						<li><strong><a href="http://homes.gersteinlab.org/people/lh372/VAT/libbios-1.0.0.tar.gz" target="_blank">BIOS library</a></strong> - VAT uses a generic C library called BIOS (version-1.0.0)</li>
					</ul>
					<p>
						The following are optional for the VAT pipeline but required for some additional functionality: 
					</p>
					<ul>
						<li><strong><a href="http://vcftools.sourceforge.net/index.html" target="_blank">VCF Tools</a></strong> - VCF tools consists of a suite of useful modules to manipulate VCF files.</li>
					</ul>
					
					<h2>VAT Download</h2>
					<h3>Note</h3>
					<p>
						THIS PACKAGE (VAT) IS PROVIDED "AS IS" AND WITHOUT ANY EXPRESSED OR IMPLIED WARRANTIES, INCLUDING, WITHOUT LIMITATION, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE.
					</p>
					<div class="row">
						<div class="span8">
							<h3>Source code</h3>
							<p>
								A tarball of the source code of the Variant Annotation Tool may be download here.
							</p>
							<div class="well">
								<a class="btn primary" href="<? echo $vat_config['VAT_RELEASES_URL']; ?>/vat-2.0.1.tar.gz">Download vat-2.0.1.tar.gz &raquo;</a>
							</div>
						</div>
						<div class="span8">
							<h3>Executables</h3>
							<p>
								A tarball containing statically built binaries for 64-bit Linux can be downloaded here:
							</p>
							<div class="well">
								<a class="btn primary" href="<? echo $vat_config['VAT_RELEASES_URL']; ?>/vat-1.0.0_64bit.zip">Download vat-1.0.0_64bit.zip &raquo;</a>
							</div>
						</div>
					</div><!-- /row -->
					
					<h3>License information</h3>
					<p>The software package is released under the <a href="http://creativecommons.org/licenses/by-nc/2.5/legalcode" target="_blank">Creative Commons license (Attribution-NonCommerical)</a></p>
					<p>For more details please refer to the <a href="http://www.gersteinlab.org/misc/permissions.html" target="_blank">Permissions Page</a> on the Gerstein Lab webpage.</p>
					
					<h2>Preprocessed GENCODE annotation sets</h2>
					<p>The following annotation sets are derived from the <a href="http://www.gencodegenes.org/" target="_blank">GENCODE</a> project. Each each entry has a set of <strong>transcript coordinates</strong> (in <a href="documentation.php#interval-format">Interval</a> format) and a set of <strong>transcript sequences</strong> (introns removed; sequence with respect to the '+' strand; in FASTA format). These annotation files may also be obtained by running the script <code>get_annotation_sets.sh</code> included in the VAT source distribution.</p>
					<p>Coding sequence (CDS) elements where the both the <code>gene_type</code> and <code>transcript_type</code> are <code>protein_coding</code>:</p>
					<table class="bordered-table zebra-striped">
						<tbody>
							<tr>
								<th>GENCODE version 3b (hg18)</th>
								<td><a href="<? echo $vat_config['VAT_ANNOTATION_URL']; ?>/gencode3b.interval">Transcript coordinates</a></th>
								<td><a href="<? echo $vat_config['VAT_ANNOTATION_URL']; ?>/gencode3b.fa">Transcript sequences</a></td>
							</tr>
							<tr>
								<th>GENCODE version 3c (hg19)</th>
								<td><a href="<? echo $vat_config['VAT_ANNOTATION_URL']; ?>/gencode3c.interval">Transcript coordinates</a></th>
								<td><a href="<? echo $vat_config['VAT_ANNOTATION_URL']; ?>/gencode3c.fa">Transcript sequences</a></td>
							</tr> 
							<tr>
								<th>GENCODE version 4 (hg19)</th>
								<td><a href="<? echo $vat_config['VAT_ANNOTATION_URL']; ?>/gencode4.interval">Transcript coordinates</a></th>
								<td><a href="<? echo $vat_config['VAT_ANNOTATION_URL']; ?>/gencode4.fa">Transcript sequences</a></td>
							</tr> 
							<tr>
								<th>GENCODE version 5 (hg19)</th>
								<td><a href="<? echo $vat_config['VAT_ANNOTATION_URL']; ?>/gencode5.interval">Transcript coordinates</a></th>
								<td><a href="<? echo $vat_config['VAT_ANNOTATION_URL']; ?>/gencode5.fa">Transcript sequences</a></td>
							</tr> 
							<tr>
								<th>GENCODE version 6 (hg19)</th>
								<td><a href="<? echo $vat_config['VAT_ANNOTATION_URL']; ?>/gencode6.interval">Transcript coordinates</a></th>
								<td><a href="<? echo $vat_config['VAT_ANNOTATION_URL']; ?>/gencode6.fa">Transcript sequences</a></td>
							</tr> 
							<tr>
								<th>GENCODE version 7 (hg19)</th>
								<td><a href="<? echo $vat_config['VAT_ANNOTATION_URL']; ?>/gencode7.interval">Transcript coordinates</a></th>
								<td><a href="<? echo $vat_config['VAT_ANNOTATION_URL']; ?>/gencode7.fa">Transcript sequences</a></td>
							</tr>  
						</tbody>
					</table>
				</div><!-- /span16 -->
				
        	</div><!-- /row -->
        	
        	<footer>
                <p>&copy; Gerstein Lab 2011</p>
            </footer>
        </div><!-- /container -->
    </body>
</html>
