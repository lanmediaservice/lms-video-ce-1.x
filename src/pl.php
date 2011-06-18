<?php
//2
//session_start();
require_once "config.php";
require_once "functions.php";

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

$filmid = (int) $_REQUEST['filmid'];
$fileid = isset($_REQUEST['fileid']) ? $wherefile = " AND files.ID = '".mysql_real_escape_string($_REQUEST['fileid'])."' " : $wherefile = "";
$userid = (isset($_REQUEST['uid'])) ? (int) $_REQUEST['uid'] : 0;
$v = isset($_REQUEST['v']) ? addslashes($_REQUEST['v']) : ""; 

if ($userid>0){
    inc_hit($userid, $filmid);
}


switch (strtolower($_REQUEST["player"])) { 
    case "ftp":
        if ($filmid && getLeechProtectionCode(array($filmid,$_REQUEST['fileid'],$userid))==$v){
            $maxdownload = (isset($config['max_ftp_download'])) ? $config['max_ftp_download'] : 0;
            $today_hits = array();
            $message = "";
            if ($maxdownload){
                $sql = "SELECT * FROM hits WHERE DateHit > NOW() - INTERVAL 1 DAY AND UserID=$userid";
                $result = mysql_query($sql);
                while ($result && $field = mysql_fetch_assoc($result)){
                    $today_hits[] = $field["FilmID"];
                }
                if ((count($today_hits)>$maxdownload) && (!in_array($filmid,$today_hits))) {
                    echo "Извините, Ваш лимит на сутки в $maxdownload закачек исчерпан.";
                    break;
                }
                else{
                    $message = "<br>Закачек за сутки ".count($today_hits)."/$maxdownload";
                }

            }
            
            $sql = "SELECT files.Path as Path, films.Name as Name FROM films INNER JOIN files ON (films.ID = files.FilmID) WHERE films.ID = '$filmid' $wherefile ORDER BY files.Name";
            $result = mysql_query($sql);
            $dnld = "";
            while ($result && $field = mysql_fetch_assoc($result)){
                $path = (str_replace($config['source'],$config['ftp'],$field["Path"]));

                $is_ie = preg_match("/(MSIE)/i",$_SERVER['HTTP_USER_AGENT']) && !preg_match("/(opera|gecko)/i",$_SERVER['HTTP_USER_AGENT']);
                if (isset($config['enc_ftpforclient'])) $path = my_convert_cyr_string($path, "w", $config['enc_ftpforclient']);
                if (!(isset($config['do_not_escape_link_for_ie']) && $config['do_not_escape_link_for_ie'] && $is_ie)){
                    $t = explode("/",$path);
                    for ($i=3;$i<count($t);$i++) $t[$i] = rawurlencode ($t[$i]);
                    $path = implode("/",$t);
                }
                $dnld = "<a href=\"".$path."\">".$field["Name"]."</a><br>";
            }
            $outstr = implode ("", file ("templates/{$config['template']}/download.htm"));
            echo str_replace("%DOWNLOADLINKS%", $dnld.$message, $outstr);
        }
    break;    
    case "la":
        header("Content-type: video/lap"); 
        header('Content-Disposition: attachment; filename="playlist.lap"'); 
        if ($filmid){
            $sql = "SELECT files.Path as Path, files.Name as Name FROM films INNER JOIN files ON (films.ID = files.FilmID) WHERE films.ID = '$filmid' $wherefile ORDER BY files.Name";
            $result = mysql_query($sql);
            while ($result && $field = mysql_fetch_assoc($result)){
                $path = str_replace($config['source'],$config['smb'],$field["Path"]);
                if ($path{0}=='/') $path = str_replace("/","\\",$path);
                $name = $field["Name"];
                echo $path."\r\n>N ".$name."\r\n\r\n";
            }
        }
    break;    
    case "mp":
        header("Content-type: video/asx"); 
        header('Content-Disposition: attachment; filename="playlist.asx"'); 
        echo "<Asx Version = \"3.0\" >\r\n<Param Name = \"Name\" />\r\n\r\n";
        if ($filmid){
            $sql = "SELECT files.Path as Path, files.Name as Name FROM films INNER JOIN files ON (films.ID = files.FilmID) WHERE films.ID = '$filmid' $wherefile ORDER BY files.Name";
            $result = mysql_query($sql);
            while ($result && $field = mysql_fetch_assoc($result)){
                $path = str_replace($config['source'],$config['smb'],$field["Path"]);
                if ($path{0}=='/') $path = str_replace("/","\\",$path);
                $name = $field["Name"];
                echo "<Entry>\r\n<Title>$name</Title>\r\n<Ref href = \"$path\"/>\r\n</Entry>\r\n";
            }
        }
        echo "</Asx>";
    break;    
    case "mpcpl":
        header("Content-type: video/mpcpl"); 
        header('Content-Disposition: attachment; filename="playlist.mpcpl"'); 
        echo "MPCPLAYLIST\r\n";
        if ($filmid){
            $sql = "SELECT files.Path as Path, files.Name as Name FROM films INNER JOIN files ON (films.ID = files.FilmID) WHERE films.ID = '$filmid' $wherefile ORDER BY files.Name";
            $result = mysql_query($sql);
            $i = 1;
            while ($result && $field = mysql_fetch_assoc($result)){
                $path = str_replace($config['source'],$config['smb'],$field["Path"]);
                if ($path{0}=='/') $path = str_replace("/","\\",$path);
                $name = $field["Name"];
                if (isset($config['mpcpl_convert_name_to_utf8']) && $config['mpcpl_convert_name_to_utf8']) $path=iconv('CP1251','UTF-8', $path);
                echo "$i,type,0\r\n$i,filename,$path\r\n";
                $i++;
            }
        }
    break;    
    case "crp":
        header("Content-type: video/mls"); 
        header('Content-Disposition: attachment; filename="playlist.mls"'); 
        if ($filmid){
            $sql = "SELECT files.Path as Path, files.Name as Name FROM films INNER JOIN files ON (films.ID = files.FilmID) WHERE films.ID = '$filmid' $wherefile ORDER BY files.Name";
            $result = mysql_query($sql);
            while ($result && $field = mysql_fetch_assoc($result)){
                $path = str_replace($config['source'],$config['smb'],$field["Path"]);
                if ($path{0}=='/') $path = str_replace("/","\\",$path);
                $name = $field["Name"];
                echo "\"".$path."\"\r\n";
            }
        }
    break;    
    case "bsl":
        header("Content-type: video/bsl"); 
        header('Content-Disposition: attachment; filename="playlist.bsl"'); 
        if ($filmid){
            $sql = "SELECT files.Path as Path, files.Name as Name FROM films INNER JOIN files ON (films.ID = files.FilmID) WHERE films.ID = '$filmid' $wherefile ORDER BY files.Name";
            $result = mysql_query($sql);
            while ($result && $field = mysql_fetch_assoc($result)){
                $path = str_replace($config['source'],$config['smb'],$field["Path"]);
                if ($path{0}=='/') $path = str_replace("/","\\",$path);
                $name = $field["Name"];
                echo "".$path."\r\n";
            }
        }
    break;    
    case "tox":
        header("Content-type: video/tox"); 
        header('Content-Disposition: attachment; filename="playlist.tox"'); 
        if ($filmid){
            $sql = "SELECT files.Path as Path, files.Name as Name FROM films INNER JOIN files ON (films.ID = files.FilmID) WHERE films.ID = '$filmid' $wherefile ORDER BY files.Name";
            $result = mysql_query($sql);
            echo "# toxine playlist\n";
            while ($result && $field = mysql_fetch_assoc($result)){
                $path = str_replace($config['source'],$config['smb'],$field["Path"]);
                if ($path{0}=='/') $path = "smb:".$path;
                $name = $field["Name"];
                echo "\nentry {\n";
                echo "\tidentifier = ".$path.";\n";
                echo "\tmrl = ".$path.";\n";
                echo "};\n";
            }
            echo "# END\n";
        }
    break;    
    case "kaf":
        header("Content-type: video/kaffeine"); 
        header('Content-Disposition: attachment; filename="playlist.kaffeine"'); 
        if ($filmid){
            $sql = "SELECT files.Path as Path, files.Name as Name FROM films INNER JOIN files ON (films.ID = files.FilmID) WHERE films.ID = '$filmid' $wherefile ORDER BY files.Name";
            $result = mysql_query($sql);
            echo "<!DOCTYPE XMLPlaylist>\n";
            echo "<playlist client=\"kaffeine\" >\n";
            while ($result && $field = mysql_fetch_assoc($result)){
                $path = str_replace($config['source'],$config['smb'],$field["Path"]);
                if ($path{0}=='/') $path = "smb:".$path;
                $name = $field["Name"];
                echo " <entry title=\"".$path."\" url=\"".$path."\" />\n";
            }
            echo "</playlist>";
        }
    break;    
    case "pls":
        header("Content-type: video/pls"); 
        header('Content-Disposition: attachment; filename="playlist.pls"'); 
        if ($filmid){
            echo "[playlist]";
            $sql = "SELECT files.Path as Path, files.Name as Name FROM films INNER JOIN files ON (films.ID = files.FilmID) WHERE films.ID = '$filmid' $wherefile ORDER BY files.Name";
            $result = mysql_query($sql);
            if ($result){
                echo "\nnumberofentries=" . mysql_num_rows($result);
            }
            $i = 1;
            while ($result && $field = mysql_fetch_assoc($result)){
                $path = str_replace($config['source'],$config['smb'],$field["Path"]);
                if ($path{0}=='/') $path = "smb:".$path;
                $name = $field["Name"];
                echo "\nFile".$i++."=".$path;
            }
        }
    break;
    case "xspf":
        header("Content-type: video/xspf"); 
        header('Content-Disposition: attachment; filename="playlist.xspf"'); 
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<playlist version=\"1\" xmlns=\"http://xspf.org/ns/0/\" xmlns:vlc=\"http://www.videolan.org/vlc/playlist/ns/0/\">\n<trackList>";
        if ($filmid){
            $sql = "SELECT files.Path as Path, files.Name as Name FROM films INNER JOIN files ON (films.ID = files.FilmID) WHERE films.ID = '$filmid' $wherefile ORDER BY files.Name";
            $result = mysql_query($sql);
            while ($result && $field = mysql_fetch_assoc($result)){
                $path = str_replace($config['source'],$config['smb'],$field["Path"]);
                if ($path{0}=='/') $path = str_replace("/","\\",$path);
                $path = htmlspecialchars(my_convert_cyr_string($path, 'w', 'UTF-8'));
                $name = htmlspecialchars(my_convert_cyr_string($field["Name"], 'w', 'UTF-8'));
                echo "\n<track>\n    <title>$name</title>\n    <location>$path</location>\n</track>\n";
            }
        }
        echo "\n</trackList>\n</playlist>";
    break;    
}
?>