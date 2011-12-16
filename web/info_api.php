<?php 

require_once 'lib/init.php';
require_once 'lib/util.php';
require_once 'lib/cfio.php';
require_once 'lib/vat.php';
require_once 'lib/rest.php';

$data = REST::process_request();

switch ($data->get_method())
{
    case 'get':
        $request_vars = $data->get_request_vars();
        
        if ( ! isset($request_vars['annotation_set']) ||
             ! isset($request_vars['type']) ||
             ! isset($request_vars['set_id']) ||
             ! isset($request_vars['gene_id']))
        {
            $response_body = array(
                'error' => 'annotation_set, gene_id, type, and set_id must all be set'
            );
            
            REST::send_response(500, json_encode($response_body), REST::TYPE_JSON);
            break;
        }
        
        $annotation_set = $request_vars['annotation_set'];
        $type           = $request_vars['type'];
        $set_id         = $request_vars['set_id'];
        $gene_id        = $request_vars['gene_id'];
        $data_set       = 'vat.' . $set_id;
        
        $cfio = new CFIO();
        $cfio->set_set_id($set_id);
        
        try
        {
            $cfio->get_gene_data($gene_id);
        }
        catch (Exception $e)
        {
            $response_body = array(
                'error' => "Cannot get VCF file for gene " . $gene_id
            );
            
            REST::send_response(500, json_encode($response_body), REST::TYPE_JSON);
            break;
        }
        
        $file = $cfio->get_working_dir() . '/vat.' . $set_id . '/' . $gene_id . '.vcf';
        
        try
        {
            $info = get_info($file, $data_set, $annotation_set, $gene_id, $type, $set_id);
        }
        catch (Exception $e)
        {
            $response_body = array(
    			'error' => "Cannot get info: " . $e->getMessage()
            );
            
            REST::send_response(500, json_encode($response_body), REST::TYPE_JSON);
            break;
        }
        
        $response_body = array(
            'info' => $info
        );
        
        REST::send_response(200, json_encode($response_body), REST::TYPE_JSON);
        break;
        
    case 'put':
    case 'post':
    case 'delete':
    default:
        $response_body = array(
                'not' => 'implemented. lol',
        );
    
        REST::send_response(501, json_encode($response_body), 'application/json');
        break;
}