<?php
require_once dirname(__FILE__). "/crypter.h.php";

class Crypter {
	var $worker;

	function Crypter($mode, $algorithm, $key = null)
	{
		$mycrypt_exists = function_exists('mcrypt_ecb');
		if ($mycrypt_exists) {
			if (!empty($key)) $key = "secretkey196";
			if (!class_exists("mcrypt")) require_once dirname(__FILE__) . '/class.mcrypt.php';
			$this->worker = new mcrypt($mode, $algorithm, $key);
		} else {
			if (!class_exists("pcrypt")) require_once dirname(__FILE__) . '/class.pcrypt.php';
			$this->worker = new pcrypt($mode, $algorithm, $key);
		}
	}
	function encrypt($plain)
	{
		return call_user_func(array($this->worker,'encrypt'), $plain);
	}
	function decrypt($cipher)
	{
		return call_user_func(array($this->worker,'decrypt'), $cipher);
	}
}

?>