<?php defined('VAT_SRC') or die('No direct script access');
/**
 * Simple REST library adapted from tutorials [Create a REST API with PHP][ref-rest1]
 * and [Making RESTful requests in PHP][ref-rest2] by Ian Selby
 * 
 * [ref-rest1]: http://www.gen-x-design.com/archives/create-a-rest-api-with-php/
 * [ref-rest2]: http://www.gen-x-design.com/archives/making-restful-requests-in-php/
 * 
 * @author adapted by David Z. Chen
 * 
 */


/**
 * REST helper class 
 *
 * @static bool process_request()
 * @static bool send_response()
 * @static string get_status_code_message()
 */

class REST {
    
    const TYPE_JSON = 'application/json';
    const TYPE_HTML = 'text/html';
    const TYPE_XML  = 'application/xml';
    
    /**
     * 
     * Enter description here ...
     */
    public static function process_request()
    {
        $request_method = strtolower($_SERVER['REQUEST_METHOD']);
        $return_obj = new RESTRxRequest();
        $data = array();
        
        switch ($request_method) 
        {
            case 'get':
                $data = $_GET;
                break;
            case 'post':
                $data = $_POST;
                break;
            case 'put':
                // PUT variables are set as a query string passed through PHP's
                // special input location and parsed using parse_str
                parse_str(file_get_contents('php://input'), $put_vars);
                $data = $put_vars;
                break;
        }
        
        $return_obj->set_method($request_method);
        $return_obj->set_request_vars($data);
        
        if (isset($data['data'])) 
        {
            $return_obj->set_data(json_decode($data['data'], TRUE));
        }
        
        return $return_obj;
    }

    /**
     * 
     * Enter description here ...
     * @param unknown_type $status
     * @param unknown_type $body
     * @param unknown_type $content_type
     */
    public static function send_response($status = 200, 
                                         $body = '', 
                                         $content_type = 'text/html')
    {
        $status_header = 'HTTP/1.1 ' . $status . ' ' . 
                         REST::get_status_code_message($status);
        // set the status
        header($status_header);
        // set the content type
        header('Content-type: ' . $content_type);
        
        // pages with body are easy
        if ($body != '') 
        {
            // send the body
            echo $body;
            exit;
        }
        // we need to create the body if none is passed
        else 
        {
            // create some body messages
            $message = '';
        
            // this is purely optional, but makes the pages a little nicer to read
            // for your users.  Since you won't likely send a lot of different status codes,
            // this also shouldn't be too ponderous to maintain
            switch ($status) 
            {
                case 401:
                    $message = 'You must be authorized to view this page.';
                    break;
                case 404:
                    $message = 'The requested URL ' . $_SERVER['REQUEST_URI'] . ' was not found.';
                    break;
                case 500:
                    $message = 'The server encountered an error processing your request.';
                    break;
                case 501:
                    $message = 'The requested method is not implemented.';
                    break;
            }
        
            // servers don't always have a signature turned on 
            // (this is an apache directive "ServerSignature On")
            $signature = ($_SERVER['SERVER_SIGNATURE'] == '') 
                       ? $_SERVER['SERVER_SOFTWARE'] . ' Server at ' . 
                         $_SERVER['SERVER_NAME'] . ' Port ' . 
                         $_SERVER['SERVER_PORT'] 
                       : $_SERVER['SERVER_SIGNATURE'];
        
            // this should be templatized in a real-world solution
            $body = array(
                'statuscode' => $status,
                'status' => REST::get_status_code_message($status),
                'message' => $message,
                'signature' => $signature,
            );    
        
            $body = json_encode($body);
            
            echo $body;
            exit;
        }
    }

    public static function get_status_code_message($status)
    {
        // these could be stored in a .ini file and loaded
        // via parse_ini_file()... however, this will suffice
        // for an example
        $codes = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Unused)',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported'
        );

        return (isset($codes[$status])) ? $codes[$status] : '';
    }
}

class RESTRxRequest {
    
    private $_request_vars;
    private $_data;
    private $_http_accept;
    private $_method;

    public function __construct()
    {
        $this->_request_vars = array();
        $this->_data         = '';
        $this->_http_accept  = (strpos($_SERVER['HTTP_ACCEPT'], 'json')) 
                             ? 'json' 
                             : 'xml';
        $this->_method       = 'get';
    }

    public function set_data($data)
    {
        $this->_data = $data;
        return $this;
    }

    public function set_method($method)
    {
        $this->_method = $method;
        return $this;
    }

    public function set_request_vars($request_vars)
    {
        $this->_request_vars = $request_vars;
        return $this;
    }

    public function get_data()
    {
        return $this->_data;
    }

    public function get_method()
    {
        return $this->_method;
    }

    public function get_http_accept()
    {
        return $this->_http_accept;
    }

    public function get_request_vars()
    {
        return $this->_request_vars;
    }
}

/**
 * 
 * Enter description here ...
 * @author dzc
 *
 */
class RESTTxRequest {
    
    protected $_url;
    protected $_method;
    protected $_request_body;
    protected $_request_length;
    protected $_username;
    protected $_password;
    protected $_accept_type;
    protected $_response_body;
    protected $_response_info;
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $url
     * @param unknown_type $method
     * @param unknown_type $request_body
     */
    public function __construct($url = NULL,
                                $method = 'GET',
                                $request_body = NULL)
    {
        $this->_url            = $url;
        $this->_method         = $method;
        $this->_request_body   = $request_body;
        $this->_request_length = 0;
        $this->_username       = NULL;
        $this->_password       = NULL;
        $this->_accept_type    = 'application/json';
        $this->_response_body  = NULL;
        $this->_response_info  = NULL;
        
        if ($this->_request_body !== NULL)
        {
            $this->build_post_body();
        }
    }
    
    /**
     * 
     * Enter description here ...
     */
    public function flush()
    {
        $this->_request_body   = NULL;
        $this->_request_length = 0;
        $this->_method         = 'GET';
        $this->_response_body  = NULL;
        $this->_response_info  = NULL;
    }
    
    public function get_response_body()
    {
        return $this->_response_body;
    }
    
    public function get_response_info()
    {
        return $this->_response_info;
    }
    
    /**
     * 
     * Enter description here ...
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function execute()
    {
        $ch = curl_init();
        $this->_set_auth($ch);
        
        try 
        {
            switch (strtoupper($this->_method)) 
            {
                case 'GET':
                    $this->_execute_GET($ch);
                    break;
                case 'POST':
                    $this->_execute_POST($ch);
                    break;
                case 'PUT':
                    $this->_execute_PUT($ch);
                    break;
                case 'DELETE':
                    $this->_execute_DELETE($ch);
                    break;
                default:
                    throw new InvalidArgumentException('Method ' . $this->_method . 'is an invalid REST method');
            }
        } 
        catch (InvalidArgumentException $e) 
        {
            curl_close($ch);
            throw $e;
        } 
        catch (Exception $e) 
        {
            curl_close($ch);
            throw $e;
        }
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $data
     * @throws InvalidArgumentException
     */
    public function build_post_body($data = NULL)
    {
        $data = ($data !== NULL) ? $data : $this->_request_body;
        
        if ( ! is_array($data)) 
        {
            throw new InvalidArgumentException('Invalid data input for postBody. Array expected');
        }
        
        $data = http_build_query($data, '', '&');
        $this->_request_body = $data;
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $ch
     */
    protected function _execute_GET($ch)
    {
        $this->_execute($ch);
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $ch
     */
    protected function _execute_POST($ch)
    {
        if ( ! is_string($this->_request_body)) 
        {
            $this->build_post_body();
        }
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_request_body);
        curl_setopt($ch, CURLOPT_POST, 1);
        
        $this->_execute($ch);
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $ch
     */
    protected function _execute_PUT($ch) 
    {
        if ( ! is_string($this->_request_body)) 
        {
            $this->_build_post_body();
        }
        
        $this->_request_length = strlen($this->_request_body);
        
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, $this->_request_body);
        rewind($fh);
        
        curl_setopt($ch, CURLOPT_INFILE, $fh);
        curl_setopt($ch, CURLOPT_INFILESIZE, $this->_request_length);
        curl_setopt($ch, CURLOPT_PUT, TRUE);
        
        $this->_execute($ch);
        
        fclose($fh);
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $ch
     */
    protected function _execute_DELETE($ch)
    {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        
        $this->_execute($ch);
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $curl_handle
     */
    protected function _execute(&$curl_handle)
    {
        $this->_set_curl_ops($curl_handle);
        $this->_response_body = curl_exec($curl_handle);
        $this->_response_info = curl_getinfo($curl_handle);
        
        curl_close($curl_handle);
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $curl_handle
     */
    protected function _set_curl_ops(&$curl_handle)
    {
        curl_setopt($curl_handle, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl_handle, CURLOPT_URL, $this->_url);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, 
                    array('Accept:' . $this->_accept_type));
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $curl_handle
     */
    protected function _set_auth(&$curl_handle)
    {
        if ($this->_username !== NULL && $this->_password !== NULL) 
        {
            curl_setopt($curl_handle, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            curl_setopt($curl_handle, CURL_USERPWD, 
                        $this->_username . ':' . $this->_password);
        }
    }
}



?>