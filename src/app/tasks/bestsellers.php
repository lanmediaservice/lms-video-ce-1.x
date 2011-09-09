#!/usr/local/bin/php -q
<?php

require_once dirname(__FILE__) . '/include/init.php';

function genre($name) 
{
    static $genres;
    if (!$genres) {
        $db = Lms_Db::get('main');
        $genres = $db->selectCol('SELECT imdbGenre AS ARRAY_KEY, ID FROM genres');
    }
    if (array_key_exists($name, $genres)) {
        return $genres[$name];
    } else {
        return false;
    }
}

function genreExist($genreName, $filmId)
{
    static $filmgenres;
    if (!$filmgenres) {
        $db = Lms_Db::get('main');
        $rows = $db->select('SELECT FilmID as film_id, GenreID as genre_id FROM filmgenres');
        $filmgenres = array();
        foreach ($rows as $row) {
            $filmgenres[$row['film_id']][] = $row['genre_id'];
        }
    }
    if (!isset($filmgenres[$filmId])) {
        return false;
    }
    return in_array(genre($genreName), $filmgenres[$filmId]);
}

$db = Lms_Db::get('main');


echo "\nPrepare ...";
$db->query('TRUNCATE `bestsellers`');

$filmMonthPopularity = $db->selectCol("SELECT ID as ARRAY_KEY, Rank FROM films"); 
$sql = "SELECT ID as ARRAY_KEY, "
     . "    ID as film_id, "
     . "    TypeOfMovie as type_of_movie, "
     . "    Rank as rank "
     . "FROM films WHERE films.Hide=0 "; 
$films = $db->select($sql);

//calculate categories 
$categoryIndex = array(); 
define('CATEGORY_SERIES',1); 
define('CATEGORY_COMEDY',2); 
define('CATEGORY_ACTION',3); 
define('CATEGORY_DOCUMENTARY',4); 
define('CATEGORY_ANIME',5); 
define('CATEGORY_ANIMATION',6); 
define('CATEGORY_HORROR',7); 
define('CATEGORY_FANTASY_SCFI',8); 
define('CATEGORY_DRAMA',9); 

$categoryNames = array( 
  CATEGORY_SERIES => 'Сериалы', 
  CATEGORY_COMEDY => 'Комедии', 
  CATEGORY_ACTION => 'Боевики', 
  CATEGORY_DOCUMENTARY => 'Документальные', 
  CATEGORY_ANIME => 'Аниме', 
  CATEGORY_ANIMATION => 'Мультфильмы', 
  CATEGORY_HORROR => 'Ужасы', 
  CATEGORY_FANTASY_SCFI => 'Фантастика/фэнтези', 
  CATEGORY_DRAMA => 'Драма/мелодрама' 
); 
echo " OK";

echo "\nSorting ...";
$n = 0;
foreach ($films as $filmId => $film) { 
    $weight = $film['rank'];
    if ($film['type_of_movie']=='Худ. телесериал' || genreExist('Series', $filmId)) { 
          $categoryIndex[CATEGORY_SERIES][$filmId] = $weight; 
    } elseif (genreExist('Documentary', $filmId)) { 
          $categoryIndex[CATEGORY_DOCUMENTARY][$filmId] = $weight; 
    } elseif (genreExist('Anime', $filmId)) { 
          $categoryIndex[CATEGORY_ANIME][$filmId] = $weight; 
    } elseif (genreExist('Animation', $filmId)) { 
          $categoryIndex[CATEGORY_ANIMATION][$filmId] = $weight; 
    } elseif (genreExist('Comedy', $filmId) && !genreExist('Thriller', $filmId) && !genreExist('Horror', $filmId)) { 
          $categoryIndex[CATEGORY_COMEDY][$filmId] = $weight; 
    } elseif ((genreExist('Fantasy', $filmId) || genreExist('Sci-Fi', $filmId))) { 
          $categoryIndex[CATEGORY_FANTASY_SCFI][$filmId] = $weight; 
    } elseif (genreExist('Horror', $filmId)) { 
          $categoryIndex[CATEGORY_HORROR][$filmId] = $weight; 
    } elseif (genreExist('Action', $filmId) || genreExist('Thriller', $filmId) || genreExist('War', $filmId) || genreExist('Adventure', $filmId)) { 
          $categoryIndex[CATEGORY_ACTION][$filmId] = $weight; 
    } elseif ((genreExist('Drama', $filmId) || genreExist('Romance', $filmId)) && !genreExist('War', $filmId) && !genreExist('Adventure', $filmId)) { 
          $categoryIndex[CATEGORY_DRAMA][$filmId] = $weight; 
    }
    if (!($n % 1000) ) {
        echo "..$n..";
    }
    $n++;
} 
echo " OK";

echo "\nSave ...";
$selectedFilms = array(); 
$categoriesWeight = array(); 
foreach ($categoryIndex as $categoryId => $categoryContent) { 
   arsort($categoryContent); 
   $c = 0; 
   foreach ($categoryContent as $filmId => $weight) { 
       $c++; 
       if ($c>5) {
           break; 
       }
       @$categoriesWeight[$categoryId] += $weight; 
       $selectedFilms[$categoryId][] = $filmId; 
   } 
} 
arsort($categoriesWeight); 

foreach ($selectedFilms as $categoryId => $films) {
    $row = array(
        'category_id' => $categoryId,
        'name' => $categoryNames[$categoryId],
        'films' => Zend_Json::encode($films),
        'rank' => $categoriesWeight[$categoryId],
    );
    $db->query('INSERT INTO bestsellers SET ?a', $row);
}
echo " OK";

echo "\nDone\n";

require_once dirname(__FILE__) . '/include/end.php'; 
