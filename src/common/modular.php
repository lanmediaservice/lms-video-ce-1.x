<?php
/**
 * LMS Library
 *
 * 
 * @version $Id$
 * @copyright 2007
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @package Modular
 */

/**
 * @abstract
 * @package Modular
 */
 class Modular {
	var $moduleClassNamePreffix = '';
	var $moduleClassNameSuffix = '';
	var $moduleFileNamePreffix = '';
	var $moduleFileNameSuffix = '';
	
	function loadModule($moduleName){
        $moduleSafeName = preg_replace('{[^a-z0-9_]}', "",$moduleName);
        $className = $this->moduleClassNamePreffix . $moduleSafeName . $this->moduleClassNameSuffix;
        if (!class_exists($className)){
	        $fileName = $this->moduleFileNamePreffix . $moduleSafeName . $this->moduleFileNameSuffix . ".php";
	        if (!file_exists($fileName)) {
	            trigger_error("Can't find module '" . $moduleName . "' ($fileName)", E_USER_WARNING);
	            return false;
	        }
	        require_once $fileName;
	
	        if (!class_exists($className)) {
	            trigger_error("Class $className doesn't exists in $fileName", E_USER_WARNING);
	            return false;
	        }
        }
        return $className;
    }
 }
 
?>