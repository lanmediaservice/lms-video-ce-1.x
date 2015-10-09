<?php
/**
 * LMS Library
 * 
 * @version $Id: kinopoisk.php 280 2009-08-23 13:44:37Z Администратор $
 * @copyright 2007
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @package WebParser
 */

class SiteParser_kinopoisk extends SiteParser{
    var $host = "www.kinopoisk.ru";

    function constructPath($action,$params){
        switch($action){
            case 'search':
                $query = urlencode(mb_convert_encoding($params['name'], 'UTF-8', 'CP1251'));
                return "http://www.kinopoisk.ru/search/?text=$query";
                break;
        } // switch
    }

    function parse($path, $what){
        $res = array();
        $dirPath = dirname($path);
        $url = $this->absUrl($path, $dirPath);
        switch($what){
            case "search_results":
                $currentResult = $this->getStructByUrl($url, 'kinopoisk', $what, array('film'), true);
                if (isset($currentResult['attaches']['film'])) {
                    $film = $currentResult['attaches']['film'];
                    $url = $currentResult['suburls']['film'][2];
                    $currentResult = array(
                        "names" => $film['names'],
                        "year" => $film['year'],
                        "info"=> $film["description"],
                        "image"=>array_pop($film["posters"]),
                        "url" => $url
                    );
                    $res[] = $currentResult;
                } else {
                    $res = $currentResult['items'];
                }
                break;
            case "film":
                $url .= 'details/cast/';
                $res = $this->getStructByUrl($url, 'kinopoisk', $what, array(), true);
            break;
            case "person":
                $res = $this->getStructByUrl($url, 'kinopoisk', $what, array(), true);
            break;
        } // switch
        return $res;
    }

}

?>