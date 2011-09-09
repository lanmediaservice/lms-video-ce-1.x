<?php
/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: local.settings.dist.php 700 2011-06-10 08:40:53Z macondos $
 */

$logDir = dirname(__FILE__) . '/logs/';
if (defined('LOGS_SUBDIR')) {
    $logDir .= rtrim(LOGS_SUBDIR, '/') . '/';
}
$writer = new Zend_Log_Writer_Stream(
    $logDir . 'error.' . date('Y-m-d') . '.log'
);
$config['logger']->addWriter($writer);
$format = '%timestamp% %ip%(%pid%) %priorityName% (%priority%): %message%'
        . PHP_EOL;
$formatter = new Zend_Log_Formatter_Simple($format);
$writer->setFormatter($formatter);
$filter = new Zend_Log_Filter_Priority(Zend_Log::WARN);
$writer->addFilter($filter);

$writer = new Zend_Log_Writer_Stream(
    $logDir . 'debug.' . date('Y-m-d') . '.log'
);
$config['logger']->addWriter($writer);
$format = '%timestamp% %ip%(%pid%) %priorityName% (%priority%): %message%'
        . PHP_EOL;
$formatter = new Zend_Log_Formatter_Simple($format);
$writer->setFormatter($formatter);

if (php_sapi_name() != 'cli' && !(isset($_GET['format']) 
    && in_array($_GET['format'], array('ajax', 'json', 'php')))
    && (!defined('SKIP_DEBUG_CONSOLE') || !SKIP_DEBUG_CONSOLE)
) {
    $writer = new Lms_Log_Writer_Console();
    $format = '%timestamp%: %message%';
    $formatter = new Zend_Log_Formatter_Simple($format);
    $writer->setFormatter($formatter);
    $config['logger']->addWriter($writer);
}

$config['auth']['cookie']['key'] = md5($config['databases']['main']['connectUri']);


/**
 * В случае если в 1.0 версии было включено шифрование cookies нужно раскомментировать и настроить блок ниже
 */
/*
$config['auth']['1.0']['cookie'] = array(
    'crypt' => true,
    'mode' => Lms_Crypt::MODE_ECB,
    'algorithm' => 'blowfish',
    'key' => 'key52345346_change_it' //ключ из logon.php
);*/

/**
 * Для кеширования обращений к файловой системе можно использовать memcached
 */
/*
$config['thumbnail']['cache'] = Zend_Cache::factory(
    'Core',
    'Memcached',
    array(
        'lifetime' => null,
        'automatic_serialization' => true
    ),
    array(
        'servers' => array(array('host' => 'localhost', 'port' => 11211, 'persistent' => true,))
    )
); */