<?php
/**
 * LMS Library
 * 
 * @version $Id: sharereactor.php 280 2009-08-23 13:44:37Z Администратор $
 * @copyright 2007
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @package WebParser
 */

class SiteParser_sharereactor extends SiteParser{
    var $host = "www.sharereactor.ru";

    function constructPath($action,$params){
        switch($action){
            case 'search':
                return "/cgi-bin/mzsearch.cgi?search=" . urlencode($params['name']);
                break;
        } // switch
    }

    function parse($path, $what){
        $res = array();
        $dirPath = dirname($path);
        $url = $this->absUrl($path, $dirPath);
        switch($what){
            case "search_results":
                $currentResult = $this->getStructByUrl($url, 'sharereactor', $what);
                $res = $currentResult['items'];
                break;
            case "film":
                $res = $this->getStructByUrl($url, 'sharereactor', $what);
            break;
        } // switch
        return $res;
    }

}

?>