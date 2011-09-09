<?php
/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: User.php 700 2011-06-10 08:40:53Z macondos $
 */

class Lms_Item_Film
{
    public static function getBayes($ratings, $min = 8, $avg=7.2453)
    {
        $sum = 0;
        $count = count($ratings);
        $result = array();
        $result['detail'] = array(1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>0, 9=>0, 10=>0);
        foreach($ratings as $value){
            $result['detail'][$value]++;
            $sum += $value;
        }
        $averange = $sum/$count;
        $result['averange'] = $averange;
        $result['count'] = $count;
        $result['bayes'] = ($count>=$min) ? $averange*($count/($count+$min)) + $avg*($min/($count+$min)) : null;
        return $result;
    }
    
    public static function updateLocalRating($filmId)
    {
        $db = Lms_Db::get('main');
        $ratings = $db->selectCol("SELECT Rating FROM userfilmratings WHERE FilmID=?d", $filmId);
        
        $rating = self::getBayes($ratings, Lms_Application::getConfig('minratingcount'));
        $db->query(
            "UPDATE films SET LocalRating=?d, CountLocalRating=?d, LocalRatingDetail=? WHERE ID=?d",
            round(10*$rating['bayes']),
            $rating['count'],
            serialize($rating['detail']),
            $filmId
        );
        return $rating;
    }
    
    public static function postProcess(&$rows, $coverWidth = 100)
    {
        foreach ($rows as &$row) {
            if (isset($row["international_name"])) {
                $row["international_name"] = htmlentities($row["international_name"], ENT_NOQUOTES, 'cp1252');
            }
            if (isset($row["covers"])) {
                $covers = array_values(array_filter(
                    preg_split("/(\r\n|\r|\n)/", $row["covers"])
                ));
                $row["cover"] = array_shift($covers);
                if ($row["cover"]) {
                    $row["cover"] = Lms_Application::thumbnail($row["cover"], $width = $coverWidth, $height = 0, $defer = true);
                }
                unset($row["covers"]);
            }
        }
    }
}