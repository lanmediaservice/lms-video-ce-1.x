<?php

/**
 *
 * (C) 2008 Ilya Spesivtsev, iljasp@tut.by
 *
 * Модуль авторизации Joomla 1.0.15
 *
 * @version $Id$
 * @copyright 2006
 * @author Ilya Spesivtsev
 */

class JOOMLAUserControl extends UserControl { 
    function getUserByLogin($login){ 
        $query = "SELECT id, username, `password` FROM {$this->prefix}users WHERE username='$login' AND activation=''"; 
        mysql_select_db($this->db, $this->resource_link);
        $result = mysql_query ($query, $this->resource_link); 
        if ($result && $field = mysql_fetch_array($result)){ 
            return $field; 
        } 
        return null; 
    } 
    function verifyUser($user, $pass,$ip=false) { 
        @list($hash, $salt) = explode(':', $user["password"]); 
        return (($hash==md5($pass.$salt))); 
    } 
} 

?>