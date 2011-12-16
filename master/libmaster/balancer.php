<?php defined('VAT_SRC') or die('No direct script access');

class Balancer {
    protected $_servers = array();
    protected $_db = NULL;
    
    public function __construct()
    {
        
    }
    
    public function connect()
    {
        @$this->_db = new mysqli($vat_config['MASTER_MYSQL_HOST'],
                                 $vat_config['MASTER_MYSQL_USER'],
                                 $vat_config['MASTER_MYSQL_PASS'],
                                 $vat_config['MASTER_MYSQL_DB']);
        
        if (mysqli_connect_errno()) {
            return FALSE;
        }
        
        $query = "SELECT * FROM workers";
        $result = $this->_db->query($query);
        $num_rows = $result->num_rows;
        
        for ($i = 0; $i < $num_rows; $i++) {
            $row = $result->fetch_assoc();
            
            $this->_servers[$row['id']] = $row;
        }
        
        $result->free();
        
        return TRUE;
    }
    
    private function _save_server($id)
    {
        
    }
    
    public function get_server()
    {
        
    }
    
    public function add_servers($num_servers) 
    {
        
    }
    
    public function remove_server($id) 
    {
        
    }
    
    public function __destruct()
    {
        $this->_servers = NULL;
        $this->_db->close();
        $this->_db = NULL;
    }
}

?>