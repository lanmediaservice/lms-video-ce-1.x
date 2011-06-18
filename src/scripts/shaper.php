<?php
/**
 * Видео-каталог
 * (C) 2006 Ilya Spesivtsev, iljasp@tut.by
 *
 * Шейпер
 *
 * @author Ilya Spesivtsev 
 * @version 1.05
 */
$PATH = dirname(__FILE__);
require_once "$PATH/../config.php";
require_once "$PATH/../functions.php";

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

$ipfw = isset($config['ipfw']) ? $config['ipfw'] : '/sbin/ipfw ';
$today = getdate(); 
$hours = $today['hours']; 
$minutes = $today['minutes']; 
$yday = $today['yday'];
$year = $today['year'];

$MinOfDay = $yday*1440 + $hours*60 + $minutes;

$currentday = $yday*1440;
$minbeforeday = ($yday-1)*1440;

$ipfw_offset = isset($config['ipfw_offset']) ? $config['ipfw_offset'] : 10000;

$UserBytes = array();

function AddRule($user, $KBps, $IP){
	global $ipfw_offset;
	global $ipfw;
	global $config;
	global $tp;
 	$ipfw_rule = $ipfw_offset + $user;
	exec($ipfw.' delete '.$ipfw_rule);
	exec($ipfw.' pipe '.$ipfw_rule.' config bw '.$KBps.'KByte/s');
	exec($ipfw.' add '.$ipfw_rule.' pipe '.$ipfw_rule.' all from me to '.$IP.' out');
	exec($ipfw.' add '.$ipfw_rule.' count all from me to '.$IP.' out');
	//if ($config['modes'][$tp]['ftp']) exec($ipfw.' add '.$ipfw_rule.' allow tcp from '.$IP.' to me 21');
	$sql = "UPDATE users SET ipfw_rule=$ipfw_rule WHERE ID=$user";
	mysql_query($sql);
}

function getUserBytes($user){
	global $ipfw_offset;
	global $ipfw;
	global $UserBytes;
 	$ipfw_rule = $ipfw_offset + $user;
	$RuleNum1 = 0;
	if (!count($UserBytes)){
		$ftext = array();
		exec($ipfw.' show',$ftext);
		for ($i=0; $i<count($ftext); $i++){
			$strTmp = trim($ftext[$i]);
			$len = strcspn($strTmp, " ");
			$RuleNum = substr($strTmp,0,$len);
			$strTmp = trim(substr_replace($strTmp, " ", 0, $len));
			$len = strcspn($strTmp, " ");
			$CountPakets = substr($strTmp,0,$len);
			$strTmp = trim(substr_replace($strTmp, " ", 0, $len));
			$len = strcspn($strTmp, " ");
			$CountBytes = substr($strTmp,0,$len);
			if ($RuleNum1!=$RuleNum){
				$RuleNum1 = $RuleNum;
				$UserBytes[$RuleNum] = $CountBytes;
			}
		}
	}
	return (isset($UserBytes[$ipfw_rule])) ? $UserBytes[$ipfw_rule] : false; 
}

$sql = "SELECT count(*) FROM counter_daily WHERE MinOfDay<$minbeforeday";
$result = mysql_query($sql);
$field = mysql_fetch_row($result);
if ($field[0]>0) {
	$sql = "SELECT UserID, sum(Bytes) as sumBytes FROM counter_daily WHERE (MinOfDay>=$minbeforeday) AND (MinOfDay<$currentday) GROUP BY UserID";
	$result2 = mysql_query($sql);
	while ($result2 && ($field2 = mysql_fetch_assoc($result2))){
		$user = $field2["UserID"];
		$sumBytes = $field2["sumBytes"];
		$sql = "INSERT INTO counter_archive(UserID,UseDate,Bytes) VALUES($user,CURDATE()-1,$sumBytes) ";
		$result = mysql_query($sql);
	}
	if ($result2){
		$sql = "DELETE FROM counter_daily WHERE MinOfDay<$minbeforeday";
		$result = mysql_query($sql);
	}
}


$result = mysql_query("SELECT ID, ipfw_rule, IP, Mode, Enabled, Balans FROM users WHERE ID<>0");
while ($result && ($field = mysql_fetch_assoc($result))){
	$user = $field["ID"]; 
	$ipfw_rule = $field["ipfw_rule"];
	$tp = $field["Mode"];
	$IP = explode(" ", $field["IP"]);
	if ($ipfw_rule) $CountBytes = getUserBytes($user);
	if (!$ipfw_rule || ($CountBytes===false)){
		AddRule($user,$config['modes'][$tp]['fullspeed'], $IP[0]);
		$CountBytes = 0;
	}
	else{
		if ($CountBytes>0){
			$sql = "INSERT INTO counter_daily (UserID,MinOfDay,Bytes) VALUES($user,$MinOfDay,$CountBytes)";
			$result2 = mysql_query($sql);
			if ($result2){
				exec($ipfw.' zero '.$ipfw_rule);
			}
		}
	}
}

$sumBytes = array();

$result = mysql_query("SELECT ID, ipfw_rule, IP, Mode, Enabled, Balans FROM users WHERE ID<>0");
while ($result && ($field = mysql_fetch_assoc($result))){
	$user = $field["ID"]; 
	$ipfw_rule = $field["ipfw_rule"];
	$tp = $field["Mode"];
	$Enabled = (($field["Enabled"]) && ($field["Balans"]>0)); 
	if ($Enabled){
		$myspeed = $config['modes'][$tp]['fullspeed'];
		foreach ($config['modes'][$tp]['limit'] as $limit){
			if (!isset($sumBytes[$limit['min']])){
				$sql = "SELECT UserID, sum(Bytes) as SumBytes FROM counter_daily WHERE MinOfDay>($MinOfDay-{$limit['min']}) GROUP BY UserID";
				$result2 = mysql_query($sql);
				while ($result2 && ($field2 = mysql_fetch_assoc($result2))){
					$sumBytes[$limit['min']][$field2['UserID']] = $field2['SumBytes'];
				}
			}
			if (isset($sumBytes[$limit['min']][$user]) && ($sumBytes[$limit['min']][$user]>$limit['bytes']) && ($myspeed>$limit['speed'])) $myspeed = $limit['speed'];
		}
		exec($ipfw.' pipe '.$ipfw_rule.' config bw '.$myspeed.'KByte/s');
	}
	else{
		exec($ipfw.' pipe '.$ipfw_rule.' config bw '.$config['modes'][$tp]['disablespeed'].'KByte/s');
	}
}		
?>