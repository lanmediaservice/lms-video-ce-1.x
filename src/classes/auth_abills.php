<?php

/**
 *
 * (C) 2006 Ilya Spesivtsev, iljasp@tut.by
 *
 * Модуль авторизации Abills
 *
 * @version $Id$
 * @copyright 2006
 * @author Ilya Spesivtsev
 */

class ABILLSUserControl extends UserControl {
	function getUserByLogin($login){
    		global $config;
    		$passphr = isset($config['integration']['abills_passphrase']) ? $config['integration']['abills_passphrase'] : 'test12345678901234567890'; 
		$passphr = mysql_real_escape_string($passphr);
		$query = "SELECT decode(`password`, '$passphr') as password FROM users WHERE id='$login'";
        mysql_select_db($this->db, $this->resource_link);
		$result = mysql_query ($query,$this->resource_link);
		if ($result && $field = mysql_fetch_array($result)){
			return $field;
		}
		return null;
	}
	function verifyUser($user, $pass,$ip=false){
		return (($user["password"]===$pass));
	}
}
?>