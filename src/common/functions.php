<?php
/**
 * Скрипты LMS
 * (C) 2006 Ilya Spesivtsev, iljasp@tut.by
 *
 * Функции
 *
 * @author Ilya Spesivtsev
 * @version 1.00
 */

/**
 * Collect cookies from httpclient response and add them to an existing array
 *
 * @param mixed $response HTTP response
 * @param array $oldcookies old cookies
 * @return array new and old cookies
 */
function get_cookies_from_response($response, $oldcookies = null)
{
	if (preg_match_all('/Set-Cookie:\s*(.+?);/', $response['header'], $_cookies, PREG_PATTERN_ORDER)) {
		foreach ($_cookies[1] as $cookie) {
			// limit split to 2 elements (key/value)
			list($key, $value) = preg_split('{=}', $cookie, 2);
			$oldcookies[$key] = $value;
		}
	}
	return $oldcookies;
}

function gzdecode(&$string)
{
	return gzinflate(substr($string, 10));
}

function httpClient($url, $cache = 0, $post = '', $timeout = 15, $cookies = null, $headers2 = '', $omitProxy = false, $omitRedirect = false)
{
	global $config;
	// since we shouldn't don't need session functionality here,
	// use this as workaround for php bug #22526 session_start/popen hang
	session_write_close();

	$method = 'GET';
	$headers = ''; // additional HTTP headers, used for post data
	if (!empty($post)) {
		// POST request
		$method = 'POST';
		$headers .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$headers .= "Content-Length: " . strlen($post) . "\r\n";
	}

	$response['error'] = '';
	$response['header'] = '';
	$response['data'] = '';
	$response['url'] = $url;
	$response['success'] = false;

	$uri = parse_url($url);
	$server = $uri['host'];
	$path = $uri['path'];
	if (empty($path)) $path = '/'; #get root on empty path
	if (!empty($uri['query'])) $path .= '?' . $uri['query'];
	$port = @$uri['port'];
	// proxy setup
	if (!empty($config['proxy_host']) && !$omitProxy) {
		$request_url = $url;
		$server = $config['proxy_host'];
		$port = $config['proxy_port'];
		if (empty($port)) $port = 8080;
	} else {
		$request_url = $path; // cpuidle@gmx.de: use $path instead of $url if HTTP/1.0
		$server = $server;
		if (empty($port)) $port = 80;
	}
	// print "<pre>$request_url</pre>";
	// open socket
	$socket = fsockopen($server, $port, $errno, $errstr, (isset($config['connection_timeout'])?$config['connection_timeout']:5));
	if (!$socket) {
		$response['error'] = "Could not connect to $server";
		return $response;
	}
	socket_set_timeout($socket, $timeout);
	// build request
	$request = "$method $request_url HTTP/1.0\r\n";
	$request .= "Host: " . $uri['host'] . "\r\n";
	$request .= "User-Agent: Mozilla/4.0 (compatible; MSIE 5.5; Windows 98)\r\n";
	// if (extension_loaded('zlib')) $request .= "Accept-encoding: gzip\r\n";
	if ($cookies) $request .= cookies2header($cookies);
	$request .= "Connection: Close\r\n";
	$request .= $headers;
	$request .= $headers2;
	$request .= "\r\n";
	$request .= $post;
	// send request
	fputs($socket, $request);
	// read headers from socket
	do {
		$response['header'] .= fread($socket, 1);
	} while (!preg_match('/\r\n\r\n$/', $response['header']) && !feof($socket));
	// read entire socket
	while (!feof($socket)) {
		$response['data'] .= fread($socket, 4096);
	}
	// chunked encoding?
	if (preg_match('/transfer\-(en)?coding:\s+chunked\r\n/i', $response['header'])) {
		$data2 = "";
		$pos = 0;
		do {
			unset($chunk_size);
			do {
				$cb = 1;
				$byte = substr($response['data'], $pos, $cb);
				$pos += $cb;
				$chunk_size .= $byte;
			} while (preg_match('/[a-zA-Z0-9]/', $byte)); // read chunksize including \r
			
			while ($response['data']{$pos}!="\n") {
			    $pos++; // readtrailing \n
			}
  		        $pos++; // readtrailing \n
			$chunk_size = hexdec($chunk_size);
			$this_chunk = substr($response['data'], $pos, $chunk_size);
			$pos += $chunk_size;
			$data2 .= $this_chunk;
			if ($chunk_size) $pos += 2; // read trailing \r\n
		} while ($chunk_size);
		$response['data'] = $data2;
	}
	// close socket
	$status = socket_get_status($socket);
	fclose($socket);
	// check for timeout
	if ($status['timed_out']) {
		$response['error'] = "Connection timed out";
		return $response;
	}
	/*  // verify status code
    if (!preg_match("/^.*? 200 .*?\n/s", $response['header']))
    {
        $response['error'] = 'Server returned wrong status.';
        return $response;
    }*/

	$response['success'] = true;
	// decode data if necessary- do not modify original headers
	if (preg_match("/Content-Encoding:\s+gzip\r?\n/i", $response['header'])) {
		$response['data'] = gzdecode($response['data']);
	}
	// decode UTF8
	if (preg_match("/Content-Type:.*?charset=UTF-8/i", $response['header'])) {
		$response['data'] = utf8_decode($response['data']);
	}

	return $response;
}

function splitName($file)
{
	// Разбиение названия на русское и английское
	/* название предполагается построено по одному из следующих вариантов:
 * "русское название"
 * "английское название"
 * "русское название (английское название)"
 * "английское название (русское название)"
 */
	$name = preg_replace(array("/(\.[^.]*?$)/i", "(_|-|\.)"), array("", " "), $file);
	if (preg_match("/([^\\x5b\(\\x7b]*)\\x5b?\(?\\x7b?([^\\x5d\)\\x7d]*)\\x5d?\)?\\x7d?(.*)/i", $name, $matches)) {
		$names[0] = trim($matches[1]);
		$names[1] = trim($matches[2]);
		$names[2] = trim($matches[3]);
	} else {
		$names[0] = $name;
		$names[1] = "";
	}
	$rname["eng"] = "";
	$rname["rus"] = "";
	for ($j = 0; $j < 3; $j++) {
		$eng = 0;
		$rus = 0;
		for ($i = 0;$i < strlen($names[$j]);$i++) {
			$num = ord($names[$j] {
					$i}
				);
			if ($num >= 65 && $num <= 122) $eng++;
			if ($num >= 192 && $num <= 255) $rus++;
		}
		if ($rus > $eng) {
			if (strlen($rname["rus"]) < strlen($names[$j])) $rname["rus"] = $names[$j];
		} else if (strlen($rname["eng"]) < strlen($names[$j])) $rname["eng"] = $names[$j];
	}
	return $rname;
}

function Lang($text)
{
	$eng = 0;
	$rus = 0;
	for ($i = 0;$i < strlen($text);$i++) {
		$num = ord($text{$i});
		if ($num >= 65 && $num <= 122) $eng++;
		if ($num >= 192 && $num <= 255) $rus++;
	}
	if ($eng > $rus) return "eng";
	else return "rus";
}

function parsePersonesNames($namesstr)
{
	/*
 * Анализ списка имен с разбиением имени на русское и английское
 * имена разделены запятыми, точкой с запятой, переносами,
 * имя предполагается построено по одному из следующих вариантов:
 * "русское название"
 * "английское название"
 * "русское название (английское название)"
 * "английское название (русское название)"
 * "русское название /английское название/"
 * "английское название /русское название/"
 */
	$anames = preg_split ("/(,|;|\r\n|\r|\n)/", $namesstr);
	$anames2 = array();
	for ($k = 0; $k < count($anames);$k++) {
		if (strlen($anames[$k])) {
			if (preg_match("/(.*?)(\(|\/)(.*)(\)|\/).*?/i", $anames[$k], $matches)) {
				$names[0] = $matches[1];
				$names[1] = $matches[3];
			} else {
				$names[0] = $anames[$k];
				$names[1] = "";
			}

			$rname["eng"] = "";
			$rname["rus"] = "";
			for ($j = 0; $j < 2; $j++) {
				$eng = 0;
				$rus = 0;
				for ($i = 0;$i < strlen($names[$j]);$i++) {
					$num = ord($names[$j] {
							$i}
						);
					if ($num >= 65 && $num <= 122) $eng++;
					if ($num >= 192 && $num <= 255) $rus++;
				}
				if ($rus > $eng) {
					if (strlen($rname["rus"]) < strlen($names[$j])) $rname["rus"] = trim($names[$j]);
				} else if (strlen($rname["eng"]) < strlen($names[$j])) $rname["eng"] = trim($names[$j]);
			}
			$anames2[] = $rname;
		}
	}
	return $anames2;
}

function GetUserID($login, $pass)
{
	global $config, $idSQLConnection;
	$DEFAULT_USER = 1;
	$IP = get_ip();
	$passmd5 = md5($pass);

	include_once "classes/auth.php";
	// conversion old stament
	if (isset($config['ipb2']) && $config['ipb2']['enabled']) {
		$config["integration"] = $config['ipb2'];
		$config["integration"]["type"] = "ipb2";
		$config["integration"]["strong"] = false;
	}
	if (isset($config['ipb1']) && $config['ipb1']['enabled']) {
		$config["integration"] = $config['ipb1'];
		$config["integration"]["type"] = "ipb1";
		$config["integration"]["strong"] = false;
	}

	if (!isset($config["integration"]["enabled"])) {
		$config["integration"]["enabled"] = false;
		$config["integration"]["strong"] = false;
	}

	if ($config["integration"]["enabled"]) {
		include_once "classes/auth_" . $config["integration"]["type"] . ".php";
		$ext_idSQLConnection = mysql_connect(
			(isset($config['integration']['mysqlhost']))?$config['integration']['mysqlhost']:$config['mysqlhost'],
			(isset($config['integration']['mysqluser']))?$config['integration']['mysqluser']:$config['mysqluser'],
			(isset($config['integration']['mysqlpass']))?$config['integration']['mysqlpass']:$config['mysqlpass'],
			true
			);
		// echo "реконнект обратно";
		$idSQLConnection = mysql_connect($config['mysqlhost'], $config['mysqluser'], $config['mysqlpass']);
		mysql_select_db($config['mysqldb']);
        if (isset($config['integration']['mysql_set_names'])) {
            mysql_query($config['integration']['mysql_set_names'], $ext_idSQLConnection);   
        } elseif (isset($config['mysql_set_names'])) {
            mysql_query($config['mysql_set_names'], $ext_idSQLConnection); 
        }
		eval ('$ExtUserControl = new ' . strtoupper($config["integration"]["type"]) . 'UserControl($ext_idSQLConnection, isset($config["integration"]["mysqldb"])?$config["integration"]["mysqldb"]:$config["mysqldb"],$config["integration"]["prefix"]);');
	}

	$LMSUserControl = new LMSUserControl($idSQLConnection, $config['mysqldb'], "");

	$lms_user = $LMSUserControl->getUserByLogin($login);
	$valid = $lms_user && $LMSUserControl->verifyUser($lms_user, $pass, ($config['ip']?$IP:false));
	if ($config["integration"]["enabled"] && (!$lms_user || ($lms_user && $lms_user["UserGroup"] == $DEFAULT_USER))) {
		// echo "включена интеграция";
		if ($config["integration"]["strong"] || (!$config["integration"]["strong"] && !$valid)) {
			// echo "строгая интеграция или не совпадают данные";
			$ext_user = $ExtUserControl->getUserByLogin($login);
			$ext_valid = $ext_user && $ExtUserControl->verifyUser($ext_user, $pass, ($config['ip']?$IP:false));
			if ($ext_valid) {
				if ($valid) {
					mysql_select_db($config['mysqldb']);
					return $lms_user;
				} else {
					if ($lms_user) {
						// echo "синхронизируем пользователя со внешним";
						$LMSUserControl->updateUser($login, array("IP" => $IP, "Password" => $passmd5));
						return GetUserID($login, $pass);
					} else {
						// echo "регистрируем пользователя во внутренней БД";
						$usergroup = 1;
						$result3 = mysql_query("SELECT ID FROM users");
						if ($result3 && mysql_num_rows($result3) == 0) $usergroup = 3;
						if ($LMSUserControl->registerUser(array("Login" => "'" . addslashes($login) . "'", "IP" => "'$IP'", "Password" => "'$passmd5'", "Balans" => 1, "UserGroup" => $usergroup, "Enabled" => 1, "Mode" => 1, "RegisterDate" => "NOW()", "Preferences" => "''"))) return GetUserID($login, $pass);
					}
				}
			}
		} else {
			if ($valid) return $lms_user;
		}
	} else {
		// echo "интеграция отключена";
		if ($valid) return $lms_user;
	}
	return null;
}

$tr = array("Ґ" => "G", "Ё" => "YO", "Є" => "E", "Ї" => "YI", "І" => "I",
	"і" => "i", "ґ" => "g", "ё" => "yo", "№" => "#", "є" => "e",
	"ї" => "yi", "А" => "A", "Б" => "B", "В" => "V", "Г" => "G",
	"Д" => "D", "Е" => "E", "Ж" => "ZH", "З" => "Z", "И" => "I",
	"Й" => "Y", "К" => "K", "Л" => "L", "М" => "M", "Н" => "N",
	"О" => "O", "П" => "P", "Р" => "R", "С" => "S", "Т" => "T",
	"У" => "U", "Ф" => "F", "Х" => "H", "Ц" => "TS", "Ч" => "CH",
	"Ш" => "SH", "Щ" => "SCH", "Ъ" => "'", "Ы" => "YI", "Ь" => "",
	"Э" => "E", "Ю" => "YU", "Я" => "YA", "а" => "a", "б" => "b",
	"в" => "v", "г" => "g", "д" => "d", "е" => "e", "ж" => "zh",
	"з" => "z", "и" => "i", "й" => "y", "к" => "k", "л" => "l",
	"м" => "m", "н" => "n", "о" => "o", "п" => "p", "р" => "r",
	"с" => "s", "т" => "t", "у" => "u", "ф" => "f", "х" => "h",
	"ц" => "ts", "ч" => "ch", "ш" => "sh", "щ" => "sch", "ъ" => "'",
	"ы" => "yi", "ь" => "", "э" => "e", "ю" => "yu", "я" => "ya"
	);

function translit($cyr_str)
{
	global $tr;
	return strtr($cyr_str, $tr);
}

function Pirson($arr)
{
	$count = count($arr);
	$x_sum = 0;
	$y_sum = 0;
	foreach($arr as $value) {
		$x_sum += $value["x"];
		$y_sum += $value["y"];
	}
	$x_avg = $x_sum / $count;
	$y_avg = $y_sum / $count;

	$var1 = 0;
	$var2 = 0;
	$var3 = 0;
	foreach($arr as $value) {
		$var1 += ($value["x"] - $x_avg) * ($value["y"] - $y_avg);
		$var2 += pow($value["x"] - $x_avg, 2);
		$var3 += pow($value["y"] - $y_avg, 2);
	}
	if ($var2 * $var3 == 0) return 0;
	return $var1 / sqrt($var2 * $var3);
}

function delete_all_from_dir($Dir)
{
	// delete everything in the directory
	if ($handle = @opendir($Dir)) {
		while (($file = readdir($handle)) !== false) {
			if ($file == "." || $file == "..") {
				continue;
			}
			if (is_dir($Dir . $file)) {
				// call self for this directory
				delete_all_from_dir($Dir . $file . "/");
				chmod($Dir . $file, 0777);
				rmdir($Dir . $file); //remove this directory
			} else {
				chmod($Dir . $file, 0777);
				unlink($Dir . $file); // remove this file
			}
		}
	}
	@closedir($handle);
}

function ForceDirectories($path)
{
	if (strlen($path) == 0) {
		return 0;
	}
	if (strlen($path) < 3) {
		return 1; // avoid 'xyz:\' problem.
	} elseif (is_dir($path)) {
		return 1; // avoid 'xyz:\' problem.
	} elseif (dirname($path) == $path) {
		return 1; // avoid 'xyz:\' problem.
	}
	return (ForceDirectories(dirname($path)) and mkdir($path, 0775));
}

function urename ($from_path, $to_path)
{
	ForceDirectories($to_path);
	$this_path = getcwd();
	if (is_dir($from_path)) {
		chdir($from_path);
		$handle = opendir('.');
		while (($file = readdir($handle)) !== false) {
			if (($file != ".") && ($file != "..")) {
				if (is_dir($file)) {
					urename ($from_path . $file . "/", $to_path . $file . "/");
					chdir($from_path);
				}
				if (is_file($file)) {
					rename($from_path . $file, $to_path . $file);
					chmod($to_path . $file, isset($config['folder_rights'])?$config['folder_rights']:0644);
				}
			}
		}
		closedir($handle);
	} else rename($from_path, $to_path);
	$res = rmdir($from_path);
	@rmdir(dirname($from_path));
	return $res;
}

function delete($file)
{
	chmod($file, 0777);
	if (is_dir($file)) {
		$handle = opendir($file);
		while ($filename = readdir($handle)) {
			if ($filename != "." && $filename != "..") {
				delete($file . "/" . $filename);
			}
		}
		closedir($handle);
		rmdir($file);
	} else {
		unlink($file);
	}
}

function limit_lev ($ltext)
{
	$res = 0.35;
	switch ($ltext) {
		case 1 : $res = 0;
			break;
		case 2 : $res = 0;
			break;
		case 3 : $res = 0;
			break;
		case 4 : $res = 0.26;
			break;
		case 5 : $res = 0.21;
			break;
		case 6 : $res = 0.34;
			break;
	}
	return $res;
}

function compare_substring($str1, $str2, $casesensive = 0)
{
	if (!$casesensive) {
		$str1 = strtolower($str1);
		$str2 = strtolower($str2);
	}
	$v = 255;
	$j = 0;
	while ($j <= (strlen($str2) - strlen($str1))) {
		$v = min($v, levenshtein($str1, substr($str2, $j, strlen($str1))));
		if ($v == 0) break;
		$j++;
	}
	return $v;
}

function MyXMLEncode($str)
{
	$str = str_replace("&", "&amp;", $str);
	$str = str_replace("<", "&lt;", $str);
	$str = str_replace(">", "&gt;", $str);
	$str = str_replace("'", "&apos;", $str);
	$str = str_replace("\"", "&quot;", $str);
	return $str;
}

function Prep($str)
{
	return preg_replace (array("/\r/", "/\n/", "/\"/"), array("\\r", "\\n", "\\\""), $str);
}

function escapeJs($content)
{
    $content = addslashes($content);
    $content = str_replace(array("\r","\n"), array("\\r","\\n"), $content);
    return $content;
}


function googleSearch($title)
{
	$googleServer = "http://images.google.com";

	$resp = httpClient($googleServer . '/search?q=' . urlencode($title) . '&safe=off&tbm=isch&ijn=1&start=0&csl=1', 1);
	$result = array();
        if (preg_match_all('{<a[^>]*?href="(?:http://images\.google\.com)?/imgres\?([^"]*?)"[^>]*?><img[^>]*?src=(.*?)[\s>]}i', $resp['data'], $data, PREG_SET_ORDER)) {
		foreach ($data as $row) {
                        $row[1] = html_entity_decode($row[1]);
                        parse_str($row[1], $vars);
			$info = array();
                        if (preg_match('{imgurl=([^&"]+)}i', $row[1], $matches)) {
                            $url = $matches[1];
                            $url = str_ireplace(array("%3F", "%3D", "%26"), array("?", "=", "&"), $url);
                        } else {
                            $url = $vars['imgurl'];
                        }
			$info['coverurl'] = $url;
			$info['imgsmall'] = trim($row[2],'"\'');
			$info['w'] = $vars['w']; // width
			$info['h'] = $vars['h']; //height
			$result[] = $info;
		}
	}
	return $result;
}

function my_convert_cyr_string($str, $from, $to)
{
	$cp = array();
	$cp["k"] = "KOI8-R";
	$cp["w"] = "CP1251";
	$cp["i"] = "ISO-8859-5";
	$cp["a"] = "CP866";
	$cp["d"] = "CP866";
	$cp["m"] = "MacCyrillic";
	if ((strlen($from) == 1) && (strlen($to) == 1)) {
		return convert_cyr_string($str, $from, $to);
	} else {
		if (isset($cp[$from])) $from = $cp[$from];
		if (isset($cp[$to])) $to = $cp[$to];
		return iconv($from, $to, $str);
	}
}

function getLeechProtectionCode($inarr)
{
	global $config;
	$str = implode("-", $inarr);
	$str .= isset($config['antileechkey']) ? $config['antileechkey'] : "secret";
	return md5($str);
}

function adapt1252To1251($str)
{
	$s1252 = array("'А'", "'Б'", "'В'", "'Г'", "'Д'", "'Е'", "'Ж'", "'З'", "'И'", "'Й'", "'К'", "'Л'", "'М'", "'Н'", "'О'", "'П'", "'Р'", "'С'", "'Т'", "'У'", "'Ф'", "'Х'", "'Ц'", "'Ч'", "'Ш'", "'Щ'", "'Ъ'", "'Ы'", "'Ь'", "'Э'", "'Ю'", "'Я'", "'а'", "'б'", "'в'", "'г'", "'д'", "'е'", "'ж'", "'з'", "'и'", "'й'", "'к'", "'л'", "'м'", "'н'", "'о'", "'п'", "'р'", "'с'", "'т'", "'у'", "'ф'", "'х'", "'ц'", "'ч'", "'ш'", "'щ'", "'ъ'", "'ы'", "'ь'", "'э'", "'ю'", "'я'");
	$s1251 = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'x', '0', 'U', 'U', 'U', 'U', 'Y', 'T', 'b', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'd', 'n', 'o', 'o', 'o', 'o', 'o', '/', 'o', 'u', 'u', 'u', 'u', 'y', 't', 'y');
	return preg_replace($s1252, $s1251, $str);
}

function convert_unicode ($n)
{
	return ($n >= 1040)? ($n-848) : $n;
}

function html2ASCII ($str)
{
	// "&#204;&#229;&#237;&#255; &#231;&#238;&#226;&#243;&#242;" -> "Меня зовут"
	// &#1051;&#1077;&#1085;&#1080;&#1085;&#1075;&#1088;&#1072;&#1076; -> "Ленинград"
	// $html_symbols = array("'&(nbsp);'i","'&(iexcl);'i","'&(cent);'i","'&(pound);'i","'&(curren);'i","'&(yen);'i","'&(brvbar);'i","'&(sect);'i","'&(uml);'i","'&(copy);'i","'&(ordf);'i","'&(laquo);'i","'&(not);'i","'&(shy);'i","'&(reg);'i","'&(macr);'i","'&(deg);'i","'&(plusmn);'i","'&(sup2);'i","'&(sup3);'i","'&(acute);'i","'&(micro);'i","'&(para);'i","'&(middot);'i","'&(cedil);'i","'&(sup1);'i","'&(ordm);'i","'&(raquo);'i","'&(frac14);'i","'&(frac12);'i","'&(frac34);'i","'&(iquest);'i","'&(agrave);'i","'&(aacute);'i","'&(acirc);'i","'&(atilde);'i","'&(auml);'i","'&(aring);'i","'&(aelig);'i","'&(ccedil);'i","'&(egrave);'i","'&(eacute);'i","'&(ecirc);'i","'&(euml);'i","'&(igrave);'i","'&(iacute);'i","'&(icirc);'i","'&(iuml);'i","'&(eth);'i","'&(ntilde);'i","'&(ograve);'i","'&(oacute);'i","'&(ocirc);'i","'&(otilde);'i","'&(ouml);'i","'&(times);'i","'&(oslash);'i","'&(ugrave);'i","'&(uacute);'i","'&(ucirc);'i","'&(uuml);'i","'&(yacute);'i","'&(thorn);'i","'&(szlig);'i","'&(agrave);'i","'&(aacute);'i","'&(acirc);'i","'&(atilde);'i","'&(auml);'i","'&(aring);'i","'&(aelig);'i","'&(ccedil);'i","'&(egrave);'i","'&(eacute);'i","'&(ecirc);'i","'&(euml);'i","'&(igrave);'i","'&(iacute);'i","'&(icirc);'i","'&(iuml);'i","'&(eth);'i","'&(ntilde);'i","'&(ograve);'i","'&(oacute);'i","'&(ocirc);'i","'&(otilde);'i","'&(ouml);'i","'&(divide);'i","'&(oslash);'i","'&(ugrave);'i","'&(uacute);'i","'&(ucirc);'i","'&(uuml);'i","'&(yacute);'i","'&(thorn);'i","'&(yuml);'i");
	$html_symbols = array("'&(nbsp);'", "'&(iexcl);'", "'&(cent);'", "'&(pound);'",
		"'&(curren);'", "'&(yen);'", "'&(brvbar);'", "'&(sect);'", "'&(uml);'",
		"'&(copy);'", "'&(ordf);'", "'&(laquo);'", "'&(not);'", "'&(shy);'",
		"'&(reg);'", "'&(macr);'", "'&(deg);'", "'&(plusmn);'", "'&(sup2);'",
		"'&(sup3);'", "'&(acute);'", "'&(micro);'", "'&(para);'", "'&(middot);'",
		"'&(cedil);'", "'&(sup1);'", "'&(ordm);'", "'&(raquo);'", "'&(frac14);'",
		"'&(frac12);'", "'&(frac34);'", "'&(iquest);'", "'&(Agrave);'",
		"'&(Aacute);'", "'&(Acirc);'", "'&(Atilde);'", "'&(Auml);'", "'&(Aring);'",
		"'&(AElig);'", "'&(Ccedil);'", "'&(Egrave);'", "'&(Eacute);'", "'&(Ecirc);'",
		"'&(Euml);'", "'&(Igrave);'", "'&(Iacute);'", "'&(Icirc);'", "'&(Iuml);'",
		"'&(ETH);'", "'&(Ntilde);'", "'&(Ograve);'", "'&(Oacute);'", "'&(Ocirc);'",
		"'&(Otilde);'", "'&(Ouml);'", "'&(times);'", "'&(Oslash);'", "'&(Ugrave);'",
		"'&(Uacute);'", "'&(Ucirc);'", "'&(Uuml);'", "'&(Yacute);'", "'&(THORN);'",
		"'&(szlig);'", "'&(agrave);'", "'&(aacute);'", "'&(acirc);'",
		"'&(atilde);'", "'&(auml);'", "'&(aring);'", "'&(aelig);'",
		"'&(ccedil);'", "'&(egrave);'", "'&(eacute);'", "'&(ecirc);'",
		"'&(euml);'", "'&(igrave);'", "'&(iacute);'", "'&(icirc);'", "'&(iuml);'",
		"'&(eth);'", "'&(ntilde);'", "'&(ograve);'", "'&(oacute);'", "'&(ocirc);'",
		"'&(otilde);'", "'&(ouml);'", "'&(divide);'", "'&(oslash);'", "'&(ugrave);'",
		"'&(uacute);'", "'&(ucirc);'", "'&(uuml);'", "'&(yacute);'", "'&(thorn);'", "'&(yuml);'");
	$html_symbols_num = array();
	$html_symbols_num[] = " ";
	for ($i = 161;$i <= 255;$i++) {
		$html_symbols_num[] = "&#$i;";
	}
	$str = preg_replace($html_symbols, $html_symbols_num, $str);
	$str = preg_replace("/&#(\d+);/e", "chr(convert_unicode(\\1))", $str);
	$outstr = str_replace(array("&amp;", "`"), array("&", "'"), $str);
	return $outstr;
}

function detectLang($str)
{
		return lms_rus_eng_detect($str);
}

function lms_rus_eng_detect($text)
{
        $eng = 0;
        $rus = 0;
        for ($i = 0;$i < strlen($text);$i++) {
                $num = ord($text{$i});
                if ($num >= 65 && $num <= 122) $eng++;
                if ($num >= 192 && $num <= 255) $rus++;
        }
        if ($eng > $rus) return "eng";
        else return "rus";
}


function decrypt($string, $key)
{
	$result = '';
	$string = base64_decode($string);

	for($i = 0; $i < strlen($string); $i++) {
		$char = substr($string, $i, 1);
		$keychar = substr($key, ($i % strlen($key))-1, 1);
		$char = chr(ord($char) - ord($keychar));
		$result .= $char;
	}

	return $result;
}

function encode_authorize_info($t, $product_code)
{
	global $config;
	$l = md5(md5($config['customer']['login']) . " " . $t);
	$p = md5(md5($config['customer']['pass']) . " " . $t);
	$product_code2 = md5($product_code . " " . $t);
	return "&l=$l&p=$p&t=$t&product_code=$product_code2";
}

function get_ip()
{
	if (getenv("HTTP_CLIENT_IP")) $IP = getenv("HTTP_CLIENT_IP");
	else if (getenv("HTTP_X_FORWARDED_FOR")) $IP = getenv ("HTTP_X_FORWARDED_FOR");
	else if (getenv("REMOTE_ADDR")) $IP = getenv("REMOTE_ADDR");
	else $IP = "";
	return $IP;
}

function ip_in_net($ip, $net)
{
	list($netip, $netsize) = explode("/", $net);
	$netsize = (int) $netsize;
	return ((ip2long($netip) >> (32 - $netsize)) == (ip2long($ip) >> (32 - $netsize)));
}

function between ($value, $first, $second)
{
	return ($value >= $first) && ($value <= $second);
}

function trim_r($arr){
	$new_arr = array();
	foreach($arr as $k=>$v){
		if (is_array($v)) $v = trim_r($v);
		if (is_string($v)) $v = trim($v);
		$new_arr[$k] = $v;
	}	
	return $new_arr;
}

function magic_decode_r($arr, $strategy = -1, $max = 0.3, $utf = -1){
	$new_arr = array();
	foreach($arr as $k=>$v){
		if (is_array($v)) $v = magic_decode_r($v, $strategy, $max, $utf);
		if (is_string($v)) $v = magic_decode($v, $strategy, $max, $utf);
		$new_arr[$k] = $v;
	}	
	return $new_arr;
}


function magic_decode($string, $strategy = -1, $max = 0.3, $utf = -1)
{
	static $s1252 = array(192 => 'A', 193 => 'A', 194 => 'A', 195 => 'A', 196 => 'A',
		197 => 'A', 198 => 'AE', 199 => 'C', 200 => 'E', 201 => 'E', 202 => 'E',
		203 => 'E', 204 => 'I', 205 => 'I', 206 => 'I', 207 => 'I', 208 => 'D',
		209 => 'N', 210 => 'O', 211 => 'O', 212 => 'O', 213 => 'O', 214 => 'O',
		215 => 'x', 216 => '0', 217 => 'U', 218 => 'U', 219 => 'U', 220 => 'U',
		221 => 'Y', 222 => 'T', 223 => 'b', 224 => 'a', 225 => 'a', 226 => 'a',
		227 => 'a', 228 => 'a', 229 => 'a', 230 => 'ae', 231 => 'c',
		232 => 'e', 233 => 'e', 234 => 'e', 235 => 'e', 236 => 'i',
		237 => 'i', 238 => 'i', 239 => 'i', 240 => 'd', 241 => 'n',
		242 => 'o', 243 => 'o', 244 => 'o', 245 => 'o', 246 => 'o',
		247 => '/', 248 => 'o', 249 => 'u', 250 => 'u', 251 => 'u',
		252 => 'u', 253 => 'y', 254 => 't', 255 => 'y');
	$ascii = 0;
	$cyr = 0;
	$other = 0;
	$symbols = array();
	$strlen = strlen($string);

	for ($i = 0; $i < $strlen; $i++) {
		$char_ord_val = ord($string[$i]);
		if ($utf !== 0) {
			$charval = 0;
			if ($char_ord_val < 0x80) {
				$charval = $char_ord_val;
				if ($strategy === -1) $ascii++;
				$symbols[] = $charval;
				continue;
			} elseif ((($char_ord_val &0xF0) >> 4) == 0x0F) {
				if ($utf === -1) {
					$utf = 0;
					$ascii = 0;
					$cyr = 0;
					$other = 0;
					$symbols = array();
					$i = -1;
					continue;
				}
				$charval = (($char_ord_val &0x07) << 18);
				$charval += ((ord($string{++$i}) &0x3F) << 12);
				$charval += ((ord($string{++$i}) &0x3F) << 6);
				$charval += (ord($string{++$i}) &0x3F);
			} elseif ((($char_ord_val &0xE0) >> 5) == 0x07) {
				if ($utf === -1) {
					$utf = 0;
					$ascii = 0;
					$cyr = 0;
					$other = 0;
					$symbols = array();
					$i = -1;
					continue;
				}
				$charval = (($char_ord_val &0x0F) << 12);
				$charval += ((ord($string{++$i}) &0x3F) << 6);
				$charval += (ord($string{++$i}) &0x3F);
			} elseif ((($char_ord_val &0xC0) >> 6) == 0x03) {
				if (($i+1) < $strlen){
					$charval = (($char_ord_val &0x1F) << 6);
					$charval += (ord($string{++$i}) &0x3F);
				} else{
					$utf = 0;
					$ascii = 0;
					$cyr = 0;
					$other = 0;
					$symbols = array();
					$i = -1;
					continue;
				}
			}
			if ($strategy === -1) {
				if (($charval &0xC0) == 0xC0) {
					$other++;
				} elseif ((($charval + 0xB0) &0x4C0) == 0x4C0) {
					$cyr++;
				}
			}
		} else $charval = $char_ord_val;
		$symbols[] = $charval;
	}
	if ($strategy === -1) {
		if ((between ($other, 1, round($ascii * $max)))) {
			$strategy = 1;
		} else {
			$strategy = 2;
		}
	}

	$return_string = '';
	foreach($symbols as $symbol) {
		$chr = chr($symbol);
		switch ($strategy) {
			case 1:
				if (($symbol &0xC0) == 0xC0) $chr = $s1252[$symbol];
				elseif ((($symbol + 0xB0) &0x4C0) == 0x4C0) $chr = chr($symbol-848);
				break;
			case 2:
				if ((($symbol + 0xB0) &0x4C0) == 0x4C0) $chr = chr($symbol-848);
				break;
			default:
		}
		$return_string .= $chr;
	}
	return $return_string;
}

function get_bayes($ratings,$min=8,$av_const=7.2453){
	$sum = 0;
	$count = count($ratings);
	$res = array();
	$res['detail'] = array(1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>0, 9=>0, 10=>0);
	foreach($ratings as $value){
		$res['detail'][$value]++;
		$sum += $value;
	}
	$averange = $sum/$count;
	$res['averange'] = $averange;
	$res['count'] = $count;
	$res['bayes'] = ($count>=$min) ? $averange*($count/($count+$min)) + $av_const*($min/($count+$min)) : 0;
	 return $res;
}

function SearchPerson($texts, $ex_id = 0, $delta = -1, $myselect = null){
        $res = array();
        $persones_exact = array();
        $persones_part = array();
        $persones_approx = array();
        $sortarray = array();

        if (!$myselect){
                $myselect = array();
                $sql = "SELECT persones.ID as ID,
                        persones.RusName as RusName,
                        persones.OriginalName as OriginalName
                        FROM persones";
                $result = mysql_query($sql);
                while ($result && $field = mysql_fetch_assoc($result)){
                        $myselect[] = $field;
                }
        }
        foreach($texts as $text){
                $ltext = strlen($text);
                $ei = 0;
                $pi = 0;
                $ai = 0;
                $delta = ($delta!=-1) ? $delta : limit_lev($ltext);
                reset($myselect);
                foreach ($myselect as $field){
                        $lev_n = levenshtein($field["RusName"], $text,2,1,1);
                        $lev_on = levenshtein($field["OriginalName"], $text,2,1,1);
                        $pos_n = strpos(strtolower($field["RusName"]),strtolower($text));
                        $pos_on = strpos(strtolower($field["OriginalName"]),strtolower($text));
                        $d_n = abs(strlen($field["RusName"])-$ltext);
                        $d_on = abs(strlen($field["OriginalName"])-$ltext);
                        $l_n = min($ltext,strlen($field["RusName"]));
                        $l_on = min($ltext,strlen($field["OriginalName"]));

                        if ($lev_n==0 || $lev_on==0){
                                $persones_exact[$ei] = $field;
                                $ei++;
                        }
                        else{
                                if (($ltext>1) && (is_integer($pos_n) || is_integer($pos_on))){
                                        $persones_part[$pi] = $field;
                                        $pi++;
                                } elseif (($lev_n<(($l_n*$delta)+$d_n)) || ($lev_on<(($l_on*$delta)+$d_on)) && ( (compare_substring($text,$field["RusName"])<=($delta*$ltext)) || (compare_substring($text,$field["OriginalName"])<=($delta*$ltext)) )){
                                        $sortarray[$ai] = min($lev_on - $d_on,$lev_n - $d_n);
                                        $persones_approx[$ai] = $field;
                                        $ai++;
                                }
                        }
                }
        }
        array_multisort($sortarray,SORT_ASC,$persones_approx);
        $c = count($persones_approx);
        for ($i = 10; $i<$c; $i++) {
                array_pop($persones_approx);
        }
        $persones = array();
        $persones[$ex_id] = 1;
        $res["persones_exact"] = array();
        $res["persones_part"] = array();
        $res["persones_approx"] = array();
        foreach ($persones_exact as $person){
                if (empty($persones[$person["ID"]])){
                        $persones[$person["ID"]] = 1;
                        $res["persones_exact"][] = $person;
                }
        }

        foreach ($persones_part as $person){
                if (empty($persones[$person["ID"]])){
                        $persones[$person["ID"]] = 1;
                        $res["persones_part"][] = $person;
                }
        }

        foreach ($persones_approx as $person){
                if (empty($persones[$person["ID"]])){
                        $persones[$person["ID"]] = 1;
                        $res["persones_approx"][] = $person;
                }
        }

        $res["pcount"] = count($res["persones_approx"])+count($res["persones_part"])+count($res["persones_exact"]);
        return $res;
}


?>
