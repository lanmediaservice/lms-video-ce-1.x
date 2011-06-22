<?php
/**
 * Видео-каталог
 * (C) 2006-2009 Ilya Spesivtsev, macondos@gmail.com
 *
 * Файл конфигурации
 *
 * @author Ilya Spesivtsev 
 * @version 1.07
 */

@setlocale(LC_ALL, array('ru_RU.CP1251','ru_RU.cp1251','ru_SU.CP1251','ru','russian'));

ini_set('display_errors', 1);
error_reporting (E_ALL ^ E_NOTICE);

//Настройки

//Прокси-сервер для закачки
//$config['proxy_host'] = "yourproxy";
//$config['proxy_port'] = "port";

//URL сайта. Используется в качестве пути при нажатии "Выход", в списке всех фильмов, на RSS-канале
$config['siteurl'] = "http://video.lanmediaservice.com/demo";
$config['sitetitle'] = "Видео-каталог";

//Cообщения для гостей
$config['can_not_setbookmark'] = "Только зарегистрированные пользователи могут создавать закладки.<br> <a href='?register=1' class='alert_link'>Зарегистрируйтесь</a><br>или войдите под своим логином<br><form action='?' method='post'><input type='hidden' name='logon' value='1'><table border='0' width='100%'><tr><td>Логин:</td><td><input name='login'></td></tr><tr><td>Пароль:</td><td><input name='pass' type='password'></td></tr><tr><td colspan='2'><input id='remember' type='checkbox' value='1' name='remember'><label for='remember'>Автоматически входить</label></td></tr><tr><td colspan='2' align='center'><input type='submit' value='OK'></td></tr></table></form>";
$config['can_not_postcomment'] = "Только зарегистрированные пользователи могут оставлять отзывы.<br> <a href='?register=1' class='alert_link'>Зарегистрируйтесь</a> или <a href='javascript:Exit();' class='alert_link'>войдите</a> под своим логином";
$config['can_not_setrating'] = "<a href='?register=1' class='alert_link'>Зарегистрируйтесь</a> или <a href='javascript:Exit();' class='alert_link'>войдите</a> под своим логином,<br> чтобы ставить рейтинги";

// Рекомендации по написанию отзывов
// Например:
// $config['comment_rules'] = "Рекомендуется:<ul><li>Не писать односложные отзывы типа \\\"гавно/супер/ща посмотрю/и т.п.\\\", для этого можно просто выставить оценку фильму</li><li>Стараться писать грамотным русским языком</li><li>Не вступать в полемику с авторами других отзывов</li><li>Ставить флажок \\\"только для модератора\\\", если хотите обратиться с жалобой или вопросом</li></ul>Администрация сервера оставляет за собой право удалять сообщения по собственному усмотрению";

$config['comment_rules'] = "Администрация сервера оставляет за собой право удалять сообщения по собственному усмотрению";

//После скольки комментариев делать блок комментариев прокручиваемым (0 - никогда не делать прокручиваемым) :
$config['scroll_comments'] = 5;


//Прибавка к имени файла при переносе НазваниеФайлаSuffix.avi
$config['suffix'] = "-lanmediaservice.com";

//Настройки сервиса обновлений
$config['customer']['login'] = "demo";
$config['customer']['pass'] = "demo";
$config['customer']['updateservice'] = "http://update.lanmediaservice.com/actions.php";
$config['customer']['parser_service'] = 'http://service.lanmediaservice.com/2/actions.php';
$config['customer']['products']['lms_platform'] = "./"; //путь к файлам общих скриптов (если идут в комплекте)
$config['customer']['products']['lms_video'] = "./"; //путь к файлам скрипта видео-каталога

//Учетные данные mysql
$config['mysqlhost'] = "localhost";
$config['mysqluser'] = "user";
$config['mysqlpass'] = "password";
$config['mysqldb'] = "db";
//$config['mysql_set_names'] = "SET NAMES cp1251";

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

/* Шаблон определения ("нейтрализации" номеров) многодисковых фильмов (найденные
 * совпадения в названиях файлов удаляются, затем файлы сравниваются друг с другом)
 * Формат: regex
 *
 * Пример1.
 * $config['multipathpattern'] = "/CD\d/";
 * Такие файлы как Matrix.CD1.(DVDRip).avi и Matrix.CD2.(DVDRip).avi будут определены как 1 фильм.
 * Такие как Matrix1.avi и Matrix2.avi - как 2 разных фильма.
 *
 * Пример2.
 * $config['multipathpattern'] = "/\d[^\d]*$/";
 * Такие файлы как Matrix1.part1.avi, Matrix1.part2.avi будут определены как 1 фильм,
 * а Matrix2.part1.avi и Matrix2.part2.avi - как другой фильм.
 * Но Matrix1.avi и Matrix2.avi - как 1 фильм (!).
 */ 
$config['multipathpattern'] = "/.CD\d+/";


// Минимально необходимое количество оценок для отображения локального рейтинга
$config['minratingcount'] = 3;

// Количество отображаемых фильмов при импорте (остальные фильмы
// будут находится в очереди, и будут появляться по мере обработки
// и внесения в основную базу фильмов)
$config['maxincoming'] = 20;

// Вычислять md5-хеш файла (0/1)?
$config['md5'] = 0;

// Привязка к IP-адресу вычисленному при регистрации
$config['ip'] = 0;

// Модуль авторизации
// Измените, чтобы защитить ваши модификации logon.php от перезаписи при обновлении
$config['logon.php'] = "logon.dist.php";

//Допустимая частота регистраций с 1 IP (минут)
$config["register_timeout"] = 60;

// Шаблон оформления 
// Шаблоны находятся в каталоге "templates/"
$config['template'] = "flat.dist";
//Дополнительные пункты меню (только для шаблона flat.dist)  
$config['topmenu_links'] = array(
    array('url'=>'/music/', 'text'=>'Музыка'),
    array('url'=>'/video/', 'text'=>'Видео', 'selected'=>true),
    array('url'=>'/forum/', 'text'=>'Форум')
);

//Дополнительные пункты в нижнем футере (только для шаблона flat.dist) 
$config['support_links'] = array(
    array('url'=>'/support/', 'text'=>'Задать вопрос'),
    array('url'=>'mailto:support@isp.com', 'text'=>'Написать письмо'),
    array('url'=>'/forum/', 'text'=>'Форум')
);
//Включить вкладку бестселлеров (только для шаблона flat.dist) 
$config['bestsellers_enable'] = true;

//Включить окно ожидаемых фильмов (только для шаблона flat.dist) 
$config['announce_enable'] = false;


//Количество символов для переменной %DESCRIPTION% в коротком
//описании для фильмов в каталоге. 0 - не использовать.
$config['short_description'] = 0;

// Имортируемые фильмы делать скрытыми для пользователей 
$config["Hide"] = 0;

//Фильмов на странице каталога
$config["resultonpage"] = 10;

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

//По-умолчанию скрывать узловые папки
$config['hide_nodes'] = false;

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
 


//Создавать директорию при переносе обработанных файлов
$config['make_genre_folder'] = 0;

//Права файлов при переносе
$config['folder_rights'] = 0644;

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
 * (ограничение трафика будет работать при работе скрипта shaper.php)
 * Количество правил "...['limit'][]" неограничено для каждого режима.
 * Действует наименьшая скорость указанная в правилах.
 */
$config['modes'][1]['smb'] = 1; //Доступ к Samba
$config['modes'][1]['ftp'] = 1; //Доступ к FTP
$config['modes'][1]['fullspeed'] = 3000; // Первоначальное ограничение скорости, КБайт/с
$config['modes'][1]['disablespeed'] = 10; // Ограничение скорости при отключении пользователя, КБайт/с
$config['modes'][1]['limit'][] = array("min"=>60, "bytes"=>900000000, "speed"=>260); // кол-во минут, объем байт, "штрафная скорость" КБайт/с
$config['modes'][1]['limit'][] = array("min"=>5, "bytes"=>75000000, "speed"=>260); // кол-во минут, объем байт, "штрафная скорость" КБайт/с
$config['modes'][1]['limit'][] = array("min"=>1, "bytes"=>30000000, "speed"=>260); // кол-во минут, объем байт, "штрафная скорость" КБайт/с

$config['modes'][2]['smb'] = 1;
$config['modes'][2]['ftp'] = 1;
$config['modes'][2]['fullspeed'] = 6000; // КБайт/с
$config['modes'][2]['disablespeed'] = 10; // КБайт/с
$config['modes'][2]['limit'][] = array("min"=>60, "bytes"=>1800000000, "speed"=>500); // кол-во минут, объем байт, "штрафная скорость" КБайт/с
$config['modes'][2]['limit'][] = array("min"=>5, "bytes"=>1800000000, "speed"=>500); // кол-во минут, объем байт, "штрафная скорость" КБайт/с
$config['modes'][2]['limit'][] = array("min"=>1, "bytes"=>1800000000, "speed"=>500); // кол-во минут, объем байт, "штрафная скорость" КБайт/с

$config['modes'][3]['smb'] = 1;
$config['modes'][3]['ftp'] = 1;
$config['modes'][3]['fullspeed'] = 10000; // КБайт/с
$config['modes'][3]['disablespeed'] = 10; // КБайт/с
$config['modes'][3]['limit'][] = array("min"=>60, "bytes"=>36000000000, "speed"=>10000); // кол-во минут, объем байт, "штрафная скорость" КБайт/с
$config['modes'][3]['limit'][] = array("min"=>5, "bytes"=>36000000000, "speed"=>10000); // кол-во минут, объем байт, "штрафная скорость" КБайт/с
$config['modes'][3]['limit'][] = array("min"=>1, "bytes"=>36000000000, "speed"=>10000); // кол-во минут, объем байт, "штрафная скорость" КБайт/с


//Группы пользователей (фиксированные константы)
$GUEST = 0;
$DEFAULT_USER = 1;
$MODERATOR = 2;
$ADMIN = 3;

//Показывать перед скачиванием по фтп лицензионное соглашение (/templates/default/download.php) (1/0)
$config['ftp_license'] = 1;

//Таймаут на соединение к серверам (imdb.com, ozon.ru и т.д.)
$config['connection_timeout'] = 5;

//Не экранировать ftp-ссылки для Internet Explorer
$config['do_not_escape_link_for_ie'] = 1;

//Настройки для генерации кадров
//Краткое описание использования возможности генерации кадров http://docs.lanmediaservice.com/index.php/Генерация_кадров_из_фильмов
$config['count_frames'] = 8;       //количество генерируемых кадров, по-умолчанию 8
$config['escape_style'] = "u";     //стиль экранирования командной строки ("u" -- Unix / "w" -- Windows), по-умолчанию "u"
$config['small_frame_width'] = 80; //ширина уменьшенного кадра, по-умолчанию 80 пикселов
$config['tempdir'] = '/tmp';       //временная директория для хранения промежуточных данных, по-умолчанию /tmp
$config['mencoder'] = 'mencoder';  //путь для запуска mencoder (например, c:/mplayer/mencoder.exe), по-умолчанию mencoder; в пути не должно быть пробелов 
$config['mplayer'] = 'mplayer';    //путь для запуска mplayer (например, c:/mplayer/mplayer.exe), по-умолчанию mplayer; в пути не должно быть пробелов
$config['vcodec'] = 'mpeg4';       //кодек, по-умолчанию mpeg4, рекомендуется ffvhuff



//Настройка обложек/постеров (некоторые настройки актуальны только при наличии модуля GD)

//Максимальная ширина нежелательных (слишком маленьких) обложек,
//от которых скрипт будет автоматически избавляться при наличии бОльших.
//если вы хотите избавляться от постеров с ozon.ru установите значение 160
$config["covers"]["undesirable_size"] =  0; 

// * Стандартная обложка в окне просмотра информации о фильме
$config["covers"]["defaultcovers"]["width"]    = 160;   // Стандартная ширина обложки, 
                                                        // рекомендуется выбрать значение между 160 (стандартная 
                                                        // обложка на ozon.ru) и 300
$config["covers"]["defaultcovers"]["maxwidth"] = 240;   // Предельно допустимая ширина обложки (при большем значении
                                                        // будет уменьшена до стандартной ширины), 0 - без ограничений
$config["covers"]["defaultcovers"]["zoom"]     = false; // Принудительная растяжка обложки до стандартной ширины,
                                                        // если ширина меньше стандартной

// * Уменьшенная копия постера в списке фильмов в каталоге
$config["covers"]["smallcovers"]["width"]    = 60;    // Стандартная ширина
$config["covers"]["smallcovers"]["maxwidth"] = 60;    // Предельно допустимая ширина обложки (при большем значении 
                                                      // будет уменьшена до стандартной ширины), 0 - без ограничений
$config["covers"]["smallcovers"]["zoom"]     = true;  // Принудительная растяжка обложки до стандартной ширины,
                                                      // если ширина меньше стандартной

// * Полная версия обложки для просмотра. 
//Не является обязательной, присутствует только при ширине исходной обложки большей стандартной ширины
$config["covers"]["bigcovers"]["width"]    = 300;   //Стандартная ширина
$config["covers"]["bigcovers"]["maxwidth"] = 0;     // Предельно допустимая ширина обложки (при большем значении 
                                                    // будет уменьшена до стандартной ширины), 0 - без ограничений
$config["covers"]["bigcovers"]["zoom"]     = false; //зарезервировано


/** 
 * Метод учета кликов на скачивание:
 * 0 (по-умолчанию) - 1 зарегистрированный пользователь + x кликов = 1 скачивание 
 * 1 - x кликов = x скачиваний (не рекомендуется)
 * 2 - 1 IP-адрес + x кликов = 1 скачивание
 * 3 - 1 сессия + x кликов = 1 скачивание
 */
$config['hitmethod'] = 0;


//Переводы и озвучивание
$config['translation_options'][] = "";
$config['translation_options'][] = "Не определен";
$config['translation_options'][] = "Дубляж";
$config['translation_options'][] = "Профессиональный многоголосый";
$config['translation_options'][] = "Любительский многоголосый";
$config['translation_options'][] = "На языке оригинала";
$config['translation_options'][] = "Одноголосый";
$config['translation_options'][] = "Профессиональный одноголосый";
$config['translation_options'][] = "Субтитры";
$config['translation_options'][] = "Lostfilm";
$config['translation_options'][] = "На языке оригинала + Одноголосый";
$config['translation_options'][] = "На языке оригинала + Дубляж";
$config['translation_options'][] = "На языке оригинала + Любительский многоголосый";
$config['translation_options'][] = "На языке оригинала + Профессиональный многоголосый";
$config['translation_options'][] = "Tycoon Studio";
$config['translation_options'][] = "Гоблин (правильный)";
$config['translation_options'][] = "Гоблин (смешной)";
$config['translation_options'][] = "Володарский Леонид";
$config['translation_options'][] = "Гаврилов Андрей";
$config['translation_options'][] = "Гланц Пётр";
$config['translation_options'][] = "Живов Юрий";


$config['quality_options'][] = "";
$config['quality_options'][] = "Не определено";
$config['quality_options'][] = "DVDRip";
$config['quality_options'][] = "DVDScr";
$config['quality_options'][] = "Telecine";
$config['quality_options'][] = "Telesync";
$config['quality_options'][] = "CamRip";
$config['quality_options'][] = "BDRip";
$config['quality_options'][] = "HDDVDRip";
$config['quality_options'][] = "HDTVRip";
$config['quality_options'][] = "VHSrip";
$config['quality_options'][] = "SATRip";
$config['quality_options'][] = "TVRip";

$config['typeofmovie_options'][] = "";
$config['typeofmovie_options'][] = "Не определен";
$config['typeofmovie_options'][] = "Документальный сериал";
$config['typeofmovie_options'][] = "Документальный фильм";
$config['typeofmovie_options'][] = "Научно - популярный фильм";
$config['typeofmovie_options'][] = "Концерт";
$config['typeofmovie_options'][] = "Короткометражный фильм";
$config['typeofmovie_options'][] = "Мультсериал";
$config['typeofmovie_options'][] = "Мьюзикл-опера";
$config['typeofmovie_options'][] = "Полнометражный мультфильм";
$config['typeofmovie_options'][] = "Сборник мультфильмов";
$config['typeofmovie_options'][] = "Спортивная видеопрограмма";
$config['typeofmovie_options'][] = "Телевизионный спектакль";
$config['typeofmovie_options'][] = "Телепередача";
$config['typeofmovie_options'][] = "Худ. кинофильм";
$config['typeofmovie_options'][] = "Худ. телесериал";
$config['typeofmovie_options'][] = "Худ. телефильм";

/*
 * Расширенные кастройки парсеров сайтов
 * $config['websites']['default']... - общие настройки
 * $config['websites'][www.xxx.yy]... - индивидуальные настройки
 * Настраиваемые параметры: proxy, user_agent, connection_timeout
 * Пример:
 * $config['websites']['default']['proxy'] = false;//'proxyhost:port';
 * $config['websites']['default']['user_agent'] = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322)';
 * $config['websites']['default']['connection_timeout'] = 5;
 * $config['websites']['www.kinopoisk.ru']['user_agent'] = 'Opera/8.51 (Windows NT 5.1; U; en)';
 * $config['websites']['www.ozon.ru']['connection_timeout'] = 5;
 * $config['websites']['www.imdb.com']['proxy'] = 'proxyhost:port';
 * $config['websites']['www.world-art.ru']['user_agent'] = 'Opera/8.51 (Windows NT 5.1; U; en)';
 * $config['websites']['www.sharereactor.ru']['user_agent'] = 'Opera/8.51 (Windows NT 5.1; U; en)';
 * 
 */

//Всегда импортировать файлы с расширениями:
$config['include_extensions'] = array('avi','vob','mpg','mpeg','mpe','m1v','m2v','asf','mov','dat','wmv','wm','rm','rv','divx','mp4','mkv','qt','ogm');

//Неимпортировать файлы с расширениями:
$config['exclude_extensions'] = array('sub');

//Парсить с помощью mplayer
$config['mplayer_extensions'] = array('mp4');

//Для unix-систем iso8601-формат даты команды ls
$config['ls_dateformat_in_iso8601'] = false;


//Отключить поддержку определения размера файлов >4Гб:
$config['disable_4gb_support'] = false;

//Экспериментальные функции:
//Включить поддержку symlink
$config['follow_symlink'] = false;

//Указать явно использовать ли пассивный режим ftp
//$config['ftp_passive_mode'] = true;

//Ограничить отображаемую ширину кадров (для корректного 
// отображения кадров из HD-фильмов), 0 - не ограничивать
$config['max_frame_width_px'] = 720;

//Отключить показ иконки "Папка фильма на FTP"
$config['ftpfolder_disable'] = false;

/**
 * Шаблоны для генерации ссылок для поиска фильмов на внешних ресурсах (из Редактора фильмов):
 * Примеры:
 * $config['external_search_engines'][] = '<a href="http://torrents.ru/forum/tracker.php?nm=%s&f[]=-1&o=4" target="_blank"><img border=0 src="http://static.torrents.ru/favicon.ico" align="middle"></a>';
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
?>