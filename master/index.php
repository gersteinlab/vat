
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
                        <li class="active"><a href="index.php">Home</a></li>
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
        
            <div class="hero-unit">
                <h1>Variant Annotation Tool</h1>
                <p>A computational framework to functionally annotate variants in personal genomes using a cloud-computing environment.</p>
                <p><a class="btn primary large" href="download.php">Latest version 2.0.1 &raquo;</a></p>
            </div>
        
            <div class="row">
		        <div class="span-one-third">
		            <h2>Uploaded data sets</h2>
		            <p>View a list of uploaded data sets processed and annotated using the Variant Annotation Tool.</p>
		            <p><a class="btn" href="sets.php">View data sets &raquo;</a></p>
		        </div>
		        <div class="span-one-third">
		            <h2>Documentation</h2>
		            <p>View the documentation for on VAT's architecture, instructions on prerequisites, installation and running VAT.</p>
		            <p><a class="btn" href="documentation.php">View documentation &raquo;</a></p>
		        </div>
		        <div class="span-one-third">
		            <h2>1000-Genomes Project</h2>
		            <p>We processed and annotate the genetic variants identified as part of the 1000 Genomes Pilot Project (R. M. Durbin et al., 2010).</p>
		            <p><a class="btn" href="datasets.php">View details &raquo;</a></p>
		        </div>
		    </div>
        
            <footer>
                <p>&copy; Gerstein Lab 2011</p>
            </footer>
        </div> <!-- /container -->
    </body>
</html>
