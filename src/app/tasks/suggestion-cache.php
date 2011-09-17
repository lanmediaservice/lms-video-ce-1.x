#!/usr/local/bin/php -q
<?php

require_once dirname(__FILE__) . '/include/init.php';

$db = Lms_Db::get('main');

$db->query('TRUNCATE `suggestion_cache`');
echo "\nIndexing...";
$n = 1;
$limit = 100;
while (true) {
    $rows = $db->select('SELECT (LEFT(`word`,?d)) as `query`, COUNT(*) as c FROM `suggestion` WHERE LENGTH(`word`)>=?d GROUP BY (LEFT(`word`, ?d)) HAVING c>?d', $n, $n, $n, $limit);
    if (!count($rows)) {
        break;
    }
    echo "\n$n: " . count($rows) . " ({$rows[0]['query']}) ";
    foreach ($rows as $num => $row) {
        if (!($num % 10)) {
            echo '.';
        }
        $suggestion = Lms_Application::getSuggestion($row['query']);
        $db->query(
            'INSERT IGNORE INTO `suggestion_cache` SET `query`=? ,`result`=?',
            $row['query'], Zend_Json::encode($suggestion)
        );
    }
    echo ' OK';
    $n += 1;
}

echo "\nDone\n";

require_once dirname(__FILE__) . '/include/end.php'; 
