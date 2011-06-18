<?php
/**
 * Видео-каталог
 * (C) 2006 Ilya Spesivtsev, iljasp@tut.by
 *
 * Обновление авторейтингов
 *
 * @author Ilya Spesivtsev 
 * @version 1.05
 */

$time1 = time()+microtime();

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



$result2 = mysql_query("SELECT CountryID,FilmID FROM filmcountries");
$filmcountries = array();
while ($result2 && ($field2 = mysql_fetch_assoc($result2))){
	 $filmcountries[$field2["FilmID"]][] = $field2["CountryID"];
}

$result2 = mysql_query("SELECT GenreID,FilmID FROM filmgenres");
$filmgenres = array();
while ($result2 && ($field2 = mysql_fetch_assoc($result2))){
	 $filmgenres[$field2["FilmID"]][] = $field2["GenreID"];
}

$result2 = mysql_query("SELECT PersonID,FilmID FROM filmpersones WHERE RoleID IN (1,2,3,4,8)");
$filmpersones = array();
while ($result2 && ($field2 = mysql_fetch_assoc($result2))){
	$filmpersones[$field2["FilmID"]][] = $field2["PersonID"];
}

$result4 = mysql_query("SELECT ID FROM users");
while ($result4 && ($field4 = mysql_fetch_assoc($result4))){
	$userid = $field4["ID"]; 
	mysql_query("DELETE FROM autouserfilmratings WHERE UserID=$userid");
	$result = mysql_query("SELECT avg(Rating) FROM userfilmratings WHERE UserID=$userid");
	if (($field = mysql_fetch_row($result)) && $field[0]) $startrating = $field[0]; else $startrating = 5;

	$countries = array();
	$result = mysql_query("SELECT ID FROM countries");
	while ($result && ($field = mysql_fetch_assoc($result))){
		$countries[$field["ID"]] = $startrating;
	}

	$genres = array();
	$result = mysql_query("SELECT ID FROM genres");
	while ($result && ($field = mysql_fetch_assoc($result))){
		$genres[$field["ID"]] = $startrating;
	}


	$persones = array();
	$persones_c = array();
	$result = mysql_query("SELECT ID FROM persones");
	while ($result && ($field = mysql_fetch_assoc($result))){
		$persones[$field["ID"]] = 0;
		$persones_c[$field["ID"]] = 0;
	}

	$mycountriescount = array();
	$mygenrescount = array();


	$result = mysql_query("SELECT FilmID, Rating FROM userfilmratings WHERE UserID=$userid");
	$count = mysql_num_rows($result);
	while ($result && ($field = mysql_fetch_assoc($result))){
		$filmid = $field["FilmID"];
		foreach ($filmcountries[$filmid] as $countryid){
			if (!isset($mycountriescount[$countryid])){
				$result3 = mysql_query("SELECT count(*) FROM filmcountries INNER JOIN userfilmratings ON(userfilmratings.FilmID = filmcountries.FilmID) WHERE UserID=$userid AND CountryID=$countryid");
				$field3 = mysql_fetch_row($result3);
				$mycountriescount[$countryid] = $field3[0];
			}
			$countries[$countryid] += $field["Rating"];
		}

		foreach ($filmgenres[$filmid] as $genreid){
			if (!isset($mygenrescount[$genreid])){
				$result3 = mysql_query("SELECT count(*) FROM filmgenres INNER JOIN userfilmratings ON(userfilmratings.FilmID = filmgenres.FilmID) WHERE UserID=$userid AND GenreID=$genreid");
				$field3 = mysql_fetch_row($result3);
				$mygenrescount[$genreid] = $field3[0];
			}
			$genres[$genreid] += $field["Rating"];
		}
		if (isset($filmpersones[$filmid])){
			foreach ($filmpersones[$filmid] as $personid){
				$persones[$personid] += $field["Rating"];
				$persones_c[$personid]++;
			}
		}
	}


	foreach($countries as $countryid=>$value){
		if ($mycountriescount[$countryid]){
			$c = $mycountriescount[$countryid];
			$avg = $value/$c;
			$countries[$countryid] = ($avg*$c/($c+10)) + ($startrating*10/($c+10));
		} else{
			$countries[$countryid] = $startrating;
		}
	}

	asort ($countries);

	foreach($genres as $genreid=>$value){
		if ($mygenrescount[$genreid]){
			$c = $mygenrescount[$genreid];
			$avg = $value/$c;
			$genres[$genreid] = ($avg*$c/($c+100)) + ($startrating*100/($c+100));
		} else{
			$genres[$genreid] = $startrating;
		}
	}

	asort ($genres);

	foreach($persones as $personid=>$value){
		if ($persones_c[$personid]){
			$c = $persones_c[$personid];
			$avg = $value/$c;
			$persones[$personid] =($avg*$c/($c+3)) + ($startrating*3/($c+3));
		} else{
			$persones[$personid] = $startrating;
		}
	}
	asort ($persones);

	$films = array();
	$result = mysql_query("SELECT films.ID as FilmID, UserID FROM films LEFT JOIN userfilmratings ON (films.ID = userfilmratings.FilmID)");
	while ($result && ($field = mysql_fetch_assoc($result))){
		$filmid = $field["FilmID"];
		if (($field["UserID"]!=$userid) && !isset($films[$filmid])){
			$films[$filmid] = 1;
		} elseif(($field["UserID"]==$userid)) {
			$films[$filmid] = 0;
		}
	}

	$inserts = array();
	foreach($films as $filmid=>$value){
		if ($value){
			$countryrating = 0;
			$genrerating = 0;
			$personrating = 0;
			
			$count = count($filmcountries[$filmid]);
			foreach ($filmcountries[$filmid] as $countryid){
				$countryrating += $countries[$countryid]/$count;
			}
			if (!$count) $countryrating = $startrating; 

			$count = 0;
			if (isset($filmgenres[$filmid])){
	                        $count = count($filmgenres[$filmid]);
        	                foreach ($filmgenres[$filmid] as $genreid){
					$genrerating += $genres[$genreid]/$count;
				}
			}
			if (!$count) $genrerating = $startrating; 

			$count = 0;
			if (isset($filmpersones[$filmid])){
				$count =  count($filmpersones[$filmid]);
				foreach ($filmpersones[$filmid] as $personid){
					$personrating += $persones[$personid]/$count;
				}
			}
			if (!$count) $personrating = $startrating; 
			$filmrating = round($countryrating*2 + $genrerating*2 + $personrating*6);
			$inserts[] = "($filmid, $userid, $filmrating)";
		}
	}
	mysql_query("INSERT INTO autouserfilmratings(FilmID, UserID, Rating) VALUES " . implode(",",$inserts));

}
		