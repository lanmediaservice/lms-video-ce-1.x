<?php
/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: Kinopoisk.php 700 2011-06-10 08:40:53Z macondos $
 */

class Lms_Service_Movie
{
    private static $_parserInstance;
    static private $modulesMaps = array(
        "world-art" => "worldart_animation"
    );

    public static function getParser()
    {
        if (!self::$_parserInstance) {
            self::setParser();
        }
        return self::$_parserInstance;
    }

    public static function setParser($parser = null)
    {
        if ($parser === null) {
            $httpClient = Lms_Application::getHttpClient();
            $requestClient = new Lms_PhpHttpRequest_Client($httpClient);
            $parserService = new Lms_Service_DataParser($requestClient,
                                                        $httpClient);
            $config = Lms_Application::getConfig('parser_service');
            $parserService->setServiceUrl($config['url']);
            $parserService->setAuthData(
                $config['username'],
                $config['password']
            );
            $parserService->setServiceApp('lms-video-1.2');
            self::$_parserInstance = $parserService;
        } else {
            self::$_parserInstance = $parser;
        }
    }

    public static function getMovieUrlById($id, $module)
    {
        $adapter = self::getAdapter($module);
        return $adapter::constructPath('film', array('id'=>$id));
    }    
    
    public static function searchMovie($queryText, $module = null)
    {
        if ($module == null) {
            $module = self::getModuleByUrl($url);
        }
        $adapter = self::getAdapter($module);
        $parserService = self::getParser();
        $results = array();
        $url = $adapter::constructPath('search', array('query'=>$queryText));
        $data = $parserService->parseUrl(
            $url,
            isset(self::$modulesMaps[$module])? self::$modulesMaps[$module] : $module,
            'search_results',
            array('film')
        );
        $adapter::afterParseSearchResults($url, $data);
        $results = $data['items'];
        return $results;
    }

    public static function parseMovie($url, $module = null)
    {
        if ($module == null) {
            $module = self::getModuleByUrl($url);
        }
        $adapter = self::getAdapter($module);
        $parserService = self::getParser();
        $data = $parserService->parseUrl(
            $url,
            isset(self::$modulesMaps[$module])? self::$modulesMaps[$module] : $module, 
            'film'
        );
        $adapter::afterParseMovie($url, $data);
        return $data;
    }

    public static function parsePerson($url, $module = null)
    {
        if ($module == null) {
            $module = self::getModuleByUrl($url);
        }
        $adapter = self::getAdapter($module);
        $parserService = self::getParser();
        $data = $parserService->parseUrl(
            $url, 
            isset(self::$modulesMaps[$module])? self::$modulesMaps[$module] : $module, 
            'person'
        );
        $adapter::afterParsePerson($url, $data);
        return $data;
    }
    
    public static function getAdapter($module)
    {
        return "Lms_Service_Adapter_" . ucfirst(preg_replace('{\W}', '', strtolower($module)));
    }
    
    public static function getModuleByUrl($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (preg_match('{([^\.]*?)\.[^\.]+$}i', $host, $matches)) {
            return $matches[1];
        } else {
            return false;
        }
    }
    
    public static function updateRatings($movies)
    {
        $parserService = self::getParser();
        $data = $parserService->updateRatings($movies);
        return $data;
    }
    
}

