<?php

/**
 *
 * (C) 2006 Ilya Spesivtsev, iljasp@tut.by
 *
 * Класс для работы с постерами/обложками
 *
 * @version $Id$
 * @copyright 2006
 * @author Ilya Spesivtsev
 */

class CoversManager {

	var $covers = array();
	var $basename = null;
	var $maincovers = null;
	var $getID3Obj = null;
	var $gd_loaded = false;
	var $quality = 80;
	var $undesirable_size = 0;
	var $delete_bad = false;

	function CoversManager($basename, $covers, $maincovers, $getID3Obj, $undesirable_size=0, $delete_bad) {
		$this->basename = $basename;
		$this->covers = $covers;
		$this->maincovers = $maincovers;
		$this->getID3Obj = $getID3Obj;
		$this->undesirable_size = $undesirable_size;
		$this->gd_loaded = function_exists('imagecreatefromgif');
		$this->delete_bad = $delete_bad;
	}

	function getContent($path){
			$cont = null;
			if (preg_match("/^http:\/\//", $path)) {
				$path = rawurldecode($path);
				$t = explode("/",$path);
				for ($i=3;$i<count($t);$i++) $t[$i] = rawurlencode ($t[$i]);
				$path = implode("/",$t);

				$response = httpClient($path, 0, '', 15, null, "Referer: " . dirname($path) . "\r\n", false, false);
				if (preg_match("/^HTTP\/1.\d 30[12].*?\n/s", $response['header']))
				{
					preg_match("/Location:\s+(.*?)\n/s",$response['header'],$matches);
					if (!empty($matches[1]))
					{
       						// get redirection target
				     		$location = trim($matches[1]);
						$response = httpClient($location, 0, '', 15, null, "Referer: " . dirname($location) . "\r\n", false, false);
					}
				}
				if (preg_match("/^.*? 200 .*?\n/s", $response['header'])) {
					$cont = $response['data'];
				} else{
				}
			} else {
				$cont = implode ('', file ($path));
			}
			return $cont;
	}

	function getNewFilename($path, $basename, $extension) {
		$pref = null;
		while (is_file($path . $basename . $pref . "." . $extension)) {
			$pref++;
		}
		return $path . $basename . $pref . "." . $extension;
	}

	function writeFile($filename, $somecontent, $mode, &$messages)
	{
		if (!$fp = fopen($filename, $mode)) {
			$messages[] = "Не могу открыть файл $filename";
			return false;
		}
		if (!fwrite($fp, $somecontent)) {
			$messages[] = "Не могу записать в файл $filename";
			return false;
		}
		$messages[] = "Файл $filename записан успешно";
		fclose($fp);
		return true;
	}

	function loadImage($path,$imageinfo){
		switch($imageinfo[2]){
			case 1:
				return imagecreatefromgif($path);
				break;
			case 2:
				return imagecreatefromjpeg($path);
				break;
			case 3:
				return imagecreatefrompng($path);
				break;
			case 6:
				return imagecreatefromwbmp ($path);
				break;
		} // switch
	}

	function repairExtension($path){
		$formats = array(
			'gif' => 1,
			'jpg' => 2,
			'jpeg' => 2,
			'png' => 3,
			'bmp' => 6
		);

		$rformats = array(
			1 => 'gif',
			2 => 'jpg',
			3 => 'png',
			6 => 'bmp'
		);

		$path_parts = pathinfo($path);
		$extension = $path_parts["extension"];
		$imageinfo = getimagesize($path);
		$ext = $imageinfo[2];

		if ($formats[$extension]!=$ext) {
			$newpath = $this->getNewFilename("", substr($path, 0, -strlen($extension)-1), $rformats[$ext]);
			if (rename($path, $newpath)) return $newpath;
		}
		return $path;
	}
	function produceImage($imgobj,$dest_width, $dest_height, $to_path, $imageinfo, $center ){
		$scr_width = $imageinfo[0];
		$scr_height = ($imageinfo[0]/$dest_width)*$dest_height;
		if ($scr_height>$imageinfo[1]){
			$scr_height = $imageinfo[1];
			$scr_width = ($imageinfo[1]/$dest_height)*$dest_width;
		}
		$scr_dx = ($imageinfo[0]-$scr_width) * $center[0];
		$scr_dy = ($imageinfo[1]-$scr_height)* $center[1];
		
		$dst_img = imagecreatetruecolor($dest_width, $dest_height);
		imagecopyresampled($dst_img, $imgobj, 0, 0, $scr_dx, $scr_dy, $dest_width, $dest_height, $scr_width, $scr_height);
		$res = imagejpeg($dst_img, $to_path, $this->quality);
		imagedestroy($dst_img);
		return $res;
	}

	function ForceCover($from_path, $to_path, $imgobj, $settings, $imageinfo){
		if ($settings["required"] || ($imageinfo[0]>$settings["width"])){
			if (($settings["maxwidth"] && ($imageinfo[0]>$settings["maxwidth"])) || ($settings["width"]>$imageinfo[0] && $settings["zoom"]) || (isset($settings["height"]) && $settings["height"]>$imageinfo[1])) {
				if (!isset($settings["height"])){
					$scale = $settings["width"] / $imageinfo[0];
					$settings["height"] = (int)($imageinfo[1] * $scale);
				} 
				if (!isset($settings["center"])) $settings["center"] = array(0.5,0.5);
				if ($this->produceImage($imgobj,$settings["width"],$settings["height"],$to_path,$imageinfo,$settings["center"])) return $to_path;
			}
			if ($from_path!=$to_path) {
				if (copy ($from_path, $to_path)) return $to_path;
			}
		} else return "";
		return $to_path;
	}

	function postProduction(){
		$maxsize = 0;
		$sizes = array();
		$maincovers = $this->covers[$this->maincovers];
		for($i = 0; $i < count($maincovers["covers"]);$i++) {
			$sizes[$i] = 0;
			foreach($this->covers as $k=>$v){
				$from_path = $v["covers"][$i];
				$imageinfo = getimagesize($from_path);
				if ($imageinfo[0]>$sizes[$i]) $sizes[$i] = $imageinfo[0];
				if ($imageinfo[0]>$maxsize) $maxsize = $imageinfo[0];
			}
		}

		for($i = count($maincovers["covers"])-1; $i>=0;$i--) {
			if (($this->delete_bad && !$sizes[$i]) || !$maincovers["covers"][$i] || ($sizes[$i] && $this->undesirable_size && ($maxsize>$this->undesirable_size) && ($sizes[$i]<=$this->undesirable_size) )) {
				foreach($this->covers as $k=>$v){
					array_splice ($this->covers[$k]["covers"], $i, 1);
				}
				array_splice ($sizes, $i, 1);
			}
		}
	}

	function ForceCovers(){
		$maincovers = $this->covers[$this->maincovers];
		for($i = 0;$i < count($maincovers["covers"]);$i++) {
			$cover = $maincovers["covers"][$i];
			$path_parts = pathinfo($cover);
			$extension = $path_parts["extension"];
			if (!$this->basename) $this->basename =  substr($path_parts["basename"], 0, -strlen($extension)-1) ;
			if (!$extension) $extension = "jpg";
			$downloaded = 0;
			//закачка изображения
			if (!((($pos = strpos($cover, $maincovers["localpath"])) !== false) && ($pos==0))){
				$cont = $this->getContent($cover);
				if (strlen($cont)) {
					$fn = $this->getNewFilename($maincovers["localpath"],$this->basename,$extension);
					if ($this->writeFile($fn,$cont,"wb",$messages)) {
						$fn = $this->repairExtension($fn);
						$path_parts = pathinfo($fn);
						$extension = $path_parts["extension"];
						foreach($this->covers as $k=>$v){
							$this->covers[$k]["covers"][$i] = "";
						}
						$this->covers[$this->maincovers]["covers"][$i] = $fn;
						$downloaded = 1;
					} else 	print_r ($messages);
				}
			} else $downloaded = 1;

			if ($downloaded && $this->gd_loaded) {
				$from_path = $this->covers[$this->maincovers]["covers"][$i];
				$imageinfo = getimagesize($from_path);
				$imgobj = $this->loadImage($from_path,$imageinfo);
				foreach($this->covers as $k=>$v){
					if ($k!=$this->maincovers) {
						if (!$this->covers[$k]["covers"][$i]) {
							$to_path = $this->getNewFilename($this->covers[$k]["localpath"],$this->basename,$extension);
							$this->covers[$k]["covers"][$i] = $this->ForceCover($from_path, $to_path, $imgobj, $this->covers[$k]["settings"], $imageinfo);
						}
					 }
				}
				$this->ForceCover($this->covers[$this->maincovers]["covers"][$i], $this->covers[$this->maincovers]["covers"][$i], $imgobj, $this->covers[$this->maincovers]["settings"], $imageinfo);
			}
		}
		$this->postProduction();
		return $this->covers;
	}

	function ForcePhoto($url, $dir){
		$path_parts = pathinfo($url);
		$extension = $path_parts["extension"];
		if (!$this->basename) $this->basename =  substr($path_parts["basename"], 0, -strlen($extension)-1) ;
		if (!$extension) $extension = "jpg";
		$downloaded = 0;
		//закачка изображения
		if (!((($pos = strpos($url, $dir)) !== false) && ($pos==0))){
			$cont = $this->getContent($url);
			if (strlen($cont)) {
				$fn = $this->getNewFilename($dir,$this->basename,$extension);
				if ($this->writeFile($fn,$cont,"wb",$messages)) {
					$fn = $this->repairExtension($fn);
					$url = $fn;
					$downloaded = 1;
				} else 	print_r ($messages);
			}
		} else $downloaded = 1;

		if ($downloaded && $this->gd_loaded) {
			$from_path = $this->covers[$this->maincovers]["covers"][$i];
			$imageinfo = getimagesize($from_path);
			$imgobj = $this->loadImage($from_path,$imageinfo);
			foreach($this->covers as $k=>$v){
				if ($k!=$this->maincovers) {
					if (!$this->covers[$k]["covers"][$i]) {
						$to_path = $this->getNewFilename($this->covers[$k]["localpath"],$this->basename,$extension);
						$this->covers[$k]["covers"][$i] = $this->ForceCover($from_path, $to_path, $imgobj, $this->covers[$k]["settings"], $imageinfo);
					}
				 }
			}
			$this->ForceCover($this->covers[$this->maincovers]["covers"][$i], $this->covers[$this->maincovers]["covers"][$i], $imgobj, $this->covers[$this->maincovers]["settings"], $imageinfo);
		}
	}
}

?>