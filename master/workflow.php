<?php

require_once 'lib/init.php';
?>

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
                        <li class="dropdown active" data-dropdown="dropdown">
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
        	<div class="sidebar">
        		<div class="well">
        			<h2>Contents</h2>

					<ol>
						<li>Example Workflow
        					<ol>
        						<li><a href="#workflow-prerequisites">Prerequisites</a></li>
        						<li><a href="#workflow-preprocessing">Preprocessing of the annotation file</a></li>
        						<li><a href="#workflow-annotation">Annotation of the SNPs</a></li>
        						<li><a href="#workflow-modification">Modification of the VCF header line</a></li>
        						<li><a href="#workflow-summaries">Generation of summaries and images</a></li>
        						<li><a href="#workflow-webserver">Setting up the web server</a></li>
        					</ol>
        				</li>
					</ol>

        		</div><!-- /well -->
        	</div><!-- /sidebar -->
        
        	<div class="content">
	        	<div class="page-header">
	                <h1>Example workflow</h1>
	            </div>
	            
				<section id="example-workflow">

                    <p>This workflow shows how the <a href="datasets.php">1000 Genomes Project, Phase I, chr22, SNP calls</a> data set was processed.</p>
                    
                    <h3 id="workflow-prerequisites">Prerequisites</h3>
                    <p>Download the GENCODE annotation set (version 3c, hg19):</p>
                    <pre>$ wget ftp://ftp.sanger.ac.uk/pub/gencode/release_3c/gencode.v3c.annotation.GRCh37.gtf.gz</pre>
                    <p>Download the human genome (hg19) in 2bit format. This is used by interval2sequences to extract the genomic sequences for the entries specified in the annotation set:</p>
                    <pre>$ wget http://hgdownload.cse.ucsc.edu/goldenPath/hg19/bigZips/hg19.2bit</pre>
                    <p>Download the SNP files in VCF format and a third file that assigns each sample to a population:</p>
<pre>$ wget ftp://ftp-trace.ncbi.nih.gov/1000genomes/ftp/release/20100804/ALL.2of4intersection.20100804.genotypes.vcf.gz
$ wget ftp://ftp-trace.ncbi.nih.gov/1000genomes/ftp/release/20100804/ALL.2of4intersection.20100804.genotypes.vcf.gz.tbi
$ wget ftp://ftp-trace.ncbi.nih.gov/1000genomes/ftp/release/20100804/20100804.ALL.panel</pre>
                    <p>Extract variants on chromosome 22:</p>
                    <pre>$ tabix -h ALL.2of4intersection.20100804.genotypes.vcf.gz 22 | bgzip -c &gt; ALL.2of4intersection.20100804.chr22.genotypes.vcf.gz</pre>
                    
                    <h3 id="workflow-preprocessing">Preprocessing of the annotation file</h3>
                    <p>Decompress the annotation file:</p>
                    <pre>$ gunzip gencode.v3c.annotation.GRCh37.gtf.gz</pre>
                    <p>Extract the coding sequence (CDS) elements where the both the <code>gene_type</code> and <code>transcript_type</code> are <code>protein_coding</code>:</p>
                    <pre>$ awk '/\t(HAVANA|ENSEMBL)\tCDS\t/ {print}' gencode.v3c.annotation.GRCh37.gtf | grep 'gene_type "protein_coding"' | grep 'transcript_type "protein_coding"' &gt; gencode.v3c.annotation.GRCh37.cds.gtpc.ttpc.gtf</pre>
                    <p>Convert the GENCODE GTF file into Interval format:</p>
                    <pre>$ gencode2interval &lt; gencode.v3c.annotation.GRCh37.cds.gtpc.ttpc.gtf &gt; gencode.v3c.annotation.GRCh37.cds.gtpc.ttpc.interval</pre>
                    <p>Retrieve the genomic sequences for the transcripts specified in the annotation file.</p>
                    <pre>$ interval2sequences hg19.2bit gencode.v3c.annotation.GRCh37.cds.gtpc.ttpc.interval exonic &gt; gencode.v3c.annotation.GRCh37.cds.gtpc.ttpc.fa</pre>
                    
                    <h3 id="workflow-annotation">Annotation of the SNPs</h3>
                    <p>Annotate the variants using snpMapper</p>
                    <pre>$ zcat ALL.2of4intersection.20100804.chr22.genotypes.vcf.gz | snpMapper gencode.v3c.annotation.GRCh37.cds.gtpc.ttpc.interval gencode.v3c.annotation.GRCh37.cds.gtpc.ttpc.fa &gt; ALL.2of4intersection.20100804.chr22.genotypes.annotated.vcf</pre>
                    
                    <h3 id="workflow-header">Modification the VCF header line</h3>
                    <p>Modify the VCF header line to assign individual samples to populations (groups). This is done by using the following syntax: <code>group:sample</code> (i.e. <code>CEU:NA0705</code>).</p>
                    <p>First get the old meta-data lines:
                    <pre>$ grep "#" ALL.2of4intersection.20100804.chr22.genotypes.annotated.vcf &gt; ALL.2of4intersection.20100804.chr22.genotypes.annotated.oldHeader.vcf </pre>
                    <p>Store the annotated variants in a separate file:</p>
                    <pre>$ grep "#" -v ALL.2of4intersection.20100804.chr22.genotypes.annotated.vcf &gt; ALL.2of4intersection.20100804.chr22.genotypes.annotated.variants.vcf</pre>
                    <p>Create the new meta-data lines:</p>
                    <pre>$ vcfModifyHeader ALL.2of4intersection.20100804.chr22.genotypes.annotated.oldHeader.vcf 20100804.ALL.panel &gt; ALL.2of4intersection.20100804.chr22.genotypes.annotated.newHeader.vcf </pre>
                    <p>Merge the new meta-data lines with the annotated variants and create a new file called <code>ALL.2of4intersection.20100804.chr22.vcf</code>:</p>
                    <pre>$ cat ALL.2of4intersection.20100804.chr22.genotypes.annotated.newHeader.vcf ALL.2of4intersection.20100804.chr22.genotypes.annotated.variants.vcf &gt; ALL.2of4intersection.20100804.chr22.vcf</pre>
                    <p>Compress the newly created VCF file with the annotated variants:</p>
                    <pre>$ bgzip ALL.2of4intersection.20100804.chr22.vcf</pre>
                    <p>Index the newly created VCF file with the annotated variants:</p>
                    <pre>$ tabix -p vcf ALL.2of4intersection.20100804.chr22.vcf.gz</pre>
                    
                    <h3 id="workflow-summaries">Generation of summaries and images</h3>
                    <p>Generate gene and sample summaries for the annotated variants</p>
                    <pre>$ vcfSummary ALL.2of4intersection.20100804.chr22.vcf.gz gencode.v3c.annotation.GRCh37.cds.gtpc.ttpc.interval</pre>
                    <p>Resulting files should be: <code>ALL.2of4intersection.20100804.chr22.geneSummary.txt</code> and <code>ALL.2of4intersection.20100804.chr22.sampleSummary.txt</code></p>
                    <p>Make a new directory to store the images and VCF files for each gene.</p>
                    <pre>$ mkdir ALL.2of4intersection.20100804.chr22</pre>
                    <p>Generate an image for each gene with at least one annotated variant.</p>
                    <pre>$ vcf2images ALL.2of4intersection.20100804.chr22.vcf.gz gencode.v3c.annotation.GRCh37.cds.gtpc.ttpc.interval ./ALL.2of4intersection.20100804.chr22</pre>
                    <p>Subset the VCF file with the annotated variants by gene.</p>
                    <pre>$ vcfSubsetByGene ALL.2of4intersection.20100804.chr22.vcf.gz gencode.v3c.annotation.GRCh37.cds.gtpc.ttpc.interval ./ALL.2of4intersection.20100804.chr22</pre>
                    
                    <h3 id="workflow-webserver">Setting up the web server</h3>
                    <p>Make a gzipped tarball containing all of the relevant files:</p>
                    <ul>
                    	<li>Directory with the images and the VCF files for each gene (ALL.2of4intersection.20100804.chr22)</li>
                    	<li>File with the gene summary (ALL.2of4intersection.20100804.chr22.geneSummary.txt)</li>
                    	<li>File with the sample summary (ALL.2of4intersection.20100804.chr22.sampleSummary.txt)</li>
                    	<li>Compressed VCF file with the annotated variants (ALL.2of4intersection.20100804.chr22.vcf.gz)</li>
                    	<li>Index file of the annotated variants (ALL.2of4intersection.20100804.chr22.vcf.gz.tbi)</li>
                    </ul>
<pre>
$ tar -pczvf ALL.2of4intersection.20100804.chr22.tar.gz \
   ALL.2of4intersection.20100804.chr22 \
   ALL.2of4intersection.20100804.chr22.geneSummary.txt \
   ALL.2of4intersection.20100804.chr22.sampleSummary.txt \
   ALL.2of4intersection.20100804.chr22.vcf.gz \
   ALL.2of4intersection.20100804.chr22.vcf.gz.tbi
</pre>
					<p>Open the upload page of your VAT installation in your web browser and click on the “Processed data set” tab for the upload form for uploading processed data sets. Choose your .tar.gz archive using the file input box and click Submit. Once the file has been processed, click View Results.</p>
                </section>
            </div><!-- /content -->
            
            <footer>
            	<p>&copy; Gerstein Lab 2011</p>
            </footer>
        </div><!-- /container-fluid -->
    </body>
</html>