<?php

/**
 *
 * (C) 2006 Ilya Spesivtsev, iljasp@tut.by
 *
 * Модуль авторизации phpBB 2
 *
 * @version $Id$
 * @copyright 2006
 * @author Ilya Spesivtsev
 */

class PHPBB2UserControl extends UserControl {
	function getUserByLogin($login){
		$query = "SELECT user_id, username, user_password FROM {$this->prefix}users WHERE username='$login' AND user_active=1";
        mysql_select_db($this->db, $this->resource_link);
		$result = mysql_query($query, $this->resource_link);
		if ($result && $field = mysql_fetch_array($result)){
			return $field;
		}
		return null;
	}
	function verifyUser($user, $pass,$ip=false){
		return (($user["user_password"]==md5($pass)));
	}
}
?>