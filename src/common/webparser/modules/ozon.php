<?php
/**
 * LMS Library
 * 
 * @version $Id: ozon.php 280 2009-08-23 13:44:37Z Администратор $
 * @copyright 2007
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @package WebParser
 */

class SiteParser_ozon extends SiteParser{
    var $host = "www.ozon.ru";

    function constructPath($action,$params){
        switch($action){
            case 'search':
                $url = array();
                if (!isset($config['websites'][$this->host]['ozon_groups'])){
                    $config['websites'][$this->host]['ozon_groups'] = array('div_dvd');
                }
                foreach ($config['websites'][$this->host]['ozon_groups'] as $group){
                        $urls[] = "/?context=search&group=$group&text=" .urlencode($params['name']);
                }
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
                    $currentResult = $this->getStructByUrl($url, 'ozon', $what, array('film'));
                    if (isset($currentResult['attaches']['film'])) {
                        $film = $currentResult['attaches']['film'];
                        $url = $currentResult['suburls']['film'][2];
                        $currentResult = array(
                            "names" => $film['names'],
                            "year" => $film['year'],
                            "info"=> $film["description"],
                            "image"=>is_array($film["posters"])? array_pop($film["posters"]) : null,
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
                $fullCastPath = preg_match("/\?type=1/i", $url)? $url : $url . '?type=1';
                $res = $this->getStructByUrl($fullCastPath, 'ozon', $what, array(), true);
            break;
            case "person":
                $dirPath = dirname($path);
                $url = $this->absUrl($path, $dirPath);
                $res = $this->getStructByUrl($url, 'ozon', $what);
            break;
        } // switch
        return $res;
    }

}

?>