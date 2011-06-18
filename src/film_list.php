<html>
<head>
<title>Фильмы в видео-каталоге</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<style>
BODY, INPUT, DIV, TABLE{
	font-family: Tahoma, Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 8pt;
}

A:HOVER {
	text-decoration : underline;
}


TABLE {
	border-top : 1px solid silver;
	border-left : 1px solid silver;
	border-right : 0px;
	border-bottom : 0px;
	border-collapse: collapse;
}
TABLE TD,TH{
	border-top : 0px;
	border-left : 0px;
	border-right : 1px solid silver;
	border-bottom : 1px solid silver;
}
TABLE TH{
	background : #F0F0F0;
}
</style>
</head>
<body>
<?php 
require_once "config.php";

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

$order = isset($_REQUEST["order"]) ? $_REQUEST["order"] : "ID";
if (!in_array($order,array("id","name","originalname","year","ssize"))) $order = "ID";
$dir = isset($_REQUEST["dir"]) ? $_REQUEST["dir"] : "asc";
if (!in_array($dir,array("asc","desc"))) $dir = "asc";


$director = array();
$actors = array();
$result2 = mysql_query("SELECT filmpersones.FilmID as FilmID, persones.RusName as RusName, persones.OriginalName as OriginalName, roles.Role as Role, roles.SortOrder as SortOrder FROM filmpersones LEFT JOIN roles ON (roles.ID = filmpersones.RoleID) LEFT JOIN persones ON (persones.ID = filmpersones.PersonID) WHERE roles.Role IN('режиссер','актер','актриса') ORDER BY filmpersones.FilmID, SortOrder");
while ($result2 && $field2 = mysql_fetch_assoc($result2)){
	$id = $field2['FilmID'];
	if ($field2["Role"]=="режиссер") $director[$id] = strlen(trim($field2["RusName"])) ? $field2["RusName"] : (($field2["OriginalName"])?$field2["OriginalName"]:"");
	if (in_array($field2["Role"],array("актер","актриса"))) $actors[$id][] = ($field2["RusName"]) ? $field2["RusName"] : $field2["OriginalName"];
}

foreach ($actors as $id=>$myactors){
	if (count($myactors)>5){
		array_splice ($myactors, 5);
		$myactors[] = " и др.";
	}
	$actors[$id] = implode(", ", $myactors);
}

$genres = array();
$countries = array();

$result2 = mysql_query("SELECT filmgenres.FilmID as FilmID, Name FROM filmgenres LEFT JOIN genres ON (genres.ID = filmgenres.GenreID)");
while ($result2 && $field2 = mysql_fetch_assoc($result2)){
	$id = $field2['FilmID'];
	$genres[$id][] = $field2["Name"];
}
$result2 = mysql_query("SELECT filmcountries.FilmID as FilmID, Name FROM filmcountries LEFT JOIN countries ON (countries.ID = filmcountries.CountryID)");
while ($result2 && $field2 = mysql_fetch_assoc($result2)){
	$id = $field2['FilmID'];
	$countries[$id][] = $field2["Name"];
}


$result = mysql_query("SELECT films.*, sum(Size) as SSize FROM films LEFT JOIN files ON(films.ID=files.FilmID) WHERE hide=0 GROUP BY films.ID ORDER BY $order $dir");

echo "<table border='1'>"
	."<tr>"
	."<th nowrap>ID <a title='Сортировать по возрастанию' href='?order=id'>&#9650;</a> <a title='Сортировать по убыванию' href='?order=id&dir=desc'>&#9660;</a></th>"
	."<th nowrap>Рус. <a title='Сортировать по возрастанию' href='?order=name'>&#9650;</a> <a title='Сортировать по убыванию' href='?order=name&dir=desc'>&#9660;</a></th>"
	."<th nowrap>Англ. <a title='Сортировать по возрастанию' href='?order=originalname'>&#9650;</a> <a title='Сортировать по убыванию' href='?order=originalname&dir=desc'>&#9660;</a></th>"
	."<th nowrap>Год <a title='Сортировать по возрастанию' href='?order=year'>&#9650;</a> <a title='Сортировать по убыванию' href='?order=year&dir=desc'>&#9660;</a></th>"
	."<th nowrap>Жанр</th>"
	."<th nowrap>Страна</th>"
	."<th nowrap>Режиссер</th>"
	."<th nowrap>В ролях</th>"
	."<th nowrap>Размер <a title='Сортировать по возрастанию' href='?order=ssize'>&#9650;</a> <a title='Сортировать по убыванию' href='?order=ssize&dir=desc'>&#9660;</a></th>"
	."</tr>";
$i = 0;
while ($field=mysql_fetch_assoc($result)){
	$OriginalName = $field["OriginalName"];
	$str = "";
	for($i=0;$i<strlen($OriginalName);$i++){
		$str .= "&#".ord($OriginalName{$i}).";";
	}
	$field["OriginalName"] = $str;

	echo "<tr>"
	."<td><a href='{$config['siteurl']}/#film:{$field['ID']}:1:0'>".$field["ID"]."</a></td>"
	."<td>".$field["Name"]."</td>"
	."<td>".$field["OriginalName"]."</td>"
	."<td>".$field["Year"]."</td>";
	
	$mygenres = isset($genres[$field["ID"]]) ? implode("&nbsp;/ ", $genres[$field["ID"]]) : "&nbsp;";
	$mycountries = isset($countries[$field["ID"]]) ? implode("&nbsp;/ ", $countries[$field["ID"]]) : "&nbsp;";

	echo "<td>$mygenres</td>"
	."<td>$mycountries</td>";
	$mydirector = isset($director[$field["ID"]]) ? $director[$field["ID"]] : "&nbsp;";
	$myactors = isset($actors[$field["ID"]]) ? $actors[$field["ID"]] : "&nbsp;";
	echo "<td>$mydirector</td>";
	echo "<td>$myactors</td>";
	echo "<td>" . round($field["SSize"]/1024/1024) . "</td>";
	echo "</tr>";
}
echo "</table><br><br>";
?>
</body>
</html>
