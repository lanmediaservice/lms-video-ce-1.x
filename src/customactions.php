<?php
/**
 * Видео-каталог
 * (C) 2006 Ilya Spesivtsev, iljasp@tut.by
 *
 * Дополнительное пользовательское ядро
 *
 * @author Ilya Spesivtsev
 * @version 1.05
 */
$time1 = time()+microtime();

require_once "config.php";

header('Expires: -1');
session_start();

require_once "functions.php";

$noajax = (isset($_REQUEST["noajax"])) ? 1 : 0;
if (!$noajax) {
    require_once "jshttprequest/JsHttpRequest.php";
    $JsHttpRequest = new JsHttpRequest("windows-1251");
}



$idSQLConnection = mysql_connect($config['mysqlhost'], $config['mysqluser'], $config['mysqlpass']);

if ( !$idSQLConnection )
{
    echo "Критическая ошибка на сервере. Ошибка при подключении к базе данных.";
    exit;
}

$result = mysql_select_db( $config['mysqldb'], $idSQLConnection );
if ( !$result )
{
    echo "Критическая ошибка на сервере. Ошибка при выборе базы данных.";
    exit;
}

if (isset($config['mysql_set_names'])) mysql_query($config['mysql_set_names']);

$login = $_SESSION['login'];
$pass = $_SESSION['pass'];

$user = GetUserID($login, $pass);

$action = (isset($_REQUEST["action"])) ? strtolower($_REQUEST["action"]) : "";

if (getRights($action,$user) || ($action=="exit")){
    switch ($action) {
        case "test":
			$text = $_REQUEST['text'];
			$_RESULT["md5"] = text;
		break;
	}
}

?>
