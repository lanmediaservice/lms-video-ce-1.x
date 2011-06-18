<?php
/**
 * Видео-каталог
 * (C) 2006-2009 Ilya Spesivtsev, macondos@gmail.com
 *
 * Ядро
 *
 * @version 1.07
 */
$time1 = time()+microtime();

require_once "config.php";
$itemFilter = isset($config['item_filter'])? $config['item_filter'] : '';

header('Expires: -1');
session_start();

require_once "functions.php";

$noajax = (isset($_REQUEST["noajax"])) ? 1 : 0;
if (!$noajax) {
    require_once "jshttprequest/JsHttpRequest.php";
    $JsHttpRequest = new JsHttpRequest("windows-1251");
}



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

$login = $_SESSION['login'];
$pass = $_SESSION['pass'];

$user = GetUserID($login, $pass);

$action = (isset($_REQUEST["action"])) ? strtolower($_REQUEST["action"]) : "";

if (getRights($action,$user) || ($action=="exit")){
    switch ($action) {
        case "check_update":
            $response = httpClient($config['customer']['updateservice']."?action=hello", 0, '', 15, null, '', false, false);
            if (!preg_match("/^.*? 200 .*?\n/s", $response['header'])) {
                    $_RESULT["products"][$product_code]["errors"][] = 'Server returned wrong status.';
            } else {
                $answer = preg_split("/(\r\n|\r|\n)/",$response['data']);
                $t = $answer[0];
                $delta_t = $t - time();
                foreach ($config['customer']['products'] as $product_code=>$product_path){
                    $v = (is_file($product_path."VERSION")) ? explode("=",implode("",file($product_path."VERSION"))) : null;
                    if (count($v))    $version[$v[0]] = $v[1];
                    $license_number = "";
                    $curent_revision = isset($version[$product_code]) ? $version[$product_code] : 0;
                    $_RESULT["products"][$product_code]["curent_revision"] = $curent_revision;
                    $response = httpClient($config['customer']['updateservice']."?action=check_update".encode_authorize_info($t,$product_code)."&myrev=$curent_revision", 0, '', 15, null, '', false, false);
                    if (!preg_match("/^.*? 200 .*?\n/s", $response['header'])) {
                            $_RESULT["products"][$product_code]["errors"][] = 'Server returned wrong status.';
                    } else {
                        $answer = preg_split("/(\r\n|\r|\n)/",$response['data']);
                        if ((int)trim($answer[0])){
                            $_RESULT["products"][$product_code]["last_revision"] = trim($answer[1]);
                            for ($i=2;$i<count($answer);$i++){
                                $f = explode(" || ",stripcslashes($answer[$i]));
                                //for($j=0;$j<count($f);$j++) $f[$j] = stripcslashes($f[$j]);
                                $_RESULT["products"][$product_code]["revision_history"][] = $f;
                            }
                        } else{
                            for ($i=1;$i<count($answer);$i++) $_RESULT["products"][$product_code]["errors"][] = trim($answer[$i]);
                        }
                    }
                    $response = httpClient($config['customer']['updateservice']."?action=check_update".encode_authorize_info($t,$product_code)."&myrev=$curent_revision", 0, '', 15, null, '', false, false);
                    if (!preg_match("/^.*? 200 .*?\n/s", $response['header'])) {
                            $_RESULT["products"][$product_code]["errors"][] = 'Server returned wrong status.';
                    } else {
                        $answer = preg_split("/(\r\n|\r|\n)/",$response['data']);
                        $result = mysql_query("SELECT revision FROM version WHERE product_code='$product_code'");
                        $curent_sqlrevision = 0;
                        if ($result && ($field = mysql_fetch_row($result))) {
                            $curent_sqlrevision = $field[0];
                        }
                        $_RESULT["products"][$product_code]["curent_sqlrevision"] = $curent_sqlrevision;
                        $t = time() + $delta_t;
                        $response = httpClient($config['customer']['updateservice']."?action=get_sql_updates".encode_authorize_info($t,$product_code)."&sqlrev=$curent_sqlrevision", 0, '', 15, null, '', false, false);
                        if (!preg_match("/^.*? 200 .*?\n/s", $response['header'])) {
                                $_RESULT["products"][$product_code]["errors"][] = 'Server returned wrong status.';
                        } else {
                            $answer = preg_split("/(\r\n|\r|\n)/",$response['data']);

                            if ((int)trim($answer[0])){
                                $_RESULT["products"][$product_code]["last_sqlrevision"] = trim($answer[1]);
                                for ($i=2;$i<count($answer);$i++){
                                    $f = explode(" || ",stripcslashes($answer[$i]));
                                //    for($j=0;$j<count($f);$j++) $f[$j] = stripcslashes($f[$j]);
                                    $_RESULT["products"][$product_code]["sql_updates"][] = $f;
                                }
                            } else{
                                for ($i=1;$i<count($answer);$i++) $_RESULT["products"][$product_code]["errors"][] = trim($answer[$i]);
                            }
                        }
                    }
                }
            }
        break;

        case "update":
            $response = httpClient($config['customer']['updateservice']."?action=hello", 0, '', 15, null, '', false, false);
            if (!preg_match("/^.*? 200 .*?\n/s", $response['header'])) {
                    $_RESULT["products"][$product_code]["errors"][] = 'Server returned wrong status.';
            } else {
                $answer = preg_split("/(\r\n|\r|\n)/",$response['data']);
                $t = $answer[0];
                $delta_t = $t - time();
                $myrev = (isset($_REQUEST['myrev'])) ? $_REQUEST['myrev'] : 0;
                $sqlrev = (isset($_REQUEST['sqlrev'])) ? $_REQUEST['sqlrev'] : 0;
                $product_code = (isset($_REQUEST['product_code'])) ? $_REQUEST['product_code'] : "";
                $product_path = $config['customer']['products'][$product_code];

                if ($myrev){
                    $response = httpClient($config['customer']['updateservice']."?action=get_files_list".encode_authorize_info($t,$product_code)."&myrev=$myrev", 0, '', 15, null, '', false, false);
                    if (!preg_match("/^.*? 200 .*?\n/s", $response['header'])) {
                            $_RESULT["products"][$product_code]["errors"][] = 'Server returned wrong status.';
                    } else {
                        $answer = preg_split("/(\r\n|\r|\n)/",$response['data']);
                        if ((int)trim($answer[0])){
                            $zlib = 0; //(extension_loaded('zlib')) ? 1 : 0;
                            for ($i=1;$i<count($answer);$i++){
                                $f = explode(" || ",stripcslashes(trim($answer[$i])));
                            //    for($j=0;$j<count($f);$j++) $f[$j] = stripcslashes($f[$j]);
                                $fn = $product_path.$f[0];
                                $is_dir = (int) $f[1];
                                $file_id = (int) $f[2];
                                $file_size = (int) $f[3];
                                $file_md5 = isset($f[4]) ? $f[4] : "";
                                $log = "";
                                if ($is_dir && !is_dir($fn)){
                                    $log .= "create directory $fn - " . (mkdir ($fn, 0775) ? "OK" : "FAILED");
                                }
                                if (!$is_dir && (!is_file($fn) || (is_file($fn) && (md5_file($fn)!=$file_md5)))){
                                    $t = time() + $delta_t;

                                    $response = httpClient($config['customer']['updateservice']."?action=download_file".encode_authorize_info($t,$product_code)."&myrev=$myrev&file_id=$file_id&zlib=$zlib", 0, '', 15, null, '', false, false);
                                    if (!preg_match("/^.*? 200 .*?\n/s", $response['header'])) {
                                            $_RESULT["products"][$product_code]["errors"][] = 'Server returned wrong status.';
                                    } else {
                                        $answer2 = preg_split("/(\r\n|\r|\n)/",$response['data']);
                                        if ((int)trim($answer2[0])){
                                            $data = decrypt($answer2[1],md5("{$config['customer']['login']} ".md5($config['customer']['pass'])." $t"));
                                            if ($zlib) $data = gzuncompress($data);
                                            $log .= (!is_file($fn) ? "create new" : "update") . " file $fn - ";
                                            if (!$fp = fopen($fn, 'wb')) {
                                                $log .= "CANNOT OPEN FILE";
                                            }
                                            else{
                                                $size = strlen($data);
                                                $writesize = fwrite($fp,$data);
                                                if ($writesize!=$size) {
                                                    $log .= "CANNOT WRITE TO FILE";
                                                }
                                                else{
                                                    $log .= "OK";
                                                }
                                            }
                                            fclose($fp);
                                        } else $log .= "DOWNLOAD FAILED";
                                    }
                                }
                                if ($log) $_RESULT["log"][] = $log;
                            }
                            $fp = fopen($product_path."VERSION", 'wb');
                            fwrite($fp, $product_code."=".$myrev);
                            fclose($fp);

                        } else{
                            for ($i=1;$i<count($answer);$i++) $_RESULT["errors"][] = trim($answer[$i]);
                        }
                    }
                }
                if ($sqlrev){
                    $result = mysql_query("SELECT revision FROM `version` WHERE product_code='$product_code'");
                    $curent_sqlrevision = 0;
                    if ($result && ($field = mysql_fetch_row($result))) {
                        $curent_sqlrevision = $field[0];
                    }
                    $t = time() + $delta_t;
                    $response = httpClient($config['customer']['updateservice']."?action=get_sql_updates".encode_authorize_info($t,$product_code)."&sqlrev=$curent_sqlrevision&sqlrevneed=$sqlrev", 0, '', 15, null, '', false, false);
                    if (!preg_match("/^.*? 200 .*?\n/s", $response['header'])) {
                            $_RESULT["products"][$product_code]["errors"][] = 'Server returned wrong status.';
                    } else {
                        $answer = preg_split("/(\r\n|\r|\n)/",$response['data']);
                        if ((int)trim($answer[0])){
                            for ($i=2;$i<count($answer);$i++){
                                $f = explode(" || ",stripcslashes($answer[$i]));
                            //    for($j=0;$j<count($f);$j++) $f[$j] = stripcslashes($f[$j]);
                                $new_revision = $f[1];
                                $sql_scripts = preg_split("/(\r\n|\r|\n)/",$f[2]);
                                foreach ($sql_scripts as $sql){
                                    $log = "Execute SQL Query '$sql' - ";
                                    $result = mysql_query($sql);
                                    if ($result){
                                        $log .= "OK, ".mysql_num_rows($result)." rows affected";
                                    } else{
                                        $log .= "FAILED, ".mysql_error();
                                    }
                                    if ($log) $_RESULT["log"][] = $log;
                                }
                                $result = @mysql_query("INSERT INTO `version`(product_code,revision) VALUES('$product_code',$new_revision)");
                                if (!$result){
                                    mysql_query("UPDATE `version` SET revision=$new_revision  where product_code='$product_code'");
                                }

                            }
                        } else{
                            for ($i=1;$i<count($answer);$i++) $_RESULT["errors"][] = trim($answer[$i]);
                        }
                    }
                }
                $fp = fopen($product_path."UPDATE.LOG", 'wb');
                fwrite($fp, implode("\r\n",$_RESULT["log"]));
                fclose($fp);
            }
        break;

        case "exit":
            $_SESSION['login'] = "";
            $_SESSION['pass'] = "";
            $_RESULT["ok"] = 1;
        break;

        case "transfervar":
            if (isset($_REQUEST['varname']) && isset($_REQUEST['value'])){
                $var = $_REQUEST['varname'];
                if (!isset($_SESSION[$var])) $_SESSION[$var] = "";
                $_SESSION[$var] .= $_REQUEST['value'];
            }
        break;

        case "getincominglist":
            $_RESULT["logon"] = 1;
            $result = mysql_query("SELECT Path FROM incoming WHERE IsNode=1");
            $nodes = array();
            while ($result && ($field = mysql_fetch_row($result))) {
                $config['rootdir'][] = $field[0] . "/";
                $nodes[] = $field[0] . "/";
            }
            $files = array();
            require_once "classes/storages.php";
            $storages = new Storages;
            if (isset($config["dir_extensions"])) {
                $storages->set_dir_extensions($config["dir_extensions"]);
            }
            sort ($config['rootdir']);
            reset($config['rootdir']);
            foreach ($config['rootdir'] as $rootdir) {
                    if (!(in_array($rootdir, $nodes) && !isset($files[$rootdir]))) {
                        $files = array_merge($files,$storages->directory_list($rootdir,1));
                    }
            }
            ksort ($files);
            reset($files);

            $count=0;
            while ((list($key, $value) = each ($files)) && ($count<$config['maxincoming'])){
                //echo "\n {$value['path'][0]} - " . (time()+ microtime()-$time1);
                $l = strlen($value["path"][0]);
                if ((!in_array ($value["path"][0] . "/", $config['rootdir'])) || in_array ($value["path"][0] . "/", $nodes)) {
                    $found = 0;
    //                $sql = "SELECT * FROM files INNER JOIN films ON(files.FilmID=films.ID) WHERE (films.AsDir=1 AND (Path='" . addslashes($value["path"][0]) . "') OR ( ( SUBSTRING(Path,1,$l)='" . addslashes($value["path"][0]) . "') AND (SUBSTRING(Path,$l+2)) NOT LIKE '%/%' ))";
                    $sql = "SELECT films.AsDir, files.Path FROM files INNER JOIN films ON(files.FilmID=films.ID) WHERE Path LIKE '" . (str_replace(array("_","%"), array("\_","\%"),addslashes($value["path"][0]))) . "%'";
                    //echo "\n$sql";
                    $result = mysql_query($sql);
                    $fdir = $value["isdir"][0] ? $value["path"][0] : dirname($value["path"][0]);
                    while ($result && ($field = mysql_fetch_assoc($result))){
                        if ($field["AsDir"]) {
                            if (dirname($field["Path"])==$fdir) {
                                $found = 1;
                                break;
                            }
                        }
                        else {
                            if ($field["Path"]==$value["path"][0]) {
                                $found = 1;
                                break;
                            }
                        }
                    }
                    if (!$found) {
    //                $result = mysql_query("SELECT count(*) FROM files WHERE Path='".addslashes($value["path"][0])."' OR Path LIKE '".addslashes($value["path"][0])."/%'");
    //                if ($result && ($field = mysql_fetch_row($result)) && ($field[0]==0) && (!in_array ($value["path"][0]."/",$config['rootdir']))){
                        $result = mysql_query("SELECT count(*) FROM incoming WHERE Path='".addslashes(implode("\r\n",$value["path"]))."'");
                        if ($result && ($field = mysql_fetch_row($result)) && ($field[0]==0)){
                            $path_parts = pathinfo(substr($key, 0, -1));
                            $names = splitName($path_parts["basename"]);
                            $hide = 0;
                            if (@$path_parts["extension"]=='info') $hide=1;
                            $result = mysql_query("INSERT INTO incoming(Path,EngName,RusName,VideoInfo,AudioInfo,RusVariants,ImdbVariants,GoogleImageVariants,imdbPersones,imdbCountries,imdbDesription,imdbGenres,rusCountries,rusGenres,rusCompanies,rusDescription,rusPersones,Hide) VALUES('".addslashes(implode("\r\n",$value["path"]))."','".addslashes($names["eng"])."','".addslashes($names["rus"])."','','','','','','','','','','','','','','',$hide)");
                        }
                        $sql = "SELECT * FROM incoming WHERE Path='".addslashes(implode("\r\n",$value["path"]))."'";
                        $result = mysql_query($sql);
                        if ($result && ($field = mysql_fetch_assoc($result)) && ($field["Hide"]==0)){
                            $file_info = preg_replace($config['multipathpattern'], "", $value["path"][0]) . ".info";
                            $file_info = $storages->decode_path($file_info);
                            if (!preg_match('/ftp:\/\//', $value["path"][0]) && !$field["rusParsed"] && !$field["imdbParsed"] && !$field["RusUrlParse"] && !$field["ImdbUrlParse"] && file_exists($file_info)){
                                require_once(dirname(__FILE__)."/common/xml/xml.php");
                                $xml = new XML();
                                $info = $xml->xml_to_array("<?xml version=\"1.0\" encoding=\"windows-1251\"?>".implode("",file($file_info)));
                                $info = trim_r(magic_decode_r($info));
                                $persones = array();
                                foreach ($info['persones'] as $person) $persones[] = $person["OzonUrl"] . "|" . $person["RusName"] . "|" . $person["Role"] . ( $person["RoleExt"]?" <b>{$person['RoleExt']}</b>":"") . "|" . $person["OriginalName"];
                                $posters = preg_split("/(\r\n|\r|\n)/i", $info['Poster']);
                                $poster = array_shift($posters);
                                $additionalPosters = array();
                                for ($i=0;$i<count($posters);$i++) {
                                    $url = $posters[$i];
                                    $additionalPosters[] = "$url|$url|?|?|1";
                                }
                                $result = mysql_query("UPDATE incoming SET EngName='".addslashes($info['OriginalName'])."'," .
                                        " RusName='".addslashes($info['Name'])."'," .
                                        " imdbYear='".addslashes($info['Year'])."'," .
                                        " rusDescription='".addslashes($info['Description'])."'," .
                                        " imdbMPAA='".addslashes($info['MPAA'])."'," .
                                        " imdbRating='".addslashes($info['ImdbRating'])."'," .
                                        " ImdbUrlParse='".addslashes($info['ImdbUrlParse'])."'," .
                                        " rusPosterUrl='".addslashes($poster)."'," .
                                        " rusTypeOfMovie='".addslashes($info['TypeOfMovie'])."'," .
                                        " imdbGenres='".addslashes(implode("|",$info['genres']))."'," .
                                        " imdbCountries='".addslashes(implode("|",$info['countries']))."'," .
                                        " rusCompanies='".addslashes(implode("|",$info['companies']))."'," .
                                        " rusPersones='".addslashes(implode("\r\n",$persones))."'," .
                                        " imdbOriginalName='".addslashes($info['OriginalName'])."'," .
                                        " rusRusName='".addslashes($info['Name'])."'," .
                                        " GoogleImageVariants='" . addslashes(implode("\r\n",$additionalPosters)) . "'," .
                                        " imdbParsed=1," .
                                        " rusParsed=1" .
                                        " WHERE ID=" . (int)$field["ID"]
                                );
                                $sql = "SELECT * FROM incoming WHERE Path='".addslashes(implode("\r\n",$value["path"]))."'";
                                $result = mysql_query($sql);
                                $field = mysql_fetch_assoc($result);
                            }
                            
                            if ($field["ImdbSearch"]>0) $validity = 1/$field["ImdbSearch"]; else $validity = 0;
                            if ($field["RusSearch"]>0) $validity += 1/$field["RusSearch"];
                            $validity = round(100*$validity/2)."%";
                            $ggi = preg_split("/(\r\n|\r|\n)/",$field["GoogleImageVariants"]);
                            $GoogleImageSearch = ($field["GoogleImageVariants"]) ? count($ggi) : 0;
                            $offset = 0;
                            foreach($config['rootdir'] as $dir){
                                if (!in_array ($dir, $nodes)) {
                                    $pos = strpos($value["path"][0],$dir);
                                    if ($pos===false) {

                                    }
                                    else {
                                        $offset = count(explode("/",$dir));
                                    }
                                }
                            }
                            $_RESULT["files"][] = array(
                                "id" => $field["ID"],
                                "IsNode" => $field["IsNode"],
                                "path" => implode("<br>",$value["path"]),
                                "dir"=>$value["isdir"][0],
                                "size"=>array_sum($value["size"]),
                                "EngName"=>$field["EngName"],
                                "RusName"=>$field["RusName"],
                                "ImdbSearch"=>$field["ImdbSearch"],
                                "RusSearch"=>$field["RusSearch"],
                                "GoogleImageSearch"=>$GoogleImageSearch,
                                "Validity"=>$validity,
                                "ImdbUrlParse"=>$field["ImdbUrlParse"],
                                "RusUrlParse"=>$field["RusUrlParse"],
                                "imdbParsed"=>$field["imdbParsed"],
                                "rusParsed"=>$field["rusParsed"],
                                "Resolution"=>$field["Resolution"],
                                "VideoInfo"=>$field["VideoInfo"],
                                "AudioInfo"=>$field["AudioInfo"],
                                "Quality"=>$field["Quality"],
                                "Translation"=>$field["Translation"],
                                "Runtime"=>$field["Runtime"],
                                "Doubles"=>SearchDoublesByIncomingField($field),
                                "Level" => count(explode("/", $value["path"][0]))-$offset,
                                "SubDir" => ($field["IsNode"]? false : $value["has_subdir"][0])
                            );
                            if (@$_REQUEST["hide_nodes"]) {
                                if  (!in_array ($value["path"][0] . "/", $nodes)) $count++;
                            }else $count++;
                        }
                    }
                }
            }
        break;

        case "unsetnode":
            $id = (int) $_REQUEST['id'];
            $result = mysql_query("SELECT Path FROM incoming WHERE ID=$id");
            if ($result && ($field = mysql_fetch_assoc($result))) {
                $path = str_replace("%", "\%", mysql_real_escape_string($field["Path"]));
                mysql_query("UPDATE incoming SET IsNode=0 WHERE Path LIKE '$path%'");
            }
        break;


        case "updatefilesinfo":
            $film = (isset($_REQUEST['id'])) ? (int)$_REQUEST['id'] : null;
            if ($film){
                require_once("getid3/getid3.php");
                require_once "classes/storages.php";
                $storages = new Storages;
                if (isset($config["dir_extensions"])) {
                    $storages->set_dir_extensions($config["dir_extensions"]);
                }
                $sql = "SELECT AsDir FROM films WHERE ID=$film";
                $result = mysql_query($sql);
                if ($result && ($field = mysql_fetch_assoc($result))){
                    $asDir = $field["AsDir"];
                }

                $mydirs = array();
                $sql = "SELECT Path FROM files WHERE FilmID=$film";
                $result = mysql_query($sql);
                while ($result && ($field = mysql_fetch_assoc($result))){
                    $mydirs[dirname($field["Path"])] = dirname($field["Path"]);
                }
                $mydirs = array_unique($mydirs);

                if (count($mydirs)){
                    require_once("getid3/getid3.php");
                    $resolution = "";
                    $videoInfo = "";
                    $audioInfo = "";
                    $runtime =0;
                    if ($asDir){
                        $sql = "UPDATE files SET Marked=1 WHERE FilmID=$film";
                        $result = mysql_query($sql);
                        $found = 0;
                        $key = 0;
                        $VideoMetas = $storages->getVideoMetas($mydirs);
                        foreach ($VideoMetas["videofiles"] as $file){
                            $md5 = ($config['md5']) ? md5_file($file["path_dec"]) : "";
                            $fs = $file["size"];
                            $fpath = $file["path"];
                            if (($result = mysql_query("SELECT ID FROM files WHERE Path='" . mysql_real_escape_string($file["path"]) . "' AND FilmID=$film"))
                                && ($field = mysql_fetch_assoc($result)))
                            {
                                $fileId = $field['ID'];
                                $sql = "UPDATE files SET Marked=0, Name='".addslashes(basename($fpath))."',`MD5`='$md5', Path='".addslashes($fpath)."', Size=$fs WHERE ID=$fileId";
                                mysql_query($sql);
                            } else {
                                $sql = "INSERT INTO files(FilmID, Name,`MD5`, Path, Size) VALUES($film,'".addslashes(basename($fpath))."','$md5','".addslashes($fpath)."',$fs)";
                                mysql_query($sql);
                            }
                            $found = 1;
                        }
                        if ($found){
                            $sql = "DELETE FROM files WHERE FilmID=$film AND Marked=1";
                            $result = mysql_query($sql);
                        }
                        else{
                            $sql = "UPDATE files SET Marked=0 WHERE FilmID=$film";
                            $result = mysql_query($sql);
                        }
                    }
                    else{
                        $sql = "SELECT ID, Path, Size FROM files WHERE FilmID=$film";
                        $result = mysql_query($sql);
                        $paths = array();
                        $files = array();
                        while ($result && ($field = mysql_fetch_assoc($result))){
                            $paths[] = $field["Path"];
                            $files[$field["Path"]]  = $field;
                        }
                        $VideoMetas = $storages->getVideoMetas($paths);
                        foreach ($VideoMetas["videofiles"] as $file){
                                if (isset($files[$file["path"]])) {
                                    $fileid = $files[$file["path"]]["ID"];
                                    $md5 = ($config['md5']) ? md5_file($file["path_dec"]) : "";
                                    $fs = $file["size"];
                                    $sql = "UPDATE files SET Size=$fs, `MD5`='$md5' WHERE ID=$fileid";
                                    echo $sql;
                                    mysql_query($sql);
                                }
                        }
                    }
                    $resolution = addslashes($VideoMetas["resolution"]);
                    $videoInfo = addslashes($VideoMetas["videoInfo"]);
                    $audioInfo = addslashes($VideoMetas["audioInfo"]);
                    $runtime = addslashes($VideoMetas["runtime_fdir"]);
                    $sql = "UPDATE films SET Resolution='$resolution', VideoInfo='$videoInfo', AudioInfo='$audioInfo', Runtime=$runtime WHERE ID=$film";
                    $result = mysql_query($sql);
                }
            }
            $_RESULT["OK"] = 1;
        break;

        case "updatefilmfield":
            $field = mysql_real_escape_string($_REQUEST['field']);
            if (isset($_SESSION['value']) && !strlen($_REQUEST['value'])){
                $value = mysql_real_escape_string($_SESSION['value']);
                unset ($_SESSION['value']);
            }
            else $value = mysql_real_escape_string($_REQUEST['value']);
            $id = (int) $_REQUEST['id'];
            mysql_query("UPDATE films SET `$field`='$value' WHERE ID=$id");
        break;

        case "updatefilefield":
            $field = mysql_real_escape_string($_REQUEST['field']);
            if (isset($_SESSION['value']) && !strlen($_REQUEST['value'])){
                $value = mysql_real_escape_string($_SESSION['value']);
                unset ($_SESSION['value']);
            }
            else $value = mysql_real_escape_string($_REQUEST['value']);
            $id = (int) $_REQUEST['id'];
            mysql_query("UPDATE files SET $field='$value' WHERE ID=$id");
        break;

        case "searchpersononozon":
            $id = (int) $_REQUEST['id'];
            $result = mysql_query("SELECT RusName, OriginalName FROM persones WHERE ID=$id");
            if ($result && ($field = mysql_fetch_assoc($result))) {
                $res = array();
                if ($field["OriginalName"]) {
                    $res2 = searchPersonOzon($field["OriginalName"], false);
                    foreach($res2 as $value) $res[$value[0]] = $value;
                }
                if ($field["RusName"]) {
                    $res2 = searchPersonOzon($field["RusName"], false);
                    foreach($res2 as $value) $res[$value[0]] = $value;
                }
            }
            $_RESULT = array_values($res);
        break;

        case "resolveozonulrperson":
            $id = (int) $_REQUEST['id'];
            $result = mysql_query("SELECT RusName, OriginalName FROM persones WHERE ID=$id");
            if ($result && ($field = mysql_fetch_assoc($result))){
                $searchby = "OriginalName";
                if ($field["OriginalName"]) $ozonurl = searchPersonOzon($field["OriginalName"]);
                if (!$ozonurl && $field["RusName"]){
                    $ozonurl = searchPersonOzon($field["RusName"]);
                    $searchby = "RusName";
                }
                if ($ozonurl){
                    $res = parsePerson($ozonurl);
                    $RusName = ($searchby != "RusName") ? addslashes($res["RusName"]) : addslashes($field["RusName"]);
                    $OriginalName = addslashes($res["OriginalName"]);
                    $about = addslashes(((strlen($res["Born"]))?$res["Born"]."\r\n":"")
                    .((strlen($res["Profile"]))?$res["Profile"]."\r\n":"")
                    .((strlen($res["About"]))?$res["About"]."\r\n":""));
                    $photos =  addslashes(implode("\r\n",$res["Photos"]));
                    $sql = "UPDATE persones SET RusName='$RusName', OriginalName='$OriginalName', Description = '$about', Images = '$photos', OzonUrl='$ozonurl', LastUpdate = NOW() WHERE ID=$id";
                    mysql_query($sql);
                }
                else{
                    $sql = "UPDATE persones SET LastUpdate = NOW() WHERE ID=$id";
                    mysql_query($sql);
                }
            }
            $_RESULT = "ok";
        break;

        case "updatepersonfield":
            $field = mysql_real_escape_string($_REQUEST['field']);
            if (isset($_SESSION['value']) && !strlen($_REQUEST['value'])){
                $value = mysql_real_escape_string($_SESSION['value']);
                unset ($_SESSION['value']);
            }
            else $value = mysql_real_escape_string($_REQUEST['value']);
            $id = (int) $_REQUEST['id'];
            mysql_query("UPDATE persones SET $field='$value' WHERE ID=$id");
        break;

        case "updateuserfield":
            $field = mysql_real_escape_string($_REQUEST['field']);
            if (isset($_SESSION['value']) && !strlen($_REQUEST['value'])){
                $value = mysql_real_escape_string($_SESSION['value']);
                unset ($_SESSION['value']);
            }
            else $value = mysql_real_escape_string($_REQUEST['value']);
            $id = (int) $_REQUEST['id'];
            mysql_query("UPDATE users SET $field='$value' WHERE ID=$id");
        break;

        case "deleteuser":
            $id = (int)$_REQUEST['userid'];
            if ($id){
                @mysql_query("UPDATE music_albums SET Moderator=0 WHERE Moderator=$id");
                @mysql_query("DELETE FROM music_autouseralbumratings WHERE UserID=$id");
                @mysql_query("DELETE FROM music_bookmarks WHERE UserID=$id");
                @mysql_query("DELETE FROM music_comments WHERE UserID=$id");
                @mysql_query("DELETE FROM music_comments WHERE ToUserID=$id");
                @mysql_query("DELETE FROM music_useralbumratings WHERE UserID=$id");

                mysql_query("UPDATE films SET Moderator=0 WHERE Moderator=$id");
                mysql_query("DELETE FROM autouserfilmratings WHERE UserID=$id");
                mysql_query("DELETE FROM bookmarks WHERE UserID=$id");
                mysql_query("DELETE FROM comments WHERE UserID=$id");
                mysql_query("DELETE FROM comments WHERE ToUserID=$id");
                mysql_query("DELETE FROM hits WHERE UserID=$id");
                mysql_query("DELETE FROM userfilmratings WHERE UserID=$id");
                mysql_query("DELETE FROM users WHERE ID=$id");
            }
        break;

        case "importpersones":
            $filmid = (int) $_REQUEST['filmid'];
            $roleid = (int) $_REQUEST['roleid'];
            if (isset($_SESSION['value']) && !strlen($_REQUEST['value'])){
                $value = $_SESSION['value'];
                unset ($_SESSION['value']);
            }
            else $value = $_REQUEST['value'];
            $persones = parsePersonesNames($value);
            if (count($persones)>0){
                foreach ($persones as $person){
                    $id = 0;
                    $wheres = array();
                    if (strlen($person["eng"])>0) $wheres[] = " OriginalName='".addslashes($person["eng"])."' ";
                    if (strlen($person["rus"])>0) $wheres[] = " RusName='".addslashes($person["rus"])."' ";
                    $where = "WHERE ".implode(" OR ",$wheres);
                    $result = mysql_query("SELECT ID FROM persones $where");
                    if ($result && (mysql_num_rows($result)>0) && ($row = mysql_fetch_row($result))) {
                        $id = $row[0];
                    }
                    else{
                        $result = mysql_query("INSERT INTO persones(RusName,OriginalName,Description,Images) VALUES('".addslashes($person["rus"])."','".addslashes($person["eng"])."','','')");
                        $id = mysql_insert_id();
                    }
                    $sql = "INSERT INTO filmpersones(FilmID,RoleID,PersonID) VALUES($filmid,$roleid,$id)";
                    $result = mysql_query($sql);
                }
            }
            $_RESULT = "ok";
        break;

        case "setroleext":
            $filmid = (int) $_REQUEST['filmid'];
            $roleid = (int) $_REQUEST['roleid'];
            $personid = (int) $_REQUEST['personid'];
            $value = mysql_real_escape_string($_REQUEST['value']);
            mysql_query("UPDATE filmpersones SET RoleExt='$value' WHERE FilmID=$filmid AND RoleID=$roleid AND PersonID=$personid");
        break;

        case "setrole":
            $filmid = (int)$_REQUEST['filmid'];
            $roleid = (int) $_REQUEST['roleid'];
            $personid = (int) $_REQUEST['personid'];
            $value = mysql_real_escape_string($_REQUEST['value']);
            mysql_query("UPDATE filmpersones SET RoleID=$value WHERE FilmID=$filmid AND RoleID=$roleid AND PersonID=$personid");
        break;

        case "deletefilmperson":
            $filmid = (int) $_REQUEST['filmid'];
            $roleid = (int) $_REQUEST['roleid'];
            $personid = (int) $_REQUEST['personid'];
            $value = mysql_real_escape_string($_REQUEST['value']);
            mysql_query("DELETE FROM filmpersones WHERE FilmID=$filmid AND RoleID=$roleid AND PersonID=$personid");
            $_RESULT = "ok";
        break;

        case "deletefilerecord":
            $fileid = (int) $_REQUEST['fileid'];
            mysql_query("DELETE FROM files WHERE ID=$fileid");
            $_RESULT = "ok";
        break;

        case "addfilerecord":
            $filmid = (int) $_REQUEST['filmid'];
            mysql_query("INSERT INTO files(FilmId) VALUES($filmid)");
            $_RESULT = "ok";
        break;

        case "updatefield":
            $field = mysql_real_escape_string($_REQUEST['field']);
            if (isset($_SESSION['value']) && !strlen($_REQUEST['value'])){
                $value = mysql_real_escape_string($_SESSION['value']);
                unset ($_SESSION['value']);
            }
            else $value = mysql_real_escape_string($_REQUEST['value']);
            $id = (int) $_REQUEST['id'];
            mysql_query("UPDATE incoming SET `$field`='$value' WHERE ID=$id");
        break;
        case "searchinfo":
            correctConfigForParser();
            $id = (int) $_REQUEST['id'];
            $result = mysql_query("SELECT * FROM incoming WHERE ID=$id");
            if ($result && ($field = mysql_fetch_assoc($result))){
                $_RESULT["ok"] = 1;
                $rusname = $field["RusName"];
                $engname = $field["EngName"];
                $res = searchInfo($rusname,$engname);
                $ImdbSearch = count($res["imdb"]);
                $RusSearch = count($res["rus"]);
                $gimdb = array();
                foreach($res["imdb"] as $value){
                    $gimdb[] = str_replace(array("\r\n","\r","\n"),"", implode("|",$value));
                }
                $grus = array();
                foreach($res["rus"] as $value){
                    $grus[] = str_replace(array("\r\n","\r","\n"),"", implode("|",$value));
                }
                $ImdbVariants = addslashes(implode("\r\n",$gimdb));
                $RusVariants = addslashes(implode("\r\n",$grus));

                if (!$engname && count($res["imdb"])>0){
                    list($n) = explode("(",$res["imdb"][0]["name"]);                
                    $setEngname = " ,EngName='".addslashes($n)."' ";
                }
                else{
                    $setEngname = "";
                }

                if (!$rusname && count($res["rus"])>0){
                    $setRusname = " ,RusName='".addslashes($res["rus"][0]["name"])."' ";
                }
                else{
                    $setRusname = "";
                }

                mysql_query("UPDATE incoming SET ImdbSearch=$ImdbSearch, RusSearch=$RusSearch, ImdbVariants='$ImdbVariants', RusVariants='$RusVariants' $setEngname $setRusname WHERE ID=$id");
            }
        break;

        case "advsearchinfo":
            correctConfigForParser();
            $id = (int) $_REQUEST['id'];
            $result = mysql_query("SELECT * FROM incoming WHERE ID=$id");
            if ($result && ($field = mysql_fetch_assoc($result))){
                $name = $_REQUEST['name'];
                $RusSearch = 0;
                $grus = array();
                foreach ($_REQUEST['where'] as $where){
                    switch ($where){
                        case "worldart":
                            $res = searchWorldArt($name);
                            $RusSearch += count($res);
                            foreach($res as $value){
                                $grus[] = implode("|",$value);
                            }
                        break;
                        case "sharereactor":
                            $res = searchShareReactor($name);
                            $RusSearch += count($res);
                            foreach($res as $value){
                                $grus[] = implode("|",$value);
                            }
                        break;
                        case "kinopoisk":
                            $res = searchKinoPoisk($name);
                            $RusSearch += count($res);
                            foreach($res as $value){
                                $grus[] = implode("|",$value);
                            }
                        break;
                        case "ozon":
                            $res = searchOzon($name);
                            $RusSearch += count($res);
                            foreach($res as $value){
                                $grus[] = implode("|",$value);
                            }
                        break;
                        case "imdb":
                            $res = searchImdb($name);
                            $ImdbSearch = count($res);
                            $gimdb = array();
                            foreach($res as $value){
                                $gimdb[] = implode("|",$value);
                            }
                            foreach ($gimdb as $k=>$v) $gimdb[$k] = str_replace(array("\r\n","\r","\n"),"", $v);
                            $ImdbVariants = addslashes(implode("\r\n",$gimdb));
                            mysql_query("UPDATE incoming SET ImdbSearch=$ImdbSearch, ImdbVariants='$ImdbVariants' WHERE ID=$id");
                        break;
                    }
                }
                if ($RusSearch){
                    foreach ($grus as $k=>$v) $grus[$k] = str_replace(array("\r\n","\r","\n"),"", $v);
                    $RusVariants = addslashes(implode("\r\n",$grus));
                    mysql_query("UPDATE incoming SET RusSearch=$RusSearch, RusVariants='$RusVariants' WHERE ID=$id");
                }
            }
        break;

        case "imgsearchinfo":
            $id = (int) $_REQUEST['id'];
            $result = mysql_query("SELECT * FROM incoming WHERE ID=$id");
            if ($result && ($field = mysql_fetch_assoc($result))){
                    $sortarray = array();
                $googleresults = array();
                $ggi = preg_split("/(\r\n|\r|\n)/",$field["GoogleImageVariants"]);
                foreach($ggi as $value1){
                    $value = explode("|",$value1);
                    if ($value[4]) {
                        $googleresults[] = $value1;
                        $sortarray[] = $value[2]*$value[3];
                    }
                }
                    if (isset($_REQUEST['name'])){
                    $name = $_REQUEST['name'];
                    $name .= (isset($config["googleaddword"])) ? $config["googleaddword"] : " (Фильм OR Постер OR DVD OR movie OR VHS OR Poster OR Film)";
                    $res = googleSearch($name);
                    foreach($res as $value){
                        if (($value["h"]/$value["w"])>1.3){
                            $value[] = 0;
                            $googleresults[] = implode("|",$value);
                            $sortarray[] = $value['w']*$value['h'];
                        }
                    }
                    }else{
                        if ($field["RusName"]){
                        $name = $field["RusName"];
                        $name .= (isset($config["googleaddword"])) ? $config["googleaddword"] : " (Фильм OR Постер OR DVD OR movie OR VHS OR Poster OR Film)";
                        $res = googleSearch($name);
                        foreach($res as $value){
                            if (($value["h"]/$value["w"])>1.3){
                                $value[] = 0;
                                $googleresults[] = implode("|",$value);
                                $sortarray[] = $value['w']*$value['h'];
                            }
                        }
                    }
                        if ($field["EngName"]){
                        $name = $field["EngName"];
                        $name .= (isset($config["googleaddword"])) ? $config["googleaddword"] : " (Фильм OR Постер OR DVD OR movie OR VHS OR Poster OR Film)";
                        $res = googleSearch($name);
                        foreach($res as $value){
                            if (($value["h"]/$value["w"])>1.3){
                                $value[] = 0;
                                $googleresults[] = implode("|",$value);
                                $sortarray[] = $value['w']*$value['h'];
                            }
                        }
                    }
                    }

                array_multisort($sortarray,SORT_DESC,$googleresults);

                $GoogleImageVariants = addslashes(implode("\r\n",$googleresults));
                mysql_query("UPDATE incoming SET GoogleImageVariants='$GoogleImageVariants' WHERE ID=$id");
            }
        break;

        case "imgfilmsearchinfo":
            $id = (int) $_REQUEST['id'];
            $result = mysql_query("SELECT * FROM films WHERE ID=$id");
            if ($result && ($field = mysql_fetch_assoc($result))){
                    $sortarray = array();
                $googleresults = array();
                    if (isset($_REQUEST['name'])){
                    $name = $_REQUEST['name'];
                    $name .= (isset($config["googleaddword"])) ? $config["googleaddword"] : " (Фильм OR Постер OR DVD OR movie OR VHS OR Poster OR Film)";
                    $res = googleSearch($name);
                    foreach($res as $value){
                        if (($value["h"]/$value["w"])>1.3){
                            $googleresults[] = $value;
                            $sortarray[] = $value['w']*$value['h'];
                        }
                    }
                    }else{
                        if ($field["Name"]){
                        $name = $field["Name"];
                        $name .= (isset($config["googleaddword"])) ? $config["googleaddword"] : " (Фильм OR Постер OR DVD OR movie OR VHS OR Poster OR Film)";
                        $res = googleSearch($name);
                        foreach($res as $value){
                            if (($value["h"]/$value["w"])>1.3){
                                $googleresults[] = $value;
                                $sortarray[] = $value['w']*$value['h'];
                            }
                        }
                    }
                        if ($field["OriginalName"]){
                        $name = $field["OriginalName"];
                        $name .= (isset($config["googleaddword"])) ? $config["googleaddword"] : " (Фильм OR Постер OR DVD OR movie OR VHS OR Poster OR Film)";
                        $res = googleSearch($name);
                        foreach($res as $value){
                            if (($value["h"]/$value["w"])>1.3){
                                $googleresults[] = $value;
                                $sortarray[] = $value['w']*$value['h'];
                            }
                        }
                    }
                    }

                array_multisort($sortarray,SORT_DESC,$googleresults);
                $_RESULT = $googleresults;
            }
        break;

        case "setimage":
            $id = (isset($_REQUEST['id'])) ? (int) $_REQUEST['id'] : null;
            $num = (isset($_REQUEST['num'])) ? addslashes($_REQUEST['num']) : null;
            $what = (isset($_REQUEST['what'])) ? addslashes($_REQUEST['what']) : null;

            if ($id){
                $result = mysql_query("SELECT * FROM incoming WHERE ID=$id");
                if ($result && ($field = mysql_fetch_assoc($result))){
                    $ggi = preg_split("/(\r\n|\r|\n)/",$field["GoogleImageVariants"]);
                    $field["GoogleImageVariants"] = array();
                    foreach($ggi as $value){
                        if ($value) $field["GoogleImageVariants"][] = explode("|",$value);
                    }
                    $field["GoogleImageVariants"][$num][4] = $what;

                    foreach($field["GoogleImageVariants"] as $value){
                        $googleresults[] = implode("|",$value);
                    }
                    $GoogleImageVariants = addslashes(implode("\r\n",$googleresults));
                    mysql_query("UPDATE incoming SET GoogleImageVariants='$GoogleImageVariants' WHERE ID=$id");
                }
            }
        break;

        case "getdetail":
            $id = (int) $_REQUEST['id'];
            $result = mysql_query("SELECT * FROM incoming WHERE ID=$id");
            if ($result && ($field = mysql_fetch_assoc($result))){
                $gimdb = preg_split("/(\r\n|\r|\n)/",$field["ImdbVariants"]);
                $grus = preg_split("/(\r\n|\r|\n)/",$field["RusVariants"]);
                $ggi = preg_split("/(\r\n|\r|\n)/",$field["GoogleImageVariants"]);
                $field["ImdbVariants"] = array();
                foreach($gimdb as $value){
                    if ($value) $field["ImdbVariants"][] = explode("|",$value);
                }
                $field["RusVariants"] = array();
                foreach($grus as $value){
                    if ($value) $field["RusVariants"][] = explode("|",$value);
                }
                $field["GoogleImageVariants"] = array();
                foreach($ggi as $value){
                    if ($value) $field["GoogleImageVariants"][] = explode("|",$value);
                }

                if ($field["ImdbSearch"]>0) $validity = 1/$field["ImdbSearch"]; else $validity = 0;
                if ($field["RusSearch"]>0) $validity += 1/$field["RusSearch"];
                $field["Validity"] = round(100*$validity/2)."%";

                $field["Doubles"] = SearchDoublesByIncomingField($field);
                $_RESULT = $field;

            }
        break;

        case "parse":
            correctConfigForParser();
            $_RESULT["rusParsed"] = 0;
            $_RESULT["imdbParsed"] = 0;
            $id = (int) $_REQUEST['id'];
            $over = addslashes($_REQUEST['over']);
            $result = mysql_query("SELECT * FROM incoming WHERE ID=$id");
            if ($result && ($field = mysql_fetch_assoc($result))){
                if ($field["RusUrlParse"] && (($over==1) || ($over==0 && $field["rusParsed"]==0))){
                    $res = parseFilm($field["RusUrlParse"]);
                    $updates = array();
                    foreach($res as $key=>$value){
                        $value = mysql_real_escape_string($value);
                        $updates[] = " $key='$value' ";
                    }
                    if (count($updates)){
                        $updates[] = " rusParsed=1 ";
                        $sql = "UPDATE incoming SET ".implode(",",$updates)." WHERE ID=$id";
                        mysql_query($sql);
                        echo mysql_error();
                        $_RESULT["rusParsed"] = 1;
                    }
                }
                if ($field["ImdbUrlParse"] && (($over==1) || ($over==0 && $field["imdbParsed"]==0))){
                    $res = parseFilm($field["ImdbUrlParse"]);
                    $updates = array();
                    foreach($res as $key=>$value){
                        $value = mysql_real_escape_string($value);
                        $updates[] = " $key='$value' ";
                    }
                    if (count($updates)){
                        $updates[] = " imdbParsed=1 ";
                        $sql = "UPDATE incoming SET ".implode(",",$updates)." WHERE ID=$id";
                        mysql_query($sql);
                        $_RESULT["imdbParsed"] = 1;
                    }
                }
            }
        break;

        case "parseavi":
            $_RESULT["ok"] = 1;
            $id = (int) $_REQUEST['id'];
            $result = mysql_query("SELECT * FROM incoming WHERE ID=$id");
            require_once("getid3/getid3.php");
            if ($result && ($field = mysql_fetch_assoc($result))){
                require_once "classes/storages.php";
                $storages = new Storages;
                if (isset($config["dir_extensions"])) {
                    $storages->set_dir_extensions($config["dir_extensions"]);
                }
                $paths = preg_split ("/(\r\n|\r|\n)/",$field["Path"]);
                //for($i=0; $i<count($paths);$i++) if ($field["IsDir"]) $paths .= "/";
                $VideoMetas = $storages->getVideoMetas($paths);
                $resolution = addslashes($VideoMetas["resolution"]);
                $videoInfo = addslashes($VideoMetas["videoInfo"]);
                $audioInfo = addslashes($VideoMetas["audioInfo"]);
                $runtime = addslashes($VideoMetas["runtime"]);
                $sql = "UPDATE incoming SET Resolution='$resolution', VideoInfo='$videoInfo', AudioInfo='$audioInfo', Runtime=$runtime WHERE ID=$id";
                mysql_query($sql);
                $_RESULT["Resolution"] = $resolution;
            }
        break;

        case "commitincoming":

            $_RESULT["ok"] = 0;
            $id = (int) $_REQUEST['id'];
            $result = mysql_query("SELECT * FROM incoming WHERE ID=$id");
            if ($result && ($field = mysql_fetch_assoc($result))){
                if (!$field["imdbOriginalName"]) $field["imdbOriginalName"] = ($field["rusOriginalName"]) ? $field["rusOriginalName"] : translit($field["rusRusName"]);
                if (!$field["rusRusName"]) $field["rusRusName"] = $field["imdbOriginalName"];
                $originalname = preg_replace("/&#(\d{2,3});/e", "chr(\\1)",$field["imdbOriginalName"]);
                if (!$originalname){
                    $pi = pathinfo ($field["Path"]);
                    $f = preg_replace("/\.[^\.]*$/","",$pi["basename"]);
                    if ($field["EngName"]){
                        $originalname = $field["EngName"];
                    } else $originalname = translit($f);
                    if (!$field["rusRusName"]){
                        $field["rusRusName"] = ($field["RusName"]) ? $field["RusName"] : $f;
                    }
                }
                $rusPersones = trim($field["rusPersones"]) ? $field["rusPersones"] : $field["imdbPersones"];
                $rusPersones = preg_split ("/(\r\n|\r|\n)/", $rusPersones);
                foreach ($rusPersones as $value){
                    $id = 0;
                    $person = explode("|",$value);
                    $result = ($person[0]) ? mysql_query("SELECT ID FROM persones WHERE OzonUrl='".addslashes($person[0])."'") : 0;
                    if (!($result && (mysql_num_rows($result)>0)) && $person[1]) {
                        $result = mysql_query("SELECT ID FROM persones WHERE RusName='".addslashes(trim($person[1]))."'");
                        if (!($result && (mysql_num_rows($result)>0)) && $person[3]) {
                            $result = mysql_query("SELECT ID FROM persones WHERE OriginalName='".addslashes(trim($person[3]))."'");
                        }
                    }
                    if ($result && (mysql_num_rows($result)>0) && ($row = mysql_fetch_row($result))) {
                        $id = $row[0];
                    }
                    else{
                        $result = mysql_query("INSERT INTO persones(OzonUrl,RusName,OriginalName,Description,Images) VALUES('".addslashes(trim($person[0]))."','".addslashes(trim($person[1]))."','".addslashes(trim($person[3]))."','','')");
                        $id = mysql_insert_id();
                    }
                    $person["id"] = $id;
                    $s = $person[2];
                    $i = 0;
                    while (preg_match("/<b>.*,.*<\/b>/i",$s)){
                        $s = preg_replace("/(<b>.*),(.*<\/b>)/","\\1 /\\2",$s);
                        $i++;
                        if ($i>10) break;
                    }
                    $roles = explode(",",$s);
                    $proles = array();
                    for($i=0;$i<count($roles);$i++){
                        preg_match("/([^:]*)(:\s<b>(.*)<\/b>)?/i",$roles[$i],$matches);
                        $role = mysql_real_escape_string(trim($matches[1]));
                        $roleext = (isset($matches[3])) ? trim($matches[3]) : "";

                        $result = mysql_query("SELECT ID FROM roles WHERE Role='$role'");
                        if ($result && (mysql_num_rows($result)>0) && ($row = mysql_fetch_row($result))) {
                            $id = $row[0];
                        }
                        else{
                            $result = mysql_query("INSERT INTO roles(Role) VALUES('$role')");
                            $id = mysql_insert_id();
                        }
                        $proles[$i]["id"] = $id;
                        $proles[$i]["roleext"] = $roleext;
                    }
                    $person["roles"] = $proles;
                    $persones[] = $person;
                }
                $countries = array();
                if (strlen($field["imdbCountries"])){
                    $imdbCountries = explode("|",$field["imdbCountries"]);
                    for($i=0;$i<count($imdbCountries);$i++){
                        $result = mysql_query("SELECT ID FROM countries WHERE imdbCountry='".$imdbCountries[$i]."'");
                        if ($result && (mysql_num_rows($result)>0) && ($row = mysql_fetch_row($result))) {
                            $id = $row[0];
                        }
                        else{
                            $result = mysql_query("INSERT INTO countries(imdbCountry,Name) VALUES('".$imdbCountries[$i]."','".$imdbCountries[$i]."')");
                            $id = mysql_insert_id();
                        }
                        $countries[$i] = $id;
                    }
                } elseif (strlen($field["rusCountries"])){
                    $rusCountries = explode("|",$field["rusCountries"]);
                    for($i=0;$i<count($rusCountries);$i++){
                        $result = mysql_query("SELECT ID FROM countries WHERE Name='".$rusCountries[$i]."'");
                        if ($result && (mysql_num_rows($result)>0) && ($row = mysql_fetch_row($result))) {
                            $id = $row[0];
                            $countries[$i] = $id;
                        }
                    }
                }

                $genres = array();
                $field["imdbGenres"] = (!$field["imdbGenres"]) ? $field["rusGenres"] : $field["imdbGenres"];
                if (strlen($field["imdbGenres"])){
                    $imdbGenres = explode("|",$field["imdbGenres"]);
                    for($i=0;$i<count($imdbGenres);$i++){
                        if (strlen($imdbGenres[$i])>0){
                            $result = mysql_query("SELECT ID FROM genres WHERE imdbGenre='".$imdbGenres[$i]."'");
                            if ($result && (mysql_num_rows($result)>0) && ($row = mysql_fetch_row($result))) {
                                $id = $row[0];
                            }
                            else{
                                $result = mysql_query("INSERT INTO genres(imdbGenre,Name) VALUES('".$imdbGenres[$i]."','".$imdbGenres[$i]."')");
                                $id = mysql_insert_id();
                            }
                            $genres[$i] = $id;
                        }
                    }
                }

                $rusCompanies = explode("|",$field["rusCompanies"]);
                $companies = array();
                for($i=0;$i<count($rusCompanies);$i++){
                    $result = mysql_query("SELECT ID FROM companies WHERE rusName='".$rusCompanies[$i]."'");
                    if ($result && (mysql_num_rows($result)>0) && ($row = mysql_fetch_row($result))) {
                        $id = $row[0];
                    }
                    else{
                        $result = mysql_query("INSERT INTO companies(Name) VALUES('".$rusCompanies[$i]."')");
                        $id = mysql_insert_id();
                    }
                    $companies[$i] = $id;
                }

                preg_match("/\/([^\/]*\/)$/",$field["ImdbUrlParse"],$matches);
                $imdbID = $matches[1];

                if (!strlen($field["rusPosterUrl"])) $field["rusPosterUrl"] = $field["imdbPosterUrl"];
                if (!strlen($field["imdbYear"])) $field["imdbYear"] = $field["rusYear"];
                if (!strlen($field["rusTypeOfMovie"])) $field["rusTypeOfMovie"] = "Не определен";

                $ggi = preg_split("/(\r\n|\r|\n)/",$field["GoogleImageVariants"]);
                $field["GoogleImageVariants"] = array();
                foreach($ggi as $value){
                    $value = explode("|",$value);
                    if ($value[4]) $field["GoogleImageVariants"][] = $value[0];
                }
                $Posters = addslashes(implode("\r\n",array_merge(array($field["rusPosterUrl"]),$field["GoogleImageVariants"])));


                $sql = "INSERT INTO films(Name,OriginalName,Description,Year,Runtime,CreateDate,UpdateDate,VideoInfo,AudioInfo,imdbID,ImdbRating,MPAA,Resolution,Poster,Hide,TypeOfMovie, Translation, Quality, Moderator,SmallPoster,BigPosters,Links,Frames,SmallFrames) VALUES("
                    ."'".addslashes($field["rusRusName"])."',"
                    ."'".addslashes($originalname)."',"
                    ."'".addslashes($field["rusDescription"])."',"
                    ."'".$field["imdbYear"]."',"
                    .$field["Runtime"].","
                    ."NOW(),"
                    ."NOW(),"
                    ."'".addslashes($field["VideoInfo"])."',"
                    ."'".addslashes($field["AudioInfo"])."',"
                    ."'".$imdbID."',"
                    .round($field["imdbRating"]*10).","
                    ."'".addslashes($field["imdbMPAA"])."',"
                    ."'".$field["Resolution"]."',"
                    ."'".addslashes($Posters)."',"
                    .$config["Hide"].","
                    ."'".addslashes($field["rusTypeOfMovie"])."',"
                    ."'".addslashes($field["Translation"])."',"
                    ."'".addslashes($field["Quality"])."',"
                    .$user["ID"].",'','','',NULL,NULL)";
                $result = mysql_query($sql);
                $filmid = mysql_insert_id();

                foreach($persones as $person){
                    foreach($person["roles"] as $role){
                        $sql = "INSERT INTO filmpersones(FilmID,RoleID,RoleExt,PersonID) VALUES($filmid,".(int)$role["id"].",'".mysql_real_escape_string($role["roleext"])."',".(int)$person["id"].")";
                        $result = mysql_query($sql);
                    }
                }
                foreach($countries as $country){
                    $sql = "INSERT INTO filmcountries(FilmID,CountryID) VALUES($filmid,$country)";
                    $result = mysql_query($sql);
                }
                foreach($genres as $genre){
                    $sql = "INSERT INTO filmgenres(FilmID,GenreID) VALUES($filmid,$genre)";
                    $result = mysql_query($sql);
                }
                foreach($companies as $company){
                    $sql = "INSERT INTO filmcompanies(FilmID,CompanyID) VALUES($filmid,$company)";
                    $result = mysql_query($sql);
                }
                $movefile = 0;
                if (isset($config['storages'])){
                    $maxfree = 0;
                    $mystorage = $config['storages'][0];
                    foreach($config['storages'] as $storage){
                        if (disk_free_space($storage)>$maxfree){
                            $mystorage = $storage;
                            $maxfree = disk_free_space($storage);
                        }
                    }
                    $movefile = 1;
                    if ($movefile && isset($config['make_genre_folder']) && $config['make_genre_folder']){
                        $mygenre = count($genres) ? translit($imdbGenres[0]) : "other";
                        $mygenre = preg_replace('{[^a-z0-9_]}', "",
                            str_replace(" ", "_",
                                str_replace("%20", "_", strtolower($mygenre))
                            )
                        );
                        $mystorage = $mystorage . $mygenre . "/";
                        if (!is_dir($mystorage)) mkdir ($mystorage, 0775);

                    }
                    $directory = translit($originalname);
                    $directory = preg_replace('{[^a-z0-9_]}', "",
                        str_replace(" ", "_",
                            str_replace("%20", "_", strtolower($directory))
                        )
                    );
                    $directory = substr($directory, 0, 40);
                    $suffix = null;
                    while (file_exists($mystorage.$directory.$suffix)){
                        $suffix++;
                    }
                }

                $includeExtensions = @$config['include_extensions']? $config['include_extensions'] : array('avi','vob','mpg','mpeg','mpe','m1v','m2v','asf','mov','dat','wmv','wm','rm','rv','divx','mp4','mkv','qt','ogm');
                $excludeExtensions = @$config['exclude_extensions']? $config['exclude_extensions'] : array('sub');

                require_once "classes/storages.php";
                $storages = new Storages;
                if (isset($config["dir_extensions"])) {
                    $storages->set_dir_extensions($config["dir_extensions"]);
                }


                $mypath_dec = $storages->decode_path($field["Path"]);
                if (preg_match('/ftp:\/\//',$field["Path"])) {
                        if (!preg_match('/(\r\n|\r|\n)/', $field["Path"]) && ($files = $storages->directory_list($field["Path"])) && count($files)){
                            mysql_query("UPDATE films SET AsDir=1 WHERE ID=$filmid");
                            foreach ($files as $file){
                                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                                if  (in_array($extension, $includeExtensions)) {
                                    $fs = $file['size'];
                                    $sql = "INSERT INTO files(FilmID,Name,MD5,Path,Size) VALUES($filmid,'".addslashes($file['name'])."','','".addslashes($field["Path"]."/".$file['name'])."',$fs)";
                                    $result = mysql_query($sql);
                                    if ($result) $_RESULT["ok"] = 1;
                                }
                            }
                        } else {
                            $multipath = preg_split ("/(\r\n|\r|\n)/",$field["Path"]);
                            foreach($multipath as $mypath){
                                $fs = $storages->getFileSize ($mypath);
                                $path_parts = pathinfo($mypath);
                                $name = $path_parts["basename"];
                                $result = mysql_query("INSERT INTO files(FilmID,Name,MD5,Path,Size) VALUES($filmid,'".addslashes($name)."','','".addslashes($mypath)."',$fs)");
                                if ($result) $_RESULT["ok"] = 1;
                            }
                        }
                }else if (is_dir($mypath_dec)){
                    mysql_query("UPDATE films SET AsDir=1 WHERE ID=$filmid");
                    $handle = opendir(realpath($mypath_dec));
                    while (false !== ($file = readdir($handle))) {
                        $extension = pathinfo($file, PATHINFO_EXTENSION);
                        if  (!is_dir($mypath_dec."/".$file) && $file!="." && $file!=".." && in_array($extension, $includeExtensions)) {
                            $md5 = ($config['md5']) ? md5_file($mypath_dec."/".$file) : "";
                            $fs = $storages->getFileSize ($mypath_dec."/".$file, false);

                            $fpath = ($movefile) ? $mystorage.$directory.$suffix."/".$file : $mypath_dec."/".$file;
                            $fpath = $storages->encode_path($fpath);
                            $path_parts = pathinfo($fpath);
                            $name = $path_parts["basename"];
                            $sql = "INSERT INTO files(FilmID,Name,MD5,Path,Size) VALUES($filmid,'".addslashes($name)."','$md5','".addslashes($fpath)."',$fs)";
                            $result = mysql_query($sql);
                        }
                    }
                    closedir($handle);
                    if ($movefile){
                        if (!urename($mypath_dec."/",$mystorage.$directory.$suffix."/")){
                            UndoFilm($filmid);
                        }
                        else $_RESULT["ok"] = 1;
                    } else $_RESULT["ok"] = 1;
                }
                else {
                    $multipath = preg_split ("/(\r\n|\r|\n)/",$field["Path"]);
                    if ($movefile) mkdir ($mystorage.$directory.$suffix, 0775);
                    foreach($multipath as $mypath){
                        $mypath_dec = $storages->decode_path($mypath);
                        preg_match("/(\.[^.]*)$/", $mypath_dec,$matches);
                        $path_parts = pathinfo($mypath_dec);
                        $ext = strtolower($matches[1]);
                        $md5 = ($config['md5']) ? md5_file($mypath) : "";
                        $fs = $storages->getFileSize ($mypath_dec, false);
                        $fpath = ($movefile) ? ((count($multipath)>1) ? $mystorage.$directory.$suffix."/".$path_parts["basename"] : $mystorage.$directory.$suffix."/".$directory.$config['suffix'].$ext) : $mypath_dec;
                        $fpath_enc = $storages->encode_path($fpath);
                        $path_parts = pathinfo($fpath_enc);
                        $name = ($movefile && (count($multipath)==1)) ? $directory.$ext : $path_parts["basename"];
                        $result = mysql_query("INSERT INTO files(FilmID,Name,MD5,Path,Size) VALUES($filmid,'".addslashes($name)."','$md5','".addslashes($fpath_enc)."',$fs)");
                        if ($movefile){
                            mysql_query("UPDATE films SET AsDir=1 WHERE ID=$filmid");
                            if (!rename($mypath_dec,$fpath)){
                                UndoFilm($filmid);
                            } else{
                                chmod($fpath,isset($config['folder_rights'])?$config['folder_rights']:0644);
                                $_RESULT["ok"] = 1;
                            }
                        } else $_RESULT["ok"] = 1;
                    }
                }
            }
        break;

        case "attach":
            $_RESULT["ok"] = 0;
            $fromid = (int) $_REQUEST['fromid'];
            $toid = (int) $_REQUEST['toid'];
            $result = mysql_query("SELECT * FROM incoming WHERE ID=$fromid");
            if ($result && ($field = mysql_fetch_assoc($result))){
                $filmid = $toid;
                $movefile = 0;
                if (isset($config['storages'])){
                    $result2 = mysql_query("SELECT AsDir, Path, OriginalName FROM films INNER JOIN files ON (films.ID=files.FilmID) WHERE films.ID=$toid");
                    if ($result2 && ($field2 = mysql_fetch_assoc($result2))){
                        if (preg_match('/ftp:\/\//',$field2["Path"])) {
                            $maxfree = 0;
                            $mystorage = $config['storages'][0];
                            foreach($config['storages'] as $storage){
                                if (disk_free_space($storage)>$maxfree){
                                    $mystorage = $storage;
                                    $maxfree = disk_free_space($storage);
                                }
                            }
                            $directory = translit($field2["OriginalName"]);
                            $directory = preg_replace("{[^a-z0-9_]}", "",
                                str_replace(" ", "_",
                                    str_replace("%20", "_", strtolower($directory))
                                )
                            );
                            $directory = substr($directory, 0, 40);
                            $suffix = null;
                            while (file_exists($mystorage.$directory.$suffix)){
                                $suffix++;
                            }
                        }
                        else{
                            $mystorage = dirname($field2["Path"]);
                            $directory = "";
                            $suffix = "";
                        }

                    }
                    $movefile = 1;
                }

                $includeExtensions = @$config['include_extensions']? $config['include_extensions'] : array('avi','vob','mpg','mpeg','mpe','m1v','m2v','asf','mov','dat','wmv','wm','rm','rv','divx','mp4','mkv','qt','ogm');
                $excludeExtensions = @$config['exclude_extensions']? $config['exclude_extensions'] : array('sub');

                require_once "classes/storages.php";
                $storages = new Storages;
                if (isset($config["dir_extensions"])) {
                    $storages->set_dir_extensions($config["dir_extensions"]);
                }
                $mypath_dec = $storages->decode_path($field["Path"]);
                if (preg_match('/ftp:\/\//',$field["Path"])) {
                        if (!preg_match('/(\r\n|\r|\n)/', $field["Path"]) && ($files = $storages->directory_list($field["Path"]."/")) && count($files)){
                            foreach ($files as $file){
                                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                                if  (in_array($extension, $includeExtensions)) {
                                    $fs = $file['size'];
                                    $sql = "INSERT INTO files(FilmID,Name,MD5,Path,Size) VALUES($filmid,'".addslashes($file['name'])."','','".addslashes($file['path'])."',$fs)";
                                    $result = mysql_query($sql);
                                    if ($result) $_RESULT["ok"] = 1;
                                }
                            }
                        } else {
                            mysql_query("UPDATE films SET AsDir=0 WHERE ID=$filmid");
                            $multipath = preg_split ("/(\r\n|\r|\n)/",$field["Path"]);
                            foreach($multipath as $mypath){
                                $fs = $storages->getFileSize ($mypath);
                                $path_parts = pathinfo($mypath);
                                $name = $path_parts["basename"];
                                $result = mysql_query("INSERT INTO files(FilmID,Name,MD5,Path,Size) VALUES($filmid,'".addslashes($name)."','','".addslashes($mypath)."',$fs)");
                                if ($result) $_RESULT["ok"] = 1;
                            }
                        }
                }else if (is_dir($mypath_dec)){
                    $handle = opendir(realpath($mypath_dec));
                    while (false !== ($file = readdir($handle))) {
                        $extension = pathinfo($file, PATHINFO_EXTENSION);
                        if  (!is_dir($mypath_dec."/".$file) && $file!="." && $file!=".." && in_array($extension, $includeExtensions)) {
                            $md5 = ($config['md5']) ? md5_file($mypath_dec."/".$file) : "";
                            $fs = $storages->getFileSize ($mypath_dec."/".$file, false);

                            $fpath = ($movefile) ? $mystorage.$directory.$suffix."/".$file : $mypath_dec."/".$file;
                            $fpath = $storages->encode_path($fpath);
                            $path_parts = pathinfo($fpath);
                            $name = $path_parts["basename"];
                            $sql = "INSERT INTO files(FilmID,Name,MD5,Path,Size) VALUES($filmid,'".addslashes($name)."','$md5','".addslashes($fpath)."',$fs)";
                            $result = mysql_query($sql);
                        }
                    }
                    closedir($handle);
                    if ($movefile){
                        if (!urename($mypath_dec."/",$mystorage.$directory.$suffix."/")){
                            //файлы не скопировались
                        }
                        else $_RESULT["ok"] = 1;
                    } else $_RESULT["ok"] = 1;
                }
                else {
                    mysql_query("UPDATE films SET AsDir=$movefile WHERE ID=$filmid");
                    $multipath = preg_split ("/(\r\n|\r|\n)/",$field["Path"]);
                    if ($movefile) @mkdir($mystorage.$directory.$suffix, 0775);
                    foreach($multipath as $mypath){
                        $mypath_dec = $storages->decode_path($mypath);
                        preg_match("/(\.[^.]*)$/", $mypath_dec,$matches);
                        $path_parts = pathinfo($mypath_dec);
                        $ext = strtolower($matches[1]);
                        $md5 = ($config['md5']) ? md5_file($mypath_dec) : "";
                        $fs = $storages->getFileSize ($mypath_dec, false);
                        $fpath = ($movefile) ? $mystorage.$directory.$suffix."/".$path_parts["basename"] : $mypath_dec;

                        $fpath_enc = $storages->encode_path($fpath);
                        $path_parts = pathinfo($fpath);
                        $name = $path_parts["basename"];

                        $path_parts = pathinfo($fpath_enc);
                        $name_enc = $path_parts["basename"];

                        $result = mysql_query("INSERT INTO files(FilmID,Name,MD5,Path,Size) VALUES($filmid,'".addslashes($name_enc)."','$md5','".addslashes($fpath_enc)."',$fs)");
                        if ($movefile){
                            if (!rename($mypath_dec,$fpath)){
                                //файлы не скопировались
                            } else{
                                chmod($fpath,isset($config['folder_rights'])?$config['folder_rights']:0644);
                                $_RESULT["ok"] = 1;
                            }
                        } else $_RESULT["ok"] = 1;
                    }
                }
            }
        break;

        case "generate_screenshots":
            $film = (isset($_REQUEST['film'])) ? (int) $_REQUEST['film'] : 0;
            $count = (isset($_REQUEST['count'])) ? (int) $_REQUEST['count'] : (isset($config['count_frames']) ? $config['count_frames'] : 8);
            $escape_style = isset($config['escape_style']) ? $config['escape_style'] : "u";
            $config['mencoder'] = isset($config['mencoder']) ? $config['mencoder'] : "mencoder";
            $config['mplayer'] = isset($config['mplayer']) ? $config['mplayer'] : "mplayer";
            $config['tempdir'] = isset($config['tempdir']) ? $config['tempdir'] : "/tmp";
            $config['vcodec'] = isset($config['vcodec']) ? $config['vcodec'] : "mpeg4";

            require_once "classes/storages.php";
            $storages = new Storages;
            if (isset($config["dir_extensions"])) {
                $storages->set_dir_extensions($config["dir_extensions"]);
            }

            $result = mysql_query("SELECT films.OriginalName as OriginalName, files.Path as Path FROM films INNER JOIN files ON(films.ID=files.FilmID) WHERE films.ID=$film");
            $paths = array();
            $sortarray = array();
            while ($result && ($field = mysql_fetch_assoc($result))){
                if (!preg_match('/ftp:\/\//',$field["Path"])) {
                    $sortarray[] = $storages->getFileSize($field["Path"]);
                    $paths[] = $field["Path"];
                }
                $originalname = $field["OriginalName"];
            }
            array_multisort($sortarray,SORT_DESC,$paths);
            if (!preg_match('/ftp:\/\//',$paths[0])) {
                $tmpdir =  $config['tempdir'] . "/MP". md5($paths[0]);
                $this_path = getcwd();
                chdir(dirname($tmpdir));
                @mkdir (basename($tmpdir), 0775);
                chdir($tmpdir);

                $directory = translit($originalname);
                $directory = preg_replace('{[^a-z0-9_]}', "",
                    str_replace(" ", "_",
                        str_replace("%20", "_", strtolower($directory))
                    )
                );
                $directory = "frames/".$film."_" . substr($directory, 0, 40);

                $filename = $paths[0];

                $ThisFileInfo = $storages->getMetaInfo($filename);

                $playtime_seconds = ($ThisFileInfo["playtime_seconds"] ? $ThisFileInfo["playtime_seconds"] : 3600);

                $frame_rate = $ThisFileInfo['video']["frame_rate"];
                $small_frame_width = isset($config['small_frame_width']) ? $config['small_frame_width'] : 80;

                $command = "{$config['mencoder']} -frames 1 -ovc lavc -lavcopts vcodec={$config['vcodec']} -nosound -o $tmpdir/tmp.avi ";
                for ($j=0; $j<$count;$j++){
                    $res = array();
                    $ss = floor(rand(300,$playtime_seconds-600));
                    $nfilename = escapeshellarg($storages->decode_path($filename));
                    $command .= " $nfilename -ss $ss ";
                }
                echo "$command<br>\n";
                exec($command, $output);
                echo implode("<br>\n",$output);
                $smallframes = array();
                $frames = array();
                if (is_file("$tmpdir/tmp.avi")){
                    ForceDirectories($this_path."/".$directory);
                    $command = "{$config['mplayer']} -vo jpeg -speed 100 $tmpdir/tmp.avi";
                    echo "$command<br>\n";
                    exec($command, $output);
                    echo implode("<br>\n",$output);

                    for($i=0;$i<$count;$i++){
                        $fn = sprintf("$tmpdir/%08s.jpg", $i+1);
                        if (is_file($fn)){
                            $to_path = $directory."/f$i.jpg";
                            @unlink($this_path."/".$to_path);
                            if (rename($fn,$this_path."/".$to_path)){
                                $frames[] = $to_path;
                            }
                        }
                    }
                    $imageinfo = getimagesize($this_path."/".$to_path);
                    $small_frame_height = round($imageinfo[1]*($small_frame_width/$imageinfo[0]));
                    $command = "{$config['mplayer']} -vo jpeg -speed 100 -vf scale=$small_frame_width:$small_frame_height $tmpdir/tmp.avi";
                    echo "$command<br>\n";
                    exec($command, $output);
                    echo implode("<br>\n",$output);
                    for($i=0;$i<$count;$i++){
                        $fn = sprintf("$tmpdir/%08s.jpg", $i+1);
                        if (is_file($fn)){
                            $to_path = $directory."/s$i.jpg";
                            @unlink($this_path."/".$to_path);
                            if (rename($fn,$this_path."/".$to_path)){
                                $smallframes[] = $to_path;
                            }
                        }
                    }
                }
                delete($tmpdir);
                mysql_query("UPDATE films SET Frames='".addslashes(implode("\r\n",$frames))."', SmallFrames='".addslashes(implode("\r\n",$smallframes))."' WHERE films.ID=$film");
            }

        break;

        case "downloadposter":
            $_RESULT["ok"] = 1;
            $id = (int) $_REQUEST['id'];
            $result = mysql_query("SELECT Poster, BigPosters, SmallPoster, OriginalName FROM films WHERE ID=$id");
            $covers = array();
            $bigcovers = array();
            $smallcovers = array();
            if ($result && ($field = mysql_fetch_assoc($result))) {
                $name = translit($field["OriginalName"]);
                $name = preg_replace('{[^a-z0-9_]}', "",
                    str_replace(" ", "_",
                        str_replace("%20", "_", strtolower($name))
                        )
                    );
                $covers = preg_split ("/(\r\n|\r|\n)/", $field["Poster"]);
                $bigcovers = preg_split ("/(\r\n|\r|\n)/", $field["BigPosters"]);
                $smallcovers = preg_split ("/(\r\n|\r|\n)/", $field["SmallPoster"]);
                include_once "classes/covers.php";
                if (!isset($config["covers"])) {
                    $config["covers"]["undesirable_size"] =  $config["hide_ozon_poster"] ? 160 : 0;
                    $config["covers"]["defaultcovers"]["width"] = 160;
                    $config["covers"]["defaultcovers"]["maxwidth"] = 240;
                    $config["covers"]["defaultcovers"]["zoom"] = false;
                    $config["covers"]["smallcovers"]["width"] = 60;
                    $config["covers"]["smallcovers"]["maxwidth"] = 60;
                    $config["covers"]["smallcovers"]["zoom"] = false;
                    $config["covers"]["bigcovers"]["width"] = 300;
                    $config["covers"]["bigcovers"]["maxwidth"] = 0;
                    $config["covers"]["bigcovers"]["zoom"] = false;
                }

                $config["covers"]["smallcovers"]["required"] = true;
                $config["covers"]["defaultcovers"]["required"] = true;
                $config["covers"]["bigcovers"]["required"] = false;

                $CoverManager = new CoversManager(
                    $name,
                    array(
                        "smallcovers"=>array(
                            "localpath"=>"smallposters/",
                            "covers"=>$smallcovers,
                            "settings"=>$config["covers"]["smallcovers"]
                        ),
                        "defaultcovers"=>array(
                            "localpath"=>"posters/",
                            "covers"=>$covers,
                            "settings"=>$config["covers"]["defaultcovers"]
                        ),
                        "bigcovers"=>array(
                            "localpath"=>"bigposters/",
                            "covers"=>$bigcovers,
                            "settings"=>$config["covers"]["bigcovers"]
                        )
                    ),
                    "defaultcovers",
                    null,
                    $config["covers"]["undesirable_size"],
                    isset($_REQUEST['deletebad']) ? $_REQUEST['deletebad'] : false
                );

                $res = $CoverManager->ForceCovers();
                $result = mysql_query("UPDATE films SET BigPosters='" . addslashes(implode("\r\n", $res["bigcovers"]["covers"])) . "', Poster='" . addslashes(implode("\r\n", $res["defaultcovers"]["covers"])) . "', SmallPoster='" . addslashes(implode("\r\n", $res["smallcovers"]["covers"])) . "' WHERE ID=$id");
            }
        break;

        case "postersfordownload":
            $result = mysql_query("SELECT ID, Poster FROM films WHERE Poster<>''");
            $_RESULT["posters"] = array();
            while ($field = mysql_fetch_assoc($result)) {
                $posters = preg_split("/(\r\n|\r|\n)/", $field["Poster"]);
                foreach ($posters as $poster) {
                    if (!preg_match("/^posters\//i", $poster)) {
                        $_RESULT["posters"][] = $field["ID"];
                        break;
                    }
                }
            }
            $_RESULT["posters"] = array_values(array_unique($_RESULT["posters"]));
            break;
        case "postersforreduce":
            $result = mysql_query("SELECT ID,Poster,SmallPoster FROM films");
            $_RESULT["posters"] = array();
            while ($field = mysql_fetch_assoc($result)) {
                $posters = preg_split ("/(\r\n|\r|\n)/", $field["Poster"]);
                $smallposters = preg_split ("/(\r\n|\r|\n)/", $field["SmallPoster"]);
                for ($i = 0; $i < count($posters);$i++) {
                    if ($posters[$i] && !$smallposters[$i]) {
                        $_RESULT["posters"][] = $field["ID"];
                    }
                }
            }
            $_RESULT["posters"] = array_values(array_unique($_RESULT["posters"]));
            break;


        case "downloadperson":
            $_RESULT["ok"] = 1;
            $id = (int) $_REQUEST['id'];
            $result = mysql_query("SELECT OzonUrl,RusName,OriginalName FROM persones WHERE ID=$id");
            while ($result && ($field = mysql_fetch_assoc($result))){
                $res = parsePerson($field["OzonUrl"]);
                if (count($res)){
                    $update_names = '';
                    if (!$field["RusName"]) $update_names .= " , RusName='" . mysql_real_escape_string($res['RusName']) . "'";     
                    if (!$field["OriginalName"]) $update_names .= " , OriginalName='" . mysql_real_escape_string($res['OriginalName']) . "'";     
                
                    $about = addslashes(((strlen($res["Born"]))?$res["Born"]."\r\n":"")
                    .((strlen($res["Profile"]))?$res["Profile"]."\r\n":"")
                    .((strlen($res["About"]))?$res["About"]."\r\n":""));
                    $photos =  addslashes(implode("\r\n",$res["Photos"]));
                    $sql = "UPDATE persones SET Description = '$about', Images = '$photos', LastUpdate = NOW() $update_names WHERE ID=$id";
                    mysql_query($sql);
                }

            }
        break;

        case "personesfordownload":
            $result = mysql_query("SELECT ID FROM persones WHERE LastUpdate='0000-00-00 00:00:00' AND OzonUrl<>''");
            $_RESULT["persones"] = array();
            while ($result && ($field = mysql_fetch_assoc($result))){
                $_RESULT["persones"][] = $field["ID"];
            }
        break;

        case "filmsforframegenerate":
            $result = mysql_query("SELECT DISTINCT films.ID as ID FROM films LEFT JOIN files ON(films.ID = files.FilmID) WHERE files.Path NOT LIKE 'ftp://%' AND ISNULL(films.Frames) ORDER BY RAND()");
            $_RESULT["filmsforframegenerate"] = array();
            while ($result && ($field = mysql_fetch_assoc($result))){
                $_RESULT["filmsforframegenerate"][] = $field["ID"];
            }
        break;
        case "personesforresolve":
            $result = mysql_query("SELECT ID FROM persones WHERE LastUpdate='0000-00-00 00:00:00' AND (OzonUrl='' OR ISNULL(OzonUrl))");
            $_RESULT["persones"] = array();
            while ($result && ($field = mysql_fetch_assoc($result))){
                $_RESULT["persones"][] = $field["ID"];
            }
        break;

        case "deletenotlinkedpersones":
                $_RESULT["count"] = 0;
                $persones = array();
                $result = mysql_query("SELECT PersonID as ID FROM filmpersones");
                while ($result && $field = mysql_fetch_assoc($result)) $persones[$field["ID"]] = 1;

                $result = @mysql_query("SELECT PersonID as ID FROM music_albums");
                while ($result && $field = mysql_fetch_assoc($result)) $persones[$field["ID"]] = 1;

                $result = @mysql_query("SELECT MasterPersonID as ID FROM music_groups");
                while ($result && $field = mysql_fetch_assoc($result)) $persones[$field["ID"]] = 1;

                $result = @mysql_query("SELECT SlavePersonID as ID FROM music_groups");
                while ($result && $field = mysql_fetch_assoc($result)) $persones[$field["ID"]] = 1;

                $result = mysql_query("SELECT ID FROM persones");
                while ($result && $field = mysql_fetch_assoc($result)) {
                        $personid = $field["ID"];
                        if (!isset($persones[$personid])) {
                                mysql_query("DELETE FROM persones WHERE ID=$personid");
                                $_RESULT["count"]++;
                        }
                }
        break;


        case "downloadphotos":
            $_RESULT["ok"] = 1;
            $id = (int) $_REQUEST['id'];
            $result = mysql_query("SELECT Images, OriginalName, RusName FROM persones WHERE ID=$id");
            if ($result && ($field = mysql_fetch_assoc($result))) {
                $name = translit($field["OriginalName"]);
                if (!$name) $name = translit($field["RusName"]);
                $name = preg_replace('{[^a-z0-9_]}', "",
                    str_replace(" ", "_",
                        str_replace("%20", "_", strtolower($name))
                        )
                    );
                $photos = preg_split ("/(\r\n|\r|\n)/", $field["Images"]);
                require_once dirname(__FILE__) . "/classes/covers.php";
                $config["photos"]["undesirable_size"] =  0;
                $config["photos"]["defaultphotos"]["width"] = 60;
                $config["photos"]["defaultphotos"]["maxwidth"] = 60;
                $config["photos"]["defaultphotos"]["height"] = 72;
                $config["photos"]["defaultphotos"]["zoom"] = false;
                $config["photos"]["defaultphotos"]["center"] = array(0.5,0.3);
                $config["photos"]["defaultphotos"]["required"] = true;

                $CoverManager = new CoversManager(
                    $name,
                    array(
                        "defaultphotos"=>array(
                            "localpath"=>"photos/",
                            "covers"=>$photos,
                            "settings"=>$config["photos"]["defaultphotos"]
                        )
                    ),
                    "defaultphotos",
                    null,
                    0,
                    false
                );
                $res = $CoverManager->ForceCovers();
                $result = mysql_query("UPDATE persones SET Images='" . addslashes(implode("\r\n", $res["defaultphotos"]["covers"])) . "' WHERE ID=$id");
            }
        break;

        case "deletebadphotos":
            $_RESULT["ok"] = 1;
            $result = mysql_query("SELECT ID, Images FROM persones WHERE Images LIKE '%http://%'");
            while ($result && ($field = mysql_fetch_assoc($result))){
                $images = preg_split ("/(\r\n|\r|\n)/",$field["Images"]);
                $id = $field["ID"];
                $newimages = array();
                for($i=0;$i<count($images);$i++){
                    if (!preg_match("/http:/i",$images[$i])){
                        $newimages[] = $images[$i];
                    }
                }
                $photos = addslashes(implode("\r\n",$newimages));
                $sql = "UPDATE persones SET Images = '$photos' WHERE ID=$id";
                mysql_query($sql);
            }
        break;

        case "photosfordownload":
            $_RESULT["photos"] = array();
            $result = mysql_query("SELECT ID FROM persones WHERE Images LIKE '%http://%'");
            while ($field = mysql_fetch_assoc($result)){
                $_RESULT["photos"][] = $field["ID"];
            }
        break;


        case "cleaning":
            require_once "classes/storages.php";
            $storages = new Storages;
            $_RESULT["deleted"] = 0;
            $_RESULT["deleted_size"] = 0;
            $really = (isset($_REQUEST['really'])) ? addslashes($_REQUEST['really']) : false;


            $files = array_merge_recursive(
                $storages->directory_list("photos/"),
                $storages->directory_list("posters/"),
                $storages->directory_list("smallposters/"),
                $storages->directory_list("bigposters/")
            );

            $result = mysql_query("SELECT Images FROM persones");
            while ($result && ($field = mysql_fetch_assoc($result))){
                $images = preg_split ("/(\r\n|\r|\n)/",$field["Images"]);
                for($i=0;$i<count($images);$i++){
                    $files[$images[$i]."/"] = false;
                }
            }

            $result = mysql_query("SELECT Poster, SmallPoster, BigPosters FROM films");
            while ($result && ($field = mysql_fetch_assoc($result))){
                $images = preg_split ("/(\r\n|\r|\n)/",$field["Poster"]);
                for($i=0;$i<count($images);$i++) $files[$images[$i]."/"] = false;

                $images = preg_split ("/(\r\n|\r|\n)/",$field["SmallPoster"]);
                for($i=0;$i<count($images);$i++) $files[$images[$i]."/"] = false;

                $images = preg_split ("/(\r\n|\r|\n)/",$field["BigPosters"]);
                for($i=0;$i<count($images);$i++) $files[$images[$i]."/"] = false;
            }
            foreach($files as $k=>$v){
                if (is_array($v)) {
                    if ($really) {
                        unlink($v['path']);
                    }
                    $_RESULT["deleted"]++;
                    $_RESULT["deleted_size"] += $v['size'];
                }
            }
            $_RESULT["deleted_size"] = number_format($_RESULT["deleted_size"], 0, ',', ' ');

        break;
        case "getfilmext":
            $film = (isset($_REQUEST['film'])) ? (int) $_REQUEST['film'] : null;
            $sql = "SELECT films.ID as ID,
                films.Name as Name,
                films.OriginalName as OriginalName,
                films.Year as Year,
                films.RunTime as RunTime,
                films.Description as Description,
                films.MPAA as MPAA,
                films.Resolution as Resolution,
                films.VideoInfo as VideoInfo,
                films.AudioInfo as AudioInfo,
                films.Translation as Translation,
                films.Quality as Quality,
                films.CreateDate as CreateDate,
                films.UpdateDate as UpdateDate,
                films.ImdbRating as ImdbRating,
                films.imdbID as imdbID,
                films.SmallPoster as SmallPoster,
                films.Poster as Poster,
                films.BigPosters as BigPosters,
                films.Trailer as Trailer,
                films.SoundTrack as SoundTrack,
                films.Links as Links,
                films.Present as Present,
                films.Group as `Group`,
                films.TypeOfMovie as TypeOfMovie,
                films.Hide as Hide,
                films.AsDir as AsDir
                FROM films WHERE films.ID=$film";
            $result = mysql_query($sql);
            if ($result && $field = mysql_fetch_assoc($result)){
                $result2 = mysql_query("SELECT GenreID FROM filmgenres WHERE filmgenres.FilmID=$film");
                $genres = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                    $genres[] = $field2["GenreID"];
                }

                $result2 = mysql_query("SELECT * FROM genres");
                $allgenres = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                    if (in_array($field2["ID"],$genres)) $field2["checked"] = 1; else $field2["checked"] = 0;
                    $allgenres[] = $field2;
                }

                $result2 = mysql_query("SELECT CountryID FROM filmcountries WHERE filmcountries.FilmID=$film");
                $countries = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                    $countries[] = $field2["CountryID"];
                }

                $result2 = mysql_query("SELECT ID, imdbCountry, Name, count(filmcountries.CountryID) as Count FROM countries LEFT JOIN filmcountries ON (countries.ID = filmcountries.CountryID) GROUP BY countries.ID ORDER BY Name");
                $allcountries = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                    if (in_array($field2["ID"],$countries)) $field2["checked"] = 1; else $field2["checked"] = 0;
                    $allcountries[] = $field2;
                }

                $result2 = mysql_query("SELECT persones.ID as ID, persones.RusName as RusName, persones.OriginalName as OriginalName, RoleID, RoleExt FROM filmpersones LEFT JOIN roles ON (roles.ID = filmpersones.RoleID) LEFT JOIN persones ON (persones.ID = filmpersones.PersonID) WHERE filmpersones.FilmID=$film ORDER BY roles.SortOrder");
                $persones = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                    $persones[] = $field2;
                }

                $files = array();
                $result2 = mysql_query("SELECT * FROM files WHERE FilmID=$film ORDER BY Path");

                while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                    $files[] = $field2;
                }

                $result2 = mysql_query("SELECT ID, Role FROM roles ORDER BY SortOrder");
                $roles = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                    $roles[] = $field2;
                }

                $OriginalName = $field["OriginalName"];
                $str = "";
                for($i=0;$i<strlen($OriginalName);$i++){
                    $str .= "&#".ord($OriginalName{$i}).";";
                }
                $field["OriginalName"] = $str;
                $field["OriginalName1252"] = $OriginalName;
                $field["genres"] = $genres;
                $field["allgenres"] = $allgenres;
                $field["countries"] = $countries;
                $field["allcountries"] = $allcountries;
                $field["persones"] = $persones;
                $field["files"] = $files;
                $field["roles"] = $roles;
                $field["SmallPoster"] = preg_split("/(\r\n|\r|\n)/", $field["SmallPoster"]);
                $field["Poster"] = preg_split("/(\r\n|\r|\n)/", $field["Poster"]);
                $field["BigPosters"] = preg_split("/(\r\n|\r|\n)/", $field["BigPosters"]);
                $_RESULT = $field;
            }
        break;

        case "generate_metainfo":
            $film = (isset($_REQUEST['film'])) ? addslashes($_REQUEST['film']) : null;
            $sql = "SELECT films.Name as Name,
                films.OriginalName as OriginalName,
                films.Year as Year,
                films.Description as Description,
                films.MPAA as MPAA,
                films.ImdbRating/10 as ImdbRating,
                films.imdbID as ImdbUrlParse,
                films.Poster as Poster,
                films.BigPosters as BigPosters,
                films.TypeOfMovie as TypeOfMovie,
                films.AsDir as AsDir
                FROM films WHERE films.ID=$film";
            $result = mysql_query($sql);
            if ($result && $field = mysql_fetch_assoc($result)){
                $result2 = mysql_query("SELECT imdbGenre FROM filmgenres INNER JOIN genres ON(filmgenres.GenreID = genres.ID) WHERE filmgenres.FilmID=$film");
                $genres = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                    $genres[] = $field2["imdbGenre"];
                }

                $result2 = mysql_query("SELECT imdbCountry FROM filmcountries INNER JOIN countries ON(filmcountries.CountryID = countries.ID) WHERE filmcountries.FilmID=$film");
                $countries = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                    $countries[] = $field2["imdbCountry"];
                }

                $result2 = mysql_query("SELECT Name FROM filmcompanies INNER JOIN companies ON(filmcompanies.CompanyID = companies.ID) WHERE filmcompanies.FilmID=$film");
                $companies = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                    $companies[] = $field2["Name"];
                }

                $result2 = mysql_query("SELECT OzonUrl, persones.RusName as RusName, persones.OriginalName as OriginalName, Role, RoleExt FROM filmpersones LEFT JOIN roles ON (roles.ID = filmpersones.RoleID) LEFT JOIN persones ON (persones.ID = filmpersones.PersonID) WHERE filmpersones.FilmID=$film ORDER BY roles.SortOrder");
                $persones = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                    $persones[] = $field2;
                }

                $metaInfo["OriginalName"] = adapt1252To1251($field["OriginalName"]);
                $metaInfo["ImdbUrlParse"] = $field["ImdbUrlParse"] ? "http://www.imdb.com/" . $field["ImdbUrlParse"] : "";
                $metaInfo["Name"] = $field["Name"];
                $metaInfo["ImdbRating"] = $field["ImdbRating"];
                $metaInfo["Year"] = $field["Year"];
                $metaInfo["Description"] = $field["Description"];
                $metaInfo["MPAA"] = $field["MPAA"];
                $metaInfo["TypeOfMovie"] = $field["TypeOfMovie"];
                $metaInfo["genres"] = $genres;
                $metaInfo["countries"] = $countries;
                $metaInfo["companies"] = $companies;
                $metaInfo["persones"] = $persones;
                
                $posters = preg_split("/(\r\n|\r|\n)/", $field["Poster"]);
                $bigposters = preg_split("/(\r\n|\r|\n)/", $field["BigPosters"]);
                for($i=0; $i<count($posters);$i++){
                    if (isset($bigposters[$i]) && strlen(trim($bigposters[$i]))) $posters[$i] = $bigposters[$i];
                    $posters[$i] = trim($posters[$i]);
                    if (!preg_match('#http://#',$posters[$i])) $posters[$i] = $config['siteurl'] . '/' . $posters[$i];
                }
                
                $metaInfo["Poster"] = implode("\n",$posters);
                
                $result2 = mysql_query("SELECT Path FROM files WHERE FilmID=$film LIMIT 1");
                if ($result2 && $field2 = mysql_fetch_assoc($result2)){
                    if ($field['AsDir']) {                   
                        $fileInfoName = dirname($field2["Path"]) . ".info";
                    } else {
                        $fileInfoName = preg_replace($config['multipathpattern'], "", $field2["Path"]) . ".info";
                    }
                    require_once "classes/storages.php";
                    $storages = new Storages;
                    if (isset($config["dir_extensions"])) {
                        $storages->set_dir_extensions($config["dir_extensions"]);
                    }
                    $fileInfoName = $storages->decode_path($fileInfoName);
                    require_once(dirname(__FILE__)."/common/xml/xml.php");
                    $xml = new XML();
                    $str_xml = $xml->array_to_xml($metaInfo, "response", "  ");
                    if ($fp = fopen($fileInfoName,"wb")) {
                        fwrite($fp, $str_xml);
                        fclose($fp);
                    }
                    
                }

            }
        break;


        case "setfilmgenre":
            $filmid = (isset($_REQUEST['filmid'])) ? (int) $_REQUEST['filmid'] : null;
            $genre = (isset($_REQUEST['genre'])) ? (int) $_REQUEST['genre'] : null;
            $what = (isset($_REQUEST['what'])) ? (int) $_REQUEST['what'] : null;

            if ($filmid && $genre){
                if ($what==1){
                    $result = mysql_query("SELECT GenreID FROM filmgenres WHERE FilmID=$filmid AND GenreID=$genre");
                    if (mysql_num_rows($result)==0){
                        mysql_query("INSERT INTO filmgenres(GenreID,FilmID) VALUES($genre,$filmid)");
                    }
                } else mysql_query("DELETE FROM filmgenres WHERE FilmID=$filmid AND GenreID=$genre");
            }
        break;

        case "setfilmcountry":
            $filmid = (isset($_REQUEST['filmid'])) ? (int) $_REQUEST['filmid'] : null;
            $country = (isset($_REQUEST['country'])) ? (int) $_REQUEST['country'] : null;
            $what = (isset($_REQUEST['what'])) ? addslashes($_REQUEST['what']) : null;

            if ($filmid && $country){
                if ($what==1){
                    $result = mysql_query("SELECT CountryID FROM filmcountries WHERE FilmID=$filmid AND CountryID=$country");
                    if (mysql_num_rows($result)==0){
                        mysql_query("INSERT INTO filmcountries(CountryID,FilmID) VALUES($country,$filmid)");
                    }
                } else mysql_query("DELETE FROM filmcountries WHERE FilmID=$filmid AND CountryID=$country");
            }
        break;

        case "personeslist":
                $personfilter = (isset($_REQUEST['personfilter'])) ? addslashes($_REQUEST['personfilter']) : "";
                $onlynoozon = (isset($_REQUEST['onlynoozon'])) ? (int) $_REQUEST['onlynoozon'] : 0;
                $doubles = (isset($_REQUEST['doubles'])) ? (int) $_REQUEST['doubles'] : 0;

                $offset = (isset($_REQUEST['offset'])) ? (int) $_REQUEST['offset'] : 0;
                $count = (isset($_REQUEST['count'])) ? (int) $_REQUEST['count'] : 100;

                if ($doubles) {
                        $myselect = array();
                        $sql = "SELECT persones.ID as ID,
                                persones.RusName as RusName,
                                persones.OriginalName as OriginalName
                                FROM persones";
                        $result = mysql_query($sql);
                        while ($result && $field = mysql_fetch_assoc($result)) {
                                $myselect[] = $field;
                        }
                }
                $sql = "SELECT ID,RusName,OriginalName,OzonUrl,LastUpdate,Images FROM persones WHERE (RusName LIKE '%$personfilter%' OR OriginalName LIKE '%$personfilter%') " . ($onlynoozon ? " AND OzonUrl=''" : "") . " ORDER BY ID";
                $result = mysql_query($sql);
                $_RESULT["count"] = mysql_num_rows($result);
                $_RESULT["persones"] = array();
                $sql = $sql . " LIMIT $offset, $count";
                $result = mysql_query($sql);
                while ($result && $field = mysql_fetch_assoc($result)) {
                        $id = $field["ID"];
                        $Images = preg_split ("/(\r\n|\r|\n)/", $field["Images"]);
                        if (strlen($Images[0]) != 0) {
                                $field["Images"] = count($Images);
                        } else $field["Images"] = 0;
                        if ($doubles) {
                                $d = 0;
                                $originalname = addslashes($field["OriginalName"]);
                                $name = addslashes($field["RusName"]);
                                $wheres = array();
                                if ($originalname) $wheres[] = " RusName LIKE '%$originalname%' OR OriginalName LIKE '%$originalname%' ";
                                if ($name) $wheres[] = " RusName LIKE '%$name%' OR OriginalName LIKE '%$name%' ";
                                $wheres = implode(" OR ", $wheres);
                                $sql2 = "SELECT ID FROM persones WHERE ($wheres) AND ID<>$id";
                                $result2 = mysql_query($sql2);
                                $d = mysql_num_rows($result2);
                        }
                        if (!$doubles || ($d > 0)) $_RESULT["persones"][] = $field;
                }
        break;

        case "userslist":
            $userfilter = (isset($_REQUEST['userfilter'])) ? addslashes($_REQUEST['userfilter']) : "";
            $ipfilter = (isset($_REQUEST['ipfilter'])) ? addslashes($_REQUEST['ipfilter']) : "";
            $offset = (isset($_REQUEST['offset'])) ? (int) $_REQUEST['offset'] : 0;
            $count = (isset($_REQUEST['count'])) ? (int) $_REQUEST['count'] : 100;

            $filter = array();
            if ($userfilter) $filter[] = "Login LIKE '%$userfilter%'";
            if ($ipfilter) $filter[] = "IP LIKE '%$ipfilter%'";
            $where = count($filter) ? "WHERE " . implode(" AND ",$filter) : "";

            $sql = "SELECT * FROM users $where ";
            $result = mysql_query($sql);
            $_RESULT["count"] = mysql_num_rows($result);
            $_RESULT["users"] = array();
            $result = mysql_query($sql." LIMIT $offset, $count");
            while ($result && $field = mysql_fetch_assoc($result)){
                $_RESULT["users"][] = $field;
            }
        break;

        case "getpersondetail":
            $person = (isset($_REQUEST['person'])) ? (int) $_REQUEST['person'] : null;
            $sql = "SELECT ID, RusName, OriginalName, Description, Images, OzonUrl, LastUpdate FROM persones WHERE ID=$person";
            $result = mysql_query($sql);
            while ($result && $field = mysql_fetch_assoc($result)) {
                $_RESULT = $field;
                $result2 = mysql_query("SELECT music_albums.ID as ID, music_albums.Name as Name, music_albums.Year as Year FROM music_albums WHERE PersonID=$person");
                $_RESULT["albums"] = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)) {
                        $_RESULT["albums"][] = $field2;
                }

                $result2 = mysql_query("SELECT DISTINCT filmpersones.FilmID as FilmID, films.Name as Name, films.Year as Year, roles.Role as Role FROM filmpersones INNER JOIN roles ON (filmpersones.RoleID = roles.ID) INNER JOIN films ON (filmpersones.FilmID =films.ID) WHERE filmpersones.PersonID = $person ORDER BY films.Year, RoleID");
                $films = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)) {
                        $films[$field2["FilmID"]]["ID"] = $field2["FilmID"];
                        $films[$field2["FilmID"]]["Name"] = $field2["Name"];
                        $films[$field2["FilmID"]]["Year"] = $field2["Year"];
                        $films[$field2["FilmID"]]["Roles"][] = $field2["Role"];
                }
                $_RESULT["films"] = array_values($films);

                $result2 = mysql_query("SELECT persones.ID as ID, persones.RusName as RusName, persones.OriginalName as OriginalName, music_groups.Comment as Comment FROM music_groups LEFT JOIN persones ON(music_groups.SlavePersonID=persones.ID) WHERE MasterPersonID=$person");
                $_RESULT["slaves"] = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)) {
                        $_RESULT["slaves"][] = $field2;
                }

                $result2 = mysql_query("SELECT persones.ID as ID, persones.RusName as RusName, persones.OriginalName as OriginalName, music_groups.Comment as Comment FROM music_groups LEFT JOIN persones ON(music_groups.MasterPersonID=persones.ID) WHERE SlavePersonID=$person");
                $_RESULT["masters"] = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)) {
                        $_RESULT["masters"][] = $field2;
                }

                $s = array();
                if ($field["RusName"])$s[] = $field["RusName"];
                if ($field["OriginalName"]) $s[] = $field["OriginalName"];
                $res = SearchPerson($s, $person, 0.20);
                $_RESULT["persones_exact"] = $res["persones_exact"];
                $_RESULT["persones_part"] = $res["persones_part"];
                $_RESULT["persones_approx"] = $res["persones_approx"];
                $_RESULT["pcount"] = $res["pcount"];
            }
        break;


        case "getuserdetail":
            $userid = (isset($_REQUEST['user'])) ? (int) $_REQUEST['user'] : null;
            $sql = "SELECT * FROM users WHERE ID=$userid";
            $result = mysql_query($sql);
            if ($result && $field = mysql_fetch_assoc($result)){
                $_RESULT = $field;
            }
        break;


        case "changepassword":
            $userid = $user["ID"];
            $where = "";
            if (getRights("changepassword_ext", $user) && isset($_REQUEST['userid'])) {
                $userid = (int) $_REQUEST['userid'];
                $where = "OR 1=1";
            }
            $_RESULT["ok"] = 0;
            $oldpass = md5($_REQUEST['oldpass']);
            $newpass1 = $_REQUEST['newpass1'];
            $newpass2 = $_REQUEST['newpass2'];
            $sql = "SELECT * FROM users WHERE ID=$userid AND (Password='$oldpass' $where)";
            $result = mysql_query($sql);
            $_RESULT["errors"] = array();
            if ($result && $field = mysql_fetch_assoc($result)) {
                if (strlen($newpass1) < 3) {
                    $_RESULT["errors"][] = "Ошибка. Пароль содержит менее 3 символов.";
                }
                if (strlen($newpass1) > 16) {
                    $_RESULT["errors"][] = "Ошибка. Пароль содержит более 16 символов.";
                }
                if (!preg_match('{^[a-z0-9][a-z0-9]*[a-z0-9]$}', strtolower($newpass1))) {
                    $_RESULT["errors"][] = "Ошибка. Пароль должен состоять только из латинских букв или цифр.";
                }
                if ($newpass1 != $newpass2) {
                    $_RESULT["errors"][] = "Ошибка. Пароли не совпадают.";
                }
                if (!count($_RESULT["errors"])) {
                    $result = mysql_query("UPDATE users SET Password='" . md5($newpass1) . "' WHERE ID=$userid");
                    if ($result) {
                        if ($user["ID"] == $userid) $_SESSION['pass'] = $newpass1;
                        $_RESULT["ok"] = 1;
                    }
                }
            } else $_RESULT["errors"][] = "Старый пароль введен не верно";
        break;


        case "getpreferences":
            $sql = "SELECT Preferences FROM users WHERE ID={$user['ID']}";
            $result = mysql_query($sql);
            if ($result && $field = mysql_fetch_assoc($result)){
                $_RESULT["ok"] = 1;
                if (strlen($field["Preferences"])) {
                    $settings = preg_split ("/(\r\n|\r|\n)/",$field["Preferences"]);
                    foreach ($settings as $value){
                        $set = explode("=",$value);
                        $_RESULT["preferences"][$set[0]] = $set[1];
                    }
                }
            }
        break;

        case "setpreferences":
            $_RESULT["ok"] = 1;
            $sql = "SELECT Preferences FROM users WHERE ID={$user['ID']}";
            $preferences = array();
            $result = mysql_query($sql);
            if ($result && $field = mysql_fetch_assoc($result)){
                if (strlen($field["Preferences"])) {
                    $tmp = preg_split ("/(\r\n|\r|\n)/",$field["Preferences"]);
                    foreach ($tmp as $value){
                        $set = explode("=",$value);
                        $preferences[$set[0]] = $set[1];
                    }
                }
            }
            $param = (isset($_REQUEST['param'])) ? $_REQUEST['param'] : null;
            $value = (isset($_REQUEST['value'])) ? $_REQUEST['value'] : null;
            if ($param && $value){
                $preferences[$param] = $value;
                $set = array();
                foreach($preferences as $key=>$pvalue){
                    $set[] = "$key=$pvalue";
                }
                $tmp = mysql_real_escape_string(implode("\r\n",$set));
                $sql = "UPDATE users SET Preferences='$tmp' WHERE ID={$user['ID']}";
                mysql_query($sql);
            }
        break;

        case "getgenres":
            $film = (isset($_REQUEST['film'])) ? (int) $_REQUEST['film'] : null;
            $country = (isset($_REQUEST['country'])) ? (int) $_REQUEST['country'] : null;
            $typeofmovie = (isset($_REQUEST['typeofmovie'])) ? addslashes($_REQUEST['typeofmovie']) : null;
            $wheres = array();
            $join = "";
            if (!getRights("show_hidden",$user)) $wheres[] = " films.Hide=0 ";
            if ($itemFilter) $wheres[] = $itemFilter;
            if ($film) {
                $wheres[] = " films.ID=$film ";
            }
            if ($country) {
                $wheres[] = " filmcountries.CountryID=$country ";
                $join .= " LEFT JOIN filmcountries ON (films.ID = filmcountries.FilmID) ";
            }
            if ($typeofmovie) {
                $wheres[] = " films.TypeOfMovie='$typeofmovie' ";
            }
            $where = (count($wheres)) ? " WHERE ".implode(" AND ",$wheres) : "";
            $_RESULT["genres"] = array();
            $sql = "SELECT genres.ID as ID, genres.Name as Name, count(*) as Count FROM genres RIGHT JOIN filmgenres ON (genres.ID = filmgenres.GenreID)  LEFT JOIN films ON (films.ID = filmgenres.filmid) $join $where GROUP BY genres.ID ORDER BY Name";
            $result = mysql_query($sql);
            while ($result && $field = mysql_fetch_assoc($result)){
                $_RESULT["genres"][] = $field;
            }
            if (count($wheres)>0) {
                $_RESULT["allgenres"] = array();
                $sql = "SELECT genres.ID as ID, genres.Name as Name, count(*) as Count FROM genres RIGHT JOIN filmgenres ON (genres.ID = filmgenres.GenreID) LEFT JOIN films ON (films.ID = filmgenres.filmid) GROUP BY genres.ID ORDER BY Name";
                $result = mysql_query($sql);
                while ($result && $field = mysql_fetch_assoc($result)){
                    $_RESULT["allgenres"][] = $field;
                }
            }
        break;

        case "getcountries":
            $film = (isset($_REQUEST['film'])) ? (int) $_REQUEST['film'] : null;
            $genre = (isset($_REQUEST['genre'])) ? (int) $_REQUEST['genre'] : null;
            $typeofmovie = (isset($_REQUEST['typeofmovie'])) ? addslashes($_REQUEST['typeofmovie']) : null;
            $join = "";
            $wheres = array();

            if (!getRights("show_hidden",$user)) $wheres[] = " films.Hide=0 ";
            if ($itemFilter) $wheres[] = $itemFilter;
            
            if ($film) {
                $wheres[] = " films.ID=$film ";
            }
            if ($genre) {
                $wheres[] = " filmgenres.GenreID=$genre ";
                $join .= " LEFT JOIN filmgenres ON (films.ID = filmgenres.FilmID) ";
            }
            if ($typeofmovie) {
                $wheres[] = " films.TypeOfMovie='$typeofmovie' ";
            }
            $where = (count($wheres)) ? " WHERE ".implode(" AND ",$wheres) : "";
            $_RESULT["countries"] = array();
            $sql = "SELECT countries.ID as ID, countries.Name as Name, count(*) as Count FROM countries RIGHT JOIN filmcountries ON (countries.ID = filmcountries.CountryID)  LEFT JOIN films ON (films.ID = filmcountries.filmid) $join $where GROUP BY countries.ID ORDER BY Name";

            $result = mysql_query($sql);
            while ($result && $field = mysql_fetch_assoc($result)){
                $_RESULT["countries"][] = $field;
            }
            if (count($wheres)>0) {
                $_RESULT["allcountries"] = array();
                $sql = "SELECT countries.ID as ID, countries.Name as Name, count(*) as Count FROM countries RIGHT JOIN filmcountries ON (countries.ID = filmcountries.CountryID) LEFT JOIN films ON (films.ID = filmcountries.filmid) GROUP BY countries.ID ORDER BY Name";
                $result = mysql_query($sql);
                while ($result && $field = mysql_fetch_assoc($result)){
                    $_RESULT["allcountries"][] = $field;
                }
            }
        break;

        case "gettypesofmovie":
            $film = (isset($_REQUEST['film'])) ? (int) $_REQUEST['film'] : null;
            $genre = (isset($_REQUEST['genre'])) ? (int) $_REQUEST['genre'] : null;
            $country = (isset($_REQUEST['country'])) ? addslashes($_REQUEST['country']) : null;
            $join = "";
            $wheres = array();

            if (!getRights("show_hidden",$user)) $wheres[] = " films.Hide=0 ";
            if ($itemFilter) $wheres[] = $itemFilter;
            
            if ($genre) {
                $wheres[] = " filmgenres.GenreID=$genre ";
                $join .= " LEFT JOIN filmgenres ON (films.ID = filmgenres.FilmID) ";
            }
            if ($country) {
                $wheres[] = " filmcountries.CountryID=$country ";
                $join .= " LEFT JOIN filmcountries ON (films.ID = filmcountries.FilmID) ";
            }
            $where = (count($wheres)) ? " WHERE ".implode(" AND ",$wheres) : "";
            $_RESULT["types"] = array();
            $sql = "SELECT films.TypeOfMovie as Type, count(*) as Count FROM films $join $where GROUP BY films.TypeOfMovie ORDER BY films.TypeOfMovie";
            $result = mysql_query($sql);
            while ($result && $field = mysql_fetch_assoc($result)){
                $_RESULT["types"][] = $field;
            }
        break;

        case "filmlist":
            $film = (isset($_REQUEST['film'])) ? (int) $_REQUEST['film'] : null;
            $genre = (isset($_REQUEST['genre'])) ? (int) $_REQUEST['genre'] : null;
            $country = (isset($_REQUEST['country'])) ? (int) $_REQUEST['country'] : null;
            $typeofmovie = (isset($_REQUEST['typeofmovie'])) ? addslashes($_REQUEST['typeofmovie']) : 'all';
            $order = (isset($_REQUEST['order'])) ? (int) $_REQUEST['order'] : 0;
            $dir = (isset($_REQUEST['dir'])) ? addslashes($_REQUEST['dir']) : "";
            $offset = (isset($_REQUEST['offset'])) ? (int) $_REQUEST['offset'] : 0;
            $count = (isset($_REQUEST['count'])) ? (int) $_REQUEST['count'] : 10;
            $namefilter = (isset($_REQUEST['namefilter'])) ? addslashes($_REQUEST['namefilter']) : null;
            $noposters = (isset($_REQUEST['noposters'])) ? (int)($_REQUEST['noposters']) : null;
            $hide = (isset($_REQUEST['hide'])) ? (int)($_REQUEST['hide']) : 0;
            $quality = (isset($_REQUEST['quality'])) ? addslashes($_REQUEST['quality']) : 'all';
            $translation = (isset($_REQUEST['translation'])) ? addslashes($_REQUEST['translation']) : 'all';
            $join = "";
            $wheres = array();

            if (!getRights("show_hidden",$user)) $wheres[] = " films.Hide=0 ";
            if ($itemFilter) $wheres[] = $itemFilter;
            
            if ($film) {
                $wheres[] = " films.ID=$film ";
            }
            if ($hide) {
                $wheres[] = " films.Hide=1 ";
            }
            if ($quality!='all') {
                $wheres[] = " films.Quality LIKE '$quality' ";
            }
            if ($translation!='all') {
                $wheres[] = " films.Translation LIKE '$translation' ";
            }
            if ($genre) {
                $wheres[] = " filmgenres.GenreID=$genre ";
                $join .= " LEFT JOIN filmgenres ON (films.ID = filmgenres.FilmID) ";
            }
            if ($country) {
                $wheres[] = " filmcountries.CountryID=$country ";
                $join .= " LEFT JOIN filmcountries ON (films.ID = filmcountries.FilmID) ";
            }

            if ($typeofmovie!='all') {
                $wheres[] = " films.TypeOfMovie='$typeofmovie' ";
            }

            if ($namefilter){
                $wheres[] = " (films.Name LIKE '%$namefilter%' OR films.OriginalName LIKE '%$namefilter%') ";
            }

            if ($noposters){
                $wheres[] = " (Poster LIKE '%http://%' OR BigPosters LIKE '%http://%' OR SmallPoster='') ";
            }

            $orderby = " ORDER BY ";
            switch ($order) {
                case 100:
                    $orderby .= " ID ";
                break;
                case 0:
                    $orderby .= " CreateDate ";
                break;
                case 1:
                    $orderby .= " Year ";
                break;
                case 2:
                    $orderby .= " ImdbRating ";
                break;
                case 3:
                    $orderby .= " LocalRating ";
                break;
                case 4:
                    $orderby .= " PersonalRating ";
                break;
                case 6:
                    $orderby .= " Hit ";
                break;
                case 7:
                    $orderby .= " AutoRating ";
                break;
                case 8:
                    $orderby .= " RHit ";
                break;

                case 5:
                    $orderby .= " Name ";
                    $letter = (isset($_REQUEST['letter'])) ? addslashes($_REQUEST['letter']) : null;
                    if (isset($letter)) {
                        if ($letter=="0") $wheres[] = " ((LEFT(films.Name, 1) BETWEEN '0' AND '9') OR (LEFT(films.OriginalName, 1) BETWEEN '0' AND '9')) ";
                            else $wheres[] = " (films.Name LIKE '$letter%' OR films.OriginalName LIKE '$letter%') ";
                    }
                    $dir = "";
                    $letters = array();
                    for ($i=32;$i<256;$i++){
                        $letters["count"][$i] = 0;
                        $letters["char1"][$i] = chr($i);
                    }
                    $result = mysql_query( "SELECT UCASE(LEFT(films.Name,1)) as Letter1, UCASE(LEFT(films.OriginalName,1)) as Letter2 FROM films");
                    while ($result && ($field = mysql_fetch_assoc($result))){
                        $field["Letter1"] = ord ($field["Letter1"]);
                        $field["Letter2"] = ord ($field["Letter2"]);
                        if (($field["Letter1"]>=30) && ($field["Letter1"]<=39)) $field["Letter1"] = 30;
                        if (($field["Letter2"]>=30) && ($field["Letter2"]<=39)) $field["Letter2"] = 30;
                        $letters["count"][$field["Letter1"]]++;
                        if ($field["Letter1"]!=$field["Letter2"]) $letters["count"][$field["Letter2"]]++;
                    }
                    $_RESULT["letters"] = $letters;
                break;
                default:
                    $orderby .= " CreateDate DESC";
            }

            $orderby .= " $dir ";

            if ($order==3) $orderby .= " , CountLocalRating DESC ";
            
            if ($order==1) $orderby .= ", ID DESC";
                 else $orderby .= ", ID";

            $where = (count($wheres)) ? " WHERE ".implode(" AND ",$wheres) : "";

            $result = mysql_query( "SELECT ID FROM films WHERE Hide=0 ORDER BY Hit DESC");
            $countfilms = mysql_num_rows($result);
            $hits = array();
            $i = 0;
            while ($result && ($field = mysql_fetch_assoc($result)) && ($i<$countfilms/10)){
                $hits[] = $field["ID"];
                $i++;
            }

            $sql = "SELECT count(*) as c FROM films $join $where";
            $result = mysql_query($sql);
            //echo  "\n$sql - " . (time()+ microtime()-$time1);
            $field = mysql_fetch_assoc($result);
            $_RESULT["count"] = $field["c"];

            $sql = "SELECT films.ID as ID,
                films.Name as Name,
                films.OriginalName as OriginalName,
                films.Year as Year,
                films.CreateDate as CreateDate,
                films.UpdateDate as UpdateDate,
                films.ImdbRating as ImdbRating,
                films.Description as Description,
                films.Poster as Poster,
                films.SmallPoster as SmallPoster,
                films.TypeOfMovie as TypeOfMovie,
                films.Translation as Translation,
                films.Quality as Quality,
                films.Hide as Hide,
                films.Hit as Hit,
                films.Hit/(TO_DAYS(NOW()) - TO_DAYS(films.CreateDate) + 1) as RHit,
                films.ImdbRating as ImdbRating,
                ufr.Rating as PersonalRating,
                films.LocalRating as LocalRating,
                autouserfilmratings.Rating as AutoRating,
                films.CountLocalRating as CountLocalRating
                FROM films $join LEFT JOIN autouserfilmratings ON (films.ID = autouserfilmratings.FilmID AND autouserfilmratings.UserID={$user['ID']}) LEFT JOIN userfilmratings ufr ON (films.ID = ufr.FilmID AND ufr.UserID={$user['ID']}) $where $orderby LIMIT $offset, $count ";
            $_RESULT["films"] = array();
            $result = mysql_query($sql);
            //echo  "\n$sql - " . (time()+ microtime()-$time1);
            $films = array();
            while ($result && $field = mysql_fetch_assoc($result)){
                $films[$field["ID"]] = $field;
            }

            $in_stament = "IN (" . implode(",",array_keys($films)) . ")";
            $sql = "SELECT FilmID, Name FROM filmgenres LEFT JOIN genres ON (genres.ID = filmgenres.GenreID) WHERE filmgenres.FilmID $in_stament ";
            $result2 = mysql_query($sql);
            //echo  "\n$sql - " . (time()+ microtime()-$time1);
            $genres = array();
            while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                $genres[$field2["FilmID"]][] = $field2["Name"];
            }
            $sql = "SELECT FilmID, Name FROM filmcountries LEFT JOIN countries ON (countries.ID = filmcountries.CountryID) WHERE filmcountries.FilmID $in_stament ";
            $result2 = mysql_query($sql);
            //echo  "\n$sql - " . (time()+ microtime()-$time1);
            $countries = array();
            while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                $countries[$field2["FilmID"]][] = $field2["Name"];
            }

            $commentscount = array();
            $sql = "SELECT FilmID, count(*) as CommentsCount FROM comments WHERE FilmID $in_stament AND (ISNULL(ToUserID) OR ToUserID IN(0,{$user['ID']}) OR UserID={$user['ID']}) GROUP BY FilmID";
            $result2 = mysql_query($sql);
            //echo  "\n$sql - " . (time()+ microtime()-$time1);
            while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                $commentscount[$field2["FilmID"]] = $field2["CommentsCount"];
            }

            $directors = array();
            $actors = array();
            $sql = "SELECT filmpersones.FilmID, persones.RusName as RusName, persones.OriginalName as OriginalName, roles.Role as Role, roles.SortOrder as SortOrder FROM filmpersones LEFT JOIN roles ON (roles.ID = filmpersones.RoleID) LEFT JOIN persones ON (persones.ID = filmpersones.PersonID) WHERE filmpersones.FilmID $in_stament ORDER BY SortOrder, LENGTH(Images) DESC";
            $result2 = mysql_query($sql);
            //echo  "\n$sql - " . (time()+ microtime()-$time1);
            while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                if ($field2["Role"]=="режиссер") $directors[$field2["FilmID"]] = strlen(trim($field2["RusName"])) ? $field2["RusName"] : (($field2["OriginalName"])?$field2["OriginalName"]:"");
                if (!isset($actors[$field2["FilmID"]])) $actors[$field2["FilmID"]] = array();
                if (in_array($field2["Role"],array("актер","актриса")) && (count($actors[$field2["FilmID"]])<=4)) $actors[$field2["FilmID"]][] = ($field2["RusName"]) ? $field2["RusName"] : $field2["OriginalName"];
            }

            foreach($films as $field){
                $id = $field["ID"];
                $field["popular"] = (in_array($id,$hits)) ? " <sup style='background:#FFFF88'>хит!</sup>" : "";

                $OriginalName = $field["OriginalName"];
                $str = "";
                for($i=0;$i<strlen($OriginalName);$i++){
                    $str .= "&#".ord($OriginalName{$i}).";";
                }
                
                if (isset($config['short_translation'])) {
                    $field["ShortTranslation"] = strtr($field["Translation"], $config['short_translation']);
                } else {
                    $field["ShortTranslation"] = '';
                }

                if (!isset($config['short_description']) || ($config['short_description']==0)) {
                    $field["Description"] = '';
                } else{
                    $field["Description"] = strip_tags($field["Description"]);
                    if (strlen($field["Description"])>$config['short_description']) {
                        $field["Description"] = substr($field["Description"], 0, $config['short_description']) . "..." ;
                    }
                }
                $field["OriginalName"] = $str;

                $field["OriginalName"] = $str;
                $field["genres"] = isset($genres[$id]) ? implode(" / ", $genres[$id]) : "";
                $field["countries"] = isset($countries[$id]) ? implode(" / ", $countries[$id]) : "";
                $field["director"] = isset($directors[$id]) ? $directors[$id] : "";
                $field["actors"] = isset($actors[$id]) ? implode(", ", $actors[$id]) : "";
                $field["CommentsCount"] = isset($commentscount[$id]) ? $commentscount[$id] : 0;
                $field["ImdbRating"] = round($field["ImdbRating"]/10,1);
                $field["LocalRating"] = round($field["LocalRating"]/10,1);
                if (!$field["ImdbRating"]) $field["ImdbRating"] = "?";
                if (!$field["LocalRating"]){
                    $field["LocalRating"] = "?";
                }
                $field["CountLocalRating"] = ($field["CountLocalRating"]>0) ? (($field["CountLocalRating"]<$config['minratingcount']) ? "(<{$config['minratingcount']})" : "({$field['CountLocalRating']})") : "";
                if (!$field["PersonalRating"]) $field["PersonalRating"] = "?";
                $covers = preg_split ("/(\r\n|\r|\n)/", $field["Poster"]);
                $smallcovers = preg_split ("/(\r\n|\r|\n)/", $field["SmallPoster"]);
                $field["Poster"] = ($smallcovers[0]) ? $smallcovers[0] : (($covers[0]) ? $covers[0] : "");
                if (!$field["Poster"]){
                    $field["Poster"] = "templates/{$config['template']}/images/noposter_m.jpg";
                }
                $_RESULT["films"][] = $field;
            }
        break;

        case "simplesearch":
            $text = (isset($_REQUEST['text'])) ? strtolower($_REQUEST['text']) : null;
            $what = (isset($_REQUEST['what'])) ? strtolower($_REQUEST['what']) : "films";
            if (!getRights("show_hidden",$user)) $wherehide = " WHERE films.Hide=0 " . ($itemFilter? "AND $itemFilter" : ''); else $wherehide = $itemFilter? "WHERE $itemFilter" : '';
            if ($text){
                switch ($what){
                    case "films":
                        $ltext = strlen($text);
                        $sql = "SELECT films.ID as ID,
                            films.Name as Name,
                            films.OriginalName as OriginalName,
                            films.Year as Year
                            FROM films $wherehide";
                        $result = mysql_query($sql);
                        $ei = 0;
                        $pi = 0;
                        $ai = 0;
                        $fcount = 0;
                        $films_exact = array();
                        $films_part = array();
                        $films_approx = array();

                        $sortarray = array();
                        while ($result && $field = mysql_fetch_assoc($result)){
                            $name = strtolower($field["Name"]);
                            $originalName = strtolower($field["OriginalName"]);
                            $lev_n = levenshtein($name, $text,2,1,1);
                            $lev_on = levenshtein($originalName, $text,2,1,1);
                            $pos_n = strpos($name,$text);
                            $pos_on = strpos($originalName, $text);
                            $d_n = abs(strlen($name)-$ltext);
                            $d_on = abs(strlen($originalName)-$ltext);
                            $l_n = min($ltext,strlen($name));
                            $l_on = min($ltext,strlen($originalName));

                            if ($lev_n==0 || $lev_on==0){
                                $films_exact[$ei] = $field;
                                $ei++;
                                $fcount++;
                            }
                            else{
                                if (($ltext>1) && (is_integer($pos_n) || is_integer($pos_on))){
                                    $films_part[$pi] = $field;
                                    $pi++;
                                    $fcount++;
                                } elseif (($lev_n<(($l_n*limit_lev($ltext))+$d_n)) || ($lev_on<(($l_on*limit_lev($ltext))+$d_on)) ){
                                    $sortarray[$ai] = min($lev_on - $d_on,$lev_n - $d_n);
                                    $films_approx[$ai] = $field;
                                    $ai++;
                                    $fcount++;
                                }
                            }
                        }
                        array_multisort($sortarray,SORT_ASC,$films_approx);
                        $_RESULT["films_exact"] = $films_exact;
                        $_RESULT["films_part"] = $films_part;
                        $_RESULT["films_approx"] = $films_approx;
                        $_RESULT["fcount"] = $fcount;
                    break;
                    case "persones":
                        $ltext = strlen($text);
                        $sql = "SELECT persones.ID as ID,
                            persones.RusName as RusName,
                            persones.OriginalName as OriginalName
                            FROM persones";
                        $result = mysql_query($sql);
                        $ei = 0;
                        $pi = 0;
                        $ai = 0;
                        $pcount = 0;
                        $persones_exact = array();
                        $persones_part = array();
                        $persones_approx = array();

                        $sortarray = array();
                        while ($result && $field = mysql_fetch_assoc($result)){
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
                                $pcount++;
                            }
                            else{
                                if (($ltext>1) && (is_integer($pos_n) || is_integer($pos_on))){
                                    $persones_part[$pi] = $field;
                                    $pi++;
                                    $pcount++;
                                } elseif (($lev_n<(($l_n*limit_lev($ltext))+$d_n)) || ($lev_on<(($l_on*limit_lev($ltext))+$d_on)) ){
                                    $sortarray[$ai] = min($lev_on - $d_on,$lev_n - $d_n);
                                    $persones_approx[$ai] = $field;
                                    $ai++;
                                    $pcount++;
                                }
                            }
                        }
                        array_multisort($sortarray,SORT_ASC,$persones_approx);
                        $c = count($persones_approx);
                        for ($i = 20; $i<$c; $i++) {
                            array_pop($persones_approx);
                            $pcount--;
                        }
                        $_RESULT["persones_exact"] = $persones_exact;
                        $_RESULT["persones_part"] = $persones_part;
                        $_RESULT["persones_approx"] = $persones_approx;
                        $_RESULT["pcount"] = $pcount;
                    break;
                }
            }
        break;

        case "getfilm":
            mysql_query("UPDATE users SET ViewActivity=ViewActivity+1 WHERE ID={$user['ID']}");
            $film = (isset($_REQUEST['film'])) ? (int) $_REQUEST['film'] : null;
            if (!getRights("show_hidden",$user)) $wherehide = " AND films.Hide=0 "; else $wherehide = "";

            $result = mysql_query( "SELECT ID FROM films WHERE Hide=0 ORDER BY Hit DESC");
            $countfilms = mysql_num_rows($result);
            $hits = array();
            $i = 0;
            while ($result && ($field = mysql_fetch_assoc($result)) && ($i<$countfilms/10)){
                $hits[] = $field["ID"];
                $i++;
            }

            $sql = "SELECT films.ID as ID,
                films.Name as Name,
                films.OriginalName as OriginalName,
                films.Year as Year,
                films.RunTime as RunTime,
                films.Description as Description,
                films.MPAA as MPAA,
                films.Resolution as Resolution,
                films.VideoInfo as VideoInfo,
                films.AudioInfo as AudioInfo,
                films.Translation as Translation,
                films.Quality as Quality,
                films.CreateDate as CreateDate,
                films.UpdateDate as UpdateDate,
                films.ImdbRating as ImdbRating,
                films.imdbID as imdbID,
                films.Poster as Poster,
                films.BigPosters as BigPosters,
                films.Trailer as Trailer,
                films.TypeOfMovie as TypeOfMovie,
                films.Hide as Hide,
                films.Hit as Hit,
                films.SoundTrack as SoundTrack,
                films.Links as Links,
                films.Present as Present,
                films.Group as `Group`,
                films.ImdbRating as ImdbRating,
                films.Frames as Frames,
                films.SmallFrames as SmallFrames,
                ufr.Rating as PersonalRating,
                films.LocalRating  as LocalRating,
                films.LocalRatingDetail as LocalRatingDetail,
                films.CountLocalRating as CountLocalRating,
                users.Login as Moderator
                FROM films LEFT JOIN userfilmratings ufr ON (films.ID = ufr.FilmID AND ufr.UserID={$user['ID']}) LEFT JOIN users ON (films.Moderator=users.ID) WHERE films.ID=$film $wherehide";
            $result = mysql_query($sql);
            //echo mysql_error();
            if ($result && $field = mysql_fetch_assoc($result)){
                $result2 = mysql_query("SELECT Name FROM filmgenres LEFT JOIN genres ON (genres.ID = filmgenres.GenreID) WHERE filmgenres.FilmID=$film");
                $genres = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                    $genres[] = $field2["Name"];
                }

                $result2 = mysql_query("SELECT Name FROM filmcountries LEFT JOIN countries ON (countries.ID = filmcountries.CountryID) WHERE filmcountries.FilmID=$film");
                $countries = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                    $countries[] = $field2["Name"];
                }

                $files = array();
                $result2 = mysql_query("SELECT * FROM files WHERE FilmID=$film ORDER BY Path");
                $mirrors = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                    $tmp = $field2;
                    unset($tmp['Path'], $tmp['Marked'], $tmp['MD5'], $tmp['FilmID']);
                    $ftp = "";
                    if (isset($config['ftp']) && ($config['modes'][$user['Mode']]['ftp']==1)){
                        $ftp = str_replace($config['source'],$config['ftp'],$field2["Path"]);
                        if (isset($config['ftp_license']) && $config['ftp_license']){
                            $v = getLeechProtectionCode(array($film,$field2["ID"],$user['ID']));
                            $tmp["ftp_license"] = "pl.php?player=ftp&uid={$user['ID']}&filmid=$film&fileid=".$field2["ID"]."&v=$v";
                        }
                        else{
                            $tmp["ftp"] = $ftp;
                            if (isset($config['enc_ftpforclient'])) $tmp["ftp"] = my_convert_cyr_string($tmp["ftp"],"w",$config['enc_ftpforclient']);
                            $is_ie = preg_match("/(MSIE)/i",$_SERVER['HTTP_USER_AGENT']) && !preg_match("/(opera|gecko)/i",$_SERVER['HTTP_USER_AGENT']);
                            if (!(isset($config['do_not_escape_link_for_ie']) && $config['do_not_escape_link_for_ie'] && $is_ie)){
                                $t = explode("/",$tmp["ftp"]);
                                for ($i=3;$i<count($t);$i++) $t[$i] = rawurlencode ($t[$i]);
                                $tmp["ftp"] = implode("/",$t);
                            }
                        }
                    }

                    $url = $ftp ? $ftp : ("smb:".str_replace($config['source'],$config['smb'],$field2["Path"]));
                    $url = parse_url($url);
                    $tmp["mirror"] = isset($url["host"]) ? $url["host"] : "";
                    $mirrors[$tmp["mirror"]] = 1;
                    $files[] = $tmp;
                }

                $field["countmirrors"] = count($mirrors);
                if (count($files) && (($config['modes'][$user['Mode']]['ftp']==1)) && !@$config['ftpfolder_disable']){
                    $path_parts = pathinfo($files[0]["ftp"]);
                    $field["ftpfolderpath"] = $path_parts["dirname"]."/";
                }
                $field["smb"] = 0;
                if (isset($config['smb']) && ($config['modes'][$user['Mode']]['smb']==1)){
                    $field["smb"] = 1;
                }

                $result2 = mysql_query("SELECT persones.ID as ID, persones.RusName as RusName, persones.OriginalName as OriginalName, persones.Images as Images, roles.Role as Role, filmpersones.RoleExt as RoleExt FROM filmpersones LEFT JOIN roles ON (roles.ID = filmpersones.RoleID) LEFT JOIN persones ON (persones.ID = filmpersones.PersonID) WHERE filmpersones.FilmID=$film ORDER BY roles.SortOrder, LENGTH(Images) DESC");
                $director = "";
                $persones = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                    if ($field2["Role"]=="режиссер") $director = ($field2["RusName"]) ? $field2["RusName"] : $field2["OriginalName"];
                    $persones[$field2["ID"]]["ID"] = $field2["ID"];
                    $persones[$field2["ID"]]["RusName"] = $field2["RusName"];
                    $persones[$field2["ID"]]["OriginalName"] = isset($field2["OriginalName"]) ? $field2["OriginalName"] : "";
                    $images = preg_split ("/(\r\n|\r|\n)/",$field2["Images"]);
                    $persones[$field2["ID"]]["Image"] = strlen($images[0]) ? $images[0] : "templates/{$config['template']}/images/nophoto.gif";
                    $persones[$field2["ID"]]["Roles"][] = array("Role" => $field2["Role"], "RoleExt" => $field2["RoleExt"]);
                }

                $result2 = mysql_query("SELECT count(*) as count FROM comments WHERE FilmID=$film AND (ISNULL(ToUserID) OR ToUserID IN(0,{$user['ID']}) OR UserID={$user['ID']})");
                if ($result2 && $field2 = mysql_fetch_assoc($result2)){
                    $field["CommentsCount"] = $field2["count"];
                }
                if (isset($config['kg_trailers']['enable']) && $config['kg_trailers']['enable']) {
                    $kgTrailers = array();
                    $result2 = mysql_query("SELECT movie_name, trailer_name, quality, size, local_path  FROM `{$config['kg_trailers']['db']}`.kg_trailers WHERE localized=1 AND (movie_name='".mysql_real_escape_string($field["Group"])."' OR movie_name='".mysql_real_escape_string($field["Name"])."' OR movie_name='".mysql_real_escape_string($field["OriginalName"])."') ORDER BY trailer_id DESC");
                    while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                        $kgTrailers[] = $field2;
                    }
                    $field['kg_trailers'] = $kgTrailers;
                }
                
                $OriginalName = $field["OriginalName"];
                $str = "";
                for($i=0;$i<strlen($OriginalName);$i++){
                    $str .= "&#".ord($OriginalName{$i}).";";
                }

                if (!getRights("show_hidden",$user)) $wherehide = " AND films.Hide=0 "; else $wherehide = "";
                $otherfilms = array();
                if ($field["Group"]){
                    $result2 = mysql_query("SELECT ID, Name, Year FROM films WHERE `Group`='{$field['Group']}' AND ID<>$film $wherehide ORDER BY Year");
                    while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                        $otherfilms[] = $field2;
                    }
                }
                $field["frames"] = $field["Frames"] ? preg_split("/(\r\n|\r|\n)/",$field["Frames"]) : array();
                $field["smallframes"] = $field["SmallFrames"] ? preg_split("/(\r\n|\r|\n)/",$field["SmallFrames"]) : array();

                $field["popular"] = (in_array($film,$hits)) ? " <sup style='background:#FFFF88'>хит!</sup>" : "";
                $field["OriginalName"] = $str;
                $field["OriginalName1252"] = $OriginalName;
                $field["genres"] = implode(" / ", $genres);
                $field["countries"] = implode(" / ", $countries);
                $field["director"] = $director;
                $field["persones"] = array_values ($persones);
                $field["files"] = $files;
                $field["otherfilms"] = $otherfilms;

                $field["Poster"] = preg_split("/(\r\n|\r|\n)/", $field["Poster"]);
                if (!$field["Poster"][0]) $field["Poster"][0] = "templates/{$config['template']}/images/noposter.jpg";;
                $field["BigPosters"] = $field["BigPosters"] ? preg_split("/(\r\n|\r|\n)/", $field["BigPosters"]) : array();

                $field["ImdbRating"] = round($field["ImdbRating"]/10,1);
                $rt = $field["RunTime"];
                $h = floor($rt/3600);
                $m = floor(($rt - $h*3600)/60);
                $field["RunTime"] = ($h ? "$h ч." : "").($m ? " $m мин." : "");
                $field["CreateDate"] = date("d.m.Y",strtotime($field["CreateDate"]));
                $field["LocalRating"] = round($field["LocalRating"]/10,1);
                $field["LocalRatingDetail"] = unserialize($field["LocalRatingDetail"]);
                $field["ImdbRatingDetail"] = unserialize($field["ImdbRatingDetail"]);
                if (!$field["ImdbRating"]) $field["ImdbRating"] = "?";
                if (!$field["LocalRating"]) $field["LocalRating"] = "?";
                $field["CountLocalRating"] = ($field["CountLocalRating"]>0) ? (($field["CountLocalRating"]<$config['minratingcount']) ? "(<{$config['minratingcount']})" : "({$field['CountLocalRating']})") : "";
                if (!$field["PersonalRating"]) $field["PersonalRating"] = "?";
                if (isset($config['short_translation'])) {
                    $field["ShortTranslation"] = strtr($field["Translation"], $config['short_translation']);
                } else {
                    $field["ShortTranslation"] = '';
                }


                if ((getRights("updatefilmfield",$user))) {
                    $field["modermode"] = 1;
                    $field["delete"] = 1;
                }
                else{
                    $field["modermode"] = 0;
                    $field["delete"] = 0;
                }
                $_RESULT = $field;

            }
        break;

        case "showfilm":
            $film = (isset($_REQUEST['film'])) ? (int) $_REQUEST['film'] : null;
            $sql = "UPDATE films SET Hide=0, CreateDate=NOW(), UpdateDate=NOW() WHERE ID=$film";
            $result = mysql_query($sql);
            if ($result) $_RESULT = 1;
        break;

        case "hidefilm":
            $film = (isset($_REQUEST['film'])) ? (int) $_REQUEST['film'] : null;
            $sql = "UPDATE films SET Hide=1 WHERE ID=$film";
            $result = mysql_query($sql);
            if ($result) $_RESULT = 1;
        break;

        case "getperson":
            $person = (isset($_REQUEST['person'])) ? (int) $_REQUEST['person'] : null;
            $sql = "SELECT RusName, OriginalName, Description, Images
                FROM persones WHERE ID=$person";
            $result = mysql_query($sql);
            while ($result && $field = mysql_fetch_assoc($result)){

                if (!getRights("show_hidden",$user)) $wherehide = " AND films.Hide=0 ";

                $result2 = mysql_query("SELECT DISTINCT filmpersones.FilmID as FilmID, films.Name as Name, films.Year as Year, roles.Role as Role FROM filmpersones INNER JOIN roles ON (filmpersones.RoleID = roles.ID) INNER JOIN films ON (filmpersones.FilmID = films.ID) WHERE filmpersones.PersonID = $person $wherehide ORDER BY films.Year, RoleID");
                $films = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                    $films[$field2["FilmID"]]["ID"] = $field2["FilmID"];
                    $films[$field2["FilmID"]]["Name"] = $field2["Name"];
                    $films[$field2["FilmID"]]["Year"] = $field2["Year"];
                    $films[$field2["FilmID"]]["Roles"][] = $field2["Role"];
                }

                $field["Description"] = str_replace("\r\n","<br>",$field["Description"]);
                $field["Description"] = str_replace("\n","<br>",$field["Description"]);
                $field["Description"] = str_replace("\r","<br>",$field["Description"]);
                $field["Images"] = preg_split ("/(\r\n|\r|\n)/",$field["Images"]);
                if (strlen($field["Images"][0])==0) {
                    $field["Images"] = array();
                }
                $OriginalName = $field["OriginalName"];
                $str = "";
                for($i=0;$i<strlen($OriginalName);$i++){
                    $str .= "&#".ord($OriginalName{$i}).";";
                }
                $field["OriginalName"] = $str;
                $field["films"] = array_values($films);
                $_RESULT = $field;
            }
        break;

        case "setrating":
            $id = (isset($_REQUEST['id'])) ? (int) $_REQUEST['id'] : null;
            $rating = (isset($_REQUEST['rating'])) ? (int) $_REQUEST['rating'] : null;
            if ($id && ($rating>0) && ($rating<=10)){
                $sql = "SELECT ID FROM films WHERE ID=$id";
                $result = mysql_query($sql);
                if ($result && mysql_num_rows($result)){
                    $sql = "SELECT * FROM userfilmratings WHERE FilmID=$id AND UserID={$user['ID']}";
                    $result = mysql_query($sql);
                    if ($result && mysql_num_rows($result)){
                        $sql = "UPDATE userfilmratings SET Rating=$rating, Date=NOW() WHERE FilmID=$id AND UserID={$user['ID']}";
                        $result = mysql_query($sql);
                    }
                    else{
                        $sql = "INSERT INTO userfilmratings(UserID,FilmID,Rating, Date) VALUES({$user['ID']},$id,$rating,NOW())";
                        $result = mysql_query($sql);
                    }
                }
            } elseif ($id && $rating==0){
                $sql = "DELETE FROM userfilmratings WHERE UserID={$user['ID']} AND FilmID=$id";
                mysql_query($sql);
            }
            $sql = "SELECT Rating FROM userfilmratings  WHERE FilmID=$id";
            $result = mysql_query($sql);
            $ratings = array();
            while ($result && $field = mysql_fetch_row($result)){
                $ratings[] = $field[0];
            }
            $crating = get_bayes($ratings,$config['minratingcount']);
            mysql_query("UPDATE films SET LocalRating=" . round(10*$crating['bayes']) . ", CountLocalRating={$crating['count']}, LocalRatingDetail='".addslashes(serialize($crating['detail']))."' WHERE ID=$id");
            $res = array(
                'LocalRating' => $crating['bayes'] ? round($crating['bayes'],1) : '?',
                'CountLocalRating' => (($crating['count']>0) ? (($crating['count']<$config['minratingcount']) ? "(<{$config['minratingcount']})" : "({$crating['count']})") : ''),
                'PersonalRating' => ($rating>0) ? $rating : '?',
                'LocalRatingDetail' => $crating['detail']
            );
            $_RESULT = $res;
        break;

        case "calclocalrating":
        /*    $sql = "SELECT FilmID,
                (avg(userfilmratings.Rating)*count(userfilmratings.Rating)/(count(userfilmratings.Rating)+{$config['minratingcount']})+7.2453*{$config['minratingcount']}/({$config['minratingcount']}+count(userfilmratings.Rating)))*IF(count(userfilmratings.Rating)<{$config['minratingcount']},0,1)  as LocalRating,
                count(userfilmratings.Rating) as CountLocalRating
                FROM userfilmratings GROUP BY FilmID";
            echo $sql;
            $result = mysql_query($sql);
            $_RESULT["updated"] = 0;
            mysql_query("UPDATE films SET LocalRating=0, CountLocalRating=0");
            while ($result && $field = mysql_fetch_assoc($result)){
                $id = $field["FilmID"];
                $sql = "UPDATE films SET LocalRating=" . (int)(10*$field["LocalRating"]) . ", CountLocalRating={$field['CountLocalRating']} WHERE ID=$id";
                echo "\n$sql";
                $result2 = mysql_query($sql);
                $_RESULT["updated"] += mysql_affected_rows();
            }*/

            $sql = "SELECT FilmID, Rating FROM userfilmratings";
            $result = mysql_query($sql);
            $ratings = array();
            $_RESULT["updated"] = 0;
            while ($result && $field = mysql_fetch_row($result)){
                $ratings[$field[0]][] = $field[1];
            }
            foreach ($ratings as $id=>$fratings){
                $crating = get_bayes($fratings,$config['minratingcount']);
                mysql_query("UPDATE films SET LocalRating=" . round(10*$crating['bayes']) . ", CountLocalRating={$crating['count']}, LocalRatingDetail='".addslashes(serialize($crating['detail']))."' WHERE ID=$id");
                $_RESULT["updated"] += mysql_affected_rows();
            }
        break;

        case "setbookmark":
            $entity = (isset($_REQUEST['entity'])) ? addslashes($_REQUEST['entity']) : null;
            switch ($entity){
            case "film":
                $id = (isset($_REQUEST['id'])) ? (int) $_REQUEST['id'] : null;
                if ($id){
                $sql = "SELECT ID, Name FROM films WHERE ID=$id";
                    $result = mysql_query($sql);
                    if ($result && $field = mysql_fetch_assoc($result)){
                        $_RESULT = $field;
                        $sql = "INSERT INTO bookmarks(UserID,TypeOfEntity,EntityID) VALUES({$user['ID']},1,$id)";
                        $result = mysql_query($sql);
                        if ($result ){
                            $_RESULT["ok"] = 1;
                        }
                    }
                }
            break;
            }
        break;

        case "removebookmark":
            $entity = (isset($_REQUEST['entity'])) ? addslashes($_REQUEST['entity']) : null;
            switch ($entity){
            case "film":
                $id = (isset($_REQUEST['id'])) ? (int) $_REQUEST['id'] : null;
                if ($id){
                    $sql = "DELETE FROM bookmarks WHERE UserID = {$user['ID']} AND TypeOfEntity = 1 AND EntityID = $id";
                    $result = mysql_query($sql);
                    if ($result ){
                        $_RESULT["ok"] = 1;
                    }
                }
            break;
            }
        break;

        case "getbookmarks":
            $sql = "SELECT EntityID as ID, Name FROM bookmarks INNER JOIN films ON (films.ID = EntityID) WHERE TypeOfEntity=1 AND UserID={$user['ID']} ORDER BY bookmarks.ID DESC";
            $result = mysql_query($sql);
            while ($result && $field = mysql_fetch_assoc($result)){
                $_RESULT["films"][] = $field;
            }
        break;

        case "getcomments":
            $id = (isset($_REQUEST['id'])) ? (int) $_REQUEST['id'] : null;
            if ($id){
                $sql = "SELECT comments.ID, comments.UserID, users.Login as Login, Text, Date, u.Login as ToUserLogin, comments.ip FROM comments INNER JOIN users ON (users.ID = comments.UserID) LEFT JOIN users u ON (u.ID = comments.ToUserID) WHERE FilmID=$id AND (ISNULL(ToUserID) OR ToUserID IN(0,{$user['ID']}) OR UserID={$user['ID']}) ORDER BY Date";
                $result = mysql_query($sql);
                $_RESULT["count"] = mysql_num_rows($result);
                while ($result && $field = mysql_fetch_assoc($result)){
                    $field["Text"] = preg_replace("/(\r\n|\r|\n)/","<br>",$field["Text"]);
                    $_RESULT["comments"][] = $field;
                }
                $_RESULT["moder"] = 0;
                if ((getRights("editcomment",$user))) $_RESULT["moder"] = 1;
            }
        break;

        case "postcomment":
            $text = (isset($_REQUEST['text'])) ? addslashes(MyXMLEncode($_REQUEST['text'])) : null;
            $id = (isset($_REQUEST['id'])) ? (int) $_REQUEST['id'] : null;
            $ip = trim($_SERVER['REMOTE_ADDR']);
            if ($id && $text){
                $sql = "SELECT ID, Name, Moderator FROM films WHERE ID=$id";
                $result = mysql_query($sql);
                if ($result && $field = mysql_fetch_assoc($result)){
                    $ToUserID = (isset($_REQUEST['formoder']) && $_REQUEST['formoder']==1) ? $field["Moderator"] : 0;
                    $sql = "INSERT INTO comments(UserID,FilmID, Text, Date, ToUserID, ip) VALUES({$user['ID']},$id,'$text',NOW(),$ToUserID, '$ip')";
                    $result = mysql_query($sql);
                    if ($result ){
                        $_RESULT["ok"] = 1;
                    }
                }
            }
        break;

        case "editcomment":
            $text = (isset($_REQUEST['text'])) ? addslashes(MyXMLEncode($_REQUEST['text'])) : null;
            $delete = (isset($_REQUEST['delete'])) ? 1 : 0;
            $id = (isset($_REQUEST['id'])) ? (int) $_REQUEST['id'] : null;
            if ($id && $text){
                $sql = "UPDATE comments SET Text = '$text' WHERE ID=$id";
                $result = mysql_query($sql);
                if ($result ){
                    $_RESULT["ok"] = 1;
                }
            }
            if ($id && $delete){
                $sql = "DELETE FROM comments WHERE ID=$id";
                $result = mysql_query($sql);
                if ($result ){
                    $_RESULT["ok"] = 1;
                }
            }
        break;

        case "getrndtext":
            $sql = "SELECT Text FROM rnd_text ORDER BY RAND() LIMIT 1 ";
            $result = mysql_query($sql);
            if ($result && $field = mysql_fetch_assoc($result)){
                $_RESULT["rndtext"] = preg_replace("/(\r\n|\r|\n)/","<br>",$field["Text"]);
            }
        break;

        case "lastcomments":
            $where = "WHERE (ISNULL(ToUserID) OR ToUserID IN(0,{$user['ID']}) OR UserID={$user['ID']})";
            if (!getRights("show_hidden",$user)) $where .= " AND films.Hide=0 ";
            $where .= $itemFilter? "AND $itemFilter" : '';
            $sql = "SELECT FilmID, films.Name as Name, max(comments.ID) as CommentID FROM comments LEFT JOIN films ON (films.ID = comments.FilmID) $where GROUP BY comments.FilmID ORDER BY CommentID DESC LIMIT 0,20";
            $result = mysql_query($sql);
            $maxlength = 80;
            while ($result && $field = mysql_fetch_assoc($result)){
                $sql = "SELECT users.Login as Login, Date, Text FROM comments LEFT JOIN users ON (users.ID = comments.UserID) WHERE comments.ID=".$field["CommentID"];
                $result2 = mysql_query($sql);
                $field2 = mysql_fetch_assoc($result2);
                $field2["Text"] = str_replace("\r\n"," ",$field2["Text"]);
                $field2["Text"] = str_replace("\n"," ",$field2["Text"]);
                $field2["Text"] = str_replace("\r"," ",$field2["Text"]);
                if (strlen($field2["Text"])>$maxlength) $field["Text"] = substr($field2["Text"], 0, $maxlength)."..."; else $field["Text"] = $field2["Text"];
                $field["Login"] = $field2["Login"];
                $field["Date"] = $field2["Date"];
                $_RESULT["comments2"][] = $field;
            }
        break;

        case "lastratings":
            $where = "";
            if (!getRights("show_hidden",$user)) $where = " WHERE films.Hide=0 " . ($itemFilter? "AND $itemFilter" : ''); else $where = ($itemFilter? "WHERE $itemFilter" : '') ;
            $sql = "SELECT FilmID, films.Name as Name, userfilmratings.Rating as Rating FROM userfilmratings LEFT JOIN films ON (films.ID = userfilmratings.FilmID) $where ORDER BY Date DESC LIMIT 0,20";
            $result = mysql_query($sql);
            while ($result && $field = mysql_fetch_assoc($result)){
                $_RESULT["ratings"][] = $field;
            }
        break;

        case "recommended":
            $where = "";
            if (!getRights("show_hidden",$user)) $where = " WHERE films.Hide=0 " . ($itemFilter? "AND $itemFilter" : ''); else $where = ($itemFilter? "WHERE $itemFilter" : '') ;
            
            $userid = $user['ID'];
            //Вычисление относительных коллаборативных связей
            $result = mysql_query("SELECT FilmID, Rating FROM userfilmratings WHERE UserID=$userid");
            $myratings = array();
            while ($result && ($field = mysql_fetch_assoc($result))){
                $myratings[$field["FilmID"]] = $field["Rating"];
            }

            $correls = array();
            $frendratings = array();
            $frendstartrating = array();
            $result2 = mysql_query("SELECT UserID, count(*) as c FROM userfilmratings WHERE UserID<>$userid GROUP BY UserID HAVING c>4");
            while ($result2 && ($field2 = mysql_fetch_assoc($result2))){
                $userid2 = $field2["UserID"];

                $result = mysql_query("SELECT FilmID, Rating FROM userfilmratings WHERE UserID=$userid2");
                while ($result && ($field = mysql_fetch_assoc($result))){
                    $frendratings[$field["FilmID"]][$userid2] = $field["Rating"];
                }

                //расчет коэффициента корреляции Пирсона
                $errors[$userid2] = 0;
                $count = 0;
                $arr = array();
                foreach ($myratings as $key=>$value){
                    if (isset($frendratings[$key][$userid2])){
                        $arr[] = array("x"=>$value, "y"=>$frendratings[$key][$userid2]);
                        $count++;
                    }
                }
                if ($count>4) {
                    $correls[$userid2] = Pirson($arr);
                }
            }


            $films = array();
            $filmnames = array();
            $result = mysql_query("SELECT EntityID as FilmID FROM bookmarks WHERE UserID=$userid");
            while ($result && ($field = mysql_fetch_assoc($result))){
                $filmid = $field["FilmID"];
                $films[$filmid] = 0;
            }

            $result = mysql_query("SELECT films.ID as FilmID, films.Name as Name, UserID FROM films LEFT JOIN userfilmratings ON (films.ID = userfilmratings.FilmID) $where");
            while ($result && ($field = mysql_fetch_assoc($result))){
                $filmid = $field["FilmID"];
                $filmnames[$filmid] = $field["Name"];
                if (($field["UserID"]!=$userid) && !isset($films[$filmid])){
                    $films[$filmid] = 1;
                } elseif(($field["UserID"]==$userid)) {
                    $films[$filmid] = 0;
                }
            }
            $filmratings = array();
            $sortarray = array();
            foreach($films as $filmid=>$value){
                if ($value){
                    $collab = 0;
                    $chartrating = array();
                    for ($j=0; $j<=10;$j++) $chartrating[$j] = 0;
                    $approxrating = 0;
                    $weight = 0;
                    if (isset($frendratings[$filmid])){
                        foreach ($frendratings[$filmid] as $frend=>$frendrating){
                            if (isset($correls[$frend])){
                                $chartrating[$frendrating] += $correls[$frend];
                            }
                        }
                        foreach ($chartrating as $r=>$w){
                            if ($w>$weight){
                                $approxrating = $r;
                                $weight = $w;
                            }
                        }
                    }
                    if ($approxrating>0){
                        $filmratings[] = array($filmid,$approxrating);
                        $sortarray[] = $approxrating;
                    }
                }
            }
            array_multisort($filmratings,SORT_NUMERIC,$sortarray,SORT_NUMERIC, SORT_DESC);
            for($i=0; $i<count($filmratings); $i++){
                $_RESULT["films"][$i]["FilmID"] = $filmratings[$i][0];
                $_RESULT["films"][$i]["Name"] = $filmnames[$filmratings[$i][0]];
                if ($i>=3) break;
            }
        break;

        case "similar_films":
            $id = (isset($_REQUEST['id'])) ? (int)$_REQUEST['id'] : 0;
            $minpercent = (isset($_REQUEST['minpercent'])) ? number_format($_REQUEST['minpercent'], 3,'.','') : "0.20";
            $minrank = (isset($_REQUEST['minrank'])) ? number_format($_REQUEST['minrank'], 3,'.','') : "0.15";
            $where = "";
            if (!getRights("show_hidden",$user)) $where = " AND films.Hide=0 ";

            $result = mysql_query("SELECT UserID FROM hits WHERE filmid=$id");
            $users_count = mysql_num_rows($result);
            $users = array();

            while ($result && ($field = mysql_fetch_assoc($result))){
                $users[] = $field["UserID"];
            }
            $sql = "SELECT FilmID, Name, count(*)/$users_count as c, count(*)/films.Hit as rank FROM hits LEFT JOIN films ON (films.ID = hits.FilmID) WHERE UserID in (".implode(",",$users).") $where GROUP BY FilmID HAVING c>$minpercent AND rank>$minrank ORDER BY rank DESC LIMIT 5";
            echo $sql;
            $result = mysql_query($sql);
            $films = array();
            while ($result && ($field = mysql_fetch_assoc($result))){
                $films[] = $field;
            }
            $_RESULT["films"] = $films;
        break;

        case "similar_films_by_rating":
            $id = (isset($_REQUEST['id'])) ? (int) $_REQUEST['id'] : 0;
            $userid = $user['ID'];
            $where = "";
            if (!getRights("show_hidden",$user)) $where = " AND films.Hide=0 ";

            $result = mysql_query("SELECT UserID FROM userfilmratings WHERE filmid=$id AND Rating>=7");
            $users = array();
            $mincount = floor(mysql_num_rows($result)*(1/10));
            while ($result && ($field = mysql_fetch_assoc($result))){
                $users[] = $field["UserID"];
            }
            $sql = "SELECT FilmID, Name, count(*) as c, sum(Rating)/CountLocalRating as rank FROM userfilmratings LEFT JOIN films ON (films.ID = userfilmratings.FilmID) WHERE UserID in (".implode(",",$users).") GROUP BY FilmID HAVING c>$mincount AND rank>=7 $where ORDER BY rank DESC LIMIT 5";
            echo $sql;
            $result = mysql_query($sql);
            $films = array();
            while ($result && ($field = mysql_fetch_assoc($result))){
                $films[] = $field;
            }
            $_RESULT["films"] = $films;
        break;

        case "deletefilm":
            $Moderator = 0;
            $id = (int) $_REQUEST['id'];
            $result = mysql_query("SELECT Moderator FROM films WHERE ID=$id");
            if ($result && ($field = mysql_fetch_assoc($result))){
                    $Moderator = $field["Moderator"];
            }
            if (($user["ID"] == $Moderator) || getRights("deletefilm_ext",$user)) {
                $all = (isset($_REQUEST['all']) && $_REQUEST['all'] == 1 && getRights("deletefilm_erase",$user)) ? 1 : 0;
                $asDir = 0;
                if ($all){
                    require_once "classes/storages.php";
                    $storages = new Storages;
                    if (isset($config["dir_extensions"])) {
                        $storages->set_dir_extensions($config["dir_extensions"]);
                    }

                    $result = mysql_query("SELECT Moderator,AsDir FROM films WHERE ID=$id");
                    if ($result && ($field = mysql_fetch_assoc($result))){
                        $asDir = $field["AsDir"];
                    }
                    $result = mysql_query("SELECT Path FROM files WHERE FilmID=$id");
                    while ($result && ($field = mysql_fetch_assoc($result))){
                        $mypath_dec = $storages->decode_path($field["Path"]);
                        $path_parts = pathinfo($mypath_dec);
                        unlink($mypath_dec);
                    }
                    if ($asDir && $path_parts["dirname"]) {
                        @rmdir($path_parts["dirname"]);
                    }
                }
                UndoFilm($id);
                $_RESULT["ok"] = 1;
            }
        break;
        case "getrndfilm":
            $result = mysql_query( "SELECT ID FROM films WHERE Hide=0 ORDER BY Hit DESC");
            $countfilms = mysql_num_rows($result);
            $hits = array();
            $i = 0;
            while ($result && ($field = mysql_fetch_assoc($result)) && ($i<$countfilms/10)){
                $hits[] = $field["ID"];
                $i++;
            }
            $sql = "SELECT films.ID as ID,
                films.Name as Name,
                films.OriginalName as OriginalName,
                films.Year as Year,
                films.Poster as Poster,
                films.Hit as Hit
                FROM films WHERE films.Hide=0 " . ($itemFilter? "AND $itemFilter" : '')  . " ORDER BY RAND() LIMIT 1";
            $result = mysql_query($sql);
            if ($result && $field = mysql_fetch_assoc($result)){
                $film = $field["ID"];
                $result2 = mysql_query("SELECT Name FROM filmgenres LEFT JOIN genres ON (genres.ID = filmgenres.GenreID) WHERE filmgenres.FilmID=$film");
                $genres = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                    $genres[] = $field2["Name"];
                }

                $result2 = mysql_query("SELECT Name FROM filmcountries LEFT JOIN countries ON (countries.ID = filmcountries.CountryID) WHERE filmcountries.FilmID=$film");
                $countries = array();
                while ($result2 && $field2 = mysql_fetch_assoc($result2)){
                    $countries[] = $field2["Name"];
                }

                $OriginalName = $field["OriginalName"];
                $str = "";
                for($i=0;$i<strlen($OriginalName);$i++){
                    $str .= "&#".ord($OriginalName{$i}).";";
                }
                $field["popular"] = (in_array($film,$hits)) ? " <sup style='background:#FFFF88'>хит!</sup>" : "";
                $posters = preg_split("/(\r\n|\r|\n)/", $field["Poster"]);
                $field["Poster"] = $posters[0] ? $posters[0] : "templates/{$config['template']}/images/noposter.jpg";
                $field["OriginalName"] = $str;
                $field["OriginalName1252"] = $OriginalName;
                $field["genres"] = implode(" / ", $genres);
                $field["countries"] = implode(" / ", $countries);
                $_RESULT = $field;

            }
        break;

        case "gettoplist":
            $count = isset($_REQUEST['count']) ? (int) $_REQUEST['count'] : 10;
            $sql = "SELECT ID, Name, Hit, Hit/(TO_DAYS(NOW()) - TO_DAYS(CreateDate) + 1) as RHit FROM films WHERE films.Hide=0 " . ($itemFilter? "AND $itemFilter" : '')  . " ORDER BY Hit DESC LIMIT 0,$count";
            $result = mysql_query($sql);
            while ($result && $field = mysql_fetch_assoc($result)){
                $_RESULT["films"][] = $field;
            }
        break;

        case "getpoplist":
            $count = isset($_REQUEST['count']) ? (int)$_REQUEST['count'] : 10;
            $sql = "SELECT ID, Name, Hit/(TO_DAYS(NOW()) - TO_DAYS(CreateDate) + 1) as Hit FROM films WHERE films.Hide=0 " . ($itemFilter? "AND $itemFilter" : '')  . " ORDER BY Hit DESC LIMIT 0,$count";
            $result = mysql_query($sql);
            while ($result && $field = mysql_fetch_assoc($result)){
                $_RESULT["films"][] = $field;
            }
        break;

        case "inc_hit":
            if (isset($_REQUEST['filmid'])){
                inc_hit($user["ID"],(int)$_REQUEST['filmid']);
            }
        break;

        case "update_imdbrating":
            $film = isset($_REQUEST['film']) ? (int)$_REQUEST['film'] : false;
            $service_url = "http://service.lanmediaservice.com/actions.php";
            if (!$film) {
                $days = isset($_REQUEST['days']) ? (int)$_REQUEST['days'] : 30;
                $result = mysql_query("SELECT ID, CreateDate, imdbID FROM films WHERE UpdateDate<=(NOW()- INTERVAL $days DAY) AND (LENGTH(imdbID)>0) ORDER BY RAND()");
                $_RESULT["films_to_update"] = array();
                $films_to_update = array();
                $updated_films = array();
                $_RESULT["updated"] = 0;
                $query = array();
                while ($result && ($field = mysql_fetch_assoc($result))){
                    preg_match("/(\d+)/i",$field["imdbID"],$matches);
                    $imdbid = (int)($matches[1]);
                    $createdate = date('Ymd',strtotime ($field["CreateDate"]));
                    $filmid = $field["ID"];
                    $films_to_update[$filmid] = $filmid;
                    $query[] = "$imdbid|$filmid|$createdate";
                }
                if (count($query)) {
                    $response = httpClient($service_url."?action=getimdbratings&l=".md5($config['customer']['login']), 0, "list=" . rawurlencode(implode("\r\n",$query)), 15, null, '', false, false);
                    if (!preg_match("/^.*? 200 .*?\n/s", $response['header'])) {
                            $_RESULT["errors"][] = 'Server returned wrong status.';
                    } else {
                        $answer = preg_split("/(\r\n|\r|\n)/",$response['data']);
                        if ((int)trim($answer[0])){
                            for ($i=1;$i<count($answer);$i++){
                                list($film, $imdbrating) = explode("|",trim($answer[$i]));
                                $sql = "UPDATE films SET ImdbRating=$imdbrating, UpdateDate=NOW() WHERE ID=$film";
                                $result = mysql_query($sql);
                                $_RESULT["updated"]++;
                                $updated_films[$film] = 1;
                            }
                        } else{
                            for ($i=1;$i<count($answer);$i++) $_RESULT["errors"][] = trim($answer[$i]);
                        }
                    }
                }
                foreach($films_to_update as $filmid){
                    if (!isset($updated_films[$filmid])) {
                        $_RESULT["films_to_update"][] = $filmid;
                    }
                }
            }
            else{
                $sql = "SELECT ID, CreateDate, imdbID FROM films WHERE ID=$film";
                $result = mysql_query($sql);
                if ($result && $field = mysql_fetch_assoc($result)){
                    preg_match("/(\d+)/i",$field["imdbID"],$matches);
                    $imdbid = (int)($matches[1]);
                    $createdate = date('Ymd',strtotime ($field["CreateDate"]));
                    $filmid = $field["ID"];
                    $response = httpClient($service_url."?action=getimdbrating&l=".md5($config['customer']['login'])."&imdbid=$imdbid&filmid=$filmid&createdate=$createdate", 0, '', 15, null, '', false, false);
                    if (!preg_match("/^.*? 200 .*?\n/s", $response['header'])) {
                            $_RESULT["errors"][] = 'Server returned wrong status.';
                    } else {
                        $answer = preg_split("/(\r\n|\r|\n)/",$response['data']);
                        if ((int)trim($answer[0])){
                            $imdbrating = (int)trim($answer[1]);
                            $sql = "UPDATE films SET ImdbRating=$imdbrating, UpdateDate=NOW() WHERE ID=$film";
                            $result = mysql_query($sql);
                        } else{
                            for ($i=1;$i<count($answer);$i++) $_RESULT["errors"][] = trim($answer[$i]);
                        }
                    }
                }
            }
        break;
        case "personabsorption":
            $person1 = (isset($_REQUEST['person1'])) ? (int) $_REQUEST['person1'] : null;
            $person2 = (isset($_REQUEST['person2'])) ? (int) $_REQUEST['person2'] : null;
            if ($person1 && $person2) {
                $result1 = mysql_query("UPDATE filmpersones SET PersonID=$person1 WHERE PersonID=$person2");
                $result2 = @mysql_query("UPDATE music_albums SET PersonID=$person1 WHERE PersonID=$person2");
                $result3 = @mysql_query("UPDATE music_groups SET MasterPersonID=$person1 WHERE MasterPersonID=$person2");
                $result4 = @mysql_query("UPDATE music_groups SET SlavePersonID=$person1 WHERE SlavePersonID=$person2");
                if ($result1) {
                    mysql_query("DELETE FROM persones WHERE ID=$person2");
                }
            }
        break;
        case "test":
            $text = $_REQUEST['text'];
            $_RESULT["md5"] = $text;
        break;

        case "check_files":
            $stage = $_REQUEST['stage'];
            $relocation = $_REQUEST['relocation'];
            $hide = $_REQUEST['hide'];
            switch($stage) {
                case "reset":
                    $directories = array_unique(array_merge(
                        $config['rootdir'], 
                        isset($config['storages'])? $config['storages']: array(), 
                        isset($config['source'])? $config['source']: array()
                    ));
                    $_SESSION['check_files'] = array(
                        'directories' => $directories,
                        'files' => array(),
                        'files_index' => array(),
                        'name_index' => array(),
                        'size_index' => array()
                    );
                    $_RESULT["status_text"] = 'Подготовка';
                    $_RESULT["nextstage"] = 'indexing';
                    break;
                case "indexing":
                    $t1 = time()+microtime();
                    $checkFiles = $_SESSION['check_files'];
                    require_once dirname(__FILE__) . "/classes/storages.php";
                    $storages = new Storages;
                    if (isset($config["dir_extensions"])) {
                        $storages->set_dir_extensions($config["dir_extensions"]);
                    }
                    while ($directory = array_shift($checkFiles['directories'])) {
                        $newFiles = $storages->directory_list($directory);
                        foreach ($newFiles as $file) {
                            if ($file['isdir']) {
                                if (preg_match('{^ftp://}',$file['path']) || is_readable($file['path'])) {
                                    $checkFiles['directories'][] = $file['path'];
                                }
                            } else {
                                $key = md5($file['name'] . ':' . $file['size']);
                                $checkFiles['files'][$key] = $file;
                                $checkFiles['files_index'][$file['path']] = true;
                                $checkFiles['name_index'][$file['name']][] = $key;
                                $checkFiles['size_index'][$file['size']][] = $key;
                            }
                        }
                            //sleep(1);
                        if ((time()+ microtime() - $t1)>5) {
                            break;
                        }
                    }
                    $_SESSION['check_files'] = $checkFiles;
                    if (count($checkFiles['directories'])) {
                        $_RESULT["status_text"] = "Сканирование директорий ... ($directory) осталось " . count($checkFiles['directories']);
                        $_RESULT["nextstage"] = 'indexing';
                    } else {
                        $_RESULT["status_text"] = "Проверка файлов ... ";
                        $_RESULT["nextstage"] = 'check';
                    }
                    break;
                case "check":
                    $checkFiles = $_SESSION['check_files'];
                    $result = mysql_query("SELECT ID, FilmID, Path, Size FROM files");
                    $dbFiles = array();
                    while ($result && ($field = mysql_fetch_assoc($result))) {
                        $dbFiles[] = $field;
                    }
                    $brokenCounter = 0;
                    $relocationCounter = 0;
                    $_RESULT["relocated"] = array();
                    $_RESULT["notfound"] = array();
                    foreach ($dbFiles as $dbFile) {
                        if (!isset($checkFiles['files_index'][$dbFile['Path']])) {
                            $brokenCounter++;
                            $dbFileName = basename($dbFile['Path']);
                            $key = md5($dbFileName . ':' . $dbFile['Size']);
                            if (isset($checkFiles['files'][$key])) {
                                $relocationCounter++;
                                $toPath = $checkFiles['files'][$key]['path'];
                                $_RESULT["relocated"][] = array(
                                    'from' => $dbFile['Path'],
                                    'to' => $toPath,
                                );
                                if ($relocation) {
                                    mysql_query("UPDATE files SET Path = '" . mysql_real_escape_string($toPath) . "' WHERE ID=" . $dbFile['ID']);
                                }
                            } else {
                                $variants = array_unique(array_merge(
                                    isset($checkFiles['name_index'][$dbFileName])? $checkFiles['name_index'][$dbFileName] : array(),
                                    isset($checkFiles['size_index'][$dbFile['Size']])? $checkFiles['size_index'][$dbFile['Size']] : array()
                                ));
                                for ($i=0; $i<count($variants); $i++) {
                                    $variants[$i] = $checkFiles['files'][$variants[$i]];
                                }
                                $_RESULT["notfound"][] = array(
                                    'path' => $dbFile['Path'],
                                    'size' => $dbFile['Size'],
                                    'filmid' => $dbFile['FilmID'],
                                    'variants' => $variants
                                );
                                if ($hide) {
                                    mysql_query("UPDATE films SET Hide=1 WHERE ID=" . $dbFile['FilmID']);
                                }
                            }
                        }
                    }

                    $_RESULT["status_text"] = "Проверка файлов завершена.<br>" 
                                            . "Потерянных файлов: $brokenCounter<br>"
                                            . "&nbsp;&nbsp;&nbsp;&nbsp;найдены: $relocationCounter<br>"
                                            . "&nbsp;&nbsp;&nbsp;&nbsp;не найдены: " . ($brokenCounter - $relocationCounter);
                    break;
            }
        break;
    }
}
else{
    $_RESULT["errors"][] = "Извините, $login, у Вас недостаточно прав для совершения действия '$action' ";
    echo "Извините, $login, у Вас недостаточно прав для совершения действия '$action' ";
}
echo mysql_error();
$_RESULT["gen_time"] = str_replace(",",".",time()+ microtime()-$time1);

//echo "\ngt: " . $_RESULT["gen_time"];
?>
