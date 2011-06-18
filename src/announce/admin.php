<?php
ini_set('display_errors',1);
error_reporting (E_ALL ^ E_NOTICE);
@include "data.inc.php";
session_start();
require_once "config.php";
require_once "logon.php";
require_once "functions.php";
if (isset($_REQUEST["action"])) {
	if (1) {
		switch ($_REQUEST["action"]) {
			case "newfilm":
				if (isset($_REQUEST["film_trailer"])) {
					$trailer = @$_REQUEST["film_trailer"]? $_REQUEST["film_trailer"] : '.mov';
					$name = $trailer;
				} else {
					$name = 'Новый фильм';
					$trailer = '.mov';
				}
				$data["films"][] = array("name"=>$name, "hit"=>0, "trailer"=>$config['trailers_path'] . $trailer,"visible"=>0);
				break;
			case "edit":
				switch ($_REQUEST["edit"]) {
					case "film":
						if (@$_REQUEST["delete"]){
							$filmID = @$_REQUEST["film_id"];
							if ($filmID) {
								$path = $config['www_path'];
								$trailer = $path . $data["films"][$_REQUEST["film"]]["trailer"];
								$path_parts = pathinfo($trailer);
								$newTrailerPath = $path . $config['removed_trailers_path'] . $filmID . $path_parts['extension'];
								rename($trailer, $newTrailerPath);
							}
							$key_index = array_keys(array_keys($data["films"]),$_REQUEST["film"]);
							array_splice($data["films"], $key_index[0], 1);
						}
						else {
							$path = $config['www_path'];
							$film_image = $_REQUEST["film_image"];
							$film_trailer = $_REQUEST["film_trailer"];
							$new = 0;
							if (isset($_FILES['newfile']) && !$_FILES['newfile']['error']){
								$film_image = $config['images_path'] . $_FILES['newfile']['name'];
								
								if (move_uploaded_file($_FILES['newfile']['tmp_name'], $path . $film_image)) {
									chmod($path . $film_image,0644);
									$new = 1;
								}
							}
							if (isset($_FILES['newtrailer']) && !$_FILES['newtrailer']['error']){
								$film_trailer = $config['trailers_path'] . $_FILES['newtrailer']['name'];
								if (move_uploaded_file($_FILES['newtrailer']['tmp_name'], $path . $film_trailer)) {
									chmod($path . $film_trailer, 0644);
								}
							}
							if (!preg_match("#^{$config['images_path']}#",$film_image)){
								$cont = implode("",file($film_image));
								file_write($path . $config['images_path'] . basename($film_image), $cont, "wb", $messages);
								$film_image = $config['images_path'] . basename($film_image);
								$new = 1;
							}
							if ($data["films"][$_REQUEST["film"]]["image"]!==$film_image) $new = 1;
							if ($new){
								require_once "class.image.php";
								$obj = new clsImage();
					    			$obj->loadfile($path . $film_image);
							    	$obj->resizetoheight(105);
								$obj->savefile($path . $film_image);
							}
							$data["films"][$_REQUEST["film"]]["name"] = $_REQUEST["film_name"];
							$data["films"][$_REQUEST["film"]]["originalname"] = $_REQUEST["film_originalname"];
							$data["films"][$_REQUEST["film"]]["image"] = $film_image;
							$data["films"][$_REQUEST["film"]]["trailer"] = $film_trailer;
							$data["films"][$_REQUEST["film"]]["trailer_label"] = $_REQUEST["film_trailer_label"];
							$data["films"][$_REQUEST["film"]]["infourl"] = $_REQUEST["film_infourl"];
							$data["films"][$_REQUEST["film"]]["hit"] = $_REQUEST["hit"];
							$data["films"][$_REQUEST["film"]]["visible"] = isset($_REQUEST["film_visible"]) ? 1 : 0;
						}
						break;
				}
				break;
			default: ;
		}
		SaveData();
	} else echo "Логин или пароль не совпадают";
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<title>Редактор анонсов</title>
<style>
*{
	font-family: Tahoma, Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 8pt;
}

</style>
</head>
<body>
<table border=1>
	<tr>
		<td valign='top'>
			<a href='admin.php'>Начало</a>
			| <a target="_blank" href='announce.php?test'>Тест</a>
		</td>
	</tr>
	<tr>
		<td valign='top'>
		Фильмы:<br>
<?php
	$trailesIndex = array();
	if (!isset($data["films"]))  $data["films"] = array();
	$films = $data["films"];
	foreach($films as $id=>$film){
		$hide = $film['visible']? '' : ' (скрыт)';
		echo "<a href='?view=film&film=$id'>$id: ".$film["name"] . "</a> [{$film['hit']}] $hide<br>";
		$trailesIndex[$film['trailer']] = 1;
	}
?>
			<br><br><br><a href='?action=newfilm'>Добавить фильм</a><hr>
Неучтенные трейлеры:<br>
<?php
    if ($dh = opendir(dirname(__FILE__).'/trailers')) {
        while (($file = readdir($dh)) !== false) {
            if (preg_match('#(avi|mov|wmv)$#', $file) && !isset($trailesIndex[$config['trailers_path'] . $file])) {
                echo "$file [<a href='?action=newfilm&film_trailer=$file'>^</a>]<br>\n";
            }
        }
        closedir($dh);
    }
?>
			<a target="_blank" href='/video/scripts/check_trailers.php'>Синхронизация трейлеров</a>

		</td>
		<td valign='top'>
<?php
	if (isset($_REQUEST["view"])) {
		switch ($_REQUEST["view"]) {
			case "film":
				if (isset($data["films"][$_REQUEST["film"]])){
					$film = $data["films"][$_REQUEST["film"]];
					$visible = $film['visible'] ? "checked" : "";
					$traffic = count($data["traffic"]) ? @array_keys(array_filter($data["traffic"], "myfilter_inc")) : array();

					echo "<form enctype='multipart/form-data' action='?action=edit&edit=film&view=film&film={$_REQUEST['film']}' method='post'>";
					echo "<table>";
					echo "<tr><td>Название (рус.):</td><td><input name='film_name' size='50' value=\"".htmlspecialchars($film['name'])."\"></td></tr>";
					echo "<tr><td>Название (ориг.):</td><td><input name='film_originalname' size='50' value=\"".htmlspecialchars($film['originalname'])."\"></td></tr>";
					echo "<tr><td>Картинка:</td><td><img src=\"".htmlspecialchars($film['image'])."\"></td></tr>";
					echo "<tr><td>Картинка:</td><td><input name='film_image' size='50' value=\"".htmlspecialchars($film['image'])."\"></td></tr>";
					echo "<tr><td>Новая картинка:</td><td><input name='newfile' size='38' type='file'></td></tr>";
					echo "<tr><td>Трейлер:</td><td><input name='film_trailer' size='50' value=\"".htmlspecialchars($film['trailer'])."\"></td></tr>";
					echo "<tr><td>Новый трейлер:</td><td><input name='newtrailer' size='38' type='file'></td></tr>";
					echo "<tr><td>Пометка:</td><td><input name='film_trailer_label' size='50' value=\"".htmlspecialchars($film['trailer_label'])."\"></td></tr>";
					echo "<tr><td>Инфо-сайт:</td><td><input name='film_infourl' size='50' value=\"".htmlspecialchars($film['infourl'])."\"></td></tr>";
					echo "<tr><td>\"Хитовость\" (0-100):</td><td><input name='hit' size='20' value=\"".htmlspecialchars($film['hit'])."\"></td></tr>";
					echo "<tr><td colspan='2' align='right'><input type='checkbox' name='film_visible' value='1' id='opened' $visible> <label for='opened'>Фильм открыт</label></td></tr>";
					echo "<tr><td colspan='2'><input type='checkbox' name='delete' value='1'> Удалить фильм, и прикрепить трейлер к <input name='film_id' size='5'> (ID фильма)</td></tr>";
					echo "<tr><td colspan='2' align='right'><input type='submit' value='Сохранить'></td></tr>";
					echo "</table>";
					echo "</form>";
				}
				break;
		}
	}
?>

		</td>
	<tr>
</table>
</body>
</html>
