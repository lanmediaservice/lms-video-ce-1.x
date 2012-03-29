<?php
/**
 * Видео-каталог
 * (C) 2006-2012 Ilya Spesivtsev, macondos@gmail.com
 *
 * Файл конфигурации
 *
 * @author Ilya Spesivtsev 
 * @version 1.2
 */

@setlocale(LC_ALL, array('ru_RU.CP1251','ru_RU.cp1251','ru_SU.CP1251','ru','russian'));

ini_set('display_errors', 1);
error_reporting (E_ALL ^ E_NOTICE);

//Настройки

//URL сайта. Используется в качестве пути при нажатии "Выход", в списке всех фильмов, на RSS-канале
$config['siteurl'] = "http://video.lanmediaservice.com/demo";
$config['sitetitle'] = "Видео-каталог";


//Настройки сервиса парсинга
$config['customer']['login'] = "demo";
$config['customer']['pass'] = "demo";
$config['customer']['parser_service'] = 'http://service.lanmediaservice.com/2/actions.php';

//Учетные данные mysql
$config['mysqlhost'] = "localhost";
$config['mysqluser'] = "user";
$config['mysqlpass'] = "password";
$config['mysqldb'] = "db";
$config['mysql_set_names'] = "SET NAMES cp1251";

/**
 * Интреграция учетных записей с форумами
 *
 * Пример:
 * $config['integration']['enabled'] = false;
 * $config['integration']['type'] = "ipb2"; //joomla, ipb2, ipb1, phpbb2, vb3
 * $config['integration']['strong'] = true;
 * /Учетные данные для базы данных форума (можно пропускать, если параметр совпадает с общим параметром скрипта)
 * $config['integration']['mysqlhost'] = "localhost";
 * $config['integration']['mysqluser'] = "ipb2user";
 * $config['integration']['mysqlpass'] = "ipb2password";
 * $config['integration']['mysqldb'] = "ipb2";
 * $config['integration']['prefix'] = "ibf_";
 */


// Минимально необходимое количество оценок для отображения локального рейтинга
$config['minratingcount'] = 3;

// Количество отображаемых фильмов при импорте (остальные фильмы
// будут находится в очереди, и будут появляться по мере обработки
// и внесения в основную базу фильмов)
$config['maxincoming'] = 20;


// Привязка к IP-адресу вычисленному при регистрации
$config['ip'] = 0;

// Модуль авторизации
// Измените, чтобы защитить ваши модификации logon.php от перезаписи при обновлении
$config['logon.php'] = "logon.dist.php";

//Допустимая частота регистраций с 1 IP (минут)
$config["register_timeout"] = 60;

// Шаблон оформления 
// Шаблоны находятся в каталоге "templates/"
$config['template'] = "modern";
//Дополнительные пункты меню
$config['topmenu_links'] = array(
    array('url'=>'/music/', 'text'=>'Музыка'),
    array('url'=>'/video/', 'text'=>'Видео', 'selected'=>true),
    array('url'=>'/forum/', 'text'=>'Форум')
);

//Дополнительные пункты в нижнем футере
$config['support_links'] = array(
    array('url'=>'/support/', 'text'=>'Задать вопрос'),
    array('url'=>'mailto:support@isp.com', 'text'=>'Написать письмо'),
    array('url'=>'/forum/', 'text'=>'Форум')
);

//Количество символов для переменной %DESCRIPTION% в коротком
//описании для фильмов в каталоге. 0 - не использовать.
$config['short_description'] = 0;

// Имортируемые фильмы делать скрытыми для пользователей 
$config["Hide"] = 0;


/* Корневые директории - полный путь откуда начинается сканирование или импортирование
 * Если дирикторий несколько, то они не должны быть вложенными друг в друга.
 * Внутри этих директорий вложенность может быть произвольной.
 * Возможно указание ftp-серверов (php должен быть сконфигурирован с ключом --enable-ftp),
 * но этот режим не совместим с режимом распределения импортируемых фильмов по дисковым
 * массивам (см. дальше $config['storages']), при подключении фтп также не будет доступна 
 * генерация кадров. 
 * Слеши должны быть прямыми (/).
 * Пример1:
 * $config['rootdir'][] = "d:/video/films1/";
 * $config['rootdir'][] = "d:/video/films2/";
 * Пример2:
 * $config['rootdir'][] = "/home/media/";
 *
 * Пример3:
 * $config['rootdir'][] = "ftp://mediaserver/films/";
 * 
 */
$config['rootdir'][] = "/home/";


/**
 * Расширенные настройки директорий и фтп. 
 *
 * Кодировка файловой системы или фтп. Если не определять конвертация не будет происходить.
 * Используется в случае неправильного отображения русских букв.
 *    k - koi8-r
 *    w - windows-1251
 *    i - iso8859-5
 *    a - x-cp866
 *    d - x-cp866
 *    m - x-mac-cyrillic
 * Пример1:
 * $config["dir_extensions"]["/home/video/"]["encoding"] = "UTF-8";
 * $config["dir_extensions"]["ftp://10.10.10.10/"]["encoding"] = "k";
 * 
 * Проверка наличия вложенных директорий (может значительно замедлять работу, если true)
 * $config["dir_extensions"]["/home/video/"]["check_subdir"] = false; //true/false
 * 
 * Параметры актуальные только для FTP
 * $config["dir_extensions"]["ftp://10.10.10.10/"]["login"] = "anonymous";
 * $config["dir_extensions"]["ftp://10.10.10.10/"]["password"] = "";
 * $config["dir_extensions"]["ftp://10.10.10.10/"]["port"] = 21;
 * $config["dir_extensions"]["ftp://10.10.10.10/"]["timeout"] = 90;
 *
 *
 *  
 */



/* Целевые директории для переноса обработанных файлов. Необязательный параметр.
 * Не совместимо с импортированием файлов из ftp-серверов.
 * Данный параметр следует определить, если требуется распределение импортируемых файлов по 
 * дисковым массивам. При указании нескольких путей автоматически происходит балансировка
 * по наличию свободного места (равномерное заполнение). Каждый фильм помещается в отдельную папку с
 * безопасными именами файлов (без пробелов и русских символов).  
 * При импорте целых папок как 1 фильм, при переносе внутри папки файлы не переименовываются (во избежание 
 * потери целостности DVD или фильмов с субтитрами).
 * Заданные директории должны существовать.
 * Слеши должны быть прямыми (/).
 * Пример1:
 * $config['storages'][] = "d:/films/";
 * $config['storages'][] = "e:/films/";
 * Пример2:
 * $config['storages'][] = "/home/media/disk/1/";
 * $config['storages'][] = "/home/media/disk/2/";
 */ 
 

/* Маски замены для Samba/FTP
 * Пример маски:
 * -------------------------------------------------------------------------------------
 * |                 |        Маска              |             Результат               |
 * -------------------------------------------------------------------------------------
 * | Физический путь | /home/media/disk/1/       | /home/media/disk/1/myfilm.avi       |
 * -------------------------------------------------------------------------------------
 * | Samba-путь      | //mediaserver/films1/     | \\mediaserver\films1\myfilm.avi     |
 * | FTP-путь        | ftp://mediaserver/films1/ | ftp://mediaserver/films1/myfilm.avi |
 * -------------------------------------------------------------------------------------
 * Внимание: количество элементов в массивах должно совпадать 
 * и их порядок синхронизирован друг с другом.
 * Слеши для сетевых ресурсов (Samba) указываются прямые (/)!
 * Алгоритм работы на примере получения фтп-ссылки:
 * в исходном пути к фильму (например, /home/media/disk/1/myfilm.avi) ищется совпадение 
 * строки $config['source'][] (например, /home/media/disk/1/) и заменяется на $config['ftp'][]
 * (например ftp://mediaserver/films1/), таким образом образуется фтп-ссылка
 * ftp://mediaserver/films1/myfilm.avi
 * 
 * Пример1:
 * $config['source'][] = "d:/films/";
 * $config['smb'][] = "//mediaserver/films1/";
 * $config['ftp'][] = "ftp://mediaserver/films1/";
 * $config['source'][] = "e:/films/";
 * $config['smb'][] = "//mediaserver/films2/";
 * $config['ftp'][] = "ftp://mediaserver/films2/";
 * В результате ссылка на сетевой ресурс для файла "e:/films/film.avi" 
 * будет выглядеть как "\\mediaserver\films2\film.avi" и т.д.
 *
 * Пример2:
 * $config['source'][] = "/home/media/disk/1/";
 * $config['ftp'][] = "ftp://mediaserver/films1/";
 * $config['source'][] = "/home/media/disk/2/";
 * $config['ftp'][] = "ftp://mediaserver/films2/";
 * В результате ссылка на ftp для файла "/home/media/disk/2/film.avi" 
 * будет выглядеть как "ftp://mediaserver/films2/film.avi" и т.д.
 * Доступа к файлам по Samb'e в данном примере будет отсутствовать
 */
  $config['source'][] = "/home/";
  $config['smb'][] = "//mediaserver/films/";
  $config['ftp'][] = "ftp://mediaserver/films/";
 



/* Режимы работы пользователей
 */
$config['modes'][1]['smb'] = 1; //Доступ к Samba
$config['modes'][1]['ftp'] = 1; //Доступ к FTP

$config['modes'][2]['smb'] = 1;
$config['modes'][2]['ftp'] = 1;

$config['modes'][3]['smb'] = 1;
$config['modes'][3]['ftp'] = 1;


//Группы пользователей (фиксированные константы)
$GUEST = 0;
$DEFAULT_USER = 1;
$MODERATOR = 2;
$ADMIN = 3;

//Показывать перед скачиванием по фтп лицензионное соглашение (/templates/default/download.php) (1/0)
$config['ftp_license'] = 1;

//Не экранировать ftp-ссылки для Internet Explorer
$config['do_not_escape_link_for_ie'] = 1;

//Настройки mplayer
$config['tempdir'] = '/tmp';       //временная директория для хранения промежуточных данных, по-умолчанию /tmp
$config['mplayer'] = 'mplayer';    //путь для запуска mplayer (например, c:/mplayer/mplayer.exe), по-умолчанию mplayer; в пути не должно быть пробелов


$config['translation_options'][] = "";
$config['translation_options'][] = "Дубляж";
$config['translation_options'][] = "Профессиональный многоголосый";
$config['translation_options'][] = "Профессиональный двухголосый";
$config['translation_options'][] = "Профессиональный одноголосый";
$config['translation_options'][] = "Любительский многоголосый";
$config['translation_options'][] = "Любительский двухголосый";
$config['translation_options'][] = "Любительский одноголосый";
$config['translation_options'][] = "Оригинал";
$config['translation_options'][] = "Субтитры";
$config['translation_options'][] = "LostFilm";
$config['translation_options'][] = "Гоблин (правильный)";
$config['translation_options'][] = "Гоблин (смешной)";


$config['quality_options'][] = "";
$config['quality_options'][] = "DVDRip";
$config['quality_options'][] = "HDRip";
$config['quality_options'][] = "BDRip";
$config['quality_options'][] = "HDDVDRip";
$config['quality_options'][] = "HDTVRip";
$config['quality_options'][] = "WEBDLRip";
$config['quality_options'][] = "WebRip";
$config['quality_options'][] = "DVDScr";
$config['quality_options'][] = "VHSrip";
$config['quality_options'][] = "SATRip";
$config['quality_options'][] = "TVRip";
$config['quality_options'][] = "Telecine";
$config['quality_options'][] = "Telesync";
$config['quality_options'][] = "CamRip";



//Для unix-систем iso8601-формат даты команды ls
$config['ls_dateformat_in_iso8601'] = false;


//Отключить поддержку определения размера файлов >4Гб:
$config['disable_4gb_support'] = false;

/**
 * Шаблоны для генерации ссылок для поиска фильмов на внешних ресурсах (из Редактора фильмов):
 * Примеры:
 * $config['external_search_engines'][] = '<a href="http://rutracker.org/forum/tracker.php?nm=%s&f[]=-1&o=4" target="_blank"><img border=0 src="http://static.rutracker.org/favicon.ico" align="middle"></a>'; 
 * $config['external_search_engines'][] = '<a href="http://kinozal.tv/browse.php?s=%s" target="_blank"><img border=0 src="http://kinozal.tv/favicon.ico" align="middle"></a>';
 */

/**
 * Настройки для преобразования полного поля озвучивания в короткий вариант %SHORTTRANSLATION%
 */
$config['short_translation'] = array();
$config['short_translation']["Дубляж"] = 'Dub';
$config['short_translation']["На языке оригинала"] = 'Original';
$config['short_translation']["Профессиональный многоголосый"] = 'MVO';
$config['short_translation']["Любительский многоголосый"] = 'MVO';
$config['short_translation']["Одноголосый"] = 'VO';
$config['short_translation']["Гоблин (правильный)"] = 'AVO(Гоблин)';
$config['short_translation']["Субтитры"] = 'Sub';

/**
 * Настройки отображения ссылок в шаблоне modern
 */
//какие ссылки пользователь может включать/отключать:
$config['download']['selectable'] = array('smb'=>true, 'dcpp'=>true, 'ed2k'=>true);
//установки по-умолчанию
$config['download']['defaults'] = array('smb'=>true, 'dcpp'=>true, 'ed2k'=>true);
//какие плейлисты может выбирать пользователь:
$config['download']['players']['selectable'] = array('la'=>false, 'mp'=>true, 'mpcpl'=>true, 'bsl'=>true, 'crp'=>true, 'tox'=>true, 'kaf'=>true, 'pls'=>true, 'xspf'=>true);
//плейлист по-умолчанию:
$config['download']['players']['default'] = 'xspf'; 