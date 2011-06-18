<?php

/**
 * Видео-каталог
 * (C) 2006-2010 Ilya Spesivtsev, macondos@gmail.com
 *
 * Статистика по серверу
 *
 * @author Ilya Spesivtsev 
 * @version $Id$
 */
require_once "config.php";
require_once "functions.php"; 
require_once isset($config['logon.php']) ? $config['logon.php'] : "logon.php" ;

header('Expires: -1');


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

echo "<h2>Статистика по серверу</h2>";
echo "<h3>Общая статистика</h3>";
echo "<table border='1'>";

$result = mysql_query("SELECT count(*) FROM users");
$field = mysql_fetch_row($result);
echo "<tr><td>Зарегистрированых пользователей</td><td>".$field[0]."</td></tr>";

$result = mysql_query("SELECT count(*) FROM films WHERE Hide=0");
$field = mysql_fetch_row($result);
echo "<tr><td>Всего фильмов в базе, в том числе<br><sup>(в скобках среднее число просмотров на 1 фильм)</sup></td><td>".$field[0]."</td></tr>";

echo "<tr><td colspan='2' align='center'>По годам</td></tr>";

for ($year=(int)date('Y'); $year>2005; $year--) {
    $result = mysql_query("SELECT count(*),sum(Hit) FROM films WHERE Hide=0 AND Year=$year");
    $field = mysql_fetch_row($result);
    echo "<tr><td style='font-size:80%;padding-left:3em'>$year</td><td style='font-size:80%;'>".$field[0]." (".round($field[1]/$field[0]).")</td></tr>";
}

$result = mysql_query("SELECT count(*),sum(Hit) FROM films WHERE Hide=0 AND Year BETWEEN 2000 AND 2005");
$field = mysql_fetch_row($result);
echo "<tr><td style='font-size:80%;padding-left:3em'>2000-2005</td><td style='font-size:80%;'>".$field[0]." (".round($field[1]/$field[0]).")</td></tr>";

$result = mysql_query("SELECT count(*),sum(Hit) FROM films WHERE Hide=0 AND Year BETWEEN 1990 AND 1999");
$field = mysql_fetch_row($result);
echo "<tr><td style='font-size:80%;padding-left:3em'>1990-1999</td><td style='font-size:80%;'>".$field[0]." (".round($field[1]/$field[0]).")</td></tr>";

$result = mysql_query("SELECT count(*),sum(Hit) FROM films WHERE Hide=0 AND Year BETWEEN 1980 AND 1989");
$field = mysql_fetch_row($result);
echo "<tr><td style='font-size:80%;padding-left:3em'>1980-1989</td><td style='font-size:80%;'>".$field[0]." (".round($field[1]/$field[0]).")</td></tr>";

$result = mysql_query("SELECT count(*),sum(Hit) FROM films WHERE Hide=0 AND Year<1980");
$field = mysql_fetch_row($result);
echo "<tr><td style='font-size:80%;padding-left:3em'>&lt;1979</td><td style='font-size:80%;'>".$field[0]." (".round($field[1]/$field[0]).")</td></tr>";

$result = mysql_query("SELECT genres.Name, count(*), sum(Hit), sum(Hit)/count(*) as sh FROM films INNER JOIN filmgenres ON(films.ID=filmgenres.FilmID) INNER JOIN genres ON(genres.ID=filmgenres.GenreID)  WHERE Hide=0 GROUP BY filmgenres.GenreID ORDER BY sh DESC");
echo "<tr><td colspan='2' align='center'>По жанрам</td></tr>";
while ($field = mysql_fetch_row($result)){
	echo "<tr><td style='font-size:80%;padding-left:3em'>".$field[0]."</td><td style='font-size:80%;'>".$field[1]." (".round($field[2]/$field[1]).")</td></tr>";
}

$result = mysql_query("SELECT countries.Name, count(*), sum(Hit), sum(Hit)/count(*) as sh FROM films INNER JOIN filmcountries ON(films.ID=filmcountries.FilmID) INNER JOIN countries ON(countries.ID=filmcountries.CountryID)  WHERE Hide=0 GROUP BY filmcountries.CountryID ORDER BY sh DESC");
echo "<tr><td colspan='2' align='center'>По странам</td></tr>";
while ($field = mysql_fetch_row($result)){
	echo "<tr><td style='font-size:80%;padding-left:3em'>".$field[0]."</td><td style='font-size:80%;'>".$field[1]." (".round($field[2]/$field[1]).")</td></tr>";
}


$result = mysql_query("SELECT sum(Size) FROM films INNER JOIN files ON(files.FilmID = films.ID) WHERE Hide=0");
$field = mysql_fetch_row($result);
echo "<tr><td>Общий объем данных</td><td>".round($field[0]/1000000000)." ГБт</td></tr>";

$result = mysql_query("SELECT sum(ViewActivity) FROM users");
$field = mysql_fetch_row($result);
echo "<tr><td>Всего просмотров информации о фильмах</td><td>".$field[0]."</td></tr>";

$result = mysql_query("SELECT sum(Hit) FROM films");
$field = mysql_fetch_row($result);
echo "<tr><td>Всего просмотров фильмов</td><td>".$field[0]."</td></tr>";

$result = mysql_query("SELECT count(*) FROM comments");
$field = mysql_fetch_row($result);
echo "<tr><td>Всего отзывов</td><td>".$field[0]."</td></tr>";

$result = mysql_query("SELECT count(*) FROM userfilmratings");
$field = mysql_fetch_row($result);
echo "<tr><td>Всего оценок</td><td>".$field[0]."</td></tr>";

echo "</table>";

$result = mysql_query("SELECT UseDate, sum(Bytes) as Bytes FROM counter_archive GROUP BY UseDate");
if (mysql_num_rows($result)) {
    echo "<h3>Статистика по дням</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Дата</th><th>Трафик</th><th>Потребителей >700Мбт</th></tr>";
    while ($result && $field = mysql_fetch_assoc($result)){
    	$UseDate = $field["UseDate"];
    	$Bytes = $field["Bytes"];
    	$result2 = mysql_query("SELECT sum(Bytes) as DBytes FROM counter_archive WHERE UseDate='$UseDate' GROUP BY UserID HAVING DBytes>700000000");
    	$active_users = mysql_num_rows($result2);
    	echo "<tr><td>".date("d.m.Y",strtotime($UseDate))."</td><td>".round($Bytes/1000000000)." Гбт</td><td>$active_users</td></tr>";
    }
    echo "</table>";
}

?>