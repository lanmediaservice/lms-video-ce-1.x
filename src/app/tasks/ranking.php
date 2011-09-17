#!/usr/local/bin/php -q
<?php

require_once dirname(__FILE__) . '/include/init.php';

$db = Lms_Db::get('main');

echo "\nRanking...";
echo "\nFilms:\n";
$db->query('UPDATE films SET Rank=1');
$n = 0; 
while (true) {
    $rows = $db->select(
        'SELECT f.ID as film_id, GREATEST(1, SUM(LEAST(1, ?d/(TO_DAYS(CURDATE()) - TO_DAYS(DateHit) + 1)))) as rank FROM `films` f INNER JOIN hits h ON (f.ID=h.FilmID) GROUP BY f.ID ORDER BY f.ID LIMIT ?d, 1000',
        7, $n
    );
    if (!count($rows)) {
        break;
    }
    echo "..$n..";
    
    foreach ($rows as $row) {
        if ($row['rank']!=1) {
            $db->query('UPDATE films SET Rank=?d WHERE ID=?d', $row['rank'], $row['film_id']);
        }
    }

    $n += 1000;
}

echo "\nPersons:\n";
$n = 0; 
while (true) {
    $rows = $db->select('SELECT p.ID as person_id, sum(f.Rank * IF(Role IN(\'режиссер\',\'режиссёр\',\'актер\',\'актриса\'), 1, 0.2)) as rank FROM `persones` p INNER JOIN filmpersones fp ON(p.ID=fp.PersonID) INNER JOIN roles r ON(fp.RoleID=r.ID) INNER JOIN films f ON(fp.FilmID=f.ID) GROUP BY p.ID ORDER BY p.ID LIMIT ?d, 1000', $n);
    if (!count($rows)) {
        break;
    }
    echo "..$n..";
    
    foreach ($rows as $row) {
        $db->query('UPDATE persones SET Rank=?d WHERE ID=?d', $row['rank'], $row['person_id']);
    }

    $n += 1000;
}

echo "\nDone\n";

require_once dirname(__FILE__) . '/include/end.php'; 
