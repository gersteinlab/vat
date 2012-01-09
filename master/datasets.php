
<?php 

require_once 'lib/init.php';

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
                <h1>1000-Genomes Data Sets</h1>
            </div>
            <div class="row">
            	<div class="span4">
            	   <div class="well">
            	       <h3>Annotation Sets</h3>
            		   <p>The GENCODE annotation sets may be downloaded <a href="documentation.php">here</a>.</p>
            	   </div>
            	</div>
            	<div class="span12">
            		<h2>1000 Genomes Pilot Project: Low coverage samples</h2>
            		<table>
                        <tbody>
                            <tr>
                                <th>Source</th>
                                <td><a href="ftp://ftp-trace.ncbi.nih.gov/1000genomes/ftp/pilot_data/release/2010_07/low_coverage/">pilot_data, release: 2010_07, FT</a></td>
                            </tr>
                            <tr>
                                <th rowspan="3">Indels</th>
                                <td><a href="ftp://ftp-trace.ncbi.nih.gov/1000genomes/ftp/pilot_data/release/2010_07/low_coverage/indels/CEU.low_coverage.2010_07.indel.genotypes.vcf.gz">CEU.low_coverage.2010_07.indel.genotypes.vcf.gz</a></td>
                            </tr>
                            <tr>
                            
                                <td><a href="ftp://ftp-trace.ncbi.nih.gov/1000genomes/ftp/pilot_data/release/2010_07/low_coverage/indels/JPTCHB.low_coverage.2010_07.indel.genotypes.vcf.gz">JPTCHB.low_coverage.2010_07.indel.genotypes.vcf.gz</a></td>
                            </tr>
                            <tr>
                                <td><a href="ftp://ftp-trace.ncbi.nih.gov/1000genomes/ftp/pilot_data/release/2010_07/low_coverage/indels/YRI.low_coverage.2010_07.indel.genotypes.vcf.gz">YRI.low_coverage.2010_07.indel.genotypes.vcf.gz</a></td>
                            </tr>
                            <tr>
                                <th rowspan="3">SNPs</th>
                                <td><a href="ftp://ftp-trace.ncbi.nih.gov/1000genomes/ftp/pilot_data/release/2010_07/low_coverage/snps/CEU.low_coverage.2010_07.genotypes.vcf.gz">CEU.low_coverage.2010_07.genotypes.vcf.gz</a></td>
                            </tr>
                            <tr>
                                <td><a href="ftp://ftp-trace.ncbi.nih.gov/1000genomes/ftp/pilot_data/release/2010_07/low_coverage/snps/CHBJPT.low_coverage.2010_07.genotypes.vcf.gz">CHBJPT.low_coverage.2010_07.genotypes.vcf.gz</a></td>
                            </tr>
                            <tr>
		                        <td><a href="ftp://ftp-trace.ncbi.nih.gov/1000genomes/ftp/pilot_data/release/2010_07/low_coverage/snps/YRI.low_coverage.2010_07.genotypes.vcf.gz">YRI.low_coverage.2010_07.genotypes.vcf.gz</a></td>
                            </tr>
                            <tr>
                                <th>Annotation Set</th>
                                <td>
                                    <a href="ftp://ftp.sanger.ac.uk/pub/gencode/release_3b/gencode.v3b.annotation.NCBI36.gtf.gz">GENCODE (version 3b, hg18)</a>, using CDS elements where gene_type = protein_coding and transcript_type = protein_coding
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="well">
                        <a href="#" class="btn primary">View results &raquo;</a>
                    </div>
                     
                   
            		<h2>1000 Genomes Project, Phase I, chr22, SNP calls</h2>
            		<table>
                        <tbody>
                            <tr>
                                <th>Source</th>
                                <td><a href="ftp://ftp-trace.ncbi.nih.gov/1000genomes/ftp/release/20100804/">release: 20100804, FTP</a></td>
                            </tr>
                            <tr>
                                <th>SNPs</th>
                                <td><a href="ftp://ftp-trace.ncbi.nih.gov/1000genomes/ftp/release/20100804/ALL.2of4intersection.20100804.genotypes.vcf.gz">ALL.2of4intersection.20100804.genotypes.vcf.gz</a></td>
                            </tr>
                            <tr>
                                <th>Annotation Set</th>
                                <td>
                                    <a href="ftp://ftp.sanger.ac.uk/pub/gencode/release_3c/gencode.v3c.annotation.GRCh37.gtf.gz">GENCODE (version 3c, hg19)</a>, using CDS elements where gene_type = protein_coding and transcript_type = protein_coding
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="well">
                        <a href="#" class="btn primary">View results &raquo;</a>&nbsp;<a href="#" class="btn">Detailed workflow &raquo;</a>
                    </div>
            	</div>
            </div>
        
            <footer>
                <p>&copy; Gerstein Lab 2011</p>
            </footer>
        </div> <!-- /container -->
    </body>
</html>