<?php defined('VAT_SRC') or die('No direct script access');
/**
 * Simple load balancer. A list of servers is maintained in a JSON file 
 * servers.json. File is read and locked and the least-recently used server
 * is returned.
 * 
 * @author David Z. Chen
 *
 */

class Server {
    
    const file = 'servers.json';
    
    /**
     * Returns the least recently used server after incrementing its load/
     * timestamp counter.
     * 
     * @param int $load
     * @throws Exception
     */
    public static function get($load = 1)
    {        
        $fp = fopen(Server::file, 'r+');
        
        if ($fp === FALSE)
        {
            throw new Exception("Cannot open servers.json");
        }
        
        flock($fp, LOCK_EX);
        
        $json = fread($fp, filesize(Server::file));
        $servers = json_decode($json, TRUE);
        
        if ($servers == NULL)
        {
            throw new Exception("File servers.json not well-formed");
        }
        
        $min = -1;
        $min_index = 0;
        for ($i = 0; $i < count($servers); $i++)
        {
            if ($min == -1)
            {
                $min = $servers[$i]['last'];
                $min_index = $i;
                continue;
            }
            
            if ($servers[$i]['last'] < $min)
            {
                $min = $servers[$i]['last'];
                $min_index = $i;
            }
        }
        
        $servers[$min_index]['last'] += $load;
        $ret = $servers[$min_index];
        
        rewind($fp);
        ftruncate($fp, 0);
        fwrite($fp, json_encode($servers));
        
        flock($fp, LOCK_UN);
        fclose($fp);
        
        return $ret;
    }

}

?>