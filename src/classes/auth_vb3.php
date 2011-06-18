<?php

/**
 *
 * (C) 2006 Ilya Spesivtsev, iljasp@tut.by
 *
 * Модуль авторизации vBulletin 3
 *
 * @version $Id$
 * @copyright 2006
 * @author Ilya Spesivtsev
 */

class VB3UserControl extends UserControl {
	function getUserByLogin($login){
		$query = "SELECT userid, salt, password, ipaddress, email FROM {$this->prefix}user WHERE username='$login'";
        mysql_select_db($this->db, $this->resource_link);
		$result = mysql_query($query, $this->resource_link);
		if ($result && $field = mysql_fetch_array($result)){
			return $field;
		}
		return null;
	}
	function verifyUser($user, $pass,$ip=false){
		if ($ip) $ip = str_replace(".","\.",$ip);
		return (($user["password"]==md5(md5($pass).$user["salt"])) && (!$ip || ($ip == $user["ipaddress"])));
	}
}
?>