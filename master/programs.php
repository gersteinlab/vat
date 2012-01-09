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
						<li><a href="#core-programs">VAT Core Modules</a>
							<ol>
								<li><a href="#snpMapper">snpMapper</a></li>
								<li><a href="#indelMapper">indelMapper</a></li>
								<li><a href="#svMapper">svMapper</a></li>
								<li><a href="#genericMapper">genericMapper</a></li>
								<li><a href="#vcfSummary">vcfSummary</a></li>
								<li><a href="#vcf2images">vcf2images</a></li>
								<li><a href="#vcfSubsetByGene">vcfSubsetByGene</a></li>
								<li><a href="#vcfModifyHeader">vcfModifyHeader</a></li>
							</ol>
						</li>
						<li><a href="#auxiliary-programs">Auxiliary programs</a>
							<ol>
								<li><a href="#gencode2interval">gencode2interval</a></li>
								<li><a href="#interval2sequences">interval2sequences</a></li>
							</ol>
						</li>
						<li><a href="#external-programs">External programs</a>
							<ol>
								<li><a href="#bgzip-tabix">bgzip/tabix</a></li>
								<li><a href="#vcf-tools">VCF Tools</a></li>
							</ol>
						</li>
					</ol>

        		</div><!-- /well -->
        	</div><!-- /sidebar -->
        
        	<div class="content">
	        	<div class="page-header">
	                <h1>List of programs</h1>
	            </div>
	            
	        	<section id="core-programs">
        		    <div class="page-header">
                        <h2>VAT Core Modules</h2>
                    </div>
                    
        		    <h3 id="snpMapper">snpMapper</h3>
        		    <p>
        		        snpMapper is a program to annotate a set of SNPs in VCF format. The program determines the effect of a SNP on the coding potential (synonymous, nonsynonymous, prematureStop, removedStop, spliceOverlap) of each transcript of a gene.
        		    </p>
        		    <h5>Usage</h5>
        		    <pre>snpMapper &lt;annotation.interval&gt; &lt;annotation.fa&gt;</pre>
        		    <table class="bordered-table">
        		        <tbody>
        		            <tr>
                                <th>Inputs</th>
                                <td>Takes a VCF input from STDIN</td>
        		            </tr>
        		            <tr>
                                <th>Outputs</th>
                                <td>
                                    Outputs annotated SNPs in VCF format. The annotation information is captured as part of the INFO field. For details refer to the VCF format specification.
                                </td>
                            </tr>
                            <tr>
                                <th>Required arguments</th>
                                <td>
                                    <ul>
                                        <li><strong>annotation.interval</strong> - Annotation file representing the genomic coordinates of the gene models in Interval format. Each line in this file represents a transcript. This file is typically generated using the gencode2interval program.</li>
                                        <li><strong>annotation.fa</strong> - File with the transcript sequences in FASTA format for each entry specified in annotation.interval. This file is typically generated by the interval2sequences program using the 'exonic' mode.</li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <th>Optional Arguments</th>
                                <td>
                                    <em>None</em>
                                </td>
                            </tr>
        		        </tbody>
        		    </table>
        		    
        		    <h3 id="indelMapper">indelMapper</h3>
        		    <p>
                        indelMapper is a program to annotate a set of indels in VCF format. The program determines the effect of an indel on the coding potential (frameshift insertion, non-frameshift insertion, frameshift deletion, non-frameshift deletion, spliceOverlap, startOverlap, endOverlap) of each transcript of a gene.
        		    </p>
        		    <h5>Usage</h5>
        		    <pre>indelMapper &lt;annotation.interval&gt; &lt;annotation.fa&gt;</pre>
        		    <table class="bordered-table">
                        <tbody>
                            <tr>
                                <th>Inputs</th>
                                <td>Takes a VCF input from STDIN</td>
                            </tr>
                            <tr>
                                <th>Outputs</th>
                                <td>Outputs annotated indels in VCF format. The annotation information is captured as part of the INFO field. For details refer to the VCF format specification.</td>
                            </tr>
                            <tr>
                                <th>Required arguments</th>
                                <td>
                                    <ul>
                                        <li><strong>annotation.interval</strong> - Annotation file representing the genomic coordinates of the gene models in Interval format. Each line in this file represents a transcript. This file is typically generated using the gencode2interval program.</li>
                                        <li><strong>annotation.fa</strong> - File with the transcript sequences in FASTA format for each entry specified in annotation.interval. This file is typically generated by the interval2sequences program using the 'exonic' mode.</li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <th>Optional Arguments</th>
                                <td>
                                    <em>None.</em>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h3 id="svMapper">svMapper</h3>
                    <p>
                        svMapper is a program to annotate a set of SVs in VCF format. The program determines if a SV overlaps with different transcript isoforms of a gene.
                    </p>
                    <h5>Usage</h5>
                    <pre>svMapper &lt;annotation.interval&gt;</pre>
                    <table class="bordered-table">
                        <tbody>
                            <tr>
                                <th>Inputs</th>
                                <td>Takes a VCF input from STDIN</td>
                            </tr>
                            <tr>
                                <th>Outputs</th>
                                <td>Outputs annotated SVs in VCF format. The annotation information is captured as part of the INFO field. For details refer to the VCF format specification.</td>
                            </tr>
                            <tr>
                                <th>Required arguments</th>
                                <td>
                                    <ul>
                                        <li><strong>annotation.interval</strong> - Annotation file representing the genomic coordinates of the gene models in Interval format. Each line in this file represents a transcript. This file is typically generated using the gencode2interval program.</li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <th>Optional Arguments</th>
                                <td>
                                    <em>None.</em>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h3 id="genericMapper">genericMapper</h3>
                    <p>genericMapper is a program to annotate a number of different variants in VCF format. The program checks whether a variant overlaps with entries in the specified annotation set (it does not determine the effect on the coding potential).</p>
                    <h5>Usage</h5>
                    <pre>genericMapper &lt;annotation.interval&gt; &lt;nameFeature&gt;</pre>
                    <table class="bordered-table">
                        <tbody>
                            <tr>
                                <th>Inputs</th>
                                <td>Takes a VCF input from STDIN</td>
                            </tr>
                            <tr>
                                <th>Outputs</th>
                                <td>Outputs the annotated variants in VCF format. The annotation information is captured as part of the INFO field.</td>
                            </tr>
                            <tr>
                                <th>Required arguments</th>
                                <td>
                                    <ul>
                                        <li><strong>annotation.interval</strong> - Annotation file representing the genomic coordinates of the gene models in Interval format. This can be a generic Interval.</li>
                                        <li><strong>nameFeature</strong> - Specifies the type of the annotation feature (for example promotor regions). The name of the feature is included as part of the annotation information (in the INFO field) in the resulting VCF file.</li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <th>Optional arguments</th>
                                <td>
                                    <em>None.</em>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h3 id="vcfSummary">vcfSummary</h3>
                    <p>vcfSummary is a program to aggregate annotated variants across genes and samples.</p>
                    <h5>Usage</h5>
                    <pre>vcfSummary &lt;file.vcf.gz&gt; &lt;annotation.interval&gt;</pre>
                    <table class="bordered-table">
                        <tbody>
                            <tr>
                                <th>Inputs</th>
                                <td>None</td>
                            </tr>
                            <tr>
                                <th>Outputs</th>
                                <td>Generates two output files. The first file, named <code>file.geneSummary.txt</code>, contains the number of variants categorized by type for each gene. A second file, named <code>file.sampleSummary.txt</code>, summarizes number of variants categorized by type for each sample.</td>
                            </tr>
                            <tr>
                                <th>Required arguments</th>
                                <td>
                                    <ul>
                                        <li><strong>file.vcf.gz</strong> - VCF file with annotated variants (this can be a mixture of indels and SNPs). This file must be compressed using bgzip and indexed using the tabix program.</li>
                                        <li><strong>annotation.interval</strong> - Annotation file representing the genomic coordinates of the gene models in Interval format. Each line in this file represents a transcript. This file is typically generated using the gencode2interval program.</li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <th>Optional arguments</th>
                                <td>
                                    <em>None.</em>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h3 id="vcfImages">vcfImages</h3>
                    <p>vcf2images is a program to generate an image for each gene to visualize effect of the annotated variants.</p>
                    <h5>Usage</h5>
                    <pre>vcf2images &lt;file.vcf.gz&gt; &lt;annotation.interval&gt; &lt;outputDir&gt;</pre>
                    <table class="bordered-table">
                        <tbody>
                            <tr>
                                <th>Inputs</th>
                                <td><em>None.</em></td>
                            </tr>
                            <tr>
                                <th>Outputs</th>
                                <td>Generates an image in PNG format for each gene that has at least one annotated variant.</td>
                            </tr>
                            <tr>
                                <th>Required arguments</th>
                                <td>
                                    <ul>
                                        <li><strong>file.vcf.gz</strong> - VCF file with annotated variants (this can be a mixture of SNPs, indels, and SVs). This file must be compressed using bgzip and indexed using the tabix program.</li>
                                        <li><strong>annotation.interval</strong> - Annotation file representing the genomic coordinates of the gene models in Interval format. Each line in this file represents a transcript. This file is typically generated using the gencode2interval program.</li>
                                        <li><strong>outputDir</strong> - The output directory where the images are stored</li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <th>Optional Arguments</th>
                                <td>
                                    <em>None.</em>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h3 id="vcfSubsetByGene">vcfSubsetByGene</h3>
                    <p>vcfSubsetByGene is a program to subset a VCF file with annotated variants by gene.</p>
                    <h5>Usage</h5>
                    <pre>vcfSubsetByGene &lt;file.vcf.gz&gt; &lt;annotation.interval&gt; &lt;outputDir&gt;</pre>
                    <table class="bordered-table">
                        <tbody>
                            <tr>
                                <th>Inputs</th>
                                <td><em>None.</em></td>
                            </tr>
                            <tr>
                                <th>Outputs</th>
                                <td>Generates a VCF file for each gene that has at least one annotated variant.</td>
                            </tr>
                            <tr>
                                <th>Required arguments</th>
                                <td>
                                    <ul>
                                        <li><strong>file.vcf.gz</strong> - VCF file with annotated variants (this can be a mixture of indels and SNPs). This file must be compressed using bgzip and indexed using the tabix program.</li>
                                        <li><strong>annotation.interval</strong> - Annotation file representing the genomic coordinates of the gene models in Interval format. Each line in this file represents a transcript. This file is typically generated using the gencode2interval program.</li>
                                        <li><strong>outputDir</strong> - The output directory where VCF files are stored</li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <th>Optional Arguments</th>
                                <td>
                                    <em>None.</em>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h3 id="vcfModifyHeader">vcfModifyHeader</h3>
                    <p>vcfModifyHeader is a program to modify the header line (part of the meta-lines) in a VCF file. Specifically, it assigns each sample to a group or population (these assignments are used by other programs including vcfSummary).</p>
                    <pre>vcfModifyHeader &lt;oldHeader.vcf&gt; &lt;groups.txt&gt;</pre>
                    <table class="bordered-table">
                        <tbody>
                            <tr>
                                <th>Inputs</th>
                                <td><em>None.</em></td>
                            </tr>
                            <tr>
                                <th>Outputs</th>
                                <td>Generates a VCF header file.</td>
                            </tr>
                            <tr>
                                <th>Required arguments</th>
                                <td>
                                    <ul>
                                        <li>
                                            <strong>oldHeader.vcf</strong> - The meta lines of a VCF file. It can be obtained by using the following command:
                                            <pre>grep '#' file.vcf &gt; file.header.vcf</pre>
                                        </li>
                                        <li>
                                            <strong>groups.txt</strong> - This tab-delimited file that assigns each sample present in the VCF to a group/population. Here is a small sample file:
<pre>HG00629 CHS
HG00634 CHS
HG00635 CHS
HG00637 PUR
HG00638 PUR
HG00640 PUR
NA06984 CEU
NA06985 CEU
NA06986 CEU
NA06989 CEU
NA06994 CEU</pre>
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <th>Optional arguments</th>
                                <td>
                                    <em>None.</em>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>
                
                <section id="auxiliary-programs">
                
                	<div class="page-header">
                    	<h2>Auxiliary programs</h2>
                    </div>
                    
                    <h3 id="gencode2interval">gencode2interval</h3>
                    <p>gencode2interval converts a GENCODE annotation file (in <a href="http://genome.ucsc.edu/FAQ/FAQformat.html#format4" target="_blank"></a>GTF format) to the Interval format.
                    <h5>Usage</h5>
                    <pre>gencode2interval</pre>
                    <table class="bordered-table">
                        <tbody>
                            <tr>
                                <th>Inputs</th>
                                <td>Takes a GENCODE annotation file in GTF format from STDIN</td>
                            </tr>
                            <tr>
                                <th>Outputs</th>
                                <td>Outputs the GENCODE annotation file in Interval format to STDOUT</td>
                            </tr>
                            <tr>
                                <th>Required arguments</th>
                                <td><em>None.</em></td>
                            </tr>
                            <tr>
                                <th>Optional arguments</th>
                                <td><em>None.</em></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <p>
                                        <strong>Note: </strong>To obtain the coding sequences of the elements with gene_type <code>protein_coding</code> and transcript_type <code>protein_coding</code> the following command should be used:
                                    </p>
<pre>awk '/\t(HAVANA|ENSEMBL)\tCDS\t/ {print}' gencode.v3c.annotation.GRCh37.gtf | grep 'gene_type "protein_coding"' | grep 'transcript_type "protein_coding"' &gt; gencode.v3c.annotation.GRCh37.cds.gtpc.ttpc.gtf
gencode2interval &lt; gencode.v3c.annotation.GRCh37.cds.gtpc.ttpc.gtf &gt; gencode.v3c.annotation.GRCh37.cds.gtpc.ttpc.interval</pre>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <h3 id="interval2sequences">interval2sequences</h3>
                    <p>Module to retrieve genomic/exonic sequences for an annotation set in Interval format.</p>
                    <h5>Usage</h5>
                    <pre>interval2sequences &lt;file.2bit&gt; &lt;file.annotation&gt; &lt;exonic|genomic&gt;</pre>
                    <table class="bordered-table">
                        <tbody>
                            <tr>
                                <th>Inputs</th>
                                <td><em>None.</em></td>
                            </tr>
                            <tr>
                                <th>Outputs</th>
                                <td>Reports the extracted sequences in FASTA format</td>
                            </tr>
                            <tr>
                                <th>Required arguments</th>
                                <td>
                                    <ul>
                                        <li><strong>file.2bit</strong> - genome reference sequence in <a href="http://genome.ucsc.edu/FAQ/FAQformat.html#format7" target="_blank">2bit format</a></li>
                                        <li><strong>file.annotation</strong> - annotation set in Interval format (each line represents one transcript)</li>
                                        <li><strong>&lt; exonic | genomic &gt;</strong> - exonic means that only the exonic regions are extracted, while genomic indicates that the intronic sequences are extracted as well</li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <th>Optional arguments</th>
                                <td><em>None.</em></td>
                            </tr>
                        </tbody>
                    </table>
                </section>
                    
                <section id="external-programs">
                    <div class="page-header">
                        <h2>External programs</h2>
                    </div>
                
                    
                    <h3 id="bgzip-tabix">bgzip/tabix</h3>
                    <p>
                        Tabix is generic tool that indexes position-sorted files in tab-delimited formats to facilitate fast retrieval. This tool was developed by Heng Li. For more information consult the <a href="http://samtools.sourceforge.net/tabix.shtml" target="_blank">tabix documentation page</a>.
                    </p>
                    
                    <h3 id="vcf-tools">VCF tools</h3>
                    <p>
                        <a href="http://vcftools.sourceforge.net/" target="_blank">VCF tools</a> consists of a suite of very useful modules to manipulate VCF files. For more information consult the <a href="http://vcftools.sourceforge.net/docs.html" target="_blank">documentation page</a>.
                    </p>
                </section>
        	</div><!-- /content -->
        	
        	<footer>
                <p>&copy; Gerstein Lab 2011</p>
            </footer>
        </div><!-- /container-fluid -->
    </body>
</html>