<?php
function ArrayGenerator($array, $level = 1)
{
	$array_elements = array();
	$sep1 = str_repeat("\t", $level-1);
	$sep2 = str_repeat("\t", $level);
	foreach ($array as $k => $v) {
		if (is_string($k)) $outstr = "'" . addslashes($k) . "'";
		else $outstr = $k;
		$outstr .= " => ";
		switch (true) {
			case is_array ($v):
				$outstr .= ArrayGenerator($v, $level + 1);
				break;
			case is_string ($v):
				$outstr .= "'" . str_replace(array("\\", "'"), array("\\\\", "\\'"), $v) . "'";
//				$outstr .= "'" . addslashes($v) . "'";
				break;
			case is_null($v):
				$outstr .= "array()";
				break;
			default:
				$outstr .= $v;
		} // switch
		$array_elements[] = $outstr;
	}
	return "array(\n$sep2" . implode(", \n$sep2", $array_elements) . "\n$sep1)";
}

function file_write($filename, $somecontent, $mode, &$messages)
{
	if (!$fp = fopen($filename, $mode)) {
		$messages[] = "Не могу открыть файл $filename";
		return false;
	}
	if (!fwrite($fp, $somecontent)) {
		$messages[] = "Не могу записать в файл $filename";
		return false;
	}
	fclose($fp);
	return true;
}

function SaveData(){
	global $data;
	file_write("data.inc.php", "<?php \n\$data = " . ArrayGenerator($data) . ";\n ?>", "wb", $messages);
}
?>
