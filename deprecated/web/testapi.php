<?php 

require_once 'lib/rest.php';

$request_body = array(
    'test' => 'test'
);

$url = "http://128.36.220.24/vat_summary_api.php";

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

echo "<pre>\n";

print_r($request, TRUE);

echo "</pre>\n";

?>