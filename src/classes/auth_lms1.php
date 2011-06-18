<?php

/**
 *
 * (C) 2006 Ilya Spesivtsev, iljasp@tut.by
 *
 * Модуль авторизации c LMS-скриптами
 *
 * @version $Id$
 * @copyright 2006
 * @author Ilya Spesivtsev
 */

class LMS1UserControl extends UserControl {
	function getUserByLogin($login){
		$query = "SELECT Login, Password, IP, Email FROM {$this->prefix}users WHERE Login='$login'";
        mysql_select_db($this->db, $this->resource_link);
		$result = mysql_query($query,$this->resource_link);
		if ($result && $field = mysql_fetch_array($result)){
			return $field;
		}
		return null;
	}
	function verifyUser($user, $pass,$ip=false){
		if ($ip) $ip = str_replace(".","\.",$ip);
		return (($user["Password"]==md5($pass)) && (!$ip || preg_match("/(^$ip$| $ip$|^$ip | $ip )/i",$user["IP"]))); 
	}
}
?>