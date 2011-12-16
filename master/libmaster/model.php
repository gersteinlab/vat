<?php defined('VAT_SRC') or die('No direct script access.');
/**
 * Simple Object Relational Mapper base class that follows the ActiveRecord
 * pattern. For example:
 * 
 *     $model = Model::factory('table');
 *     $model->set('field', 'value');
 *     $model->save();
 * 
 * @author  David Z. Chen
 * @package VAT
 */

class Model {
    
    protected $_table;
    
    protected $_primary_key;
    
    protected $_fields = array();
    
    protected $_object = array();
    
    /**
     * 
     * Enter description here ...
     * @var unknown_type
     */
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
    public function __construct($id = 0)
    {
        if ($id == 0)
        {
            $this->_new = TRUE;
            $this->_saved = FALSE;
        }
        else
        {
            $this->_saved = TRUE;
            $this->_new = FALSE;
            if ($this->find($id) === FALSE)
            {
                throw new Exception("Cannot find record with ID " . $id);
            }
        }
    }

    public function as_array()
    {
        return $this->_object;
    }
    
    public function find($id)
    {
        global $db; 
        
        $sql = "SELECT * FROM " . $this->_table . " WHERE " . $this->_primary_key . " = " . $id;
        
        $result = $db->query($sql);
        if ($result === FALSE || $result->num_rows == 0)
        {
            return FALSE;
        }
        
        $array = $result->fetch_assoc();
        
        foreach ($this->_fields as $field)
        {
            $this->_object[$field] = $array[$field];
        }
        $result->close();
        
        return TRUE;
    }

    public function set($column, $value)
    {
        if ( ! in_array($column, $this->_fields))
        {
            throw new InvalidArgumentException("Field " . $column . " does not exist for table " . $this->_table);
        }
        
        $this->_object[$column] = $value;
        $this->_changed[$column] = TRUE;
        $this->_saved = FALSE;
        
        return $this;
    }
    
    public function get($column)
    {
        if ( ! in_array($column, $this->_fields))
        {
            return FALSE;
        }
        
        return $this->_object[$column];
    }
    
    public function save()
    {
        global $db;
        
        if ($this->_new === TRUE)
        {
            $sql = "INSERT INTO " . $this->_table . " (";
            $first = 1;
            foreach ($this->_object as $key => $value)
            {
                if ($key == $this->_primary_key)
                    continue;
                
                if ($first == 0)
                {
                    $sql .= ", ";
                }
                $first = 0;
                
                $sql .= $key;
            }
            $sql .= ") VALUES (";
            
            $first = 1;
            foreach ($this->_object as $key => $value)
            {
                if ($key == $this->_primary_key)
                    continue;
                
                if ($first == 0)
                {
                    $sql .= ", ";
                }
                $first = 0;
                
                $sql .= "'" . $value . "'";
            }
            $sql .= ");";
        }
        else
        {
            $sql = "UPDATE " . $this->_table . " SET ";
            
            $first = 1;
            foreach ($this->_changed as $key => $value)
            {
                if ($key == $this->_primary_key)
                    continue;
                
                if ($first == 0)
                {
                    $sql .= ", ";
                }
                $first = 0;
                
                $sql .= $key . " = '" . $this->_object[$key] . "'";
            }
            
            $sql .= " WHERE " . $this->_primary_key . " = " . $this->_object[$this->_primary_key] . ";";
        }
        
        $result = $db->query($sql);
        if ($result == FALSE)
        {
            return FALSE;
        }
        
        if ($this->_new === TRUE)
        {
            $this->_object[$this->_primary_key] = $db->insert_id();
        }
        
        $this->_saved = TRUE;
        $this->_new = FALSE;
        $this->_changed = array();
        
        return TRUE;
    }
    
    
    public static function factory($name, $id = 0)
    {
        $class = "Model_" . ucfirst($name);
    
        return new $class($id);
    }
}



?>