<?php
/**
 * Видео-каталог
 * (C) 2006-2009 Ilya Spesivtsev, macondos@gmail.com
 *
 * Функции
 *
 * @author Ilya Spesivtsev
 * @version 1.07
 */

$PATH = dirname(__FILE__);
require_once "$PATH/common/functions.php";
define('LIB_MODULAR_PATH', "$PATH/common/");
define('LIB_HTTP_PATH', "$PATH/common/");
define('LIB_PHP_HTTP_REQUEST_PATH', "$PATH/common/");

ini_set('max_execution_time',600);

function correctConfigForParser(){
    global $config;
    if (!isset($config['websites']['default']['proxy'])){
        if (isset($config['proxy_host'])){ 
        $config['websites']['default']['proxy'] = $config['proxy_host'] . ":" . $config['proxy_port']; 
        } else $config['websites']['default']['proxy'] = false;
    }
    if (!isset($config['websites']['default']['connection_timeout'])){
        if (isset($config['connection_timeout'])){ 
        $config['websites']['default']['connection_timeout'] = $config['connection_timeout']; 
        } else $config['websites']['default']['connection_timeout'] = 5;
    }
    if (!isset($config['customer']['parser_service'])){
        $config['customer']['parser_service'] = 'http://service.lanmediaservice.com/2/actions.php';
    }
}

function searchImdb($name){
    require_once dirname(__FILE__) . "/common/webparser/webparser.php";
    global $config;
    $web_parser = new WebParser($config['websites'], $config['customer']);
    $path = $web_parser->constructPath('imdb','search',array('name'=>$name));
    $results = $web_parser->Parse('imdb','search_results',array('path'=>$path));
    $res = array();
    foreach($results as $result){
        $name = $result['names'][0]
            . ' (' . $result['year'] . ')' 
            . ' (' . $result['section'] . ')'; 
        $res[] = array("name" => $name, "url" => $result['url'], "","");
    }
    return $res;
}

function searchOzon($name){
	require_once dirname(__FILE__) . "/common/webparser/webparser.php";
    global $config;
    $web_parser = new WebParser($config['websites'], $config['customer']);
	$path = $web_parser->constructPath('ozon','search',array('name'=>$name));
	$results = $web_parser->Parse('ozon','search_results',array('path'=>$path));
	$res = array();
	foreach($results as $result){
		$res[] = array("name" => $result['names'][0], "url"=> $result['url'], "engname"=>$result['names'][1] . (isset($result['year'])? ", {$result['year']}" : ""), "info"=>$result['info'],"image"=>$result['image']);
	}
	return $res;
}

function searchKinopoisk($name){
	require_once dirname(__FILE__) . "/common/webparser/webparser.php";
    global $config;
    $web_parser = new WebParser($config['websites'], $config['customer']);
	$path = $web_parser->constructPath('kinopoisk','search',array('name'=>$name));
	$results = $web_parser->Parse('kinopoisk','search_results',array('path'=>$path));
	$res = array();
	foreach($results as $result){
		$res[] = array("name" => $result['names'][0], "url"=> $result['url'], "engname"=>$result['names'][1] . (isset($result['year'])? ", {$result['year']}" : ""), "info"=>@$result['info'],"image"=>($result['image']) ? $result['image'] : "images/kinopoisk.jpg");
	}
	return $res;
}

function searchShareReactor($name){
	require_once dirname(__FILE__) . "/common/webparser/webparser.php";
    global $config;
    $web_parser = new WebParser($config['websites'], $config['customer']);
	$path = $web_parser->constructPath('sharereactor','search',array('name'=>$name));
	$results = $web_parser->Parse('sharereactor','search_results',array('path'=>$path));
	$res = array();
	foreach($results as $result){
		$result['info'] = str_replace(array("\r\n","\r","\n"),"", $result['info']);
		$res[] = array("name" => $result['names'][0], "url" => $result['url'], "engname" => $result['names'][1] . (isset($result['year'])? ", {$result['year']}" : ""), "info" => $result['genres'], "image" => ($result['image']) ? $result['image'] : "images/sharereactor.jpg");
	}
	return $res;
}

function searchWorldArt($name){
	require_once dirname(__FILE__) . "/common/webparser/webparser.php";
    global $config;
    $web_parser = new WebParser($config['websites'], $config['customer']);
	$path = $web_parser->constructPath('worldart','search',array('name'=>$name));
	$results = $web_parser->Parse('worldart','search_results',array('path'=>$path));
	//echo "<pre>" . print_r($results,true) . "</pre>";
	$res = array();
	foreach($results as $result){
	    $rusName = array_shift($result['names']);
        $engName = array_shift($result['names']) . (isset($result['year'])? ", {$result['year']}" : "");
        $info = '';
        if (count($result['names'])) $info .= 'Альтернативные названия:<br>' . implode("<br>", $result['names']);
        if ($result['country']) $info .= '<br>Страна: ' . $result['country'];
        if ($result['type_of_movie']) $info .= '<br>Тип: ' . $result['type_of_movie'];
		$res[] = array("name" => $rusName, "url" => $result['url'], "engname" => $engName, "info" => $info, "image" => ($result['image']) ? $result['image'] : "images/worldart.jpg");
	}
	return $res;
}

function searchPersonOzon($name, $first=true){
	$ozonurl = "http://www.ozon.ru";
	$response = httpClient($ozonurl."/?context=search&type=person&text=".urlencode($name), 0, '', 15, null, '', false, false);
    $res = array();
	$contents = $response['data'];
	preg_match_all("/<big><a href=\"(.*?)\".*?class=\"bigger\">(.*?)<\/a><\/big>/is", $contents, $matches, PREG_SET_ORDER);
	foreach ($matches as $value){
		if ($first) return "http://www.ozon.ru".$value[1];
			else $res[] = array("http://www.ozon.ru".$value[1],$value[2]);
	}
	return $res;
}

function parseFilm($url){
	preg_match("/^(http:\/\/)?([^:\/]+)/i", $url, $matches);
	$host = $matches[2];
	preg_match("/[^\.\/]+\.[^\.\/]+$/",$host,$matches);
	$where = $matches[0];

	if ($where=="ozon.ru") $url .= "?type=1";
	if ($where=="kinopoisk.ru"){
		$url = preg_replace("/sr\/\d\//i","",$url);
	}

    $adaptCountries = array("Англия"=>"Великобритания");

    $adaptRoles = array(
            'Режиссер' => 'режиссер',
            'Сценарий' => 'сценарист',
            'Сценарий/идея для фильма' => 'сценарист',
            'Роли исполняли' => 'актер',
            'Роли озвучивали' => 'озвучивание',
            'Дизайн' => 'дизайнер',
            'Анимация' => 'аниматор',
            'Композитор' => 'композитор',
            'Оператор-постановщик' => 'оператор',
            'Также в ролях' => 'актер',
            'Художник-постановщик' => 'художник-постановщик',
            'Музыкальная композиция' => 'музыка',
            'Продюсер' => 'продюсер',
            'Автор оригинала' => 'автор оригинала',
            'композитор' => 'автор музыки',
            'actor'=>'актер',
            'director'=>'режиссер'
        );

    $adapt2imdb_genres = array("биография"=>"Biography",
            "боевик"=>"Action",
            "вестерн"=>"Western",
            "военный"=>"War",
            "детектив"=>"Crime",
            "детский"=>"Family",
            "для взрослых"=>"Erotic",
            "документальный"=>"Documentary",
            "мелодрама"=>"Romance",
            "драма"=>"Drama",
            "игра"=>"Game-Show",
            "история"=>"History",
            "комедия"=>"Comedy",
            "короткометражка"=>"Short",
            "криминал"=>"Crime",
            "мистика"=>"Mystery",
            "музыка"=>"Music",
            "мультфильм"=>"Animation",
            "мюзикл"=>"Musical",
            "новости"=>"News",
            "приключения"=>"Adventure",
            "реальное ТВ"=>"Reality-TV",
            "семейный"=>"Family",
            "спорт"=>"Sport",
            "ток-шоу"=>"Talk-Show",
            "триллер"=>"Thriller",
            "ужасы"=>"Horror",
            "фантастика"=>"Sci-Fi",
            "фэнтези"=>"Fantasy",
            "фильм-нуар"=>"Film-Noir",
            "Анимационный" => "Animation",
            "Детский\/Семейный" => "Family",
            "Исторический" => "History",
            "Мелодрама" => "Romance",
            "Отечественный" => "",
            "Ужасы" => "Horror",
            "Эротика" => "Erotic",
            "Боевик" => "Action",
            "Документальный" => "Documentary",
            "Комедия" => "Comedy",
            "Мистика" => "Mystery",
            "Приключения" => "Adventure",
            "Фантастика" => "Sci-Fi",
            "Детектив" => "Crime",
            "Драма" => "Drama",
            "Любовный роман" => "Romance",
            "Музыкальный" => "Musical",
            "Триллер" => "Thriller",
            "Фэнтази" => "Fantasy",
            "боевик" => "Action",
            "биографический фильм" => "Biography",
            "вампиры" => "Action",
            "вестерн" => "Western",
            "война" => "War",
            "военный фильм" => "War",
            "мелодрама" => "Romance",
            "детектив" => "Crime",
            "для детей" => "Family",
            "семейный фильм" => "Family",
            "документальный фильм" => "Documentary",
            "история" => "History",
            "исторический фильм" => "History",
            "киберпанк" => "Sci-Fi",
            "комедия" => "Comedy",
            "криминальный фильм" => "Crime",
            "криминал" => "Crime",
            "драма" => "Drama",
            "музыкальный" => "Musical",
            "мистика" => "Mystery",
            "нуар" => "Film-Noir",
            "ограбление" => "Crime",
            "пародия" => "Comedy",
            "повседневность" => "Drama",
            "приключения" => "Adventure",
            "психология" => "Drama",
            "романтика" => "Romance",
            "самурайский боевик" => "Action",
            "сказка" => "Family",
            "спортивный фильм" => "Sport",
            "спорт" => "Sport",
            "триллер" => "Thriller",
            "фильм ужасов" => "Horror",
            "ужасы" => "Horror",
            "фантастика" => "Sci-Fi",
            "фэнтези" => "Fantasy",
            "эротика" => "Erotic",
            "боевые искусства" => "Action",
            "махо-сёдзё" => "Anime",
            "меха" => "Anime",
            "мистерия" => "Mystery",
            "образовательный" => "Documentary",
            "паропанк" => "Anime",
            "полиция" => "Crime",
            "постапокалиптика" => "Anime",
            "сёдзё" => "Anime",
            "сёнэн" => "Anime",
            "сёнэн-ай" => "Anime",
            "социальный фильм" => "Documentary",
            "школа" => "Anime",
            "хентай" => "Anime",
            "юри" => "Anime",
            "яой" => "Anime"
        );

    $tom_from = array("/Художественный/i",
                "/Цветной; /i",
                "/ТВ \(\d+ эп.*\)/i",
                "/полнометражный мультфильм/i",
                "/OAV \(\d+ эп.*\)/i",
                "/полнометражный фильм/i",
                "/^сериал/i",
                "/телевизионный фильм/i"
                );
    
    $tom_to = array(
                "Худ.",
                "",
                "Мультсериал",
                "Полнометражный мультфильм",
                "Мультсериал",
                "Худ. кинофильм",
                "Худ. телесериал",
                "Худ. телефильм"
            );


	$res = array();
    list($module) = explode(".",$where);
    require_once dirname(__FILE__) . "/common/webparser/webparser.php";
    global $config;
    $web_parser = new WebParser($config['websites'], $config['customer']);
    $results = $web_parser->Parse($module,'film',array('path'=>$url));
	switch ($where){
        case "kinopoisk.ru":
        case "ozon.ru":
        case "sharereactor.ru":
        case "world-art.ru":
            foreach ($results['names'] as $name){
                if (!$res["rusRusName"] && (lms_rus_eng_detect($name)=='rus')) $res["rusRusName"] = $name;
                if (!$res["rusOriginalName"] && (lms_rus_eng_detect($name)!='rus')) $res["rusOriginalName"] = $name;
            }
            $res["rusPosterUrl"] = array_pop($results['posters']);
            $res["rusYear"] = $results['year'];
            $countries = array();
            foreach (@$results['countries'] as $country){
                $countries[] = strtr($country, $adaptCountries);
            }
            $res["rusCountries"] = implode("|", $countries);
            
            $genres = array();
            if (is_array($results['genres'])) {
                foreach ($results['genres'] as $genre){
                    $genres[] = strtr($genre,$adapt2imdb_genres);
                }
            }
            $res["rusGenres"] = implode("|",array_unique($genres));

            $res["rusCompanies"] = is_array($results['companies'])?  implode("|",$results['companies']) : '';
            
            $typesofmovie = array();
            if (is_array($results['typeofmovie'])) {
                foreach ($results['typeofmovie'] as $typeofmovie){
                    $typesofmovie[] = preg_replace($tom_from, $tom_to, $typeofmovie);
                }
            }
            $res["rusTypeOfMovie"] = implode(", ",$typesofmovie);

            $persones = array();
            foreach ($results['persones'] as $person){
                $rusname = '';  
                $engname = '';  
                foreach ($person['names'] as $name){
                    if (!$rusname && (lms_rus_eng_detect($name)=='rus')) $rusname = $name;
                    if (!$engname && (lms_rus_eng_detect($name)!='rus')) $engname = $name;
                }
                $role = strtr($person['role'], $adaptRoles);
                if (($role=='актер') && isset($person['character'])) $role .= ": <b>{$person['character']}</b>"; 
                $persones[] = implode("|", array("url"=>$person['url'], "rusname"=>$rusname, "role"=>$role, "originalname"=>$engname));
            }
            $res["rusPersones"] = implode("\r\n",$persones);
            $res["rusDescription"] = $results['description'];
            return $res;
        break;
		case "imdb.com":

		    $res = array();	// result
		    $ary = array();	// temp

		    $res['imdbOriginalName']  = adapt1252To1251(html2ASCII($results['names'][0]));
		    $res['imdbSubtitle'] = '';
		    $res['imdbYear'] = $results['year'];
		    $res['imdbPosterUrl'] = array_pop($results['posters']);
		    $res['imdbMPAA'] = $results['mpaa'];
		    $res['imdbRating'] = $results['rating'];

			$res['imdbCountries'] = implode("|",$results['countries']);

		    $res['imdbDesription'] = adapt1252To1251(html2ASCII($results['desription']));

			$res['imdbGenres'] = implode("|",$results['genres']);
			
			$persones = array();
			foreach ($results['persones'] as $person){
				$name = adapt1252To1251(html2ASCII($person['names'][0]));
				$role = strtr($person['role'], $adaptRoles);
				if (($role=='актер') && isset($person['character'])) $role .= ": <b>{$person['character']}</b>"; 
				$persones[] = implode("|", array("url"=>$person['url'], "", "role"=>$role, "originalname"=>$name));
			}
			$res["imdbPersones"] =  implode("\r\n",$persones);
			return $res;
		break;
	}
}

function parsePerson($url){
    correctConfigForParser();
	preg_match("/^(http:\/\/)?([^\/]+)/i", $url, $matches);
	$host = $matches[2];
	preg_match("/[^\.\/]+\.[^\.\/]+$/",$host,$matches);
	$where = $matches[0];

	$res = array();
	switch ($where){
		case "imdb.com":
		case "world-art.ru":
		case "kinopoisk.ru":
		case "ozon.ru":
			list($module) = explode(".",$where);
			require_once dirname(__FILE__) . "/common/webparser/webparser.php";
            global $config;
            $web_parser = new WebParser($config['websites'], $config['customer']);
   			$results = $web_parser->Parse($module,'person',array('path'=>$url));
			//echo "<pre>" . print_r($results,true) . "</pre>";
			$rusname = '';
			$engname = '';
			foreach ($results['names'] as $name){
				if (!$rusname && (lms_rus_eng_detect($name)=='rus')) $rusname = $name;
				if (!$engname && (lms_rus_eng_detect($name)!='rus')) $engname = adapt1252To1251(html2ASCII($name));
			}
				
			$res["RusName"] = $rusname;
			$res["OriginalName"] = $engname;

			$adaptDate = array(
					'January' => 'января',
					'February' => 'февраля',
					'March' => 'марта',
					'April' => 'апреля',
					'May' => 'мая',
					'June' => 'июня',
					'July' => 'июля',
					'August' => 'августа',
					'September' => 'сентября',
					'October' => 'октября',
					'November' => 'ноября',
					'December' => 'декабря'
				);
			
			$res["Born"] = $results['born_date'];
			if (isset($results['born_place'])) $res["Born"] .= " ({$results['born_place']}) ";
			$res["Profile"] = strtolower($results['profile']);
			$res["About"] = $results['about'];
			if (isset($config['allowable_tags'])) $res["About"] = strip_tags($res["About"],$config['allowable_tags']);
			$res["Photos"] = $results['photos'];
			return $res;
		break;
	}
}


function searchInfo($rusname,$engname){
	$resKinopoisk = array();
	if (!($engname && $rusname)){
		if ($engname){
			$resKinopoisk = searchKinopoisk($engname);
			$rusname = $resKinopoisk[0]["rusname"];
		}
		if ($rusname){
			$resKinopoisk = searchKinopoisk($rusname);
			$engname = $resKinopoisk[0]["engname"];
		}
	}

	if ($engname){
		$resOzonE = searchOzon($engname);
		$resImdb = searchImdb($engname);
	}
	if ($rusname){
		$resOzonR = searchOzon($rusname);
	}

	if ($rusname && !$engname && count($resOzonR)>0){
		$engname = $resOzonR[0]["engname"];
		$resImdb = searchImdb($engname);
	}

	for($i=0;$i<count($resOzonR);$i++){
		$url = $resOzonR[$i]["url"];
		$dubl = 0;
		for ($j=0;$j<count($resOzonE);$j++){
			if ($resOzonE[$j]["url"]==$url){
				$dubl = 1;
				break;
			}
		}
		if ($dubl==0) $resOzonE[] = $resOzonR[$i];
	}
	for($i=0;$i<count($resKinopoisk);$i++){
		$resOzonE[] = $resKinopoisk[$i];
	}

	return array("imdb"=>$resImdb, "rus"=>$resOzonE);
}

function getRights($action,$user){
	global $config;

	if (!isset($config['default_rights']['admin'])) $config['default_rights']['admin'] = 'return (in_array($user[\'UserGroup\'],array(3)) && $user[\'Enabled\']);';
	if (!isset($config['default_rights']['moder'])) $config['default_rights']['moder'] = 'return (in_array($user[\'UserGroup\'],array(2,3,5)) && $user[\'Enabled\'] && $user[\'Balans\']);';
	if (!isset($config['default_rights']['user'])) $config['default_rights']['user'] = 'return (in_array($user[\'UserGroup\'],array(1,2,3,4,5)) && $user[\'Enabled\'] && $user[\'Balans\']);';
	if (!isset($config['default_rights']['guest'])) $config['default_rights']['guest'] = 'return (in_array($user[\'UserGroup\'],array(0,1,2,3,4,5)) && $user[\'Enabled\'] && $user[\'Balans\']);';

	if (!isset($config['rights']['check_update'])) $config['rights']['check_update'] = $config['default_rights']['admin'];
	if (!isset($config['rights']['update'])) $config['rights']['update'] = $config['default_rights']['admin'];
	if (!isset($config['rights']['cleaning'])) $config['rights']['cleaning'] = $config['default_rights']['admin'];
	if (!isset($config['rights']['calclocalrating'])) $config['rights']['calclocalrating'] = $config['default_rights']['moder'];

	if (!isset($config['rights']['exit'])) $config['rights']['exit'] = $config['default_rights']['guest'];
	if (!isset($config['rights']['generate_screenshots'])) $config['rights']['generate_screenshots'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['generate_sample'])) $config['rights']['generate_sample'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['filmsforframegenerate'])) $config['rights']['filmsforframegenerate'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['unsetnode'])) $config['rights']['unsetnode'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['transfervar'])) $config['rights']['transfervar'] = $config['default_rights']['user'];
	if (!isset($config['rights']['getincominglist'])) $config['rights']['getincominglist'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['deletefilm'])) $config['rights']['deletefilm'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['deletefilm_ext'])) $config['rights']['deletefilm_ext'] = $config['default_rights']['admin'];
	if (!isset($config['rights']['deletefilm_erase'])) $config['rights']['deletefilm_erase'] = $config['default_rights']['admin'];
	if (!isset($config['rights']['showfilm'])) $config['rights']['showfilm'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['hidefilm'])) $config['rights']['hidefilm'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['updatefilesinfo'])) $config['rights']['updatefilesinfo'] = $config['default_rights']['guest'];
	if (!isset($config['rights']['updatefilmfield'])) $config['rights']['updatefilmfield'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['updatepersonfield'])) $config['rights']['updatepersonfield'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['updateuserfield'])) $config['rights']['updateuserfield'] = $config['default_rights']['admin'];
	if (!isset($config['rights']['deleteuser'])) $config['rights']['deleteuser'] = $config['default_rights']['admin'];
	if (!isset($config['rights']['setroleext'])) $config['rights']['setroleext'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['setrole'])) $config['rights']['setrole'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['deletefilmperson'])) $config['rights']['deletefilmperson'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['importpersones'])) $config['rights']['importpersones'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['updatefield'])) $config['rights']['updatefield'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['updatefilefield'])) $config['rights']['updatefilefield'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['deletefilerecord'])) $config['rights']['deletefilerecord'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['addfilerecord'])) $config['rights']['addfilerecord'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['searchinfo'])) $config['rights']['searchinfo'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['advsearchinfo'])) $config['rights']['advsearchinfo'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['imgsearchinfo'])) $config['rights']['imgsearchinfo'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['imgfilmsearchinfo'])) $config['rights']['imgfilmsearchinfo'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['setimage'])) $config['rights']['setimage'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['getdetail'])) $config['rights']['getdetail'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['parse'])) $config['rights']['parse'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['parseavi'])) $config['rights']['parseavi'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['commitincoming'])) $config['rights']['commitincoming'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['attach'])) $config['rights']['attach'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['downloadposter'])) $config['rights']['downloadposter'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['reduceposter'])) $config['rights']['reduceposter'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['postersfordownload'])) $config['rights']['postersfordownload'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['postersforreduce'])) $config['rights']['postersforreduce'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['downloadperson'])) $config['rights']['downloadperson'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['resolveozonulrperson'])) $config['rights']['resolveozonulrperson'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['personesfordownload'])) $config['rights']['personesfordownload'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['personesforresolve'])) $config['rights']['personesforresolve'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['downloadphotos'])) $config['rights']['downloadphotos'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['deletebadphotos'])) $config['rights']['deletebadphotos'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['photosfordownload'])) $config['rights']['photosfordownload'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['getfilmext'])) $config['rights']['getfilmext'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['setfilmgenre'])) $config['rights']['setfilmgenre'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['setfilmcountry'])) $config['rights']['setfilmcountry'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['personeslist'])) $config['rights']['personeslist'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['userslist'])) $config['rights']['userslist'] = $config['default_rights']['admin'];
	if (!isset($config['rights']['getpersondetail'])) $config['rights']['getpersondetail'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['getuserdetail'])) $config['rights']['getuserdetail'] = $config['default_rights']['admin'];
	if (!isset($config['rights']['changepassword'])) $config['rights']['changepassword'] = $config['default_rights']['user'];
	if (!isset($config['rights']['changepassword_ext'])) $config['rights']['changepassword_ext'] = $config['default_rights']['admin'];
	if (!isset($config['rights']['getpreferences'])) $config['rights']['getpreferences'] = $config['default_rights']['user'];
	if (!isset($config['rights']['setpreferences'])) $config['rights']['setpreferences'] = $config['default_rights']['user'];
	if (!isset($config['rights']['getgenres'])) $config['rights']['getgenres'] = $config['default_rights']['guest'];
	if (!isset($config['rights']['getcountries'])) $config['rights']['getcountries'] = $config['default_rights']['guest'];
	if (!isset($config['rights']['gettypesofmovie'])) $config['rights']['gettypesofmovie'] = $config['default_rights']['guest'];
	if (!isset($config['rights']['filmlist'])) $config['rights']['filmlist'] = $config['default_rights']['guest'];
	if (!isset($config['rights']['simplesearch'])) $config['rights']['simplesearch'] = $config['default_rights']['guest'];
	if (!isset($config['rights']['getfilm'])) $config['rights']['getfilm'] = $config['default_rights']['guest'];
	if (!isset($config['rights']['getperson'])) $config['rights']['getperson'] = $config['default_rights']['guest'];
	if (!isset($config['rights']['setrating'])) $config['rights']['setrating'] = $config['default_rights']['user'];
	if (!isset($config['rights']['setbookmark'])) $config['rights']['setbookmark'] = $config['default_rights']['user'];
	if (!isset($config['rights']['removebookmark'])) $config['rights']['removebookmark'] = $config['default_rights']['user'];
	if (!isset($config['rights']['getbookmarks'])) $config['rights']['getbookmarks'] = $config['default_rights']['user'];
	if (!isset($config['rights']['getcomments'])) $config['rights']['getcomments'] = $config['default_rights']['guest'];
	if (!isset($config['rights']['postcomment'])) $config['rights']['postcomment'] = $config['default_rights']['user'];
	if (!isset($config['rights']['editcomment'])) $config['rights']['editcomment'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['lastcomments'])) $config['rights']['lastcomments'] = $config['default_rights']['guest'];
	if (!isset($config['rights']['lastratings'])) $config['rights']['lastratings'] = $config['default_rights']['guest'];
	if (!isset($config['rights']['getrndtext'])) $config['rights']['getrndtext'] = $config['default_rights']['guest'];
	if (!isset($config['rights']['gettoplist'])) $config['rights']['gettoplist'] = $config['default_rights']['guest'];
	if (!isset($config['rights']['getpoplist'])) $config['rights']['getpoplist'] = $config['default_rights']['guest'];
	if (!isset($config['rights']['recommended'])) $config['rights']['recommended'] = $config['default_rights']['user'];
	if (!isset($config['rights']['show_hidden'])) $config['rights']['show_hidden'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['admin_view'])) $config['rights']['admin_view'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['similar_films'])) $config['rights']['similar_films'] = $config['default_rights']['guest'];
	if (!isset($config['rights']['getrndfilm'])) $config['rights']['getrndfilm'] = $config['default_rights']['guest'];
	if (!isset($config['rights']['inc_hit'])) $config['rights']['inc_hit'] = $config['default_rights']['guest'];
	if (!isset($config['rights']['update_imdbrating'])) $config['rights']['update_imdbrating'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['deletenotlinkedpersones'])) $config['rights']['deletenotlinkedpersones'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['searchpersononozon'])) $config['rights']['searchpersononozon'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['personabsorption'])) $config['rights']['personabsorption'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['generate_metainfo'])) $config['rights']['generate_metainfo'] = $config['default_rights']['moder'];
	if (!isset($config['rights']['check_files'])) $config['rights']['check_files'] = $config['default_rights']['admin'];
	return isset($config['rights'][$action]) ? (eval($config['rights'][$action]) ? 1 : 0) : 0;

}

function UndoFilm($filmid) {
	$sql = "DELETE FROM films WHERE ID=$filmid";
	mysql_query($sql);
	$sql = "DELETE FROM files WHERE FilmID=$filmid";
	mysql_query($sql);
	$sql = "DELETE FROM filmpersones WHERE FilmID=$filmid";
	mysql_query($sql);
	$sql = "DELETE FROM filmcountries WHERE FilmID=$filmid";
	mysql_query($sql);
	$sql = "DELETE FROM filmgenres WHERE FilmID=$filmid";
	mysql_query($sql);
	$sql = "DELETE FROM filmcompanies WHERE FilmID=$filmid";
	mysql_query($sql);
	$sql = "DELETE FROM userfilmratings WHERE FilmID=$filmid";
	mysql_query($sql);
	$sql = "DELETE FROM comments WHERE FilmID=$filmid";
	mysql_query($sql);
	$sql = "DELETE FROM bookmarks WHERE TypeOfEntity=1 AND EntityID=$filmid";
	mysql_query($sql);


}

function SearchDoublesByIncomingField($field){
	$imdbID = "";
	if ($field["ImdbUrlParse"]){
		preg_match("/\/([^\/]*\/)$/",$field["ImdbUrlParse"],$matches);
		$imdbID = $matches[1];
	}
	$originalname = preg_replace("/&#(\d{2,3});/e", "chr(\\1)",$field["imdbOriginalName"]);
	$wheres = array();
	$doubles = array();
	if ($imdbID) $wheres[] = " imdbID='$imdbID' ";
	if ($field["EngName"]) $wheres[] = " OriginalName='".addslashes($field['EngName'])."' ";
	if ($field["rusOriginalName"]) $wheres[] = " OriginalName='".addslashes($field['rusOriginalName'])."' ";
	if ($field["imdbOriginalName"]) $wheres[] = " OriginalName='".addslashes($originalname)."' ";
	if ($field["rusRusName"]) $wheres[] = " Name='".addslashes($field['rusRusName'])."' ";
	if ($field["RusName"]) $wheres[] = " Name='".addslashes($field['RusName'])."' ";

	$where = "WHERE ".implode(" OR ",$wheres);
	$sql = "SELECT ID, Name, OriginalName, Year FROM films $where";
	$result2 = mysql_query($sql);
	while ($result2 && ($field2 = mysql_fetch_assoc($result2))){
		$doubles[] = $field2;
	}
	return $doubles;
}

function inc_hit($userid,$filmid){
	global $config;
	if ($userid && $filmid){
		$hitmethod = isset($config['hitmethod']) ? $config['hitmethod'] : 0;
		switch ($hitmethod){
			case 1:
				mysql_query("UPDATE films SET Hit=Hit+1 WHERE films.ID = $filmid");
			break;
			case 2:
				$ip = ip2long(get_ip());
				$result = mysql_query("SELECT * FROM hits WHERE FilmID=$filmid AND UserID=$ip");
				if (mysql_num_rows($result)==0){
					mysql_query("INSERT INTO hits(FilmID,UserID,DateHit) VALUES($filmid,$ip,NOW())");
					mysql_query("UPDATE films SET Hit=Hit+1 WHERE films.ID = $filmid");
				}
			break;
			case 3:
				session_cache_limiter('none');
				@session_start();
				if (!isset($_SESSION['films'][$filmid])) {
					mysql_query("UPDATE films SET Hit=Hit+1 WHERE films.ID = $filmid");
					$_SESSION['films'][$filmid] = 1;	
				}
			break;
			default:
				mysql_query("UPDATE users SET PlayActivity=PlayActivity+1 WHERE ID=$userid");
				$result = mysql_query("SELECT * FROM hits WHERE FilmID=$filmid AND UserID=$userid");
				if (mysql_num_rows($result)==0){
					mysql_query("INSERT INTO hits(FilmID,UserID,DateHit) VALUES($filmid,$userid,NOW())");
					mysql_query("UPDATE films SET Hit=Hit+1 WHERE films.ID = $filmid");
				}
		}
	}
}
?>
