<?php defined('VAT_SRC') or die('No direct script access.');

class Model {
    
    protected $_object = array();
    
    protected $_changed = array();
    
    /**
     * Has the current record been saved?
     * 
     * @var unknown_type
     */
    protected $_saved;
    
    /**
     * Are we making a new tuple?
     * 
     * @var bool
     */
    protected $_new;
    
    /**
     * Class Constructor
     * 
     * @param unknown_type $id
     */
    public function __construct($id = -1)
    {
        if ($id == -1)
        {
            $this->_new = TRUE;
            $this->_saved = FALSE;
        }
    }
    
    public static function factory($name, $id = -1)
    {
        $class = "Model_" . ucfirst($name);
        
        return new $class;
    }
    
    public function set($column, $value)
    {
        
    }
    
    public function get($column)
    {
        
    }
    
    public function save()
    {
        
    }
}



?>