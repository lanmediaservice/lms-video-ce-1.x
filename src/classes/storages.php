<?php

/**
 * (C) 2006-2009 Ilya Spesivtsev, macondos@gmail.com
 *
 *  ласс дл€ работы с хранилищами
 *
 * @author Ilya Spesivtsev
 * @version 1.07
 */

$PATH = dirname(__FILE__);
require_once "$PATH/../functions.php";

class Storages {
    // private
    var $ftp_connect_id = Array();
    var $dir_extensions = Array();
    var $getID3 = null;

    function set_dir_extensions($value)
    {
        $this->dir_extensions = $value;
    }

    function Storages(){
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            define('OS_ISWINDOWS', true);
        } else {
            define('OS_ISWINDOWS', false);
        }
    }
    
    function true_filesize($file, $f = false){
        global $config;
        static $cache;
        static $fsobj = false;
        if (isset($cache[$file])) return $cache[$file];
        if (is_dir($file)) return 0;
        if ((PHP_INT_SIZE > 4) || @$config['disable_4gb_support']) {
            $size = filesize($file);
        } else {
            if (OS_ISWINDOWS){
                if (class_exists("COM")){
                    if ($fsobj===FALSE) $fsobj = new COM("Scripting.FileSystemObject");
                    $ofile = $fsobj->GetFile($file);
                    $size = ($ofile->Size) + 1 - 1;
                    $cache[$file] = $size;
                }else{
                    $size = filesize($file);    
                }
            }else{
                $dir = dirname($file);
                $list = array();
                exec("ls -l ".escapeshellarg($dir) . '/', $list);
                $files = $this->ParseUnixList($list, @$config['ls_dateformat_in_iso8601']);
                foreach ($files as $v){
                    $cache[$dir."/".$v['name']] = $v['size'];
                }
                if (isset($config['follow_symlink']) && $config['follow_symlink']) {
                    foreach ($files as $v){
                        if ($v['is_link']) {
                            $cache[$dir."/".$v['name']] = $this->true_filesize($v['link'], true);
                        }
                    }
                }
                $size = $cache[$file];
            }
        }
        return $size;
    }

    function best_comment($ThisFileInfo, $tag, $default = '')
    {
        $cur = 0;
        $ret_string = $default;
        if (isset($ThisFileInfo['comments'][$tag])) {
            foreach($ThisFileInfo['comments'][$tag] as $value) {
                $value = magic_decode($value);
                if (strlen($value) > $cur) {
                    $ret_string = $value;
                    $cur = strlen($value);
                }
            }
        }
        if (isset($ThisFileInfo['comments_html'][$tag])) {
            foreach($ThisFileInfo['comments_html'][$tag] as $value) {
                $value = html2ASCII($value);
                if (strlen($value) > $cur) {
                    $ret_string = $value;
                    $cur = strlen($value);
                }
            }
        }
        return $ret_string;
    }

    function buildUrl($urlparts)
    {
        return $urlparts['scheme'] . "://"
         . (@$urlparts['username'] ? ($urlparts['username'] . (@$urlparts['password'] ? ":" . $urlparts['password'] : "") . "@") : "")
         . @$urlparts['host']
         . (@$urlparts['port'] ? ":" . $urlparts['port'] : "")
         . @$urlparts['path']
         . (@$urlparts['query'] ? "?" . $urlparts['query'] : "")
         . (@$urlparts['fragment'] ? "#" . $urlparts['fragment'] : "");
    }
    // Split line to specified chunks count
    function SplitToChunks($str, $chunksLimit)
    {
        $chunks = array();
        while (($chunksLimit-- > 0) && (($pos = strpos($str, " ")) !== false)) {
            $chunks[] = substr($str, 0, $pos);
            $str = ltrim(substr($str, $pos));
        }
        $chunks[] = $str;
        return $chunks;
    }
    // Parse DOS style formatted dir content list
    function ParseWinList($list)
    {
        $dirEntries = array();

        foreach ($list as $line) {
            if ($chunks = $this->SplitToChunks($line, 3)) {
                list($month, $day, $year) = explode("-", $chunks[0]);
                // change from 2-digit year format to 4-digit
                $year = $year + ($year < 70 ? 2000 : 1900);
                // fill dir entry
                $dirEntry["is_dir"] = ($chunks[2] == "<DIR>");
                $dirEntry["name"] = $chunks[3];
                $dirEntry["date"] = $year . "-" . $month . "-" . $day;
                $dirEntry["time"] = date("H:i", strtotime($chunks[1]));
                $dirEntry["size"] = $dirEntry["is_dir"] ? 0 : $chunks[2];
                if ($dirEntry["size"] < 0) $dirEntry["size"] = 4294967296 + $dirEntry["size"];
                $dirEntries[] = $dirEntry;
            }
        }

        return $dirEntries;
    }
    // Parse UNIX style formatted dir content list
    function ParseUnixList($list, $iso8601 = false)
    {
        $dirEntries = array();
        $countChunks = $iso8601? 7 : 8;
        foreach ($list as $line) {
            if ($chunks = $this->SplitToChunks($line, $countChunks)) {
                // fill dir entry
                $dirEntry = array();
                $dirEntry["is_dir"] = ($chunks[0]{0} == 'd');
                $dirEntry["is_link"] = ($chunks[0]{0} == 'l');
                $dirEntry["size"] = $chunks[4];
                if ($dirEntry["size"] < 0) $dirEntry["size"] = 4294967296 + $dirEntry["size"];
                if ($iso8601) {
                    $dirEntry["date"] = $chunks[5];
                    $dirEntry["time"] = $chunks[6];
                    $dirEntry["name"] = $chunks[7];
                } else {
                    $isTime = strpos($chunks[7], ":") != false;
                    $dirEntry["date"] = ($isTime ? date("Y") : $chunks[7]) . "-" . date("m-d", strtotime($chunks[5] . " " . $chunks[6]));
                    $dirEntry["time"] = $isTime ? $chunks[7] : "00:00";
                    $dirEntry["name"] = $chunks[8];
                }
                if ($dirEntry["is_link"]) {
                    $dirEntry["name"] = preg_replace('# \-> .*?$#i', '', $dirEntry["name"]);
                    preg_match('# \-> (.*?)$#i', $line, $matches);
                    $dirEntry["link"] = $matches[1];
                }
                $dirEntries[] = $dirEntry;
            }
        }
        return $dirEntries;
    }

    function getDirExtension($path)
    {
        global $config;
        // определение настроек дл€ места
        $url = @parse_url($path);
        $ext["login"] = isset($url["user"]) ? $url["user"] : "anonymous";
        $ext["password"] = isset($url["pass"]) ? $url["pass"] : "";
        $ext["port"] = 21;
        $ext["timeout"] = 90;
        $ext["encoding"] = (preg_match('/^ftp:\/\//', $path)) ? (isset($config["enc_ftp"]) ? $config["enc_ftp"] : "w") : (isset($config["enc_fs"]) ? $config["enc_fs"] : "w");
        foreach($this->dir_extensions as $dir => $extension) {
            $pos = strpos($path, $dir);
            if ($pos === false) {
            } else {
                $ext["login"] = isset($extension["login"]) ? $extension["login"] : $ext["login"];
                $ext["password"] = isset($extension["password"]) ? $extension["password"] : $ext["password"];
                $ext["port"] = isset($extension["port"]) ? $extension["port"] : (isset($url["port"]) ? $url["port"] : 21);
                $ext["timeout"] = isset($extension["timeout"]) ? $extension["timeout"] : 90;
                $ext["encoding"] = isset($extension["encoding"]) ? $extension["encoding"] : "w";
            }
        }
        return $ext;
    }
    function getFileSize($path, $encode=true)
    {
        global $config;
        if ($encode){
            $ext = $this->getDirExtension($path);
            $path = my_convert_cyr_string($path, "w", $ext["encoding"]);
        }
        if (preg_match('/^ftp:\/\//', $path)) {
            $url = parse_url($path);
            if (!isset($this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]])) {
                $this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]] = ftp_connect($url["host"], $ext["port"], $ext["timeout"]);
                $login_result = ftp_login($this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]], $ext["login"], $ext["password"]);
                if (!$login_result) {
                    ftp_close($this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]]);
                    $this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]] = 0;
                }
                if (isset($config['ftp_passive_mode'])) {
                    ftp_pasv($this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]], $config['ftp_passive_mode']);
                }
            }
            $conn_id = $this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]];
            $systype = ftp_systype($conn_id);
            if ($conn_id) {
                $size = ftp_size($conn_id, $url["path"]);
                if ($size < 0) {
                    $size = 4294967296 + $size;
                }
                return $size;
            }
        } else {
            //return sprintf("%u", filesize($path));
            return $this->true_filesize($path);
        }
        return 0;
    }

    function CheckSubDir($path)
    {
        $ext = $this->getDirExtension($path);
        if (!preg_match("/\/$/i", $path)) $path .= "/";
        if (preg_match('/^ftp:\/\//', $path)) {
            $url = parse_url($path);
            if (!isset($this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]])) {
                $this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]] = ftp_connect($url["host"], $ext["port"], $ext["timeout"]);
                $login_result = ftp_login($this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]], $ext["login"], $ext["password"]);
                if (!$login_result) {
                    ftp_close($this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]]);
                    $this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]] = 0;
                }
                if (isset($config['ftp_passive_mode'])) {
                    ftp_pasv($this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]], $config['ftp_passive_mode']);
                }
            }
            $conn_id = $this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]];
            $systype = ftp_systype($conn_id);
            if ($conn_id) {
                if (ftp_chdir($conn_id, $url["path"])) {
                    $raw_list = ftp_rawlist($conn_id, "");
                    if (($systype == "Windows_NT") && (@$raw_list[0][0] >= '0' && @$raw_list[0][0] <= '9'))
                        $ftpdir = @$this->ParseWinList($raw_list);
                    else // usually ftp servers uses Unix format style by default
                        $ftpdir = @$this->ParseUnixList($raw_list);

                    foreach ($ftpdir as $dirinfo) {
                        if ($dirinfo['is_dir']) {
                            return true;
                        }
                    }
                }
            }
        } else {
            $handle = opendir(realpath($path));
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    if (is_dir($path . $file)) return true;
                }
            }
        }
        return false;
    }

    function encode_path($path)
    {
        // определение настроек места
        $ext = $this->getDirExtension($path);
        return my_convert_cyr_string($path, $ext["encoding"], "w");
    }

    function decode_path($path)
    {
        // определение настроек места
        $ext = $this->getDirExtension($path);
        return my_convert_cyr_string($path, "w", $ext["encoding"]);
    }

    function directory_list($path, $unionstyle = false)
    {
        global $config;
        $path = str_replace("\\","/",$path);
        if (!preg_match("/\/$/i", $path)) $path .= "/";
        $files = array();
        // определение настроек места
        $ext = $this->getDirExtension($path);
        $path_dec = my_convert_cyr_string($path, "w", $ext["encoding"]);
        if (preg_match("/^ftp:\/\//i", $path)) {
            // работаем с ftp
            $url = parse_url($path_dec);
            if (!isset($this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]])) {
                $this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]] = ftp_connect($url["host"], $ext["port"], $ext["timeout"]);
                $login_result = ftp_login($this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]], $ext["login"], $ext["password"]);
                if (!$login_result) {
                    ftp_close($this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]]);
                    $this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]] = 0;
                }
                if (isset($config['ftp_passive_mode'])) {
                    ftp_pasv($this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]], $config['ftp_passive_mode']);
                }
            }
            $conn_id = $this->ftp_connect_id[$url["host"]][$ext["port"]][$ext["login"]][$ext["password"]];
            $systype = ftp_systype($conn_id);
            if ($conn_id) {
                if (ftp_chdir($conn_id, $url["path"])) {
                    $raw_list = ftp_rawlist($conn_id, "");
                    if (($systype == "Windows_NT") && (@$raw_list[0][0] >= '0' && @$raw_list[0][0] <= '9'))
                        $ftpdir = @$this->ParseWinList($raw_list);
                    else // usually ftp servers uses Unix format style by default
                        $ftpdir = @$this->ParseUnixList($raw_list);
                    foreach ($ftpdir as $dirinfo) {
                        $file_enc = my_convert_cyr_string($dirinfo['name'], $ext["encoding"], "w");
                        $key = $path . $file_enc . "/";
                        $files[$key]["name"] = $file_enc;
                        $files[$key]["path"] = $path . $file_enc;
                        $files[$key]["path_dec"] = $path_dec . $dirinfo['name'];
                        $files[$key]["isdir"] = $dirinfo['is_dir'];
                        $files[$key]["size"] = $dirinfo['size'];
                        $files[$key]["has_subdir"] = @$ext["check_subdir"] ? ($files[$key]["isdir"] ? $this->CheckSubDir($path_dec . $dirinfo['name']) : 0) : 0;
                    }
                }
            }
        } else if ($this->isDir($path_dec)) {
            // работаем с локальным каталогом
            $handle = @opendir(realpath($path_dec));
            $count = 0;
            while (false !== ($file = @readdir($handle))) {
                $file_enc = my_convert_cyr_string($file, $ext["encoding"], "w");
                if ($file != "." && $file != "..") {
                    $key = $path . $file_enc . "/";
                    $files[$key]["name"] = $file_enc;
                    $files[$key]["path"] = $path . $file_enc;
                    $files[$key]["path_dec"] = $path_dec . $file;
                    $files[$key]["isdir"] = is_dir($path_dec . $file);
                    $files[$key]["size"] = $this->true_filesize($path_dec . $file);
                    $files[$key]["has_subdir"] = @$ext["check_subdir"] ? ($files[$key]["isdir"] ? $this->CheckSubDir($path_dec . $file) : 0) : 0;
                }
            }
        }

        if ($unionstyle) {
            $oldfiles = $files;
            $files = array();
            foreach($oldfiles as $key => $file) {
                if (!$file["isdir"]) $key = preg_replace($config['multipathpattern'], "", $key);
                $files[$key]["name"][] = $file["name"];
                $files[$key]["path"][] = $file["path"];
                $files[$key]["path_dec"][] = $file["path_dec"];
                $files[$key]["isdir"][] = $file["isdir"];
                $files[$key]["size"][] = $file["size"];
                $files[$key]["has_subdir"][] = $file["has_subdir"];
            }
        }

        return $files;
    }

    function urename($pathfrom, $pathto)
    {
        // определение настроек места
        $ext = $this->getDirExtension($pathfrom);
        $pathfrom_dec = my_convert_cyr_string($pathfrom, "w", $ext["encoding"]);
        return urename($pathfrom_dec, $pathto);
    }
    function getMetaInfo($path, $size = 0, $disableMplayer = false)
    {
        global $config;
        if (!$this->getID3) {
            if (!class_exists("getID3")) require_once(dirname(dirname(__FILE__)) . "/getid3/getid3.php");
            $this->getID3 = new getID3;
        }
        $ext = $this->getDirExtension($path);
        $path_dec = my_convert_cyr_string($path, "w", $ext["encoding"]);
        $tmpfname = null;

        if (preg_match("/^ftp:\/\//i", $path_dec)) {
            $urlparts = parse_url($path_dec);
            $urlparts["username"] = $ext["login"];
            $urlparts["password"] = $ext["password"];
            $path_dec = $this->buildUrl($urlparts);
            $fd = fopen ($path_dec, "rb");
            if ($fd) {
                $contents = "";
                while (!feof($fd) && (strlen($contents) < 32768)) {
                    $contents .= fread ($fd, 32768);
                }
                fclose ($fd);
                $tmpfname = tempnam("/tmp", "FOO");

                $handle = fopen($tmpfname, "w");
                fwrite($handle, $contents);
                fclose($handle);
            }
        }
        $extensionsForMplayerUse = @$config['mplayer_extensions']? $config['mplayer_extensions'] : array('mp4');
        $extension = pathinfo($path_dec, PATHINFO_EXTENSION);
        if (!$disableMplayer && in_array($extension, $extensionsForMplayerUse)) {
            require_once(dirname(dirname(__FILE__)) . "/common/mplayer.php");
            $mplayer = new Mplayer(@$config['mplayer']?$config['mplayer']:'mplayer');
            $ThisFileInfo = $mplayer->analyze($tmpfname?$tmpfname:$path_dec);
        } else {
            $this->getID3->option_max_2gb_check = true;
            $ThisFileInfo = $this->getID3->analyze($tmpfname?$tmpfname:$path_dec);
            getid3_lib::CopyTagsToComments($ThisFileInfo);
            if (!isset($ThisFileInfo['video']) && !isset($ThisFileInfo['audio']) && !$disableMplayer) {
                require_once(dirname(dirname(__FILE__)) . "/common/mplayer.php");
                $mplayer = new Mplayer(@$config['mplayer']?$config['mplayer']:'mplayer');
                $ThisFileInfo = $mplayer->analyze($tmpfname?$tmpfname:$path_dec);
            }
        }

        if ($tmpfname) unlink($tmpfname);
        return $ThisFileInfo;
    }

    function getAudioInfoStr($ThisFileInfo)
    {
        return ($ThisFileInfo['audio']["sample_rate"] / 1000) . " к√ц, " . $ThisFileInfo['audio']["channelmode"] . ", %d kbps" . (($ThisFileInfo['audio']["bitrate_mode"]) ? " (" . $ThisFileInfo['audio']["bitrate_mode"] . ")" : "") . ((isset($ThisFileInfo['audio']["codec"]))?(", " . $ThisFileInfo['audio']["codec"]):"");
    }
    
    function safeDirname($filename){
        return str_replace("\\", "/", dirname($filename));
    }
    
    function getAudioMetas($paths)
    {
        if (!is_array($paths)) $paths = array($paths);
        if (preg_match("/\/$/i", $paths[0])) {
            // это каталог
            $files = $this->directory_list($paths[0]);
        } else {
            $dirs = array();
            $tmp = array();
            foreach($paths as $path) {
                $dirs[$this->safeDirname($path)] = 1;
            }
            foreach($dirs as $dir => $v) {
                $tmp = array_values($this->directory_list($dir));
                foreach($tmp as $file) {
                    if (in_array($file["path"], $paths)) {
                        $files[] = $file;
                    }
                }
            }
        }
        $check_misc_artist = array();
        $sortarray1 = array();
        $sortarray2 = array();
        $coversize = 0;
        $cover = "";
        $audioinfo = "";
        $genre = "";
        $bitrate = 0;
        $countfiles = 0;
        $packed_tracks = array();
        $tracks = array();
        $other_files = array();
        $res = array();
        $album = "";
        $artist = "";
        $year = "";
        foreach($files as $file) {
            if (!$file["isdir"]) {
                $ThisFileInfo = $this->getMetaInfo($file["path"], $file["size"], true);
                if ((isset($ThisFileInfo["audio"]) && !isset($ThisFileInfo["video"])) || (preg_match("/(mp3|ogg|wma)$/i", $file["name"]))) {
                    // парсим им€ файла
                    if (preg_match("/(\d+)(.*)\.[^\.]*/i", $file["name"], $matches)) {
                        $track = $matches[1];
                        $title = $matches[2];
                    }
                    $ThisFileInfo['playtime_seconds'] = ($file["size"] - $ThisFileInfo['avdataoffset']) * 8 / $ThisFileInfo['audio']['bitrate'];
                    $title = $this->best_comment($ThisFileInfo, 'title', trim($title));
                    $artist = $this->best_comment($ThisFileInfo, 'artist');
                    $album = $this->best_comment($ThisFileInfo, 'album');
                    $year = isset($ThisFileInfo['comments']['year']) ? implode(";", $ThisFileInfo['comments']['year']) : "";
                    $track = isset($ThisFileInfo['comments']['track']) ? $ThisFileInfo['comments']['track'][0] : $track;
                    $track = explode("/", $track);
                    $track = preg_replace('{[^0-9]}', "", $track[0]);
                    $genre = isset($ThisFileInfo['comments']['genre']) ? implode(";", $ThisFileInfo['comments']['genre']) : "";
                    $tracks[] = array("name" => $file["name"],
                        "track" => $track,
                        "artist" => $artist,
                        "title" => $title,
                        "album" => $album,
                        "year" => $year,
                        "genre" => $genre,
                        "length" => round($ThisFileInfo["playtime_seconds"]),
                        "bitrate" => round($ThisFileInfo['audio']["bitrate"] / 1000),
                        "sample_rate" => $ThisFileInfo['audio']["sample_rate"] / 1000,
                        "channelmode" => $ThisFileInfo['audio']["channelmode"],
                        "bitrate_mode" => $ThisFileInfo['audio']["bitrate_mode"],
                        "size" => $file["size"],
                        "path" => $file["path"]
                        );
                    $packed_tracks[] = str_replace("\r\n", ";", implode("|", $tracks[count($tracks)-1]));
                    $sortarray1[] = $track;
                    $sortarray2[] = $file["name"];
                    if (!$audioinfo) $audioinfo = $this->getAudioInfoStr($ThisFileInfo);
                    $bitrate += $ThisFileInfo['audio']["bitrate"];
                    $countfiles++;
                    $check_misc_artist[$artist] = 1;
                } else {
                    $other_files[] = implode("|", array($file["name"], 0));
                }
                if (preg_match("/(jpe?g|gif|png)$/i", $file["name"]) && (($ThisFileInfo["video"]["resolution_x"] * $ThisFileInfo["video"]["resolution_y"]) > $coversize)) {
                    $cover = $file["path"];
                    $coversize = $ThisFileInfo["video"]["resolution_x"] * $ThisFileInfo["video"]["resolution_y"];
                }
            } else $other_files[] = implode("|", array($file["name"], 1));
        }
        array_multisort($sortarray1, SORT_NUMERIC, $sortarray2, SORT_NUMERIC, $packed_tracks);
        $res["alerts"] = getAlerts(implode ("\r\n", $packed_tracks));
        if (count($check_misc_artist) > 1 && !$res["alerts"]["TracksNotWhole"]) $artist = "Various Artist";

        $typeof = "јльбом";
        if (preg_match("/soundtrack/i", $paths[0] . $genre . $album . $artist)) $typeof = "—аундтрек";
        if (preg_match("/(live|concert)/i", $paths[0] . $album)) $typeof = " онцерт";
        if (preg_match("/single/i", $paths[0] . $album)) $typeof = "—ингл";
        if (preg_match("/remix/i", $paths[0] . $album)) $typeof = "–емикс";
        if (preg_match("/various/i", $paths[0] . $album)) $typeof = "—борник";
        if (preg_match("/(best|this is me|gold|collection|platinum|коллекци€)/i", $paths[0] . $album)) $typeof = "—борник";
        $res["cover"] = $cover;
        $res["year"] = $year;
        $res["artist"] = $artist;
        $res["album"] = $album;
        $res["genre"] = $genre;
        $res["tracks"] = $tracks;
        $res["packed_tracks"] = $packed_tracks;
        $res["bitrate"] = ($countfiles) ? round($bitrate / $countfiles / 1000):0;
        $res["audioinfo"] = sprintf ($audioinfo, $res["bitrate"]);
        $res["other_files"] = $other_files;
        $res["typeof"] = $typeof;
        $res["count_tracks"] = $countfiles;
        return $res;
    }

    function getVideoMetas($paths)
    {
        global $config;
         
        if (!is_array($paths)) $paths = array($paths);
        $files = array();
        foreach($paths as $path) {
            if (preg_match("/\/$/i", $path)) {
                // это каталог
                $files = array_merge($files, $this->directory_list($path));
            } else {
                $files = array_merge($files, $this->directory_list($path));
                $dirs = array();
                $tmp = array();
                foreach($paths as $path) {
                    $dirs[$this->safeDirname($path)] = 1;
                }
            }
        }
        foreach($dirs as $dir => $v) {
            $tmp = array_values($this->directory_list($dir));
            foreach($tmp as $file) {
                if (in_array($file["path"], $paths)) {
                    if (!$file["isdir"]) $files[] = $file;
                }
            }
        }
        $resolution = "";
        $videoInfo = "";
        $audioInfo = "";
        $runtime = 0;
        $runtime_fdir = 0;
        $cover = "";
        $res = array();
        $videofiles = array();
        $dirname1 = null;
        $coversize = 0;

        $includeExtensions = @$config['include_extensions']? $config['include_extensions'] : array('avi','vob','mpg','mpeg','mpe','m1v','m2v','asf','mov','dat','wmv','wm','rm','rv','divx','mp4','mkv','qt','ogm');
        $excludeExtensions = @$config['exclude_extensions']? $config['exclude_extensions'] : array();
        
        foreach ($files as $file) {
            if (!$dirname1) $dirname1 = $this->safeDirname($file["path"]);
            $dirname2 = $this->safeDirname($file["path"]);
            $extension = pathinfo($file["name"], PATHINFO_EXTENSION);
            $ThisFileInfo = !in_array($extension, $excludeExtensions)? $this->getMetaInfo($file["path"], $file["size"]) : array();
            if (isset($ThisFileInfo["video"]) && $ThisFileInfo['video']["codec"] && !in_array($extension, $excludeExtensions)) {
                $resolution = $ThisFileInfo['video']["resolution_x"] . "x" . $ThisFileInfo['video']["resolution_y"];
                $videoInfo = (($ThisFileInfo['video']["codec"]) ? $ThisFileInfo['video']["codec"] : $ThisFileInfo['video']["dataformat"]) . (($ThisFileInfo['video']["frame_rate"] > 1) ? ", " . $ThisFileInfo['video']["frame_rate"] . " fps" : "");
                $audioInfo = isset($ThisFileInfo['audio']) ? ($ThisFileInfo['audio']["sample_rate"] / 1000) . " к√ц, " . $ThisFileInfo['audio']["channelmode"] . (($ThisFileInfo['audio']["bitrate"]) ? ", " . round($ThisFileInfo['audio']["bitrate"] / 1000) . " kbps" : "") . (($ThisFileInfo['audio']["bitrate_mode"]) ? " (" . $ThisFileInfo['audio']["bitrate_mode"] . ")" : "") . ", " . $ThisFileInfo['audio']["codec"] : " ";
                $runtime += round($ThisFileInfo["playtime_seconds"]);
                $runtime_fdir += ($dirname1 == $dirname2) ? round($ThisFileInfo["playtime_seconds"]) : 0;
                $videofiles[] = $file;
            } elseif (in_array($extension, $includeExtensions)) {
                $videofiles[] = $file;
            }
            if (preg_match("/(jpe?g|gif|png)$/i", $file["name"]) && (($ThisFileInfo["video"]["resolution_x"] * $ThisFileInfo["video"]["resolution_y"]) > $coversize)) {
                $cover = $file["path"];
                $coversize = $ThisFileInfo["video"]["resolution_x"] * $ThisFileInfo["video"]["resolution_y"];
            }
        }
        $res["cover"] = $cover;
        $res["resolution"] = $resolution;
        $res["videoInfo"] = $videoInfo;
        $res["audioInfo"] = $audioInfo;
        $res["runtime"] = $runtime;
        $res["runtime_fdir"] = $runtime_fdir;
        $res["videofiles"] = $videofiles;
        return $res;
    }

    function isDir($path)
    {
        if (preg_match('{^[\\\/]{2}([^\\/]+)[\\\/]([^\\\/]+)[\\\/]?$}', $path)) {
            return true;
        } else {
            return is_dir(realpath($path));
        }
    }

}
?>