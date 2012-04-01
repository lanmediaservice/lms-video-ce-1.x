<?php 
/**
 * Настройки конфигурации по-умолчанию
 * 
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: default.settings.php 700 2011-06-10 08:40:53Z macondos $
 */

/**
 * Режим вывода ошибок
 */
error_reporting(E_ALL);

@setlocale(LC_ALL, array('ru_RU.CP1251','ru_RU.cp1251','ru_SU.CP1251','ru','russian')); 
@setlocale(LC_NUMERIC, '');

/**
 * Установка временной зоны
 */
date_default_timezone_set('UTC');

ini_set("iconv.internal_encoding", 'CP1251');
 
/**
 * Инициализация отладки
 */
$config['logger'] = new Zend_Log();
$config['logger']->setEventItem('pid', getmypid());
$config['logger']->setEventItem('ip', Lms_Ip::getIp());


/**
 * Конфигурация баз данных
 */

$config['databases']['main'] = array(
    'connectUri' => "mysql://{$config['mysqluser']}" . ($config['mysqlpass']? ":{$config['mysqlpass']}" : "")  ."@{$config['mysqlhost']}/{$config['mysqldb']}?ident_prefix=",
    'initSql' => isset($config['mysql_set_names'])? $config['mysql_set_names'] : "",
    'debug' => 0
);


/**
 * Настройка языков
 */
$config['langs']['supported'] = array('ru'=>'Русский', 
                                      'en'=>'English (US)');
$config['langs']['default'] = 'en';


/**
 * Временная директория для общих нужд
 */
$config['tmp'] = isset($config['tempdir'])? $config['tempdir'] : ((isset($_ENV['TEMP']))? $_ENV['TEMP'] : '/tmp');

$config['optimize']['classes_combine'] = 0;
$config['optimize']['js_combine'] = 0;
$config['optimize']['js_compress'] = 0;
$config['optimize']['css_combine'] = 0;
$config['optimize']['css_compress'] = 0;
$config['optimize']['less_combine'] = 0;

/**
 * Настройки сервиса парсинга
 */
$config['parser_service']['username'] = $config['customer']['login'];
$config['parser_service']['password'] = $config['customer']['pass'];
$config['parser_service']['url'] = $config['customer']['parser_service'];

$config['http_client']['maxredirects'] = 5;
$config['http_client']['timeout'] = 60;
$config['http_client']['useragent'] = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.1.5) Gecko/20091102 Firefox/3.5.5 (.NET CLR 3.5.30729)';
                       

$config['hit_factor'] = 3;//коэффициент скачиваний выше среднего, чтобы считаться хитом

$config['indexing']['stop_words'] = preg_split('{\s+}', 'of the or and in to i ii iii iv v on de la le les no at it na ne vs hd season сезон в на не из от по до за или');

$config['thumbnail']['key'] = md5($config['databases']['main']['connectUri']);
$config['thumbnail']['script'] = 'thumbnail.php';
$config['thumbnail']['cache'] = false;


$config['auth']['1.0']['cookie'] = array(
    'crypt' => false
);

$config['parsing']['default_engines'] = array(
    'kinopoisk' => true,
    'ozon' => false,
    'world-art' => false,
    'sharereactor' => false,
    'imdb' => false,
);

//коэффициент качество полей данных, учитываемое при автослиянии данных
$config['automerge'] = array();
$config['automerge']['manual'] = array(
    'default' => 10,
);
$config['automerge']['imdb'] = array(
    'default' => 0.9,
    'description' => 0.1,
    'rating_imdb_value' => 2,
    'rating_imdb_count' => 2,
    'mpaa' => 2,
    'poster' => 2,
);

$config['automerge']['kinopoisk'] = array(
    'default' => 1,
    'poster' => 0.5,
    'genres' => 2,
    'countries' => 2,
    'name' => 2,
    'persones' => 2,
);

$config['automerge']['ozon'] = array(
    'default' => 1,
    'genres' => 0,
    'countries' => 0.1,
    'description' => 2,
);

$config['automerge']['world-art'] = array(
    'default' => 1,
    'genres' => 2.1,
    'description' => 2.1,
);

$config['incoming']['ignore_files'] = array('Thumbs.db', 'desktop.ini', '/^\./', '/\.(zip|rar|txt|pdf)$/');
$config['incoming']['cache_time'] = 600; //секунды
$config['incoming']['hide_import'] = $config['Hide'];//добавлять скрытыми
$config['incoming']['force_tasks'] = true;

$config['metaparser']['ignore_files'] = array('Thumbs.db', 'desktop.ini', '/^\./', '/\.(zip|rar|txt|pdf)$/');
$config['metaparser']['max_deep'] = 5;

//convert old config
$config['mplayer'] = array(
    'bin' => $config['mplayer'],
    'tmp' => $config['tempdir']
);
$config['frames']['count'] = 10;

$config['files']['tth'] = array(
    'enabled' => false,
    'bin' => false,
);



$encodings = array(
    "k" => "KOI8-R",
    "w" => "CP1251",
    "i" => "ISO-8859-5",
    "a" => "CP866",
    "d" => "CP866",
    "m" => "MacCyrillic",
);


$config['download']['license'] = $config['ftp_license'];
$config['download']['escape']['enabled'] = true;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $isIE = preg_match('/MSIE/i', $_SERVER['HTTP_USER_AGENT']) && !preg_match('/(opera|gecko)/i', $_SERVER['HTTP_USER_AGENT']);
    if (!empty($config['do_not_escape_link_for_ie']) && $isIE) {
        $config['download']['escape']['enabled'] = false;
    }
}
$config['download']['escape']['encoding'] = isset($config['enc_ftpforclient'])? (isset($encodings[$config['enc_ftpforclient']])? $encodings[$config['enc_ftpforclient']] : $config['enc_ftpforclient']) : false;

$config['filesystem']['permissions']['directory'] = 0755;
$config['filesystem']['permissions']['file'] = isset($config['folder_rights'])? $config['folder_rights'] : 0644;//from 1.0

Lms_Ufs::setInternalEncoding('CP1251');

Lms_Ufs::setSystemEncoding('CP1251');

Lms_Ufs::addConfig('ls_dateformat_in_iso8601', @$config['ls_dateformat_in_iso8601']);

Lms_Ufs::addConfig('disable_4gb_support', @$config['disable_4gb_support']);

if (!empty($config["dir_extensions"])) {
    foreach ($config["dir_extensions"] as $path => $pathConfig) {
        if (!empty($pathConfig['encoding'])) {
            Lms_Ufs::setEncoding($path, $pathConfig['encoding']);
        }
    }
}

$config['update'] = array(
    'backup_path' => false,
    'channel' => 'http://update.lanmediaservice.com/get/lms-video/1.2/',
);