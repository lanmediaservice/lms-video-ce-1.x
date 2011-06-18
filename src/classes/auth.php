<?php

/**
 *
 * (C) 2006 Ilya Spesivtsev, iljasp@tut.by
 *
 * Модуль авторизации
 *
 * @version $Id$
 * @copyright 2006
 * @author Ilya Spesivtsev
 */

// Базовый класс управления пользователями
class UserControl {
	var $resource_link = null;
	var $db = null;
	var $prefix = "";

	function UserControl($resource_link, $db, $prefix="") {
		$this->resource_link = $resource_link;
		$this->db = $db;
		$this->prefix = $prefix;
	}

	function getUserByLogin($login){

	}

	function registerUser(){

	}

	function verifyUser(){

	}

	function updateUser(){

	}
}

class LMSUserControl extends UserControl {
	function getUserByLogin($login){
		$login = addslashes($login);
        mysql_select_db($this->db, $this->resource_link);
		$query = "SELECT * FROM users WHERE Login='$login'";
		$result = mysql_query($query, $this->resource_link);
		if ($result && $field = mysql_fetch_array($result)){
			return $field;
		}
		return null;
	}

	function verifyUser($user, $pass,$ip=false){
		if ($ip) $ip = str_replace(".","\.",$ip);
		return (($user["Password"]==md5($pass)) && (!$ip || preg_match("/(^$ip$| $ip$|^$ip | $ip )/i",$user["IP"])));
	}

	function updateUser($login,$values){
		$login = addslashes($login);
		$pairs = array();
        foreach ($values as $column => $value){
        	$value = addslashes($value);
            $pairs[] = " $column='$value' ";
		}
		$query = "UPDATE users SET ".implode(",", $pairs) . " WHERE Login='$login'";
		return (mysql_db_query ($this->db,$query,$this->resource_link)!==false);
	}

	function registerUser($values){
        $query = "INSERT INTO users (".implode(",", array_keys($values)).") VALUES (".implode(",", $values).")";
		return (mysql_db_query ($this->db,$query,$this->resource_link)!==false);
	}}
?>