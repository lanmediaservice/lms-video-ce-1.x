<?php
/**
 * LMS Library
 * 
 * @version $Id: imdb.php 280 2009-08-23 13:44:37Z Администратор $
 * @copyright 2007
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @package WebParser
 */

class SiteParser_imdb extends SiteParser{
    var $host = "www.imdb.com";

    function constructPath($action,$params){
        switch($action){
            case 'search':
                return "/find?q=".rawurlencode($params['name']).";s=tt";
                break;
        } // switch
    }

    function parse($path, $what){
        $res = array();
        $dirPath = dirname($path);
        $url = $this->absUrl($path, $dirPath);
        switch($what){
            case "search_results":
                $currentResult = $this->getStructByUrl($url, 'imdb', $what, array('film'));
                if (isset($currentResult['attaches']['film'])) {
                    $film = $currentResult['attaches']['film'];
                    $url = $currentResult['suburls']['film'][2];
                    $currentResult = array(
                        "names" => $film['names'],
                        "year" => $film['year'],
                        "section" => "Redirect",
                        "url" => $url
                    );
                    $res[] = $currentResult;
                } else {
                    $res = $currentResult['items'];
                }
                break;
            case "film":
                $res = $this->getStructByUrl($url, 'imdb', $what);
            break;
            case "person":
                $res = $this->getStructByUrl($url, 'imdb', $what);
            break;
        } // switch
        return $res;
    }

}

?>