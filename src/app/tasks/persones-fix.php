#!/usr/local/bin/php -q
<?php

require_once dirname(__FILE__) . '/include/init.php';

$pid = Lms_Pid::getPid(Lms_Application::getConfig('tmp') . '/persones-fix.pid');
if ($pid->isRunning()) {
    exit;
}

$log = Lms_Item_Log::create('persones-fix', '��������');

try {
    echo "\nFix persones: ";
    $result = Lms_Item_Person::fixAll();
    echo " done\n";
    
    $report = "���������� ����������: " . $result['merged']
            . "\n������� ����������� � �������� ����������: " . $result['persones_deleted']
            . "\n������� ������ �� �������������� ����������: " . $result['participants_deleted'];
    echo $report;
    $log->done(Lms_Item_Log::STATUS_DONE, "������", $report);
    
} catch (Exception $e) {
    Lms_Debug::crit($e->getMessage());
    $log->done(Lms_Item_Log::STATUS_ERROR, "������: " . $e->getMessage());
}

require_once dirname(__FILE__) . '/include/end.php'; 
