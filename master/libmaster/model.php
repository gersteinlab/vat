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
 * 
 * @method array as_array()
 * @method 
 */

class Model {
    
    /**
     * Table name
     * @var string
     */
    protected $_table;
    
    /**
     * Name of primary key field
     * @var string
     */
    protected $_primary_key;
    
    /**
     * Array of fields for the object
     * @var array
     */
    protected $_fields = array();
    
    /**
     * Used to hold the data for a record's fields
     * @var array
     */
    protected $_object = array();
    
    /**
     * Array used to keep track of which fields have been changed 
     * @var array
     */
    protected $_changed = array();
    
    /**
     * Has the current record been saved?
     * @var bool
     */
    protected $_saved;
    
    /**
     * Are we making a new tuple?
     * @var bool
     */
    protected $_new;
    
    /**
     * Class Constructor
     * 
     * @param int $id
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

    /**
     * Return an associative array
     *  
     * @return array
     */
    public function as_array()
    {
        $ret = array();
        
        foreach ($this->_object as $key => $value)
        {
            $ret[$key] = $value;
        }
        
        return $ret;
    }
    
    /**
     * Finds all tuples that fit the SQL conditions parsed from the $conds
     * argument.
     * 
     * array(
     *     'where' => array(
     *         array('id', '>', '7'),
     *         array('type', '=', '2'),
     *     ),
     *     'orderby' => array('id', 'desc'),
     *     'limit' => array('1')
     * )
     * 
     * XXX I'm basically making a query builder here. Might as well create an 
     * actual query builder sometime
     * 
     * @param array $conds
     * @throws InvalidArgumentException
     * @return boolean|multitype
     */
    public function find_all($conds)
    {
        global $db;
        
        $sql = 'SELECT * FROM ' . $this->_table . ' ';
        
        if ( ! empty($conds))
        {
            if (isset($conds['where']))
            {
                $wherecond = $conds['where'];
                
                $sql .= 'WHERE ';
                if ( ! is_array($wherecond))
                {
                    throw new InvalidArgumentException('Argument of where must be array');
                }
                if (is_array($wherecond[0]))
                {
                    $first = 1;
                    
                    foreach ($wherecond as $cond)
                    {
                        if (count($cond) != 3)
                        {
                            throw new InvalidArgumentException('Each WHERE condition must have three parts');
                        }
                        
                        if ($first == 0)
                        {
                            $sql .= 'AND ';
                        }
                        $first = 0;
                        
                        $sql .= $cond[0] . ' ' . $cond[1] . ' ' . $cond[2] . ' ';
                    }
                }
                else
                {
                    $sql .= $wherecond[0] . ' ' . $wherecond[1] . ' ' . $wherecond[2] . ' ';
                }
            }
            
            if (isset($conds['orderby']))
            {
                $orderbycond = $conds['orderby'];
                
                if ( ! is_array($orderbycond) || count($orderbycond) > 2)
                {
                    throw new InvalidArgumentException('ORDER BY condition must be an array and have one or two parts');
                }
                
                if ( ! in_array($orderbycond[0], $this->_fields))
                {
                    throw new InvalidArgumentException('ORDER BY field must exist in table');
                }
                
                if (count($orderbycond) > 1 && ! in_array(strtoupper($orderbycond[1]), array('DESC', 'ASC')))
                {
                    throw new InvalidArgumentException('ORDER BY must either be ASC or DESC');
                }
                
                $sql .= 'ORDER BY ' . $orderbycond[0] . ' ';
                
                if (count($orderbycond) > 1)
                {
                    $sql .= $orderbycond[1] . ' ';
                }
            }
            
            if (isset($conds['limit']))
            {
                $limitcond = $conds['limit'];
                
                if ( ! is_array($limitcond) || count($limitcond > 1))
                {
                    throw new InvalidArgumentException('LIMIT condition must be an array and have only one part');
                }
                
                $sql .= 'LIMIT ' . $limitcond[0];
            }
        }
        
        $result = $db->query($sql);
        if ($result === FALSE || $result->num_rows == 0)
        {
            return FALSE;
        }
        
        $rows = array();
        while ($row = $result->fetch_assoc())
        {
            array_push($rows, $row);
        }
        $result->close();
        
        return $rows;
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $id
     * @return boolean|Model
     */
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
        
        return $this;
    }

    /**
     * 
     * Enter description here ...
     * @param unknown_type $column
     * @param unknown_type $value
     * @throws InvalidArgumentException
     * @return Model
     */
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
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $column
     * @return boolean|multitype:
     */
    public function get($column)
    {
        if ( ! in_array($column, $this->_fields))
        {
            return FALSE;
        }
        
        return $this->_object[$column];
    }
    
    /**
     * 
     * Enter description here ...
     */
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
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $name
     * @param unknown_type $id
     */
    public static function factory($name, $id = 0)
    {
        $class = "Model_" . ucfirst($name);
    
        return new $class($id);
    }
}



?>