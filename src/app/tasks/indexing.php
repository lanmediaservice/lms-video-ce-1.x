#!/usr/local/bin/php -q
<?php

require_once dirname(__FILE__) . '/include/init.php';

function indexText($text, $type, $id, &$trigramValues, &$suggestionValues)
{
    if (!trim($text)) {
        return;
    }
    static $stopWords, $db;
    if (!$stopWords) {
        $stopWords = Lms_Application::getConfig('indexing', 'stop_words');
    }
    if (!$db) {
        $db = Lms_Db::get('main');
    }

    $trigrams = array();
    $textLength = Lms_Text::length($text);
    if ($textLength>=3) {
        for ($i=0; $i<=$textLength-3; $i++) {
            $trigram = substr($text, $i, 3);
            $trigramValues[] = sprintf(
                "('%s','%s',%d)",
                mysql_real_escape_string(strtolower($trigram)),
                $type, 
                $id
            );
        }
    }
    
    preg_match_all('{\w{2,}}', strtolower($text), $words, PREG_PATTERN_ORDER);
    $wordsFiltered = array();
    foreach (array_diff($words[0], $stopWords) as $word) {
        if (!preg_match('{^\d+$}', $word)) {
            $wordsFiltered[] = $word;
        }
    }
    array_unshift($wordsFiltered, strtolower($text));
    //print_r($wordsFiltered);
    foreach ($wordsFiltered as $word) {
        $suggestionValues[] = sprintf(
            "('%s','%s',%d)",
            mysql_real_escape_string(trim($word, ' .\'"')),
            $type, 
            $id
        );
    }
}

$db = Lms_Db::get('main');

$db->query('TRUNCATE `suggestion`');
$db->query('TRUNCATE `search_trigrams`');

echo "\nIndexing...";
echo "\nFilms:\n";
$n = 0;
$size = 200;
while (true) {
    $rows = $db->select('SELECT ID as film_id, Name as name, OriginalName as international_name FROM films ORDER BY ID LIMIT ?d, ?d', $n, $size);
    if (!count($rows)) {
        break;
    }
    echo "..$n..";
    $trigramValues = array();
    $suggestionValues = array();
    foreach ($rows as $row) {
        indexText($row['name'], 'film', $row['film_id'], $trigramValues, $suggestionValues);
        if ($row['name']!=$row['international_name']) {
            indexText($row['international_name'], 'film', $row['film_id'], $trigramValues, $suggestionValues);
        }
    }
    $db->query('INSERT IGNORE INTO `search_trigrams`(`trigram`,`type`, `id`) VALUES ' . implode(', ', $trigramValues));
    $db->query('INSERT IGNORE INTO `suggestion`(`word`,`type`, `id`) VALUES ' . implode(', ', $suggestionValues));

    $n += $size;
}

echo "\nPersons:\n";
$n = 0; 
$size = 500;
while (true) {
    $rows = $db->select('SELECT ID as person_id, RusName as name, OriginalName as international_name FROM persones ORDER BY ID LIMIT ?d, ?d', $n, $size);
    if (!count($rows)) {
        break;
    }
    echo "..$n..";

    $trigramValues = array();
    $suggestionValues = array();
    foreach ($rows as $row) {
        indexText($row['name'], 'person', $row['person_id'], $trigramValues, $suggestionValues);
        if ($row['name']!=$row['international_name']) {
            indexText($row['international_name'], 'person', $row['person_id'], $trigramValues, $suggestionValues);
        }
    }
    $db->query('INSERT IGNORE INTO `search_trigrams`(`trigram`,`type`, `id`) VALUES ' . implode(', ', $trigramValues));
    $db->query('INSERT IGNORE INTO `suggestion`(`word`,`type`, `id`) VALUES ' . implode(', ', $suggestionValues));

    $n += $size;
}
echo "\nDone\n";

require_once dirname(__FILE__) . '/include/end.php'; 
