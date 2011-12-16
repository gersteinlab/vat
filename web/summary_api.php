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
             ! isset($request_vars['set_id']))
        {
            $response_body = array(
                'error' => 'annotation_set, type, and set_id must all be set'
            );
            
            REST::send_response(500, json_encode($response_body), REST::TYPE_JSON);
            break;
        }
        
        $annotation_set = $request_vars['annotation_set'];
        $type           = $request_vars['type'];
        $set_id         = $request_vars['set_id'];
        $data_set       = 'vat.' . $set_id;
        
        $cfio = new CFIO();
        $cfio->set_set_id($set_id);
        
        try
        {
            $cfio->get_data(array('gene_summary', 'sample_summary'));
            
            $gene_summary = get_gene_summary($cfio->get_working_dir(), $data_set, $annotation_set, $type, $set_id);
            $sample_summary = get_sample_summary($cfio->get_working_dir(), $data_set);
        }
        catch (Exception $e)
        {
            $response_body = array(
                'error' => 'Cannot get summary info: ' . $e->getMessage()
            );
            
            REST::send_response(500, json_encode($response_body), REST::TYPE_JSON);
            break;
        }
        
        $gene_summary['bProcessing']       = TRUE;
        $gene_summary['iDisplayLength']    = 25;
        $gene_summary['bStateSave']        = TRUE;
        $gene_summary['sPaginationType']   = "full_numbers";
        
        $sample_summary['bProcessing']     = TRUE;
        $sample_summary['iDisplayLength']  = 25;
        $sample_summary['bStateSave']      = TRUE;
        $sample_summary['sPaginationType'] = "full_numbers";
        
        $response_body = array(
            'gene_summary' => $gene_summary,
            'sample_summary' => $sample_summary
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


?>