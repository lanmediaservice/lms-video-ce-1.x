<?php
/**
 * LMS Library
 *
 * 
 * @version $Id: php_http_request_client.php 145 2008-06-27 11:43:02Z macondos $
 * @copyright 2007
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @package PhpHttpRequest
 */
 
/**
 * @package PhpHttpRequest
 */
class PhpHttpRequestClient{
	
	var $_http_client;
	
	function PhpHttpRequestClient($http_client){
		$this->_http_client = $http_client;
	}
	
	function query($url, $params=array()){
		$parsedUrl = parse_url($url);
		$host = $parsedUrl['host'];
		$port = isset($parsedUrl['port'])? $parsedUrl['port'] : 80;
		$this->_http_client->host = $host;
		$this->_http_client->port = $port;
		$queryPath = isset($parsedUrl['path'])? $parsedUrl['path'] : '/'; 
		$queryPath .= isset($parsedUrl['query'])? '?'.$parsedUrl['query'] : ''; 
		$status = $this->_http_client->post($queryPath, $params);
		if ( $status == HTTP_STATUS_OK){
			$response = trim($this->_http_client->get_response_body());
			return $this->_decodeResponse($response);
			
		} else{
			trigger_error("'Error while get '$url', server return wrong status '$status'", E_USER_WARNING);
		} 
	}
	
	function _decodeResponse($data){
        return unserialize($data);
	}
	
}

/*
define('LIB_PATH',dirname(__FILE__));
require_once LIB_PATH . "/http.php";
$http_client = new http( HTTP_V11, false);
$params = array();
$params['action'][0] = 'logon';
$params['login'][0] = 'downloader';
$params['pass'][0] = 'FpbzTdhfpbz';
$params['action'][1] = 'getTags';
$params['view_method'] = 'php';
$phpHttpRequestClient = new PhpHttpRequestClient($http_client);
print_r($phpHttpRequestClient->query('http://lms.local/ptd/ptd.php', $params));
*/

?>