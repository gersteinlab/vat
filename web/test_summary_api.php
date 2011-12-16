<?php 

require_once 'lib/rest.php';

$request_body = array();

$url = "http://128.36.220.24/summary_api.php?annotation_set=gencode3b&set_id=1&type=coding";

$request = new RESTTxRequest($url, 'GET', $request_body);

try
{
    $request->execute();
}
catch (InvalidArgumentException $ie)
{
    die("Invalid argument exception: " . $ie);
}
catch (Exception $e)
{
    die("Cannot execute request to " . $url);  
}

echo "<h1>Gene summary</h1>";

$response_body = json_decode($request->get_response_body(), TRUE);

echo "<pre>\n";
var_dump($response_body['gene_summary']);
echo "</pre>\n";

echo "<h1>Sample summary</h1>";

echo "<pre>\n";
var_dump($response_body['sample_summary']);
echo "</pre>\n";

?>
