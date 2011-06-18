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
	$title .= ": Последние отзывы";
	echo "<?xml version=\"1.0\" encoding=\"windows-1251\"?>"
	."\n<rss version=\"2.0\" \nxmlns:content=\"http://purl.org/rss/1.0/modules/content/\">"
	."\n<channel>"
	."\n<title>$title</title>"
	."\n<link>{$config['siteurl']}</link>"
	."\n<description>Последние отзывы</description>"
	."\n<language>ru</language>"
	."\n<pubDate>".date("r")."</pubDate>";

$where = "WHERE (ISNULL(ToUserID) OR ToUserID=0) ";
$where .= " AND films.Hide=0 ";
$sql = "SELECT FilmID as ID, films.Name as Name, films.OriginalName as OriginalName, films.Year,  users.Login as Login, Date, Text, comments.ID as CommentID FROM comments LEFT JOIN films ON (films.ID = comments.FilmID) LEFT JOIN users ON (users.ID = comments.UserID) $where ORDER BY comments.Date DESC LIMIT 0,20";
$result = mysql_query($sql);
while ($result && $field = mysql_fetch_assoc($result)){
	$id = $field["ID"];

	$field["Text"] = preg_replace("/(\r\n|\n|\r)/","<br/>",$field["Text"]);
    $title = "{$field['Name']} / {$field['OriginalName']} ({$field['Year']})";
    $maxPreviewLength = @$config['rss_max_preview_length']? $config['rss_max_preview_length'] : 120;
    $previewLength = $maxPreviewLength - strlen($title);
    $previewText = trim(strip_tags($field["Text"]));
    if (strlen($previewText)>$previewLength) $previewText = substr($previewText, 0, $previewLength) . "...";
	echo "\n<item>"
	."\n<title>" . htmlspecialchars($title) . ($previewText? htmlspecialchars(" - $previewText") : "" ) . "</title>"
    ."\n<author>" . htmlspecialchars($field['Login']) . "</author>"
	."\n<link>{$config['siteurl']}/#film:{$field['ID']}:0:1</link>"
	."\n<description>{$field['Login']}: ".htmlspecialchars($field["Text"])."</description>"
	."\n<content:encoded><![CDATA["
	."<div style='font-family: Tahoma, Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 8pt;'><b>{$field['Login']} комментирует {$field['Name']} / {$field['OriginalName']} ({$field['Year']}):</b><br>" . $field["Text"] . "</div>"
	."]]></content:encoded>"
	."\n<pubDate>".date("r",strtotime($field['Date']))."</pubDate>"
	."\n<guid>{$config['siteurl']}/#film:{$field['ID']}:0:1:{$field['CommentID']}</guid>"
	."\n</item>";
}
?>
  </channel>
</rss>
