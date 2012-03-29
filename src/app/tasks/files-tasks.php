#!/usr/local/bin/php -q
<?php

require_once dirname(__FILE__) . '/include/init.php';

$pid = Lms_Pid::getPid(Lms_Application::getConfig('tmp') . '/files-tasks.pid');
if ($pid->isRunning()) {
    exit;
}


$log = Lms_Item_Log::create('files-tasks', '��������');
$report = '';

try {
    echo "\nMove files: ";
    $i = 0;
    while (true) {
        $filesTasks = Lms_Item_FileTask::select(100);
        if (!count($filesTasks)) {
            break;
        }
        foreach ($filesTasks as $fileTask) {
            try {
                echo '.';
                $from = $fileTask->getFrom();
                $to = $fileTask->getTo();
                $log->progress("������������ ���� '$from' -> '$to' (������� #" . $fileTask->getTries() . ")");
                
                $fileTask->exec();
                
                $i++;
                $report .= "\n����������� �����: '$from' -> '$to'";
            } catch (Lms_Exception $e) {
                Lms_Debug::err($e->getMessage());
                $message = "������: " . $e->getMessage();
                $report .= "\n$message";
                $log->progress($message);
            }
        }
    }

    echo " done\n";
    $log->done(Lms_Item_Log::STATUS_DONE, "������. ���������� ������: $i", trim($report));
    
} catch (Exception $e) {
    Lms_Debug::crit($e->getMessage());
    $log->done(Lms_Item_Log::STATUS_ERROR, "������: " . $e->getMessage(), trim($report));
}

require_once dirname(__FILE__) . '/include/end.php'; 
