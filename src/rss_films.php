<?php 
header("Content-type: text/xml");
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

	$l = isset($config['rss']['count']) ? $config['rss']['count'] : 10;
	$title = isset($config['rss']['title']) ? $config['rss']['title'] : "Видео-каталог";
	echo "<?xml version=\"1.0\" encoding=\"windows-1251\"?>"
	."\n<rss version=\"2.0\" \nxmlns:content=\"http://purl.org/rss/1.0/modules/content/\">"
	."\n<channel>"
	."\n<title>$title</title>"
	."\n<link>{$config['siteurl']}</link>"
	."\n<description>Последние поступления</description>"
	."\n<language>ru</language>"
	."\n<pubDate>".date("r")."</pubDate>";

$result = mysql_query("select * from films where hide=0 order by CreateDate DESC LIMIT 0,10");
$i = 0;
while ($field=mysql_fetch_assoc($result)){
	$id = $field["ID"];
	$result2 = mysql_query("SELECT Name FROM filmgenres LEFT JOIN genres ON (genres.ID = filmgenres.GenreID) WHERE filmgenres.FilmID={$field['ID']}");
	$genres = array();
	while ($result2 && $field2 = mysql_fetch_assoc($result2)){
		$genres[] = $field2["Name"];
	}
	$result2 = mysql_query("SELECT Name FROM filmcountries LEFT JOIN countries ON (countries.ID = filmcountries.CountryID) WHERE filmcountries.FilmID={$field['ID']}");
	$countries = array();
	while ($result2 && $field2 = mysql_fetch_assoc($result2)){
		$countries[] = $field2["Name"];
	}
	$sgenres = implode(" / ", $genres);
	$scountries = implode(" / ", $countries);
	$director = "";
	$actors = array();
	$result2 = mysql_query("SELECT persones.RusName as RusName, persones.OriginalName as OriginalName, roles.Role as Role, roles.SortOrder as SortOrder FROM filmpersones LEFT JOIN roles ON (roles.ID = filmpersones.RoleID) LEFT JOIN persones ON (persones.ID = filmpersones.PersonID) WHERE filmpersones.FilmID=$id ORDER BY SortOrder");
	while ($result2 && $field2 = mysql_fetch_assoc($result2)){
		if ($field2["Role"]=="режиссер") $director = strlen(trim($field2["RusName"])) ? $field2["RusName"] : (($field2["OriginalName"])?$field2["OriginalName"]:"");
		if (in_array($field2["Role"],array("актер","актриса"))) $actors[] = ($field2["RusName"]) ? $field2["RusName"] : $field2["OriginalName"];
		if (count($actors)>4) break;
	}
	$actors = implode(", ", $actors);

	$field['Poster'] = preg_split("(\r\n|\r|\n)",$field['Poster']);

	echo "\n<item>"
	."\n<title>".htmlspecialchars("{$field['Name']} / {$field['OriginalName']} ({$field['Year']})")."</title>"
	."\n<link>{$config['siteurl']}/#film:{$field['ID']}:1:0</link>"
	."\n<description>".$sgenres." - ".$scountries . "</description>"
	."\n<content:encoded><![CDATA["
	."\n<table style='font-family: Tahoma, Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 8pt;'><tr><td valign='top'><img src='" . ((!preg_match("/^http:\/\//",$field['Poster'][0])) ? $config['siteurl']."/" : "") . $field['Poster'][0]. "'></td><td valign='top'>" 
	."\n<b>Жанр:</b> " . $sgenres." <br/>" 
	."\n<b>Страна:</b> " . $scountries." <br/>" 
	."\n<b>Режиссер:</b> " . $director." <br/>" 
	."\n<b>В ролях:</b> " . $actors." <br/>"
	. (($field["ImdbRating"]>0) ? "\n<img src='{$config['siteurl']}/images/imdb.gif'> " . round($field["ImdbRating"]/10,1) ." <br/>" : "")
	."\n<b>От издателя:</b> " .$field['Description'] . "</td></tr></table>"
	."]]></content:encoded>"
	."\n<pubDate>".date("r",strtotime($field['CreateDate']))."</pubDate>"
	."\n<guid>{$config['siteurl']}/#film:{$field['ID']}:1:0</guid>"
	."\n</item>";
}
?>
  </channel>
</rss>
