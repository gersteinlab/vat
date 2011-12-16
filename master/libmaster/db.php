<?php defined('VAT_SRC') or die('No direct script access');
/**
 * Very thin wrapper around mysqli as a basis for a better database abstraction
 * layer. Might be nice to make a neat little query builder at some point.
 * 
 * @author David Z. Chen
 *
 */

class DB {
    
    protected $_host;
    
    protected $_user;
    
    protected $_pass;
    
    protected $_db;
    
    protected $_connection;
    
    public function __construct($host, $user, $pass, $db)
    {
        $this->_host = $host;
        $this->_user = $user;
        $this->_pass = $pass;
        $this->_db = $db;
        
        $this->_connection = NULL;
    }
    
    public function connect()
    {
        $this->_connection = new mysqli($this->_host, $this->_user, $this->_pass, $this->_db);
        
        if (mysqli_connect_errno()) {
            throw new Exception("Cannot connect to MySQL");
        }
    }
    
    public function query($sql)
    {
        if ($this->_connection == NULL)
        {
            throw new Exception("Not connected to MySQL");
        }
        
        return $this->_connection->query($sql);
    }
    
    public function insert_id()
    {
        if ($this->_connection == NULL)
        {
            throw new Exception("Not connected to MySQL");
        }
        
        return $this->_connection->insert_id;
    }
}


?>