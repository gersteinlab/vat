<?php 

include_once 'lib/init.php';

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
        				<li>Installing
        					<ol>
        						<li><a href="#install-vat">Installing and Configuring VAT</a>
        							<ol>
        								<li><a href="#install-gsl-gd">Installation of the external GSL and GD libraries</a></li>
        								<li><a href="#install-libbios">Installation and Configuration of libBIOS</a></li>
        								<li><a href="#install-libs3">Installation of libs3</a></li>
        								<li><a href="#install-vat-proper">Installation and Configuration of VAT</a></li>
        							</ol>
        						</li>
        						<li><a href="#setup-web">VAT Web Application Setup</a>
        							<ol>
        								<li><a href="#configuring-php">Configuring PHP</a></li>
        								<li><a href="#vat-web-setup">VAT Setup and Configuration</a></li>
        							</ol>
        						</li>
        					</ol>
        				</li>
        				<li><a href="#data-formats">Data formats</a>
        					<ol>
        						<li><a href="#vcf-format">VCF</a></li>
        						<li><a href="#interval-format">Interval</a></li>
        					</ol>
        				</li>
        				<li><a href="#programs-list">List of programs</a>
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
        				</li>
        				<li><a href="#example-workflow">Example Workflow</a>
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
	                <h1>VAT Documentation</h1>
	            </div>
	            
	            <section id="install-vat">
					<div class="page-header">
						<h2>Installing and Configuration VAT</h2>
					</div>
				
                    <h3 id="install-gsl-gd">Installation of the external GSL and GD libraries</h3>
                    <p>In order to install VAT two external libraries must be installed first.  The libBIOS library depends on GSL, whereas VAT makes use of the GD library.  Please follow the instructions provided by each package.  The GSL library can be installed on most systems using the following commands (for details, please refer to the specific instructions at the GNU Scientific Library website):</p>
<pre>$ cd /path/to/gsl-1.14/
$ ./configure --prefix=`pwd`
$ make
$ make install</pre>
                    <p>Similarly, the GD library can be installed on most systems with the following commands:</p>
<pre>$ cd /path/to/gd-2.0.35/
$ ./configure --prefix=`pwd` --with-jpeg=/path/to/jpegLib/
$ make
$ make install</pre>
                    <p>After they are installed, the first step to install VAT is the installation and configuration of libBIOS.</p>
                    
                    <h3 id="install-libbios">Installation and Configuration of libBIOS</h3>
                    <p>Depending on where the three libraries (GSL, libBIOS, and GD) are installed, the following variables need to be set:</p>
<pre>export CPPFLAGS="-I/path/to/gsl-1.14/include -I/path/to/libbios/include -I/path/to/gd-2.0.35/include"
export LDFLAGS="-L/path/to/gsl-1.14/lib -L/path/to/libbios/lib -L/path/to/gd-2.0.35/lib"</pre>
                    <p>libBIOS can be installed on most systems with the following commands:</p>
<pre>$ cd /path/to/libbios-x.x.x/
$ ./configure --prefix=`pwd` 
$ make
$ make install</pre>
                    
                    <h3 id="install-libs3">Installation of libs3</h3>
                    <p>The VAT I/O layer uses libs3 to store  See the website of libs3 for the prerequisites of libs3. Without the prerequisites, libs3 will fail to build. Once the prerequisites are installed, libs3 can be installed as follows:</p>
<pre>$ cd /path/to/libs3-x.x.x/
$ make
$ make install
</pre>

                    <h3 id="install-vat-proper">Installation and Configuration of VAT</h3>
                    <p>A few simple steps are required to install VAT:</p>
<pre>$ cd /path/to/vat-x.x.x/
$ ./configure --prefix=`pwd` 
$ make
$ make install</pre>
                    
                    <p>VAT contains a configuration file that resides in one's home directory as <code>.vatrc</code> and in the web root as <code>vat.conf</code>, which contains a set of variables that are used by a number of different programs. The name/value pairs are space or tab-delimited. Empty lines are lines starting with '//' are ignored.</p>
<pre>// =============================================================================
// REQUIRED
// =============================================================================

// Tabix directory (includes both tabix and bgzip)
TABIX_DIR /path/to/tabixdir

// Directory where VAT executables are
VAT_EXEC_DIR /path/to/vat_exe


// =============================================================================
// OPTIONAL (required only for CGIs)
// =============================================================================

// Path to processed data sets
WEB_DATA_DIR /path/to/data/sets

// URL to preprocessed files
WEB_DATA_URL https://webserver/data/sets

// Path to the web data directory where the preprocessed files are stored
WEB_DATA_REFERENCE_DIR /path/to/data/reference

WEB_DATA_WORKING_DIR /path/to/data/working

WEB_DATA_RAW_DIR /path/to/data/raw

// =============================================================================
// AWS/S3 Configuration values
// =============================================================================

// Option for turning on or off Amazon Simple Storage Support (S3) support. 
// Use true to activate S3, false to deactivate. Note that if S3 support is
// active, you will need to enter your AWS account infomation for the VAT web
// components in web/lib/aws/config.inc.php
AWS_USE_S3 false

// S3 access key ID
AWS_ACCESS_KEY_ID access_key_id

// S3 secret access key
AWS_SECRET_ACCESS_KEY secret_key

// S3 hostname
AWS_S3_HOSTNAME s3.amazonaws.com

// The name of the S3 bucket for processed data sets. If S3 support is enabled,  
// this bucket is used instead of WEB_DATA_DIR
AWS_S3_DATA_BUCKET data-bucket

// The name of the S3 bucket for raw VCF input files. If S3 support is enabled,
// this bucket is used instead of WEB_DATA_RAW_DIR 
AWS_S3_RAW_BUCKET raw-bucket

// =============================================================================
// Set only if setting up as master node in master/worker configuration
// =============================================================================

// Set to true if we are using the master/worker cluster configuration, false
// if we are running single-node only
CLUSTER false

// IP address of master node. Used by worker to access the master's API
MASTER_ADDRESS xxx.xx.xxx.xx
// ----------------------------------------------------------------------------
// Used by master only:
// ----------------------------------------------------------------------------

// MySQL configuration
MASTER_MYSQL_HOST localhost
MASTER_MYSQL_USER user
MASTER_MYSQL_PASS pass
MASTER_MYSQL_DB dbname
</pre>
                    <p>This file has to be configured properly by filling in the required information. </p>
                    <p>Running make install will copy the configuration file to your home directory as <code>.vatrc</code> and is used when manually running VAT programs on the command line. Subsequently, the environment variable <code>VAT_CONFIG_FILE</code> should be set. It is recommended that your shell start-up script sets this variable:</p>
<pre>VAT_CONFIG_FILE=/pathTo/vat/.vatrc</pre>
					<p>A VAT configuration file also exists in the web root as <code>vat.conf</code> and is expected and loaded by the VAT web application. </p>
                </section>
                <section id="setup-web">
                	<div class="page-header">
                		<h2>VAT Web Application Setup</h2>
                	</div>
                	<p>This step is optional, but is very useful for visualizing the results of processed data sets.</p>
                	
                	<h3 id="configuring-php">Configuring PHP</h3>
                	<p>Due to the large file sizes uploaded to VAT, PHP must be configured to allow larger upload sizes. In your php.ini file, set <code>upload_max_filesize</code> and <code>post_max_size</code> to at least 100M:</p>
<pre>upload_max_filesize = 100M
post_max_size = 100M</pre>
					<p>It is also recommended to turn off output buffering so that <code>flush()</code> works properly:</p>
<pre>output_buffering = Off</pre>
					
					<h3 id="vat-web-setup">VAT Setup and Configuration</h3>
					<p>In the web directory under the VAT source tree, the VAT configuration file should have been copied into this directory during make. If it is not present, copy the VAT configuration file <code>default.vatrc</code> from the root of the source tree into the web directory and rename it <code>vat.conf</code>.</p>
					<p>Copy the contents of the web directory to your Apache web root directory. This is usually <code>/var/www/html</code> or <code>/var/www</code>. Make the /data directory that contains directory tree used by the VAT I/O layer readable and writable:</p>
<pre>$ sudo chmod -R 777 data</pre>
					<p>You will need to download the GENCODE annotation files used by VAT. The get_annotation_sets.sh script in the <code>/scripts</code> directory under the VAT source tree may be used to download all the necessary annotation files using wget:</p>
<pre>$ cd /web/root/data/reference
$ sudo /path/to/vat-x.x.x/scripts/get_annotation_sets.sh</pre>
                    <p>Edit the VAT configuration file in the web root according to your installation. If you wish to set up an Amazon S3-backed installation, create two web-accessible buckets, one for storing raw VCF files and one for storing processed data sets. In your VAT configuration file, enable S3-backed storage by setting the <code>AWS_USE_S3</code> directive to true and setting your AWS credentials and bucket names:</p>
<pre>AWS_USE_S3 true

AWS_ACCESS_KEY_ID access_key_id
AWS_SECRET_ACCESS_KEY secret_key

AWS_S3_DATA_BUCKET data-bucket
AWS_S3_RAW_BUCKET raw-bucket</pre>
					<p>The <code>WEB_DATA_URL</code> directive must be set to the URL where the processed data sets are stored. If S3-backed storage is enabled, it should be set to the S3 URL of your data bucket:</p>
<pre>WEB_DATA_URL http://s3.amazonaws.com/data-bucket</pre>
					<p>If you are setting up VAT to store all files locally, set <code>WEB_DATA_URL</code> to the URL to the directory where processed data sets are stored, which is by default <code>data/sets</code>:</p>
<pre>WEB_DATA_URL http://webserver/data/sets</pre>
					<p>Regardless of whether S3-backed storage is enabled, the <code>WEB_DATA_WORKING_DIR</code> directive must be set to the working directory that the I/O layer uses to give each VAT process a unique copy of files requested on demand. Also, the <code>WEB_DATA_REFERENCE_DIR</code> directive must be set to the directory containing the reference GENCODE annotation files. By default the directories are data/working and data/reference respectively:</p>
<pre>WEB_DATA_WORKING_DIR /web/root/data/working
WEB_DATA_REFERENCE_DIR /web/root/data/reference</pre>
					<p>If S3-backed storage is disabled, instead of using two S3 buckets, raw VCF files and processed data sets are stored in local directories. The directives <code>WEB_DATA_RAW_DIR</code> and <code>WEB_DATA_DIR</code> must be set to point to the directives used to store raw VCF files and processed data sets, which are by default <code>data/raw</code> and <code>data/sets</code> respectively:</p>
<pre>WEB_DATA_RAW_DIR /web/root/data/raw
WEB_DATA_DIR /web/root/data/sets</pre>
                </section>
	        	<section id="data-formats">
	        		<div class="page-header">
	        			<h2>Data formats</h2>
	        		</div>
	
     				<h3 id="vcf-format">VCF</h3>
     				<p>
     					The Variant Call Format (VCF) is a tab-delimited text file format to represent a number of different genetic variants including single nucleotide polymorphisms (SNPs), small insertions and deletions (indels), and structural variants (SVs). This format was developed as part of the <a href="http://www.1000genomes.org/" target="external">1000 Genomes Project</a>. A detailed summary of this file format can be found <a href="http://www.1000genomes.org/wiki/Analysis/Variant%20Call%20Format/vcf-variant-call-format-version-40" target="external">here</a>. The annotation information is captured as part of the <strong>INFO field</strong> using the <strong>VA (Variant Annotation) tag</strong>. The string with the variant information has the following format:
     				</p>
     				<pre>AlleleNumber:GeneName:GeneId:Strand:Type:FractionOfTranscriptsAffected:{List of transcripts}</pre>
     				<p>
     					All annotated variant use the above format to capture information about the gene. The format describing the list of affected transcripts depends on the variant class (SNP, indel, or SV) and the variant type as shown in the table below:
     				</p>
     				<table>
     					<thead>
     						<tr>
      						<th>Variant</th>
      						<th>Type<sup>1</sup></th>
      						<th>Transcript name</th>
      						<th>Transcirpt ID</th>
      						<th>Transcript length</th>
      						<th>Relative position of variant<sup>2</sup></th>
      						<th>Relative position of amino acid</th>
      						<th>Amino acid substitution</th>
      						<th>Transcript overlap</th>
     						</tr>
     					</thead>
     					<tbody>
     						<tr>
      						<th rowspan="5">SNP</th>
      						<td>synonymous</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>No</td>
      					</tr>
      					<tr>
      						<!-- SNP -->
      						<td>nonsynonymous</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>No</td>
      					</tr>
      					<tr>
      						<!-- SNP -->
      						<td>prematureStop</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>No</td>
      					</tr>
      					<tr>
      						<!-- SNP -->
      						<td>removedStop</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>No</td>
      					</tr>
      					<tr>
      						<!-- SNP -->
      						<td>spliceOverlap</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>No</td>
      					</tr>
      					
      					<tr>
      						<th rowspan="7">Indel</th>
      						<td>insertionFS</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>No</td>
      					</tr>
      					<tr>
      						<!-- Indel -->
      						<td>insertionNFS</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>No</td>
      					</tr>
      					<tr>
      						<!-- Indel -->
      						<td>deletionFS</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>No</td>
      					</tr>
      					<tr>
      						<!-- Indel -->
      						<td>deletionNFS</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>No</td>
      					</tr>
      					<tr>
      						<!-- Indel -->
      						<td>startOverlap</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>No</td>
      						<td>No</td>
      						<td>No</td>
      						<td>No</td>
      					</tr>
      					<tr>
      						<!-- Indel -->
      						<td>endOverlap</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>Yes</td>
      						<td>No</td>
      						<td>No</td>
      						<td>No</td>
      						<td>No</td>
      					</tr>
      					<tr>
    						<!-- Indel -->
       						<td>spliceOverlap</td>
       						<td>Yes</td>
       						<td>Yes</td>
       						<td>Yes</td>
       						<td>No</td>
       						<td>No</td>
       						<td>No</td>
       						<td>No</td>
       					</tr>
       					<tr>
       						<th>SV</th>
       						<td>svOverlap</td>
       						<td>Yes</td>
       						<td>Yes</td>
       						<td>Yes</td>
       						<td>No</td>
       						<td>No</td>
       						<td>No</td>
       						<td>Yes</td>
       					</tr>
      					</tbody>
      					<tfoot>
      						<tr>
      							<td colspan="9">
      								<h5>Notes:</h5>
      								<ol>
      									<li>FS &lt;=&gt; frameshift, NFS &lt;=&gt; non-frameshift</li>
      									<li>Relative position respect to the transcript start site</li>
      								</ol>
      							</td>
      						</tr>
      					</tfoot>
      				</table>
      				<p>
      					The allele number refers to the numbering of the alleles. By definition, the reference allele has zero as the allele number, whereas the alternate alleles are numbered starting at one (some variants have more than one alternate alleles). The type refers to the type of variant. For SNPs, the types can take on the following values (generated by snpMapper): synonymous, nonsynonymous, prematureStop, removedStop, and spliceOverlap. For indels (generated by indelMapper), the types can take on the following values: spliceOverlap, startOverlap, endOverlap, insertionFS, insertionNFS, deletionFS, deletionNFS, where FS denotes 'frameshift' and NFS indicates 'non-frameshift'. The term spliceOverlap (for both SNPs and indels) refers to a genetic variant that overlaps with a splice site (either two nucleotides downstream of an exon or two nucleotides upstream of an exon).
      				</p>
      				
      				<h4>Example 1</h4>
      				<p>A SNP is introducing a premature stop codon. This variant affects one out of five transcripts for this gene.</p>
      				<pre>chr1	23112837	.	A	T	.	PASS	AA=A;AC=7;AN=118;DP=168;SF=2;VA=1:EPHB2:ENSG00000133216:+:prematureStop:1/5:EPHB2-001:ENST00000400191:3165_3055_1019_K-&gt;*</pre>
       			
	       			<h4>Example 2</h4>
	       			<p>A SNP leads to a non-synonymous substitution. This variant affects two out of four transcripts for this gene.</p>
	       			<pre>chr1	1110357	.	G	A	.	PASS	AA=G;AC=3;AN=118;DP=203;SF=2;VA=1:TTLL10:ENSG00000162571:+:nonsynonymous:2/4:TTLL10-001:ENST00000379288:1212_1187_396_R-&gt;H:TTLL10-202:ENST00000400931:1212_1187_396_R-&gt;H</pre>
	       			
	       			<h4>Example 3</h4>
	       			<p>A SNP causing a non-synonymous substitution in one transcript and a splice overlap in another transcript of the same gene.</p>
	       			<pre>chr9	35819390	rs2381409	C	T	.	PASS	AA=N;AC=157;AN=240;DP=49;SF=0,1;VA=1:TMEM8B:ENSG00000137103:+:nonsynonymous:1/7:TMEM8B-202:ENST00000360192:2109_166_56_P-&gt;S,1:TMEM8B:ENSG00000137103:+:spliceOverlap:1/7:TMEM8B-001:ENST00000450762:2106</pre>
	       			
	       			<h4>Example 4</h4>
	       			<p>An indel with two alternate alleles. Each alternate allele leads to a non-frameshift deletion.</p>
	       			<pre>chr7	140118541	.	TACAACAACA	T,TACA	.	PASS	HP=1;VA=1:AC006344.1:ENSG00000236914:+:deletionNFS:1/1:AC006344.1-201:ENST00000434223:66_23_8_LQQQ-&gt;L,2:AC006344.1:ENSG00000236914:+:deletionNFS:1/1:AC006344.1-201:ENST00000434223:66_23_8_LQQ-&gt;L</pre>
	       			
	       			<p>
	       				Notice that multiple annotation entries are comma-separated. Multiple annotation entries arise when a variant causes different types of effects on different transcripts (Example 3) or if there are multiple alternate alleles (Example 4).
	       			</p>
	       			<p>
	       				VAT also enables the grouping of samples. For examples, samples can be assigned to different sub-populations or they can be designated as cases or controls. This is done by modifying the header line using vcfModifyHeader. Specifically, the sample is prefixed by group identifier using the ':' character as a delimiter.
	       			</p>
	       			
	       			<h3 id="interval-format">Interval</h3>
	       			<p>The Interval format consists of eight tab-delimited columns and is used to represent genomic intervals such as genes. This format is closely associated with the <a href="http://homes.gersteinlab.org/people/lh372/SOFT/bios/intervalFind_8c.html" target="external">intervalFind module</a>, which is part of libBIOS. This module efficiently finds intervals that overlap with a query interval. The underlying algorithm is based on containment sublists: Alekseyenko, A.V., Lee, C.J. "Nested Containment List (NCList): A new algorithm for accelerating interval query of genome alignment and interval databases" <em>Bioinformatics</em> 2007;23:1386-1393 <a href="http://bioinformatics.oxfordjournals.org/cgi/content/abstract/23/11/1386" target="external">[1]</a></p>
	       			<ol>
	       				<li>Name of the interval</li>
	       				<li>Chromosome</li>
	       				<li>Strand</li>
	       				<li>Interval start (with respect to the "+")</li>
	       				<li>Interval end (with respect to the "+")</li>
	       				<li>Number of sub-intervals</li>
	       				<li>Sub-interval starts (with respect to the "+", comma-delimited)</li>
	       				<li>Sub-interval end (with respect to the "+", comma-delimited)</li>
	       			</ol>
	       			<p>
	       				<strong>Note:</strong>  For the purpose of VAT, the name field in the Interval file must contain four pieces of information delimited by the '|' symbol (geneId|transcriptId|geneName|transcriptName). Using the gencode2interval program ensures proper formatting.
	       			</p>
	       			<p>Example file:</p>
	        			
<pre>ENSG00000008513|ENST00000319914|ST3GAL1|ST3GAL1-201	chr8	-	134472009	134488267	6	134472009,134474117,134475656,134477020,134478136,134487961	134472180,134474237,134475702,134477200,134478333,134488267
ENSG00000008513|ENST00000395320|ST3GAL1|ST3GAL1-202	chr8	-	134472009	134488267	6	134472009,134474117,134475656,134477020,134478136,134487961	134472180,134474237,134475702,134477200,134478333,134488267
ENSG00000008513|ENST00000399640|ST3GAL1|ST3GAL1-203	chr8	-	134472009	134488267	6	134472009,134474117,134475656,134477020,134478136,134487961	134472180,134474237,134475702,134477200,134478333,134488267
ENSG00000008516|ENST00000325800|MMP25|MMP25-201	chr16	+	3097544	3105947	4	3097544,3100009,3100254,3105830	3097548,3100145,3100546,3105947
ENSG00000008516|ENST00000336577|MMP25|MMP25-202	chr16	+	3096918	3109096	10	3096918,3097415,3100009,3100254,3107033,3107310,3107531,3108181,3108412,3108827	3097017,3097548,3100145,3100547,3107210,3107395,3107614,3108334,3108670,3109096</pre>
	        			
        		</section>
        		
                <section id="programs-list">
        		    <div class="page-header">
                        <h2>List of programs</h2>
                    </div>
        		    <h3 id="core-modules">VAT Core Modules</h3>
        		    <h4 id="snpMapper">snpMapper</h4>
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
        		    
        		    <h4 id="indelMapper">indelMapper</h4>
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
                    
                    <h4 id="svMapper">svMapper</h4>
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
                    
                    <h4 id="genericMapper">genericMapper</h4>
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
                    
                    <h4 id="vcfSummary">vcfSummary</h4>
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
                    
                    <h4 id="vcfImages">vcfImages</h4>
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
                    
                    <h4 id="vcfSubsetByGene">vcfSubsetByGene</h4>
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
                    
                    <h4 id="vcfModifyHeader">vcfModifyHeader</h4>
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
                    
                    <h3 id="auxiliary-programs">Auxiliary programs</h3>
                    
                    <h4 id="gencode2interval">gencode2interval</h4>
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
                    
                    <h4 id="interval2sequences">interval2sequences</h4>
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
                    
                    <h3 id="external-programs">External programs</h3>
                    
                    <h4 id="bgzip-tabix">bgzip/tabix</h4>
                    <p>
                        Tabix is generic tool that indexes position-sorted files in tab-delimited formats to facilitate fast retrieval. This tool was developed by Heng Li. For more information consult the <a href="http://samtools.sourceforge.net/tabix.shtml" target="_blank">tabix documentation page</a>.
                    </p>
                    
                    <h4 id="vcf-tools">VCF tools</h4>
                    <p>
                        <a href="http://vcftools.sourceforge.net/" target="_blank">VCF tools</a> consists of a suite of very useful modules to manipulate VCF files. For more information consult the <a href="http://vcftools.sourceforge.net/docs.html" target="_blank">documentation page</a>.
                    </p>
                </section>
                
                <section id="example-workflow">
                    <div class="page-header">
                        <h2>Example workflow</h2>
                    </div>
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
					<p>Open the upload page of your VAT installation in your web browser and click on the "Processed data set" tab for the upload form for uploading processed data sets. Choose your .tar.gz archive using the file input box and click Submit. Once the file has been processed, click View Results.</p>
                </section>
            </div><!-- /content -->
            
            <footer>
                <p>&copy; Gerstein Lab 2011</p>
            </footer>
        </div><!-- /container-fluid -->
    </body>
</html>