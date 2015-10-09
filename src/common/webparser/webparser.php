<?php
/**
 * LMS Library
 * 
 * @version $Id: webparser.php 302 2010-02-01 18:44:00Z Администратор $
 * @copyright 2007
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @package WebParser
 */

/**
 * Including depends
 */
if (!class_exists("Modular")) {
    if (defined('LIB_MODULAR_PATH')) {
        require_once LIB_MODULAR_PATH . "/modular.php";
    }
}

class WebParser extends Modular{
    
    var $_websites_config;
    var $_customer_config;
    var $moduleClassNamePreffix = 'SiteParser_';
    
    function WebParser($websites_config = array(), $customer_config = array())
    {
        $this->_websites_config = $websites_config;
        $this->_customer_config = $customer_config;
        $this->moduleFileNamePreffix = dirname(__FILE__) . "/modules/";
    }

    function action($module, $actionName, $params){
        if ($class_name = $this->loadModule($module)) {
            $site_parser = new $class_name($this->_websites_config, $this->_customer_config);
            return call_user_func_array(array(&$site_parser, $actionName), $params);
        }
    }

    function constructPath($module,$action,$params){
        if ($class_name = $this->loadModule($module)) {
            $site_parser = new $class_name($this->_websites_config, $this->_customer_config);
            return $site_parser->constructPath($action,$params);
        }
    }

    function Parse($module,$what,$params){
        if ($class_name = $this->loadModule($module)) {
            $site_parser = new $class_name($this->_websites_config, $this->_customer_config);
            return $site_parser->Parse($params['path'], $what);
        }
    }
}

class SiteParser {
    var $host;
    var $_websites_config;
    var $_customer_config;
    var $phpHttpRequestClient;
    
    function SiteParser($websites_config = array(), $customer_config = array())
    {
        $this->_websites_config = $websites_config;
        $this->_customer_config = $customer_config;
    }
    
    function compactTags($htmlText)
    {
        return preg_replace('/>(\s+)</i', '><', $htmlText);
    }

    function absUrl($path, $parentpath='', $encode=false){
        //fixing problem in windows-system
        $strTransform = array("\\"=>"/");
        $path = strtr($path, $strTransform);        
        $parentpath = strtr($parentpath, $strTransform);        
    
        if ($encode){
            $start = 0;    
            if (substr($path,0,7)=='http://') $start = 3;
            $t = explode("/",$path);
            for ($i=$start;$i<count($t);$i++) $t[$i] = rawurlencode ($t[$i]);
            $path = implode("/",$t);
        }
        if  (substr($parentpath,-1)!='/') $parentpath .= '/';
        
        if ($path{0}=='/')
            return 'http://' . $this->host . $path;
        elseif (substr($path,0,7)=='http://')
            return $path;
        elseif($parentpath{0}=='/')
            return 'http://' . $this->host . $parentpath . $path;
        elseif (substr($parentpath,0,7)=='http://')
            return $parentpath . $path;
    }

    /**
     * @abstract
     */
    function constructPath($params){

    }

    function getHttpRetriever($path){
        //Including depends
        if (!class_exists("http")) {
            if (defined('LIB_HTTP_PATH')) {
                require_once LIB_HTTP_PATH . "/http.php";
            }
        }
        $httpClient = new http( HTTP_V11, false);
        $parsedUrl = parse_url($path);
        $httpClient->host = isset($parsedUrl['host']) ? $parsedUrl['host'] : $this->host;
        $httpClient->port = isset($parsedUrl['port']) ? $parsedUrl['port'] : 80;
        $path = $parsedUrl['path'] . (isset($parsedUrl['query'])? ('?'.$parsedUrl['query']) : '');
        $proxySettings = isset($this->_websites_config[$this->host]['proxy']) ? $this->_websites_config[$this->host]['proxy'] : $this->_websites_config['default']['proxy'];
        if ($proxySettings) {
            $parsedUrl = parse_url($proxySettings);
            $httpClient->use_proxy($parsedUrl['host'], $parsedUrl['port'], $parsedUrl['user'], $parsedUrl['pass']);
        }
        if (isset($this->_websites_config['default']['user_agent'])) {
            $httpClient->user_agent = $this->_websites_config['default']['user_agent'];
        }
        if (isset($this->_websites_config[$this->host]['user_agent'])) {
            $httpClient->user_agent = $this->_websites_config[$this->host]['user_agent'];
        }

        $connectionTimeout = isset($this->_websites_config[$this->host]['connection_timeout']) ? $this->_websites_config[$this->host]['connection_timeout'] : (isset($this->_websites_config['default']['connection_timeout'])? $this->_websites_config['default']['connection_timeout'] : 30);
        $httpClient->connection_timeout = $connectionTimeout;
        return $httpClient;
    }
    
    function getStructByUrl($url, $module, $context, $acceptedAttaches = array(), $followRidrects = false)
    {
        $res = false;
        $request = array(
            'action' => 'parseUrl',
            'url' => $url,
            'context' => $context,
            'module' => $module,
            'accepted_attaches' => $acceptedAttaches
        );
        $result = $this->execServiceAction($request);
        if ($result['success']) {
            $res = $result['response'];
        } elseif (in_array($result['response'], array(404,500))) {
            if ($module=='kinopoisk') {
                $url = $url . ((strpos($url, "?")===FALSE)? '?' : '&');
                $url .= 'nocookiesupport=yes';
            }
            $httpClient = $this->getHttpRetriever($url);
            $response = $httpClient->get($url, $followRidrects, '', true, true);
            
            if ($module=='kinopoisk') {
                require_once __DIR__ . '/../../app/libs/tplib/Zend/Exception.php';
                require_once __DIR__ . '/../../app/libs/tplib/Zend/Http/Exception.php';
                require_once __DIR__ . '/../../app/libs/tplib/Zend/Http/Response.php';
                $responseObject = Zend_Http_Response::fromString($response);
                $redirectUrl = null;
                if ($responseObject->isRedirect()) {
                    $redirectUrl = $responseObject->getHeader('Location');
                } else {
                    $body = $responseObject->getBody();
                    if (preg_match('{<meta http-equiv="Refresh"[^>]*url=(.*?)">}is', $body, $matches)) {
                        $redirectUrl = html_entity_decode($matches[1]);
                    }
                }
                if ($redirectUrl) {
                    $response = $httpClient->get($redirectUrl, $followRidrects, '', true, true);
                }
            }
            $request['action'] = 'parseResponse';
            $request['response'] = $response;
            $result = $this->execServiceAction($request);
            if ($result['success']) {
                $res = $result['response'];
            }
        } else {
            trigger_error($result['response'] . ' ' . $result['message'], E_USER_WARNING);
        }
        if ($res && count($acceptedAttaches) && isset($res['suburls'])) {
            foreach ($res['suburls'] as $attachName => $subUrlStruct) {
                if (!isset($res['attaches'][$attachName])) {
                    list($subModule, $subContext, $subUrl) = $subUrlStruct;
                    $res['attaches'][$attachName] = $this->getStructByUrl($subUrl, $subModule, $subContext);
                }
            }
        }
        return $res;
    }
    
    function execServiceAction($request)
    {
        if (!$this->phpHttpRequestClient) {
            $httpClient = $this->getHttpRetriever('http://lms.local/');
            if (!class_exists("PhpHttpRequestClient")) {
                if (defined('LIB_PHP_HTTP_REQUEST_PATH')) {
                    require_once LIB_PHP_HTTP_REQUEST_PATH . "/php_http_request_client.php";
                }
            }
            $this->phpHttpRequestClient = new PhpHttpRequestClient($httpClient);
        }
        $params = array();
        $params['view_method'] = 'php';
        $logonRequest = array(
            'action' => 'logon',
            'username' => $this->_customer_config['login'],
            'password' => $this->_customer_config['pass'],
            'auth_provider_key' => 'local'
        );
        $logonRequestID = $this->_addParams($logonRequest, $params, 0);
        $requestID = $this->_addParams($request, $params, $logonRequestID);
        $response = $this->phpHttpRequestClient->query($this->_customer_config['parser_service'], $params);
        if (strlen($response['text'])) echo $response['text'];
        $result = $response['php'];
        //print_r($params); 
        //print_r($result);
        
        return $result[$requestID];
    }
    
    function _addParams($inParams, &$outParams, $lastActionNum=0){
        $actionNum = $lastActionNum + 1;
        foreach ($inParams as $paramKey=>$paramValue){
            $outParams[$paramKey][$actionNum] = $paramValue;
        }
        return $actionNum;
    }

}

?>