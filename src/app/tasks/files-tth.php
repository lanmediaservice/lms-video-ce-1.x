#!/usr/local/bin/php -q
<?php

require_once dirname(__FILE__) . '/include/init.php';

if (!Lms_Application::getConfig('files', 'tth', 'enabled')) {
    exit;
}

$pid = Lms_Pid::getPid(Lms_Application::getConfig('tmp') . '/files-tth.pid');
if ($pid->isRunning()) {
    exit;
}

$log = Lms_Item_Log::create('files-tth', '��������');
$report = '';

try {
    echo "\nCalc TTH: ";

    $files = Lms_Item_File::selectWithoutTthHash(20);
    $i = 0;
    foreach ($files as $file) {
        try {
            echo '.';
            $log->progress("�������������� ����: " . $file->getPath());
            $file->calcTthHash();
            $i++;
            $report .= "\n��������� ����: " . $file->getPath();
        } catch (Lms_Exception $e) {
            Lms_Debug::err($e->getMessage());
            $message = "������: " . $e->getMessage();
            $report .= "\n$message";
            $log->progress($message);
        }
    }
    echo " done\n";
    $log->done(Lms_Item_Log::STATUS_DONE, "������. ���������� ������: $i", trim($report));
    
} catch (Exception $e) {
    Lms_Debug::crit($e->getMessage());
    $log->done(Lms_Item_Log::STATUS_ERROR, "������: " . $e->getMessage(), trim($report));
}

require_once dirname(__FILE__) . '/include/end.php';