<?php 

require_once 'lib/init.php';
require_once 'libmaster/init_master.php';
require_once 'lib/rest.php';

$data = REST::process_request();

switch ($data->get_method())
{
    /*
     * If we are dealing with a PUT request, update the status of the data set
     * with the value provided in the request.
     */
    case 'put':
        $request_vars = $data->get_request_vars();
        $request_data = $data->get_data();
        
        if ( ! isset($request_vars['id']) || ! isset($request_data['status']))
        {
            $response_body = array(
                'error' => 'id and request data must be set'
            );
            
            REST::send_response(500, json_encode($response_body), REST::TYPE_JSON);
            break;
        }
        
        $set_id = $request_vars['id'];
        
        try
        {
            $model = Model::factory('dataset', $set_id);
        }
        catch (Exception $e)
        {
            $response_body = array(
                'error' => 'Data set with id ' . $set_id . ' cannot be found'
            );
            
            REST::send_response(500, json_encode($response_body), REST::TYPE_JSON);
            break;
        }
       
        $model->set('status', $request_data['status']);
        $model->save();
        
        $response_body = array(
            'status' => 'updated'
        );
        
        REST::send_response(200, json_encode($response_body), REST::TYPE_JSON);
        break;
        
    /*
     * If we are dealing with a GET request, return the information about the 
     * data set.
     */
    case 'get':
        $request_vars = $data->get_request_vars();
        if ( ! isset($request_vars['id']))
        {
            $response_body = array(
            	'error' => 'id must be set'
            );
            
            REST::send_response(500, json_encode($response_body), REST::TYPE_JSON);
            break;
        }
        
        $set_id = $request_vars['id'];
        try 
        {
            $model = Model::factory('dataset', $set_id);
        }
        catch (Exception $e)
        {
            $response_body = array(
                'error' => 'Data set with id ' . $set_id . ' cannot be found'
            );
            
            REST::send_response(500, json_encode($response_body), REST::TYPE_JSON);
        }
        
        $response_body = array(
            'dataset' => $model->as_array()
        );
        
        REST::send_response(200, json_encode($response_body), REST::TYPE_JSON);
        break;
    
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