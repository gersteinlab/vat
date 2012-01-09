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
					</ol>

        		</div><!-- /well -->
        	</div><!-- /sidebar -->
        
        	<div class="content">
	        	<div class="page-header">
	                <h1>Installing</h1>
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
            </div><!-- /content -->
            
            <footer>
            	<p>&copy; Gerstein Lab 2011</p>
            </footer>
        </div><!-- /container-fluid -->
    </body>
</html>