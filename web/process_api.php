<?php 

require_once 'lib/init.php';
require_once 'lib/util.php';
require_once 'lib/rest.php';
require_once 'lib/cfio.php';

$data = REST::process_request();

switch ($data->get_method())
{
    case 'post':
        $request_vars = $data->get_request_vars();
        
        if ( ! isset($request_vars['annotation_file']) ||
             ! isset($request_vars['variant_type']) ||
             ! isset($request_vars['raw_filename']) ||
             ! isset($request_vars['set_id']))
        {
            $response_body = array(
                'error' => 'annotation_file, variant_type, raw_filename, and set_id must all be set'
            );
            
            REST::send_response(500, json_encode($response_body), REST::TYPE_JSON);
            break;
        }
        
        $annotation_file = $request_vars['annotation_file'];
        $variant_type    = $request_vars['variant_type'];
        $raw_filename    = $request_vars['raw_filename'];
        $set_id          = $request_vars['set_id'];
        
        $cfio = new CFIO();
        
        try
        {
            $cfio->get_raw($raw_filename);
        }
        catch (Exception $e)
        {
            $response_body = array(
                'error' => 'File ' . $raw_filename . ' could not be retrieved.'
            );
            
            REST::send_response(500, json_encode($response_body), REST::TYPE_JSON);
        }
        
        $cmd = sprintf("php process_cli.php %s %s %s %s > /dev/null &", $raw_filename, $annotation_file, $variant_type, $set_id);
        exec($cmd);
        
        $response_body = array(
            'processed' => '1'
        );
        
        REST::send_response(200, json_encode($response_body), REST::TYPE_JSON);
        break;
        
    case 'get':
    case 'put':
    case 'delete':
    default:
        $response_body = array(
            'not' => 'implemented. lol',
        );
        
        REST::send_response(501, json_encode($response_body), 'application/json');
        break;
}



?>