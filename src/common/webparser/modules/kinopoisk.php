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
                return "http://s.kinopoisk.ru/index.php?level=7&m_act[what]=item&from=forma&m_act[id]=0&m_act[find]=".urlencode($params['name']);
                break;
        } // switch
    }

    function parse($path, $what){
        $res = array();
        $dirPath = dirname($path);
        $url = $this->absUrl($path, $dirPath);
        switch($what){
            case "search_results":
                $currentResult = $this->getStructByUrl($url, 'kinopoisk', $what, array('film'));
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