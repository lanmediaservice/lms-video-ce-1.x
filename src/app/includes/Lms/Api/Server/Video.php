<?php
/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: Movies.php 700 2011-06-10 08:40:53Z macondos $
 * @package Api
 */
 
/**
 * @package Api
 */
class Lms_Api_Server_Video extends Lms_Api_Server_Abstract
{

    public static function getCatalog($params)
    {
        try {
            $genre = (isset($params['genre'])) ? (int) $params['genre'] : null;
            $country = (isset($params['country'])) ? (int) $params['country'] : null;
            $typeofmovie = (isset($params['typeofmovie'])) ? $params['typeofmovie'] : null;
            $order = (isset($params['order'])) ? (int) $params['order'] : 0;
            $dir = (isset($params['dir'])) ? $params['dir'] : "";
            $offset = (isset($params['offset'])) ? (int) $params['offset'] : 0;
            $size = (isset($params['size'])) ? (int) $params['size'] : 20;

            $join = "";
            $wheres = array();

            $user = Lms_User::getUser();
            $db = Lms_Db::get('main');
            
            if (!$user->isAllowed("film", "moderate")) {
                $wheres[] = " f.Hide=0 ";
            }
            
            if ($genre) {
                $wheres[] = " filmgenres.GenreID=$genre ";
                $join .= " LEFT JOIN filmgenres ON (f.ID = filmgenres.FilmID) ";
            }
            if ($country) {
                $wheres[] = " filmcountries.CountryID=$country ";
                $join .= " LEFT JOIN filmcountries ON (f.ID = filmcountries.FilmID) ";
            }

            if ($typeofmovie) {
                $wheres[] = " f.TypeOfMovie='" . mysql_real_escape_string($typeofmovie) . "' ";
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
                    $orderby .= " rating_personal_value ";
                break;
                case 6:
                    $orderby .= " Hit ";
                break;
                case 7:
                    $orderby .= " AutoRating ";
                break;
                case 8:
                    $orderby .= " Rank ";
                break;
                case 5:
                    $orderby .= " Name ";
                break;
                default:
                    $orderby .= " CreateDate DESC";
            }
            switch ($dir) {
                case 'DESC':
                case 'ASC':
                    $orderby .= " $dir ";
                    break;
                default:
                    $orderby .= " DESC ";
            }

            if ($order==3) {
                $orderby .= " ,CountLocalRating DESC ";
            }
            
            if ($order==1) {
                $orderby .= ", ID DESC";
            } else {
                $orderby .= ", ID";
            }

            $where = (count($wheres)) ? " WHERE ".implode(" AND ",$wheres) : "";

            $avgHit = $db->selectCell('SELECT sum(Hit)/count(*) FROM films WHERE Hide=0');
            $hitThreshold = round($avgHit*Lms_Application::getConfig('hit_factor'));

            $sql = "SELECT f.ID AS ARRAY_KEY,
                f.ID as film_id,
                f.Name as name,
                f.OriginalName as international_name,
                f.Year as year,
                f.CreateDate as create_date,
                f.UpdateDate as update_date,
                ROUND(f.ImdbRating/10, 1) as rating_imdb_value,
                f.Description as description,
                CONCAT(f.BigPosters, '\n', f.Poster) as `covers`,
                f.TypeOfMovie as type_of_movie,
                f.Translation as translation,
                f.Quality as quality,
                f.Hide as hide,
                f.Hit as hit,
                ufr.Rating as rating_personal_value,
                ROUND(f.LocalRating/10, 1) as rating_local_value,
                f.CountLocalRating as rating_local_count
                FROM films f $join LEFT JOIN userfilmratings ufr ON (f.ID = ufr.FilmID AND ufr.UserID=?d) $where $orderby LIMIT ?d, ?d";
            
            $total = 0;
            $films = $db->selectPage(
                $total, $sql, 
                $user->getId(),
                $offset, $size
            );
            $result['total'] = $total;
            $result['offset'] = $offset;
            $result['pagesize'] = $size;
            
            $filmsIds = array_keys($films);
            
            $rows = $db->select(
                "SELECT FilmID, Name FROM filmgenres LEFT JOIN genres ON (genres.ID = filmgenres.GenreID) WHERE filmgenres.FilmID IN(?a)",
                $filmsIds
            );
            foreach ($rows as $row) {
                $filmId = $row['FilmID'];
                $films[$filmId]['genres'][] = $row['Name'];
            }

            $rows = $db->select(
                "SELECT FilmID, Name FROM filmcountries LEFT JOIN countries ON (countries.ID = filmcountries.CountryID) WHERE filmcountries.FilmID IN(?a)",
                $filmsIds
            );
            foreach ($rows as $row) {
                $filmId = $row['FilmID'];
                $films[$filmId]['countries'][] = $row['Name'];
            }

            $rows = $db->select(
                "SELECT FilmID, count(*) as c FROM comments WHERE FilmID IN(?a) AND (ISNULL(ToUserID) OR ToUserID IN(0, ?d) OR UserID=?d) GROUP BY FilmID",
                $filmsIds,
                $user->getId(),
                $user->getId()
            );
            foreach ($rows as $row) {
                $filmId = $row['FilmID'];
                $films[$filmId]['comments_count'] = $row['c'];
            }

            $rows = $db->select(
                "SELECT filmpersones.FilmID, persones.RusName, persones.OriginalName, roles.Role, roles.SortOrder FROM filmpersones LEFT JOIN roles ON (roles.ID = filmpersones.RoleID) LEFT JOIN persones ON (persones.ID = filmpersones.PersonID) WHERE filmpersones.FilmID IN(?a) ORDER BY SortOrder, LENGTH(Images) DESC",
                $filmsIds
            );
            foreach ($rows as $row) {
                $filmId = $row['FilmID'];
                if ($row["Role"]=="режиссер") {
                    if (!isset($films[$filmId]['directors'])) {
                        $films[$filmId]['directors'] = array();
                    }
                    if (count($films[$filmId]['directors'])<=2) {
                        $films[$filmId]['directors'][] = trim($row["RusName"])? $row["RusName"] : $row["OriginalName"];
                    }
                }
                if (in_array($row["Role"], array("актер","актриса"))) {
                    if (!isset($films[$filmId]['cast'])) {
                        $films[$filmId]['cast'] = array();
                    }
                    if (count($films[$filmId]['cast'])<=4) {
                        $films[$filmId]['cast'][] = trim($row["RusName"]) ? $row["RusName"] : $row["OriginalName"];
                    }
                }
            }

            foreach ($films as &$film) {
                $film["popular"] = $film['hit']>$hitThreshold? true : false;
                $film["hide"] = $film['hide']? true : false;
                
                if (Lms_Application::getConfig('short_translation')) {
                    $film["short_translation"] = strtr($film["translation"], Lms_Application::getConfig('short_translation'));
                }

                if (Lms_Application::getConfig('short_description')) {
                    $film["description"] = Lms_Text::tinyString(strip_tags($film["description"]), Lms_Application::getConfig('short_description'), 1);
                } else{
                    unset($film["description"]);
                }
            }
            Lms_Item_Film::postProcess($films, 100);
            
            $result['films'] = array_values($films);

            return new Lms_Api_Response(200, null, $result);
            
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }
    
    public static function getGenres($params)
    {
        try {
            $country = isset($params['country']) ? (int) $params['country'] : null;
            $wheres = array();
            $join = "";

            $user = Lms_User::getUser();
            $db = Lms_Db::get('main');
            
            if (!$user->isAllowed("film", "moderate")) {
                $wheres[] = " films.Hide=0 ";
            }
            if ($country) {
                $wheres[] = " filmcountries.CountryID=$country ";
                $join .= " LEFT JOIN filmcountries ON (films.ID = filmcountries.FilmID) ";
            }
            $where = (count($wheres)) ? " WHERE " . implode(" AND ", $wheres) : "";

            $result["genres"] = array();
            
            $sql = "SELECT genres.ID as id, genres.Name as name, count(*) as count FROM genres INNER JOIN filmgenres ON (genres.ID = filmgenres.GenreID) INNER JOIN films ON (films.ID = filmgenres.filmid) $join $where GROUP BY genres.ID ORDER BY Name";
            $rows = $db->select($sql);
            foreach ($rows as $row) {
                $result["genres"][] = $row;
            }
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    
    public static function getCountries($params)
    {
        try {
            $genre = isset($params['genre']) ? (int) $params['genre'] : null;
            $wheres = array();
            $join = "";

            $user = Lms_User::getUser();
            $db = Lms_Db::get('main');
            
            if (!$user->isAllowed("film", "moderate")) {
                $wheres[] = " films.Hide=0 ";
            }
            if ($genre) {
                $wheres[] = " filmgenres.GenreID=$genre ";
                $join .= " LEFT JOIN filmgenres ON (films.ID = filmgenres.FilmID) ";
            }
            $where = (count($wheres)) ? " WHERE " . implode(" AND ", $wheres) : "";

            $result["countries"] = array();
            
            $sql = "SELECT countries.ID as id, countries.Name as name, count(*) as count FROM countries INNER JOIN filmcountries ON (countries.ID = filmcountries.CountryID) INNER JOIN films ON (films.ID = filmcountries.filmid) $join $where GROUP BY countries.ID ORDER BY Name";
            $rows = $db->select($sql);
            foreach ($rows as $row) {
                $result["countries"][] = $row;
            }
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }
    
    public static function getLastComments($params)
    {
        try {
            $user = Lms_User::getUser();
            $db = Lms_Db::get('main');
            $wheres = array();
            
            $wheres[] = "(ISNULL(ToUserID) OR ToUserID=0 {OR ToUserID=?d OR UserID=?d})";
            if (!$user->isAllowed("film", "moderate")) {
                $wheres[] = " films.Hide=0 ";
            }
            $sql = "SELECT FilmID as film_id, films.Name as name, max(comments.ID) as comment_id "
                 . "FROM comments LEFT JOIN films ON (films.ID = comments.FilmID) "
                 . "WHERE " . implode(' AND ', $wheres) . " "
                 . "GROUP BY comments.FilmID ORDER BY comment_id DESC LIMIT 0,20";
            
            $films = $db->select(
                $sql,
                $user->getId()? $user->getId() : DBSIMPLE_SKIP,
                $user->getId()? $user->getId() : DBSIMPLE_SKIP
            );
            if (count($films)) {
                $maxlength = 80;
                $commentsIds = array();
                foreach ($films as $row) {
                    $commentsIds[] = $row['comment_id'];
                }

                $sql = 'SELECT comments.ID AS ARRAY_KEY, users.Login as user_name, `Date` as `added_at`, `Text` as `text` '
                     . 'FROM comments LEFT JOIN users ON (users.ID = comments.UserID) ' 
                     . 'WHERE comments.ID IN(?a)';
                $comments = $db->select($sql, $commentsIds);

                foreach ($films as &$row) {
                    $commentId = $row['comment_id'];
                    $comment = $comments[$commentId];
                    $row['text'] = Lms_Text::tinyString($comment['text'], 500, 1);
                    $row['user_name'] = $comment["user_name"];
                    $row["added_at"] = $comment["added_at"];
                }
            }
            $result['films'] = $films;
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }
    
    public static function getLastRatings($params)
    {
        try {
            $user = Lms_User::getUser();
            $db = Lms_Db::get('main');
            $wheres = array();
            if (!$user->isAllowed("film", "moderate")) {
                $wheres[] = " films.Hide=0 ";
            }
            $sql = "SELECT FilmID as film_id, films.Name as `name`, userfilmratings.Rating as `rating` "
                 . "FROM userfilmratings LEFT JOIN films ON (films.ID = userfilmratings.FilmID) "
                 . (count($wheres)? "WHERE " . implode(' AND ', $wheres) . " " : "")
                 . "ORDER BY Date DESC LIMIT 0,20";
            
            $ratings = $db->select($sql);
            $result['films'] = $ratings;
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }
        
    public static function getRandomFilm($params)
    {
        try {
            $user = Lms_User::getUser();
            $db = Lms_Db::get('main');

            $avgHit = $db->selectCell('SELECT sum(Hit)/count(*) FROM films WHERE Hide=0');
            $hitThreshold = round($avgHit*Lms_Application::getConfig('hit_factor'));
            $maxFilmId = $db->selectCell('SELECT MAX(ID) FROM films WHERE Hide=0');
            $offsetId = rand(0, $maxFilmId);
            
            $wheres = array();
            if (!$user->isAllowed("film", "moderate")) {
                $wheres[] = " films.Hide=0 ";
            }
            $wheres[] = " films.ID>=$offsetId";
            $sql = "SELECT films.ID as film_id, "
                 . "    films.Name as name, "
                 . "    films.OriginalName as international_name, "
                 . "    films.Year as `year`, "
                 . "    films.BigPosters as big_posters, "
                 . "    films.Poster as poster, "
                 . "    films.Hit as hit "
                 . "FROM films "
                 . (count($wheres)? "WHERE " . implode(' AND ', $wheres) . " " : "")
                 . "LIMIT 1";
            $film = $db->selectRow($sql);
            $result = array();
            if ($film) {
                $film["popular"] = $film['hit']>$hitThreshold? 1 : 0;
                $film["international_name"] = htmlentities($film["international_name"], ENT_NOQUOTES, 'cp1252');
                $covers = array_values(array_filter(array_merge(
                    preg_split("/(\r\n|\r|\n)/", $film["big_posters"]), 
                    preg_split("/(\r\n|\r|\n)/", $film["poster"])
                )));
                $film["cover"] = array_shift($covers);
                if ($film["cover"]) {
                    $film["cover"] = Lms_Application::thumbnail($film["cover"], $width = 100, $height = 0);
                }
                unset($film["poster"]);
                unset($film["big_posters"]);
                $result['film'] = $film;
            }
            
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function getPopFilms($params)
    {
        try {
            $count = isset($params['count']) ? (int) $params['count'] : 10;

            $db = Lms_Db::get('main');
            $sql = "SELECT ID as film_id, Name as `name` FROM films " 
                 . "WHERE films.Hide=0 " 
                 . "ORDER BY Rank DESC LIMIT ?d";
            $films = $db->select($sql, $count);
            $result['films'] = $films;
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function getFilm($params)
    {
        try {
            $filmId = (int) $params['film_id'];
            $user = Lms_User::getUser();
            $db = Lms_Db::get('main');
            
            if ($user->getId()) {
                $db->query('UPDATE users SET ViewActivity=ViewActivity+1 WHERE ID=?d', $user->getId());
            }
            
            $avgHit = $db->selectCell('SELECT sum(Hit)/count(*) FROM films WHERE Hide=0');
            $hitThreshold = round($avgHit*Lms_Application::getConfig('hit_factor'));
            
            $wheres = array();
            if (!$user->isAllowed("film", "moderate")) {
                $wheres[] = " films.Hide=0 ";
            }
            $sql = "SELECT films.ID as film_id, "
                 . "films.Name as name, "
                 . "films.OriginalName as international_name, "
                 . "films.Year as year, "
                 . "films.RunTime as runtime, "
                 . "films.Description as description, "
                 . "films.MPAA as mpaa, "
                 . "films.Resolution as resolution, "
                 . "films.VideoInfo as video_info, "
                 . "films.AudioInfo as audio_info, "
                 . "films.Translation as translation, "
                 . "films.Quality as quality, "
                 . "films.CreateDate as create_date, "
                 . "films.UpdateDate as update_date, "
                 . "ROUND(films.ImdbRating/10, 1) as rating_imdb_value, "
                 . "IF(LENGTH(films.imdbID)>0, CONCAT('http://www.imdb.com/title/', films.imdbID), '') as imdb_url, "
                 . "films.Poster as poster, "
                 . "films.BigPosters as big_posters, "
                 . "films.Trailer as trailer, "
                 . "films.TypeOfMovie as type_of_movie, "
                 . "films.Hide as hide, "
                 . "films.Hit as hit, "
                 . "films.SoundTrack as soundtrack, "
                 . "films.Links as links, "
                 . "films.Present as present, "
                 . "films.Group as `group`, "
                 . "films.Frames as frames, "
                 . "films.SmallFrames as small_frames, "
                 . "ufr.Rating as rating_personal_value, "
                 . "ROUND(films.LocalRating/10, 1) as rating_local_value, "
                 . "films.LocalRatingDetail as rating_local_detail, "
                 . "films.CountLocalRating as rating_local_count, "
                 . "users.Login as moderator "
                 . "FROM films " 
                 . "    LEFT JOIN userfilmratings ufr ON (films.ID = ufr.FilmID AND ufr.UserID=?d) " 
                 . "    LEFT JOIN users ON (films.Moderator=users.ID) WHERE films.ID=?d"
                 . (count($wheres)? " AND " . implode(' AND ', $wheres) . " " : "");
            $film = $db->selectRow($sql, $user->getId(), $filmId);
            $result = array();
            if ($film) {
                $film["popular"] = $film['hit']>$hitThreshold? true : false;
                $film["hide"] = $film['hide']? true : false;
                $film["international_name"] = htmlentities($film["international_name"], ENT_NOQUOTES, 'cp1252');

                $film["genres"] = $db->selectCol('SELECT Name FROM filmgenres LEFT JOIN genres ON (genres.ID = filmgenres.GenreID) WHERE filmgenres.FilmID=?d', $filmId);
                $film["countries"] = $db->selectCol('SELECT Name FROM filmcountries LEFT JOIN countries ON (countries.ID = filmcountries.CountryID) WHERE filmcountries.FilmID=?d', $filmId);
                

                $rows = $db->select(
                    "SELECT persones.ID as person_id, persones.RusName as name, persones.OriginalName as international_name, persones.Images as old_photos, persones.Photos as photos, roles.Role as role, filmpersones.RoleExt as `character` FROM filmpersones LEFT JOIN roles ON (roles.ID = filmpersones.RoleID) LEFT JOIN persones ON (persones.ID = filmpersones.PersonID) WHERE filmpersones.FilmID=?d ORDER BY SortOrder, LENGTH(Images) DESC",
                    $filmId
                );
                $persones = array();
                foreach ($rows as $row) {
                    $personId = $row['person_id'];
                    if ($row["role"]=="режиссер") {
                        $film['directors'][] = trim($row["name"])? $row["name"] : $row["international_name"];
                    }
                    $persones[$personId]['person_id'] = $personId;
                    $persones[$personId]['name'] = $row["name"];
                    $persones[$personId]['international_name'] = $row["international_name"];
                    $persones[$personId]['names'] = array_values(array_filter(array(trim($row["name"]), trim($row["international_name"]))));
                    $photos = array_values(array_filter(array_merge(
                        preg_split("/(\r\n|\r|\n)/", $row["photos"]), 
                        preg_split("/(\r\n|\r|\n)/", $row["old_photos"])
                    )));
                    $photo = array_shift($photos);
                    if ($photo) {
                        $photo = Lms_Application::thumbnail($photo, $width = 90, $height = 0, $defer = true);
                    }
                    $persones[$personId]['photo'] = $photo;
                    $persones[$personId]['roles'][] = array("role" => $row["role"], "character" => $row["character"]);;
                }
                $film['persones'] = array_values($persones);
                
                
                $covers = array_values(array_filter(array_merge(
                    preg_split("/(\r\n|\r|\n)/", $film["big_posters"]), 
                    preg_split("/(\r\n|\r|\n)/", $film["poster"])
                )));
                
                $film["cover"] = array_shift($covers);
                if ($film["cover"]) {
                    $film["cover"] = Lms_Application::thumbnail($film["cover"], $width = 200, $height = 0);
                }
               

                $film["frames"] = preg_split("/(\r\n|\r|\n)/", $film["frames"]); 
                if ($film["frames"]) {
                    //TODO: delete frame_width, frame_height
                    list($width, $height)= getimagesize(dirname(APP_ROOT) . '/' . $film["frames"][0]); 
                    $film["frame_width"] = $width;
                    $film["frame_height"] = $height;
                    $film["small_frames"] = array();
                    foreach ($film["frames"] as $frame) {
                        $film["small_frames"][] = Lms_Application::thumbnail($frame, $width = 225, $height);
                        $film["small_frame_width"] = $width;
                        $film["small_frame_height"] = $height;
                    }
                }
                
                $film["comments_count"] = $db->selectCell(
                    "SELECT count(*) as count FROM comments WHERE FilmID=?d AND (ISNULL(ToUserID) OR ToUserID=0 {OR ToUserID=?d OR UserID=?d})",
                    $filmId,
                    $user->getId()? $user->getId() : DBSIMPLE_SKIP,
                    $user->getId()? $user->getId() : DBSIMPLE_SKIP
                );

                $rows = $db->select("SELECT ID as file_id, Name as name, Path as path, Size as `size`, ed2kLink as ed2k_link, dcppLink as dcpp_link FROM files WHERE FilmID=?d ORDER BY Path", $filmId);
                $files = array();
                foreach ($rows as $row) {
                    $links = array();
                    if (Lms_Application::getConfig('download', 'license')) {
                        $v = Lms_Application::getLeechProtectionCode(array($filmId, $row["file_id"], $user->getId()));
                        $links['license'] = "pl.php?player=ftp&uid=" . $user->getId() . "&filmid=$filmId&fileid=" . $row["file_id"] . "&v=$v";
                    } else {
                        $links['download'] = str_replace(Lms_Application::getConfig('source'), Lms_Application::getConfig('ftp'), $row['path']);
                        if ($encoding = Lms_Application::getConfig('download', 'escape', 'encoding')) {
                            $links['download'] = Lms_Translate::translate('CP1251', $encoding, $links['download']);
                        }
                        if (Lms_Application::getConfig('download', 'escape', 'enabled')) {
                            $t = explode("/", $links['download']);
                            for ($i=3; $i<count($t); $i++) {
                                $t[$i] = rawurlencode($t[$i]);
                            }
                            $links['download'] = implode("/", $t);
                        }
                    }
                    if ($row['ed2k_link']) {
                        $links['ed2k'] = $row['ed2k_link'];
                    }
                    if ($row['dcpp_link']) {
                        $links['dcpp'] = $row['dcpp_link'];
                    }
                    
                    $files[] = array(
                        'file_id' => $row['file_id'],
                        'name' => $row['name'],
                        'size' => $row['size'],
                        'links' => $links,
                    );
                }
                $film['files'] = $files;
                if (Lms_Application::getConfig('smb')) {
                    $mode = $user->getMode();
                    if (Lms_Application::getConfig('modes', $mode, 'smb')) {
                        $film['smb'] = 1;
                    }
                }
                

                if ($film["group"]) {
                    $films = $db->select(
                        "SELECT ID as film_id, Name as name, "
                          . "   OriginalName as international_name, "
                          . "   Year as year, "
                          . "   CONCAT(BigPosters, '\n', Poster) as `covers` "
                          . "FROM films "
                          . "WHERE `Group`=? AND ID!=?d {AND films.Hide=?d} "
                          . "ORDER BY Year",
                        $film["group"],
                        $filmId,
                        $user->isAllowed("film", "moderate")? DBSIMPLE_SKIP : 0
                    );
                    Lms_Item_Film::postProcess($films, 90);
                    $film['other_films'] = $films;
                }
            } else {
                $film = null;
            }
            $result['film'] = $film;
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
        
    }
    
    public static function getPerson($params) 
    {
        try {
            $personId = (int) $params['person_id'];
            $sql = "SELECT ID as `person_id`, " 
                 . "    RusName as `name`, " 
                 . "    OriginalName as `international_name`, " 
                 . "    Description as `description`, " 
                 . "    IF(LENGTH(Images)>LENGTH(Photos), Images, Photos) as photos, " 
                 . "    OzonUrl as `url` " 
                 . "FROM persones WHERE ID=?d";
            $user = Lms_User::getUser();
            $db = Lms_Db::get('main');

            $person = $db->selectRow($sql, $personId);
            if (!$person) {
                return new Lms_Api_Response(404);
            }
            $person["description"] = Lms_Text::htmlizeText($person["description"]);

            $person["photos"] = preg_split("/(\r\n|\r|\n)/", $person["photos"]);
            foreach ($person["photos"] as &$photo) {
                $photo = Lms_Application::thumbnail($photo, $width = 120, $height = 0, $defer = true);
            }

            $wheres = array();
            if (!$user->isAllowed("film", "moderate")) {
                $wheres[] = " films.Hide=0 ";
            }

            $sql = "SELECT DISTINCT filmpersones.FilmID as film_id, "
                 . "    films.Name as `name`, "
                 . "    films.Year as `year`, "
                 . "    CONCAT(films.BigPosters, '\n', films.Poster) as `covers`, "
                 . "    roles.Role as `role` " 
                 . "FROM filmpersones INNER JOIN roles ON (filmpersones.RoleID = roles.ID) INNER JOIN films ON (filmpersones.FilmID = films.ID) " 
                 . "WHERE filmpersones.PersonID=?d " . (count($wheres)? " AND " . implode(' AND ', $wheres) . " " : "") 
                 . "ORDER BY films.Year, SortOrder";

            $rows = $db->select($sql, $personId);
            $films = array();
            foreach ($rows as $row) {
                $filmId = $row['film_id'];
                $films[$filmId]["film_id"] = $filmId;
                $films[$filmId]["name"] = $row["name"];
                $films[$filmId]["year"] = $row["year"];
                $films[$filmId]["covers"] = $row["covers"];
                $films[$filmId]["roles"][] = $row["role"];
            }
            /*
            foreach ($films as &$film) {
                $covers = array_values(array_filter(
                    preg_split("/(\r\n|\r|\n)/", $film["covers"])
                ));
                $film["cover"] = array_shift($covers);
                if ($film["cover"]) {
                    $film["cover"] = Lms_Application::thumbnail($film["cover"], $width = 30, $height = 0);
                }
                unset($film["covers"]);
            }
            */
            $person['films'] = array_values($films);
            $result['person'] = $person;
            
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
        
    }

    public static function getComments($params)
    {
        try {
            $filmId = (int) $params['film_id'];
            $user = Lms_User::getUser();
            $db = Lms_Db::get('main');

            $wheres = array();
            $wheres[] = "films.ID=?d";
            $wheres[] = "(ISNULL(ToUserID) OR ToUserID=0 {OR ToUserID=?d OR UserID=?d})";
            if (!$user->isAllowed("film", "moderate")) {
                $wheres[] = " films.Hide=0 ";
            }
            $sql = "SELECT comments.ID as comment_id, "
                 . "    comments.UserID as user_id, "
                 . "    users.Login as user_name, "
                 . "    `Text` as `text`, "
                 . "    `Date` as `posted_at`, "
                 . "    u.Login as to_user_name, "
                 . "    comments.ip as ip "
                 . "FROM comments "
                 . "    INNER JOIN films ON (films.ID = comments.FilmID) "
                 . "    INNER JOIN users ON (users.ID = comments.UserID) "
                 . "    LEFT JOIN users u ON (u.ID = comments.ToUserID) "
                 . "WHERE " . implode(' AND ', $wheres) . " "
                 . "ORDER BY `Date`";

            $comments = $db->select(
                $sql,
                $filmId,
                $user->getId()? $user->getId() : DBSIMPLE_SKIP,
                $user->getId()? $user->getId() : DBSIMPLE_SKIP
            );
            foreach ($comments as &$comment) {
                //$field["text"] = preg_replace("/(\r\n|\r|\n)/","<br>",$field["Text"]);
            }
            if (!$user->isAllowed("comment", "edit")) {
                foreach ($comments as &$comment) {
                    unset($comment['ip']);
                }
            }
            $result['comments'] = $comments;
            $result['film_id'] = $filmId;
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }
            
    public static function getSuggestion($params)
    {
        try {
            $db = Lms_Db::get('main');
            $query = $params['query'];
            
            $cell = $db->selectCell('SELECT `result` FROM `suggestion_cache` WHERE `query` LIKE ?', $query);
            if ($cell) {
                $suggestion = Zend_Json::decode($cell);
            } else {
                $suggestion = Lms_Application::getSuggestion($query);
            }
            
            if ($suggestion['films']) {
                $sql = "SELECT `ID` as film_id , "
                     . "    Name as name, "
                     . "    OriginalName as `international_name`, " 
                     . "    Year as year, "
                     . "    CONCAT(BigPosters, '\n', Poster) as `covers` "
                     . "FROM `films`"
                     . "WHERE ID IN(?a) "
                     . "ORDER BY rank DESC";
                $films = $db->select($sql, $suggestion['films']);

                foreach ($films as &$film) {
                    $film["international_name"] = htmlentities($film["international_name"], ENT_NOQUOTES, 'cp1252');
                    $covers = array_values(array_filter(
                        preg_split("/(\r\n|\r|\n)/", $film["covers"])
                    ));
                    $film["cover"] = array_shift($covers);
                    if ($film["cover"]) {
                        $film["cover"] = Lms_Application::thumbnail($film["cover"], $width = 40, $height = 0, $defer = true);
                    }
                    unset($film["covers"]);
                }
            } else {
                $films = array();
            }
            
            if ($suggestion['persones']) {
                $sql = "SELECT ID as person_id , "
                     . "    RusName as `name`, " 
                     . "    OriginalName as `international_name`, " 
                     . "    Description as `description`, " 
                     . "    IF(LENGTH(Images)>LENGTH(Photos), Images, Photos) as photos "
                     . "FROM persones "
                     . "WHERE ID IN(?a) "
                     . "ORDER BY rank DESC";

                $persones = $db->select($sql, $suggestion['persones']);
                foreach ($persones as &$person) {
                    $photos = array_values(array_filter(
                        preg_split("/(\r\n|\r|\n)/", $person["photos"])
                    ));
                    $person["photo"] = array_shift($photos);
                    if ($person["photo"]) {
                        $person["photo"] = Lms_Application::thumbnail($person["photo"], $width = 40, $height = 0, $defer = true);
                    }
                    unset($person["photos"]);
                }
            } else {
                $persones = array();
            }
            $result['query'] = $query;
            $result['films'] = $films;
            $result['persones'] = $persones;
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function search($params)
    {
        try {
            $db = Lms_Db::get('main');
            $query = $params['query'];
            
            $words = preg_split('{\s+}i', $query);
            
            $queryLength = Lms_Text::length($query);
            if ($queryLength>=3) {
                $wheres = array();
                $wheresLike = array();
                $trigramCount = 0;
                for ($i=0; $i<=$queryLength-3; $i++) {
                    $trigram = strtolower(substr($query, $i, 3));
                    $wheresLike[] = "`trigram`='" . mysql_real_escape_string($trigram) . "'";
                    $trigramCount++;
                }
                $wheres[] = "(". implode(' OR ', $wheresLike) .")";
                $wheres[] = "`type` = 'film'";
                $wheres[] = " f.Hide=0 ";

                $sql = "SELECT s.id as film_id, "
                     . "    Name as name, "
                     . "    OriginalName as `international_name`, " 
                     . "    Year as year, "
                     . "    CONCAT(BigPosters, '\n', Poster) as `covers` "
                     . "FROM `search_trigrams` s "
                     . "INNER JOIN `films` f ON(s.id=f.ID) "
                     . "WHERE " . implode(' AND ', $wheres) . " "
                     . "GROUP BY s.id "
                     . "HAVING count(*)>=?d "
                     . "ORDER BY count(*) DESC, Rank DESC LIMIT ?d";
                $films = $db->select($sql, floor(0.66*$trigramCount), 20);
            } else {
                $wheres = array();
                $joins = array();
                foreach ($words as $n => $word) {
                    $table = "s$n";
                    $joins[] = "INNER JOIN suggestion $table ON ($table.id = f.ID) ";
                    $wheres[] = "$table.`word` LIKE '" . mysql_real_escape_string($word) . "%'";
                    $wheres[] = "$table.`type` = 'film'";
                }
                $wheres[] = " f.Hide=0 ";

                $sql = "SELECT DISTINCT f.`id` as film_id, "
                     . "    Name as name, "
                     . "    OriginalName as `international_name`, " 
                     . "    Year as year, "
                     . "    CONCAT(BigPosters, '\n', Poster) as `covers` "
                     . "FROM `films` f "
                     . implode(' ', $joins) . " "
                     . "WHERE " . implode(' AND ', $wheres) . " "
                     . "ORDER BY rank DESC LIMIT ?d";
                $films = $db->select($sql, 20);
            }
          
            if ($queryLength>=3) {
                $wheres = array();
                $wheresLike = array();
                $trigramCount = 0;
                for ($i=0; $i<=$queryLength-3; $i++) {
                    $trigram = strtolower(substr($query, $i, 3));
                    $wheresLike[] = "`trigram`='" . mysql_real_escape_string($trigram) . "'";
                    $trigramCount++;
                }
                $wheres[] = "(". implode(' OR ', $wheresLike) .")";
                $wheres[] = "`type` = 'person'";

                $sql = "SELECT p.ID as person_id , "
                     . "    RusName as `name`, " 
                     . "    OriginalName as `international_name`, " 
                     . "    Description as `description`, " 
                     . "    IF(LENGTH(Images)>LENGTH(Photos), Images, Photos) as photos "
                     . "FROM `search_trigrams` s "
                     . "INNER JOIN `persones` p ON(s.id=p.ID) "
                     . "WHERE " . implode(' AND ', $wheres) . " "
                     . "GROUP BY p.id "
                     . "HAVING count(*)>=?d "
                     . "ORDER BY count(*) DESC, Rank DESC LIMIT ?d";
                $persones = $db->select($sql, floor(0.66*$trigramCount), 20);
            } else {
                $wheres = array();
                $joins = array();
                foreach ($words as $n => $word) {
                    $table = "s$n";
                    $joins[] = "INNER JOIN suggestion $table ON ($table.id = p.ID) ";
                    $wheres[] = "$table.`word` LIKE '" . mysql_real_escape_string($word) . "%'";
                    $wheres[] = "$table.`type` = 'person'";
                }

                $sql = "SELECT p.ID as person_id , "
                     . "    RusName as `name`, " 
                     . "    OriginalName as `international_name`, " 
                     . "    Description as `description`, " 
                     . "    IF(LENGTH(Images)>LENGTH(Photos), Images, Photos) as photos "
                     . "FROM persones p "
                     . implode(' ', $joins) . " "
                     . "WHERE " . implode(' AND ', $wheres) . " "
                     . "ORDER BY rank DESC LIMIT ?d";
                $persones = $db->select($sql, 20);
            }
            
            $result['films'] = array();
            foreach ($films as $film) {
                if (self::matchStrings($query, $film['name']) || self::matchStrings($query, $film['international_name'])) {
                    $result['films'][] = $film;
                }
            }
            $result['persones'] = array();
            foreach ($persones as $person) {
                if (self::matchStrings($query, $person['name']) || self::matchStrings($query, $person['international_name'])) {
                    $result['persones'][] = $person;
                }
            }
            
            foreach ($result['films'] as &$film) {
                $film["international_name"] = htmlentities($film["international_name"], ENT_NOQUOTES, 'cp1252');
                $covers = array_values(array_filter(
                    preg_split("/(\r\n|\r|\n)/", $film["covers"])
                ));
                $film["cover"] = array_shift($covers);
                if ($film["cover"]) {
                    $film["cover"] = Lms_Application::thumbnail($film["cover"], $width = 40, $height = 0, $defer = true);
                }
                unset($film["covers"]);
            }
            
            foreach ($result['persones'] as &$person) {
                $photos = array_values(array_filter(
                    preg_split("/(\r\n|\r|\n)/", $person["photos"])
                ));
                $person["photo"] = array_shift($photos);
                if ($person["photo"]) {
                    $person["photo"] = Lms_Application::thumbnail($person["photo"], $width = 40, $height = 0, $defer = true);
                }
                unset($person["photos"]);
            }
           
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }
    
    public static function getBestsellers()
    {
        try {
            $db = Lms_Db::get('main');
            
            $bestsellers = $db->select('SELECT category_id, name, films FROM `bestsellers` ORDER BY rank DESC');
            foreach ($bestsellers as &$bestseller) {
                $filmsIds = Zend_Json::decode($bestseller['films']);

                $sql = "SELECT `ID` as film_id , "
                     . "    Name as name, "
                     . "    OriginalName as `international_name`, " 
                     . "    Year as year, "
                     . "    CONCAT(BigPosters, '\n', Poster) as `covers` "
                     . "FROM `films`"
                     . "WHERE ID IN(?a) "
                     . "ORDER BY rank DESC";
                $films = $db->select($sql, $filmsIds);

                foreach ($films as &$film) {
                    $film["international_name"] = htmlentities($film["international_name"], ENT_NOQUOTES, 'cp1252');
                    $covers = array_values(array_filter(
                        preg_split("/(\r\n|\r|\n)/", $film["covers"])
                    ));
                    $film["cover"] = array_shift($covers);
                    if ($film["cover"]) {
                        $film["cover"] = Lms_Application::thumbnail($film["cover"], $width = 120, $height = 0, $defer = true);
                    }
                    unset($film["covers"]);
                }
                $bestseller['films'] = $films;
            }
            $result['bestsellers'] = $bestsellers;
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    
    private static function maxLevensteinDistance($length)
    {
	$res = 2;
	switch (true) {
            case $length<=3:
                $res = 0;
                break;
            case $length<=7:
                $res = 1;
                break;
            default:
                $res = round($length*0.25);
                break;
	}
	return $res;
    }

    private static function compareStrings($str1, $str2)
    {
        $str1 = strtolower($str1);
        $str2 = strtolower($str2);
	$distance = 255;
	$j = 0;
	while ($j <= (strlen($str2) - strlen($str1))) {
            $distance = min($distance, levenshtein($str1, substr($str2, $j, strlen($str1))));
            if ($distance == 0) break;
            $j++;
	}
	return $distance;
    } 

    private static function matchStrings($str1, $str2)
    {
        return (self::compareStrings($str1, $str2)<=self::maxLevensteinDistance(Lms_Text::length($str1)));
    } 

    public static function changePassword($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();
            if (!$user->isAllowed("user", "edit")) {
                return new Lms_Api_Response(403, 'Forbidden');
            }

            $oldPassword = md5($params['password_old']);
            if ($user->getPassword()!=md5($params['password_old'])) {
                return new Lms_Api_Response(400, 'Старый пароль введен не верно');
            }
            $errors = array();
            $newPassword = $params['password_new'];
            if (strlen($newPassword) < 3) {
                $errors[] = "Ошибка. Пароль содержит менее 3 символов.";
            }
            if (strlen($newPassword) > 16) {
                $errors[] = "Ошибка. Пароль содержит более 16 символов.";
            }
            if (!preg_match('{^[a-z0-9][a-z0-9]*[a-z0-9]$}i', $newPassword)) {
                $errors[] = "Ошибка. Пароль должен состоять только из латинских букв или цифр.";
            }
            if (!count($errors)) {
                $db->query("UPDATE users SET Password=? WHERE ID=?d", md5($newPassword), $user->getId());
                $_SESSION['pass'] = $newPassword;
                return new Lms_Api_Response(200);
            } else {
                return new Lms_Api_Response(400, implode(" ", $errors));
            }
            
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function logoff($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }


    public static function addBookmark($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();
            if (!$user->getId()) {
                return new Lms_Api_Response(401, 'Unauthorized');
            }
            if (!$user->isAllowed("bookmark", "add")) {
                return new Lms_Api_Response(403, 'Forbidden');
            }
            $filmId = $params['film_id'];
            $film = $db->select("SELECT * FROM films WHERE ID=?d", $filmId);
            if ($film) {
                $db->query(
                    "INSERT INTO bookmarks(UserID,TypeOfEntity,EntityID) VALUES(?d,1,?d)",
                    $user->getId(), $filmId
                );
                return self::getBookmarks();
            }
            
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function deleteBookmark($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();
            if (!$user->getId()) {
                return new Lms_Api_Response(401, 'Unauthorized');
            }
            if (!$user->isAllowed("bookmark", "delete")) {
                return new Lms_Api_Response(403, 'Forbidden');
            }
            $filmId = $params['film_id'];
            $sql = "DELETE FROM bookmarks WHERE UserID=?d AND TypeOfEntity=1 AND EntityID=?d";
            $db->query($sql, $user->getId(), $filmId);
            return new Lms_Api_Response(200);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function getBookmarks()
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();
            if (!$user->getId()) {
                return new Lms_Api_Response(401, 'Unauthorized');
            }
            if (!$user->isAllowed("bookmark", "view")) {
                return new Lms_Api_Response(403, 'Forbidden');
            }
            
            $sql = "SELECT EntityID as film_id, "
                 . "    Name as name, "
                 . "    OriginalName as `international_name`, " 
                 . "    Year as year, "
                 . "    CONCAT(BigPosters, '\n', Poster) as `covers` "
                 . "FROM bookmarks INNER JOIN films ON (films.ID = EntityID) " 
                 . "WHERE TypeOfEntity=1 AND UserID=?d ORDER BY bookmarks.ID DESC";
            $films = $db->select($sql, $user->getId());
            
            foreach ($films as &$film) {
                $film["international_name"] = htmlentities($film["international_name"], ENT_NOQUOTES, 'cp1252');
                $covers = array_values(array_filter(
                    preg_split("/(\r\n|\r|\n)/", $film["covers"])
                ));
                $film["cover"] = array_shift($covers);
                if ($film["cover"]) {
                    $film["cover"] = Lms_Application::thumbnail($film["cover"], $width = 16, $height = 0, $defer = true);
                }
                unset($film["covers"]);
            }
            $result['films'] = $films;
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function postComment($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();

            if (!$user->isAllowed("comment", "post")) {
                return new Lms_Api_Response(403, 'Forbidden');
            }
            
            $filmId = $params['film_id'];
            $text = trim($params['text']);
            
            $film = $db->select("SELECT * FROM films WHERE ID=?d", $filmId);
            if ($film && $text) {
                $db->query(
                    "INSERT INTO comments SET UserID=?, FilmID=?d, Text=?, `Date`=NOW(), `ip`=?",
                    $user->getId(), 
                    $filmId,
                    $text,
                    Lms_Ip::getIp()
                );
                return self::getComments(array('film_id'=>$filmId));
            }
            return new Lms_Api_Response(503);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function editComment($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();
            if (!$user->isAllowed("comment", "edit")) {
                return new Lms_Api_Response(403, 'Forbidden');
            }
            $commentId = $params['comment_id'];
            $text = trim($params['text']);
            $db->query(
                "UPDATE comments SET Text=? WHERE ID=?d",
                $text,
                $commentId
            );
            return new Lms_Api_Response(200);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function deleteComment($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();
            if (!$user->isAllowed("comment", "delete")) {
                return new Lms_Api_Response(403, 'Forbidden');
            }
            
            $commentId = $params['comment_id'];
            $db->query("DELETE FROM comments WHERE ID=?d", $commentId);
            return new Lms_Api_Response(200);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function setRating($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();

            if (!$user->isAllowed("rating")) {
                return new Lms_Api_Response(403, 'Forbidden');
            }

            $filmId = $params['film_id'];
            $rating = $params['rating'];
            if ($filmId && ($rating>0) && ($rating<=10)){
                $film = $db->selectRow("SELECT ID FROM films WHERE ID=?d", $filmId);
                if ($film){
                    $sql = "INSERT INTO userfilmratings SET UserID=?d, FilmID=?d, Rating=?d, `Date`=NOW() ON DUPLICATE KEY UPDATE Rating=?d, `Date`=NOW()";
                    $db->query(
                        $sql,
                        $user->getId(),
                        $filmId,
                        $rating,
                        $rating
                    );
                }
            } else if ($filmId && $rating==0) {
                $db->query(
                    'DELETE FROM userfilmratings WHERE UserID=?d AND FilmID=?d',
                    $user->getId(),
                    $filmId
                );
            }
            $localRating = Lms_Item_Film::updateLocalRating($filmId);
            $result = array(
                'rating_local_value' => $localRating['bayes'] ? round($localRating['bayes'],1) : null,
                'rating_local_count' => $localRating['count'],
                'rating_local_detail' => $localRating['detail'],
                'rating_personal_value' => $rating
            );
            
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function setFilmField($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();
            if (!$user->isAllowed("film", "moderate")) {
                return new Lms_Api_Response(403);
            }
            $filmId = $params['film_id'];
            $field = $params['field'];
            $value = $params['value'];
            $db->query('UPDATE films SET ?#=? WHERE ID=?d', $field, $value, $filmId);
            return new Lms_Api_Response(200);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function getRandomText($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function sendOpinion($params)
    {
        try {
            $db = Lms_Db::get('main');
            $text = trim($params['text']);
            $db->query(
                'INSERT INTO opinions SET `ip`=?, `text`=?, posted_at=NOW(), user_agent=?', 
                Lms_Ip::getIp(), 
                $text,
                $_SERVER['HTTP_USER_AGENT']
            );
            return new Lms_Api_Response(200);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }
    
    public static function hitFilm($params)
    {
        try {
            $db = Lms_Db::get('main');
            $filmId = (int) $params['film_id'];
            Lms_Application::hitFilm($filmId);
            return new Lms_Api_Response(200);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }
}
