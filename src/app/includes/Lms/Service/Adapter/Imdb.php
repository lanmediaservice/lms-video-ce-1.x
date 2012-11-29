<?php
/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: Kinopoisk.php 700 2011-06-10 08:40:53Z macondos $
 */

class Lms_Service_Adapter_Imdb
{
    static private $rolesMap = array(
        'director' => '��������',
        'actor' => '�����'
    );
    static private $genresMap = array(
        "Action" => "������",
        "Adventure" => "�����������",
        "Animation" => "����������",
        "Biography" => "���������",
        "Comedy" => "�������",
        "Crime" => "��������",
        "Documentary" => "��������������",
        "Drama" => "�����",
        "Family" => "��������",
        "Fantasy" => "�������",
        "Film-Noir" => "�����-����",
        "Game-Show" => "������� ���",
        "History" => "�������",
        "Horror" => "�����",
        "Music" => "������",
        "Musical" => "������",
        "Mystery" => "�������",
        "News" => "�������",
        "Reality-TV" => "����-��",
        "Romance" => "���������",
        "Sci-Fi" => "����������",
        "Short" => "����������������",
        "Sport" => "�����",
        "Talk-Show" => "���-���",
        "Thriller" => "�������",
        "War" => "�����",
        "Western" => "�������",
    );
    
    static private $countriesMap = array(
        "France" => "�������",
        "UK" => "��������������",
        "USA" => "���",
        "Soviet Union" => "����",
        "Romania" => "�������",
        "Switzerland" => "���������",
        "Russia" => "������",
        "Australia" => "���������",
        "West Germany" => "���",
        "Thailand" => "�������",
        "China" => "�����",
        "Hong Kong" => "��������",
        "Mexico" => "�������",
        "Italy" => "������",
        "Spain" => "�������",
        "Germany" => "��������",
        "Japan" => "������",
        "Canada" => "������",
        "South Korea" => "����� �����",
        "Netherlands" => "����������",
        "Taiwan" => "�������",
        "Hungary" => "�������",
        "Ireland" => "��������",
        "Poland" => "������",
        "Czech Republic" => "�����",
        "Denmark" => "�����",
        "Sweden" => "������",
        "Norway" => "��������",
        "Finland" => "���������",
        "New Zealand" => "����� ��������",
        "South Africa" => "���",
        "Ukraine" => "�������",
        "Belgium" => "�������",
        "Luxembourg" => "����������",
        "Croatia" => "��������",
        "Israel" => "�������",
        "Bulgaria" => "��������",
        "Turkey" => "������",
        "Malta" => "������",
        "Bosnia-Herzegovina" => "������-�����������",
        "Slovenia" => "��������",
        "Greece" => "������",
        "East Germany" => "���",
        "Slovakia" => "��������",
        "Singapore" => "��������",
        "Austria" => "�������",
        "Afghanistan" => "����������",
        "Albania" => "�������",
        "Algeria" => "�����",
        "Andorra" => "�������",
        "Angola" => "������",
        "Antigua and Barbuda" => "������� � �������",
        "Argentina" => "���������",
        "Armenia" => "�������",
        "Azerbaijan" => "�����������",
        "Bahamas" => "��������� �������",
        "Bahrain" => "�������",
        "Bangladesh" => "���������",
        "Barbados" => "��������",
        "Belarus" => "��������",
        "Belize" => "�����",
        "Benin" => "�����",
        "Bhutan" => "�����",
        "Bolivia" => "�������",
        "Botswana" => "��������",
        "Brazil" => "��������",
        "Burkina Faso" => "�������-����",
        "Burma" => "�����",
        "Burundi" => "�������",
        "Cambodia" => "��������",
        "Cameroon" => "�������",
        "Cape Verde" => "������� ���",
        "Central African Republic" => "����������-����������� ����������",
        "Chad" => "���",
        "Chile" => "����",
        "Colombia" => "��������",
        "Congo" => "�����",
        "Costa Rica" => "����� ����",
        "Cuba" => "����",
        "Cyprus" => "����",
        "Czechoslovakia" => "������������",
        "Djibouti" => "�������",
        "Dominican Republic" => "������������� ����������",
        "Ecuador" => "�������",
        "Egypt" => "������",
        "El Salvador" => "���������",
        "Eritrea" => "�������",
        "Estonia" => "�������",
        "Ethiopia" => "�������",
        "Faroe Islands" => "��������� �������",
        "Federal Republic of Yugoslavia" => "����������� ���������� ���������",
        "Fiji" => "�����",
        "Gabon" => "�����",
        "Georgia" => "��������",
        "Ghana" => "����",
        "Greenland" => "����������",
        "Guadeloupe" => "���������",
        "Guatemala" => "���������",
        "Guinea" => "������",
        "Guinea-Bissau" => "������-������",
        "Guyana" => "������",
        "Haiti" => "�����",
        "Honduras" => "��������",
        "Iceland" => "��������",
        "India" => "�����",
        "Indonesia" => "���������",
        "Iran" => "����",
        "Iraq" => "����",
        "Ivory Coast" => "����� �������� �����",
        "Jamaica" => "������",
        "Jordan" => "��������",
        "Kazakhstan" => "���������",
        "Kenya" => "�����",
        "Korea" => "�����",
        "Kosovo" => "������",
        "Kuwait" => "������",
        "Kyrgyzstan" => "����������",
        "Laos" => "����",
        "Latvia" => "������",
        "Lebanon" => "�����",
        "Liberia" => "�������",
        "Libya" => "�����",
        "Liechtenstein" => "�����������",
        "Lithuania" => "�����",
        "Macau" => "�����",
        "Madagascar" => "����������",
        "Malaysia" => "��������",
        "Mali" => "����",
        "Martinique" => "��������",
        "Mauritania" => "����������",
        "Mauritius" => "��������",
        "Moldova" => "�������",
        "Monaco" => "������",
        "Mongolia" => "��������",
        "Morocco" => "�������",
        "Mozambique" => "��������",
        "Namibia" => "�������",
        "Nepal" => "�����",
        "Nicaragua" => "���������",
        "Niger" => "�����",
        "Nigeria" => "�������",
        "North Korea" => "�������� �����",
        "North Vietnam" => "�������� �������",
        "Pakistan" => "��������",
        "Palestine" => "���������",
        "Panama" => "������",
        "Papua New Guinea" => "����� ����� ������",
        "Paraguay" => "��������",
        "Peru" => "����",
        "Philippines" => "���������",
        "Portugal" => "����������",
        "Puerto Rico" => "������-����",
        "Republic of Macedonia" => "���������� ���������",
        "Rwanda" => "������",
        "San Marino" => "���-������",
        "Saudi Arabia" => "���������� ������",
        "Senegal" => "�������",
        "Serbia and Montenegro" => "������ � ����������",
        "Seychelles" => "����������� �������",
        "Siam" => "����",
        "Somalia" => "������",
        "Sri Lanka" => "��� �����",
        "Sudan" => "�����",
        "Suriname" => "��������",
        "Syria" => "�����",
        "Tajikistan" => "�����������",
        "Tanzania" => "��������",
        "Togo" => "����",
        "Tonga" => "�����",
        "Trinidad And Tobago" => "�������� � ������",
        "Tunisia" => "�����",
        "Turkmenistan" => "������������",
        "Uganda" => "������",
        "United Arab Emirates" => "������������ �������� �������",
        "Uruguay" => "�������",
        "Uzbekistan" => "����������",
        "Venezuela" => "����������",
        "Vietnam" => "�������",
        "Western Sahara" => "�������� ������",
        "Yemen" => "�����",
        "Yugoslavia" => "���������",
        "Zaire" => "����",
        "Zambia" => "������",
        "Zimbabwe" => "��������"
    );
    
    
    
    public static function constructPath($action, $params)
    {
        switch($action){
            case 'search':
                $query = Lms_Text::translit($params['query']);
                $query = urlencode($query);
                return "http://www.imdb.com/find?q=$query&s=tt";
                break;
            case 'film':
                return "http://www.imdb.com/title/tt" . sprintf('%07d', $params['id']) . "/";
                break;
        }
    }

    public static function getImdbIdFromUrl($url)
    {
        if (preg_match('{http://www.imdb.com/title/tt(\d+)}', $url, $matches)) {
            return (int)$matches[1];
        } else {
            throw new Lms_Exception("Invalid imdb url: $url");
        }
    }

    public static function afterParseSearchResults($url, &$data)
    {
        if (isset($data['attaches']['film'])) {
            $film = $data['attaches']['film'];
            $url = $data['suburls']['film'][2];
            
            self::afterParseMovie($url, $film);
            
            $cast = array();
            $directors = array();
            foreach ($film['persones'] as $person) {
                switch ($person['role']) {
                    case '��������':
                        $directors[] = array_pop($person['names']);
                        break;
                    case '�����': // break intentionally omitted
                    case '�������':
                        $cast[] = array_pop($person['names']);
                        break;
                    default:
                        break;
                }
            }            
            $data['items'] = array();
            $data['items'][] = array(
                "names" => $film['names'],
                "year" => $film['year'],
                "url" => $url,
                "image" => $film['poster'],
                "country" => implode(", ", $film['countries']),
                "director" => implode(", ", $directors),
                "genre" => implode(", ", $film['genres']),
                "actors" => Lms_Text::tinyString(implode(", ", $cast), 100, 1),
                "rating" => isset($film['rating_imdb_value'])? $film['rating_imdb_value'] : null,
            );
        }
    }    
    
    public static function afterParseMovie($url, &$data)
    {
        $imdbId = self::getImdbIdFromUrl($url);
        if ($data) {
            $data['international_name'] = $data['names'][0];
            $data['poster'] = isset($data['posters'][0])? $data['posters'][0] : '';
            $data['imdb_id'] = $imdbId;
            $data['rating_imdb_value'] = isset($data['rating'])? $data['rating'] : '';
            $data['rating_imdb_count'] = isset($data['rating_count'])? $data['rating_count'] : '';
            foreach ($data['genres'] as &$genre) {
                $genre = isset(self::$genresMap[$genre])? self::$genresMap[$genre] : $genre;
            }
            foreach ($data['countries'] as &$country) {
                $country = isset(self::$countriesMap[$country])? self::$countriesMap[$country] : $country;
            }
            foreach ($data['persones'] as &$person) {
                $person['role'] = isset(self::$rolesMap[$person['role']])? self::$rolesMap[$person['role']] : $person['role'];
            }
        }
    }
    
    public static function afterParsePerson($url, &$data)
    {
    }    
}

