<?php
/**
 * LMS Library
 * 
 * @version $Id: worldart.php 280 2009-08-23 13:44:37Z Администратор $
 * @copyright 2007
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @package WebParser
 */

class SiteParser_worldart extends SiteParser{
    var $host = "www.world-art.ru";

    function constructPath($action,$params){
        switch($action){
            case 'search':
                $url = array();
                $urls[] = "/search.php?sector=cinema&name=".urlencode($params['name']);
                $urls[] = "/search.php?sector=animation&name=".urlencode($params['name']);
                return $urls;
                break;
        } // switch
    }

    function parse($path, $what){
        $res = array();
        switch($what){
            case "search_results":
                if (!is_array($path)) {
                    $paths = array($path);
                } else {
                    $paths = $path;
                }
                foreach ($paths as $path) {
                    $dirPath = dirname($path);
                    $url = $this->absUrl($path, $dirPath);
                    if (preg_match('{sector=cinema}i', $url)) {
                        $module = 'worldart_cinema';
                    } else {
                        $module = 'worldart_animation';
                    }
                    $currentResult = $this->getStructByUrl($url, $module, $what, array('film'));
                    if (isset($currentResult['attaches']['film'])) {
                        $film = $currentResult['attaches']['film'];
                        $url = $currentResult['suburls']['film'][2];
                        $currentResult = array(
                            "names" => $film['names'],
                            "year" => $film['year'],
                            "country" => implode(", ", $film['countries']),
                            "info"=> $film["description"],
                            "image"=>array_pop($film["posters"]),
                            "url" => $url
                        );
                        $res[] = $currentResult;
                    } elseif ($currentResult['items']) {
                        $res = array_merge($res, $currentResult['items']);
                    }
                }
                break;
            case "film":
                $dirPath = dirname($path);
                $url = $this->absUrl($path, $dirPath);
                if (preg_match('{/cinema/}i', $url)) {
                    $module = 'worldart_cinema';
                } else {
                    $module = 'worldart_animation';
                }
                $res = $this->getStructByUrl($url, $module, $what, array('cast'));
                if (isset($res['attaches']['cast'])) {
                    $res['persones'] = $res['attaches']['cast']['persones'];
                    unset($res['suburls']);
                    unset($res['attaches']);
                }
                break;
            case "person":
                $dirPath = dirname($path);
                $url = $this->absUrl($path, $dirPath);
                $res = $this->getStructByUrl($url, 'worldart_animation', $what);
            break;
        } // switch
        return $res;
    }

}

?>