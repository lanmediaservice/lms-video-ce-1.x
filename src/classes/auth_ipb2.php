<?php

/**
 *
 * (C) 2006 Ilya Spesivtsev, iljasp@tut.by
 *
 * Модуль авторизации Invision Power Board 2
 *
 * @version $Id$
 * @copyright 2006
 * @author Ilya Spesivtsev
 */

class IPB2UserControl extends UserControl {
	function getUserByLogin($login){
		$query = "SELECT m.id as id, converge_pass_salt, converge_pass_hash, ip_address, email FROM {$this->prefix}members m INNER JOIN {$this->prefix}members_converge mc ON (m.id=mc.converge_id) WHERE name='$login'";
        mysql_select_db($this->db, $this->resource_link);
		$result = mysql_query($query,$this->resource_link);
		if ($result && $field = mysql_fetch_array($result)){
			return $field;
		}
		return null;
	}
	function verifyUser($user, $pass,$ip=false){
		if ($ip) $ip = str_replace(".","\.",$ip);
		return (($user["converge_pass_hash"]==md5(md5($user["converge_pass_salt"]).md5($pass))) && (!$ip || ($ip == $user["ip_address"])));
	}
}
?>