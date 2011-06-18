<?php

/**
 *
 * (C) 2006 Ilya Spesivtsev, iljasp@tut.by
 *
 * Модуль авторизации Invision Power Board 1
 *
 * @version $Id$
 * @copyright 2006
 * @author Ilya Spesivtsev
 */

class IPB1UserControl extends UserControl {
	function getUserByLogin($login){
		$query = "SELECT m.id as id, m.email as email, `password`, ip_address FROM {$this->prefix}members m WHERE name='$login'";
        mysql_select_db($this->db, $this->resource_link);
		$result = mysql_query ($query,$this->resource_link);
		if ($result && $field = mysql_fetch_array($result)){
			return $field;
		}
		return null;
	}
	function verifyUser($user, $pass,$ip=false){
		if ($ip) $ip = str_replace(".","\.",$ip);
		return (($user["password"]==md5($pass)) && (!$ip || ($ip == $user["ip_address"])));
	}
}
?>