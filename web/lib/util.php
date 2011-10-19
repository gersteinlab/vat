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
 * Finds the range intersection for two ranges 
 *
 * @param int $start1
 * @param int $end1
 * @param int $start2
 * @param int $end2
 * @return int
 */
function range_intersection($start1, $end1, $start2, $end2)
{
    $s = max($start1, $start2);
    $e = min($end1, $end2);
  
    return $e - $s;
}


/**
 * Flushes all output buffers
 */
function flush_buffers()
{
    //ob_end_flush();
    ob_flush();
    flush();
    //ob_start();
}

/**
 * Takes a JSON representation produced by json_encode and attempts to 
 * reformats it in a pretty-print format
 * 
 * @param string $json
 */
function json_format($json)
{
    $tab = "    ";
    $new_json = "";
    $indent_level = 0;
    $in_string = FALSE;

    $json_obj = json_decode($json);

    if ($json_obj === FALSE)
        return $json;

    $json = json_encode($json_obj);
    $len = strlen($json);

    for ($c = 0; $c < $len; $c++)
    {
        $char = $json[$c];
        
        switch($char)
        {
            case '{':
            case '[':
                if ( ! $in_string)
                {
                    $new_json .= $char . "\n" . str_repeat($tab, $indent_level+1);
                    $indent_level++;
                }
                else
                {
                    $new_json .= $char;
                }
                break;
                
            case '}':
            case ']':
                if ( ! $in_string)
                {
                    $indent_level--;
                    $new_json .= "\n" . str_repeat($tab, $indent_level) . $char;
                }
                else
                {
                    $new_json .= $char; 
                }
                break;
                
            case ',':
                if ( ! $in_string)
                {
                    $new_json .= ",\n" . str_repeat($tab, $indent_level);
                }
                else
                {
                    $new_json .= $char;
                }
                break;
                
            case ':':
                if ( ! $in_string)
                {
                    $new_json .= ": ";
                }
                else
                {
                    $new_json .= $char;
                }
                break;
                
            case '"':
                if ($c > 0 && $json[$c - 1] != '\\')
                {
                    $in_string = !$in_string;
                }
            default:
                $new_json .= $char;
                break;
        }
    }

    return $new_json;
}

?>