<?php

@ini_set('max_execution_time', 0);
@set_time_limit();

if (!defined('LOGS_SUBDIR')) {
    define('LOGS_SUBDIR', 'tasks');
}

require_once dirname(dirname(dirname(__FILE__))) . "/config.php";

Lms_Application::prepareApi();