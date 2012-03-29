<?php
/**
 * �����-�������
 * (C) 2006-2012 Ilya Spesivtsev, macondos@gmail.com
 *
 * ���� ������������
 *
 * @author Ilya Spesivtsev 
 * @version 1.2
 */

@setlocale(LC_ALL, array('ru_RU.CP1251','ru_RU.cp1251','ru_SU.CP1251','ru','russian'));

ini_set('display_errors', 1);
error_reporting (E_ALL ^ E_NOTICE);

//���������

//URL �����. ������������ � �������� ���� ��� ������� "�����", � ������ ���� �������, �� RSS-������
$config['siteurl'] = "http://video.lanmediaservice.com/demo";
$config['sitetitle'] = "�����-�������";


//��������� ������� ��������
$config['customer']['login'] = "demo";
$config['customer']['pass'] = "demo";
$config['customer']['parser_service'] = 'http://service.lanmediaservice.com/2/actions.php';

//������� ������ mysql
$config['mysqlhost'] = "localhost";
$config['mysqluser'] = "user";
$config['mysqlpass'] = "password";
$config['mysqldb'] = "db";
$config['mysql_set_names'] = "SET NAMES cp1251";

/**
 * ����������� ������� ������� � ��������
 *
 * ������:
 * $config['integration']['enabled'] = false;
 * $config['integration']['type'] = "ipb2"; //joomla, ipb2, ipb1, phpbb2, vb3
 * $config['integration']['strong'] = true;
 * /������� ������ ��� ���� ������ ������ (����� ����������, ���� �������� ��������� � ����� ���������� �������)
 * $config['integration']['mysqlhost'] = "localhost";
 * $config['integration']['mysqluser'] = "ipb2user";
 * $config['integration']['mysqlpass'] = "ipb2password";
 * $config['integration']['mysqldb'] = "ipb2";
 * $config['integration']['prefix'] = "ibf_";
 */


// ���������� ����������� ���������� ������ ��� ����������� ���������� ��������
$config['minratingcount'] = 3;

// ���������� ������������ ������� ��� ������� (��������� ������
// ����� ��������� � �������, � ����� ���������� �� ���� ���������
// � �������� � �������� ���� �������)
$config['maxincoming'] = 20;


// �������� � IP-������ ������������ ��� �����������
$config['ip'] = 0;

// ������ �����������
// ��������, ����� �������� ���� ����������� logon.php �� ���������� ��� ����������
$config['logon.php'] = "logon.dist.php";

//���������� ������� ����������� � 1 IP (�����)
$config["register_timeout"] = 60;

// ������ ���������� 
// ������� ��������� � �������� "templates/"
$config['template'] = "modern";
//�������������� ������ ����
$config['topmenu_links'] = array(
    array('url'=>'/music/', 'text'=>'������'),
    array('url'=>'/video/', 'text'=>'�����', 'selected'=>true),
    array('url'=>'/forum/', 'text'=>'�����')
);

//�������������� ������ � ������ ������
$config['support_links'] = array(
    array('url'=>'/support/', 'text'=>'������ ������'),
    array('url'=>'mailto:support@isp.com', 'text'=>'�������� ������'),
    array('url'=>'/forum/', 'text'=>'�����')
);

//���������� �������� ��� ���������� %DESCRIPTION% � ��������
//�������� ��� ������� � ��������. 0 - �� ������������.
$config['short_description'] = 0;

// ������������ ������ ������ �������� ��� ������������� 
$config["Hide"] = 0;


/* �������� ���������� - ������ ���� ������ ���������� ������������ ��� ��������������
 * ���� ���������� ���������, �� ��� �� ������ ���� ���������� ���� � �����.
 * ������ ���� ���������� ����������� ����� ���� ������������.
 * �������� �������� ftp-�������� (php ������ ���� ��������������� � ������ --enable-ftp),
 * �� ���� ����� �� ��������� � ������� ������������� ������������� ������� �� ��������
 * �������� (��. ������ $config['storages']), ��� ����������� ��� ����� �� ����� �������� 
 * ��������� ������. 
 * ����� ������ ���� ������� (/).
 * ������1:
 * $config['rootdir'][] = "d:/video/films1/";
 * $config['rootdir'][] = "d:/video/films2/";
 * ������2:
 * $config['rootdir'][] = "/home/media/";
 *
 * ������3:
 * $config['rootdir'][] = "ftp://mediaserver/films/";
 * 
 */
$config['rootdir'][] = "/home/";


/**
 * ����������� ��������� ���������� � ���. 
 *
 * ��������� �������� ������� ��� ���. ���� �� ���������� ����������� �� ����� �����������.
 * ������������ � ������ ������������� ����������� ������� ����.
 *    k - koi8-r
 *    w - windows-1251
 *    i - iso8859-5
 *    a - x-cp866
 *    d - x-cp866
 *    m - x-mac-cyrillic
 * ������1:
 * $config["dir_extensions"]["/home/video/"]["encoding"] = "UTF-8";
 * $config["dir_extensions"]["ftp://10.10.10.10/"]["encoding"] = "k";
 * 
 * �������� ������� ��������� ���������� (����� ����������� ��������� ������, ���� true)
 * $config["dir_extensions"]["/home/video/"]["check_subdir"] = false; //true/false
 * 
 * ��������� ���������� ������ ��� FTP
 * $config["dir_extensions"]["ftp://10.10.10.10/"]["login"] = "anonymous";
 * $config["dir_extensions"]["ftp://10.10.10.10/"]["password"] = "";
 * $config["dir_extensions"]["ftp://10.10.10.10/"]["port"] = 21;
 * $config["dir_extensions"]["ftp://10.10.10.10/"]["timeout"] = 90;
 *
 *
 *  
 */



/* ������� ���������� ��� �������� ������������ ������. �������������� ��������.
 * �� ���������� � ��������������� ������ �� ftp-��������.
 * ������ �������� ������� ����������, ���� ��������� ������������� ������������� ������ �� 
 * �������� ��������. ��� �������� ���������� ����� ������������� ���������� ������������
 * �� ������� ���������� ����� (����������� ����������). ������ ����� ���������� � ��������� ����� �
 * ����������� ������� ������ (��� �������� � ������� ��������).  
 * ��� ������� ����� ����� ��� 1 �����, ��� �������� ������ ����� ����� �� ����������������� (�� ��������� 
 * ������ ����������� DVD ��� ������� � ����������).
 * �������� ���������� ������ ������������.
 * ����� ������ ���� ������� (/).
 * ������1:
 * $config['storages'][] = "d:/films/";
 * $config['storages'][] = "e:/films/";
 * ������2:
 * $config['storages'][] = "/home/media/disk/1/";
 * $config['storages'][] = "/home/media/disk/2/";
 */ 
 

/* ����� ������ ��� Samba/FTP
 * ������ �����:
 * -------------------------------------------------------------------------------------
 * |                 |        �����              |             ���������               |
 * -------------------------------------------------------------------------------------
 * | ���������� ���� | /home/media/disk/1/       | /home/media/disk/1/myfilm.avi       |
 * -------------------------------------------------------------------------------------
 * | Samba-����      | //mediaserver/films1/     | \\mediaserver\films1\myfilm.avi     |
 * | FTP-����        | ftp://mediaserver/films1/ | ftp://mediaserver/films1/myfilm.avi |
 * -------------------------------------------------------------------------------------
 * ��������: ���������� ��������� � �������� ������ ��������� 
 * � �� ������� ��������������� ���� � ������.
 * ����� ��� ������� �������� (Samba) ����������� ������ (/)!
 * �������� ������ �� ������� ��������� ���-������:
 * � �������� ���� � ������ (��������, /home/media/disk/1/myfilm.avi) ������ ���������� 
 * ������ $config['source'][] (��������, /home/media/disk/1/) � ���������� �� $config['ftp'][]
 * (�������� ftp://mediaserver/films1/), ����� ������� ���������� ���-������
 * ftp://mediaserver/films1/myfilm.avi
 * 
 * ������1:
 * $config['source'][] = "d:/films/";
 * $config['smb'][] = "//mediaserver/films1/";
 * $config['ftp'][] = "ftp://mediaserver/films1/";
 * $config['source'][] = "e:/films/";
 * $config['smb'][] = "//mediaserver/films2/";
 * $config['ftp'][] = "ftp://mediaserver/films2/";
 * � ���������� ������ �� ������� ������ ��� ����� "e:/films/film.avi" 
 * ����� ��������� ��� "\\mediaserver\films2\film.avi" � �.�.
 *
 * ������2:
 * $config['source'][] = "/home/media/disk/1/";
 * $config['ftp'][] = "ftp://mediaserver/films1/";
 * $config['source'][] = "/home/media/disk/2/";
 * $config['ftp'][] = "ftp://mediaserver/films2/";
 * � ���������� ������ �� ftp ��� ����� "/home/media/disk/2/film.avi" 
 * ����� ��������� ��� "ftp://mediaserver/films2/film.avi" � �.�.
 * ������� � ������ �� Samb'e � ������ ������� ����� �������������
 */
  $config['source'][] = "/home/";
  $config['smb'][] = "//mediaserver/films/";
  $config['ftp'][] = "ftp://mediaserver/films/";
 



/* ������ ������ �������������
 */
$config['modes'][1]['smb'] = 1; //������ � Samba
$config['modes'][1]['ftp'] = 1; //������ � FTP

$config['modes'][2]['smb'] = 1;
$config['modes'][2]['ftp'] = 1;

$config['modes'][3]['smb'] = 1;
$config['modes'][3]['ftp'] = 1;


//������ ������������� (������������� ���������)
$GUEST = 0;
$DEFAULT_USER = 1;
$MODERATOR = 2;
$ADMIN = 3;

//���������� ����� ����������� �� ��� ������������ ���������� (/templates/default/download.php) (1/0)
$config['ftp_license'] = 1;

//�� ������������ ftp-������ ��� Internet Explorer
$config['do_not_escape_link_for_ie'] = 1;

//��������� mplayer
$config['tempdir'] = '/tmp';       //��������� ���������� ��� �������� ������������� ������, ��-��������� /tmp
$config['mplayer'] = 'mplayer';    //���� ��� ������� mplayer (��������, c:/mplayer/mplayer.exe), ��-��������� mplayer; � ���� �� ������ ���� ��������


$config['translation_options'][] = "";
$config['translation_options'][] = "������";
$config['translation_options'][] = "���������������� ������������";
$config['translation_options'][] = "���������������� �����������";
$config['translation_options'][] = "���������������� �����������";
$config['translation_options'][] = "������������ ������������";
$config['translation_options'][] = "������������ �����������";
$config['translation_options'][] = "������������ �����������";
$config['translation_options'][] = "��������";
$config['translation_options'][] = "��������";
$config['translation_options'][] = "LostFilm";
$config['translation_options'][] = "������ (����������)";
$config['translation_options'][] = "������ (�������)";


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



//��� unix-������ iso8601-������ ���� ������� ls
$config['ls_dateformat_in_iso8601'] = false;


//��������� ��������� ����������� ������� ������ >4��:
$config['disable_4gb_support'] = false;

/**
 * ������� ��� ��������� ������ ��� ������ ������� �� ������� �������� (�� ��������� �������):
 * �������:
 * $config['external_search_engines'][] = '<a href="http://rutracker.org/forum/tracker.php?nm=%s&f[]=-1&o=4" target="_blank"><img border=0 src="http://static.rutracker.org/favicon.ico" align="middle"></a>'; 
 * $config['external_search_engines'][] = '<a href="http://kinozal.tv/browse.php?s=%s" target="_blank"><img border=0 src="http://kinozal.tv/favicon.ico" align="middle"></a>';
 */

/**
 * ��������� ��� �������������� ������� ���� ����������� � �������� ������� %SHORTTRANSLATION%
 */
$config['short_translation'] = array();
$config['short_translation']["������"] = 'Dub';
$config['short_translation']["�� ����� ���������"] = 'Original';
$config['short_translation']["���������������� ������������"] = 'MVO';
$config['short_translation']["������������ ������������"] = 'MVO';
$config['short_translation']["�����������"] = 'VO';
$config['short_translation']["������ (����������)"] = 'AVO(������)';
$config['short_translation']["��������"] = 'Sub';

/**
 * ��������� ����������� ������ � ������� modern
 */
//����� ������ ������������ ����� ��������/���������:
$config['download']['selectable'] = array('smb'=>true, 'dcpp'=>true, 'ed2k'=>true);
//��������� ��-���������
$config['download']['defaults'] = array('smb'=>true, 'dcpp'=>true, 'ed2k'=>true);
//����� ��������� ����� �������� ������������:
$config['download']['players']['selectable'] = array('la'=>false, 'mp'=>true, 'mpcpl'=>true, 'bsl'=>true, 'crp'=>true, 'tox'=>true, 'kaf'=>true, 'pls'=>true, 'xspf'=>true);
//�������� ��-���������:
$config['download']['players']['default'] = 'xspf'; 