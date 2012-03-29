#!/usr/local/bin/php -q
<?php

require_once dirname(__FILE__) . '/include/init.php';

$pid = Lms_Pid::getPid(Lms_Application::getConfig('tmp') . '/ratings-update.pid');
if ($pid->isRunning()) {
    exit;
} 

$log = Lms_Item_Log::create('ratings-update', '��������');

try {

    echo "\nUpdate ratings";
    $result = Lms_Item_Rating::updateRatings();
    echo "\nDone\n";
    
    $report = "��������� ��������� KinoPoisk: " . $result['kinopoisk_add']
            . "\n��������� ��������� KinoPoisk: " . $result['kinopoisk_update']
            . "\n��������� ��������� IMDb: " . $result['imdb_add']
            . "\n��������� ��������� IMDb: " . $result['imdb_update'];
    echo $report;
    $log->done(Lms_Item_Log::STATUS_DONE, "������", $report);
    
} catch (Exception $e) {
    Lms_Debug::crit($e->getMessage());
    $log->done(Lms_Item_Log::STATUS_ERROR, "������: " . $e->getMessage());
}

require_once dirname(__FILE__) . '/include/end.php'; 
