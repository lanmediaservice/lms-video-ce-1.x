<?php
/**
 * Видео-каталог
 * Проверка и исправление путей файлов
 *
 * @author Anton "Platosha" Platonov
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru" xml:lang="ru">
<head>
  <title>Проверка и обновление файлов видео-каталога</title>
  <style type="text/css">
body {
  width: 50em;
  margin: 1em auto;
  font-size: 75%;
  font-family: Arial, Helvetica, serif;
  color: #333;
  padding: 1em;
}

.file {
  font-size: 1.1em;
  clear: both;
  padding: 1em;
  border: solid #ddd;
  border-width: 0 0 1px 0;
}
.result {
  padding: 0.3em 1em;
  font-size: 0.9em;
  float: left;
  text-align: right;
  color: #555;
}
.result-code {
  float: right;
  font-size: 3em;
}
.ok .result-code {
  color: #beb;
}
.error .result-code {
  color: #ebb;
}
.error span {
  color: #e88;
}
.error .result {
  text-align: left;
}
.copy {
  text-align: right;
}
  </style>
</head>
<body>
  <h1>Проверка и обновление файлов видео-каталога</h1>
<?php
$PATH = dirname(__FILE__);
require_once "$PATH/../config.php";

$idSQLConnection = mysql_connect($config['mysqlhost'], $config['mysqluser'], $config['mysqlpass']);

if (!$idSQLConnection) {
	echo "Критическая ошибка на сервере. Ошибка при подключении к базе данных.";
	exit;
}

$result = mysql_select_db($config['mysqldb'], $idSQLConnection);

if (!$result) {
	echo "Критическая ошибка на сервере. Ошибка при выборе базы данных.";
	exit;
}

$m = 0;
$n = 0;

foreach($config['source'] as $skey => $source_dir) {
	$source_dir_escaped = str_replace(array("%","_"), array("%","_"), mysql_real_escape_string($source_dir));
	$result = mysql_query("SELECT f.ID, f.Path FROM files f WHERE f.Path LIKE \"{$source_dir_escaped}%\"");
	while ($f = mysql_fetch_object($result)) {
		if (!file_exists($f->Path)) {
			$f->RelPath = str_replace($source_dir, "", $f->Path);
			$found = false;
			foreach($config['source'] as $dkey => $dest_dir) {
				$dest_file = $dest_dir . $f->RelPath;
				if ($skey == $dkey) continue;
				if (file_exists($dest_file)) {
					$found = true;
					break;
				}
			}
			if ($found) {
				$dest_file_escaped = mysql_real_escape_string($dest_file);
				mysql_query("UPDATE files SET Path=\"{$dest_file}\" WHERE ID={$f->ID}");
				$m++;
				echo <<<FILE
				  <div class="file ok">
				    <div class="result-code">&#x2714;</div>
				    Файл <strong>{$f->RelPath}</strong>
				    <div class="result">Был в: <em><strong>{$source_dir}</strong>{$f->RelPath}</em><br />А теперь в: <em><strong>{$dest_dir}</strong>{$f->RelPath}</em></div>
				    <br style="clear: both;" />
				  </div>
FILE;
			} else {
				$n++;
				echo <<<FILE
				  <div class="file error">
				    <div class="result-code">&#x2718;</div>
				    Файл <strong>{$f->RelPath}</strong>
				    <div class="result">Был в: <em><strong>{$source_dir}</strong>{$f->RelPath}</em><br /><span>Не был найден</span></div>
				    <br style="clear: both;" />
				  </div>
FILE;
			}
		}
	}
}

$t = $m + $n;

if ($t == 0) {
	echo <<<SUMMARY
  <div class="summary">
    <p>Ура, все файлы на своих местах!</p>
  </div>
SUMMARY;
} else {
	echo <<<SUMMARY
  <div class="summary">
    <h2>Итого</h2>
    <p>Было потеряных файлов &mdash; $t, из них нашлось &mdash; $m и не нашлось &mdash; $n.</p>
  </div>
SUMMARY;
}

?>
  <div class="copy">&copy; 2006 Платонов Антон</div>
</body>
</html>
