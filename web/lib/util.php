<?php
/**
 * Various utility functions for [Variant Annotation Tools][ref-vat]
 * 
 * [ref-vat]: http://vat.gersteinlab.org
 * 
 * @package    VAT
 * @copyright  (c) 2011 Gerstein Lab
 * @author     David Z. Chen
 * @license    ???
 */

 
/**
 * Interface for a classes for comparable objects
 */
interface Comparable {

    public static function compare($a, $b);

}
 

/**
 * Searches the array for a given value using the given comparison callback
 * function for comparision and returns the corresponding key if successful or
 * FALSE if element is not found.
 * 
 * @param mixed $needle
 * @param array $haystack
 * @param callback $cmp_function
 * @return mixed
 */
function array_usearch($needle, $haystack, $cmp_function)
{
    foreach ($haystack as $key => $value)
    {
        $res = call_user_func($cmp_function, $value, $needle);
        if ($res == 0)
            return $key;
    }
    
    return FALSE;
}


/**
 * Returns a copy of the array with duplicates removed. Duplicates are 
 * determined by using the given callback function for comparison.
 * 
 * @param array $array
 * @param callback @cmp_function
 * @return array filtered array
 */
function array_uunique($array, $cmp_function)
{
    foreach ($array as $key => $value)
    {
        foreach ($array as $key2 => $value2)
        {
            if (call_user_func($cmp_function, $value, $value2) == 0 &&
                $key != $key2)
            {
                unset($array[$key2]);
            }
        }
    }

    return $array;
}

/**
 * 
 */
function strpbrkpos($haystack, $char_list) 
{
    $result = strcspn($haystack, $char_list);
    
    if ($result != strlen($haystack)) 
    {
        return $result;
    }
    
    return false;
}

/**
 * 
 */
function range_intersection($start1, $end1, $start2, $end2)
{
    $s = max($start1, $start2);
    $e = min($end1, $end2);
  
    return $e - $s;
}

?>