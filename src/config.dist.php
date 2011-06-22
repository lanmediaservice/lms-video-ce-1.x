<?php
/**
 * �����-�������
 * (C) 2006-2009 Ilya Spesivtsev, macondos@gmail.com
 *
 * ���� ������������
 *
 * @author Ilya Spesivtsev 
 * @version 1.07
 */

@setlocale(LC_ALL, array('ru_RU.CP1251','ru_RU.cp1251','ru_SU.CP1251','ru','russian'));

ini_set('display_errors', 1);
error_reporting (E_ALL ^ E_NOTICE);

//���������

//������-������ ��� �������
//$config['proxy_host'] = "yourproxy";
//$config['proxy_port'] = "port";

//URL �����. ������������ � �������� ���� ��� ������� "�����", � ������ ���� �������, �� RSS-������
$config['siteurl'] = "http://video.lanmediaservice.com/demo";
$config['sitetitle'] = "�����-�������";

//C�������� ��� ������
$config['can_not_setbookmark'] = "������ ������������������ ������������ ����� ��������� ��������.<br> <a href='?register=1' class='alert_link'>�����������������</a><br>��� ������� ��� ����� �������<br><form action='?' method='post'><input type='hidden' name='logon' value='1'><table border='0' width='100%'><tr><td>�����:</td><td><input name='login'></td></tr><tr><td>������:</td><td><input name='pass' type='password'></td></tr><tr><td colspan='2'><input id='remember' type='checkbox' value='1' name='remember'><label for='remember'>������������� �������</label></td></tr><tr><td colspan='2' align='center'><input type='submit' value='OK'></td></tr></table></form>";
$config['can_not_postcomment'] = "������ ������������������ ������������ ����� ��������� ������.<br> <a href='?register=1' class='alert_link'>�����������������</a> ��� <a href='javascript:Exit();' class='alert_link'>�������</a> ��� ����� �������";
$config['can_not_setrating'] = "<a href='?register=1' class='alert_link'>�����������������</a> ��� <a href='javascript:Exit();' class='alert_link'>�������</a> ��� ����� �������,<br> ����� ������� ��������";

// ������������ �� ��������� �������
// ��������:
// $config['comment_rules'] = "�������������:<ul><li>�� ������ ����������� ������ ���� \\\"�����/�����/�� ��������/� �.�.\\\", ��� ����� ����� ������ ��������� ������ ������</li><li>��������� ������ ��������� ������� ������</li><li>�� �������� � �������� � �������� ������ �������</li><li>������� ������ \\\"������ ��� ����������\\\", ���� ������ ���������� � ������� ��� ��������</li></ul>������������� ������� ��������� �� ����� ����� ������� ��������� �� ������������ ����������";

$config['comment_rules'] = "������������� ������� ��������� �� ����� ����� ������� ��������� �� ������������ ����������";

//����� ������� ������������ ������ ���� ������������ �������������� (0 - ������� �� ������ ��������������) :
$config['scroll_comments'] = 5;


//�������� � ����� ����� ��� �������� �������������Suffix.avi
$config['suffix'] = "-lanmediaservice.com";

//��������� ������� ����������
$config['customer']['login'] = "demo";
$config['customer']['pass'] = "demo";
$config['customer']['updateservice'] = "http://update.lanmediaservice.com/actions.php";
$config['customer']['parser_service'] = 'http://service.lanmediaservice.com/2/actions.php';
$config['customer']['products']['lms_platform'] = "./"; //���� � ������ ����� �������� (���� ���� � ���������)
$config['customer']['products']['lms_video'] = "./"; //���� � ������ ������� �����-��������

//������� ������ mysql
$config['mysqlhost'] = "localhost";
$config['mysqluser'] = "user";
$config['mysqlpass'] = "password";
$config['mysqldb'] = "db";
//$config['mysql_set_names'] = "SET NAMES cp1251";

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

/* ������ ����������� ("�������������" �������) ������������� ������� (���������
 * ���������� � ��������� ������ ���������, ����� ����� ������������ ���� � ������)
 * ������: regex
 *
 * ������1.
 * $config['multipathpattern'] = "/CD\d/";
 * ����� ����� ��� Matrix.CD1.(DVDRip).avi � Matrix.CD2.(DVDRip).avi ����� ���������� ��� 1 �����.
 * ����� ��� Matrix1.avi � Matrix2.avi - ��� 2 ������ ������.
 *
 * ������2.
 * $config['multipathpattern'] = "/\d[^\d]*$/";
 * ����� ����� ��� Matrix1.part1.avi, Matrix1.part2.avi ����� ���������� ��� 1 �����,
 * � Matrix2.part1.avi � Matrix2.part2.avi - ��� ������ �����.
 * �� Matrix1.avi � Matrix2.avi - ��� 1 ����� (!).
 */ 
$config['multipathpattern'] = "/.CD\d+/";


// ���������� ����������� ���������� ������ ��� ����������� ���������� ��������
$config['minratingcount'] = 3;

// ���������� ������������ ������� ��� ������� (��������� ������
// ����� ��������� � �������, � ����� ���������� �� ���� ���������
// � �������� � �������� ���� �������)
$config['maxincoming'] = 20;

// ��������� md5-��� ����� (0/1)?
$config['md5'] = 0;

// �������� � IP-������ ������������ ��� �����������
$config['ip'] = 0;

// ������ �����������
// ��������, ����� �������� ���� ����������� logon.php �� ���������� ��� ����������
$config['logon.php'] = "logon.dist.php";

//���������� ������� ����������� � 1 IP (�����)
$config["register_timeout"] = 60;

// ������ ���������� 
// ������� ��������� � �������� "templates/"
$config['template'] = "flat.dist";
//�������������� ������ ���� (������ ��� ������� flat.dist)  
$config['topmenu_links'] = array(
    array('url'=>'/music/', 'text'=>'������'),
    array('url'=>'/video/', 'text'=>'�����', 'selected'=>true),
    array('url'=>'/forum/', 'text'=>'�����')
);

//�������������� ������ � ������ ������ (������ ��� ������� flat.dist) 
$config['support_links'] = array(
    array('url'=>'/support/', 'text'=>'������ ������'),
    array('url'=>'mailto:support@isp.com', 'text'=>'�������� ������'),
    array('url'=>'/forum/', 'text'=>'�����')
);
//�������� ������� ������������ (������ ��� ������� flat.dist) 
$config['bestsellers_enable'] = true;

//�������� ���� ��������� ������� (������ ��� ������� flat.dist) 
$config['announce_enable'] = false;


//���������� �������� ��� ���������� %DESCRIPTION% � ��������
//�������� ��� ������� � ��������. 0 - �� ������������.
$config['short_description'] = 0;

// ������������ ������ ������ �������� ��� ������������� 
$config["Hide"] = 0;

//������� �� �������� ��������
$config["resultonpage"] = 10;

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

//��-��������� �������� ������� �����
$config['hide_nodes'] = false;

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
 


//��������� ���������� ��� �������� ������������ ������
$config['make_genre_folder'] = 0;

//����� ������ ��� ��������
$config['folder_rights'] = 0644;

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
 * (����������� ������� ����� �������� ��� ������ ������� shaper.php)
 * ���������� ������ "...['limit'][]" ������������ ��� ������� ������.
 * ��������� ���������� �������� ��������� � ��������.
 */
$config['modes'][1]['smb'] = 1; //������ � Samba
$config['modes'][1]['ftp'] = 1; //������ � FTP
$config['modes'][1]['fullspeed'] = 3000; // �������������� ����������� ��������, �����/�
$config['modes'][1]['disablespeed'] = 10; // ����������� �������� ��� ���������� ������������, �����/�
$config['modes'][1]['limit'][] = array("min"=>60, "bytes"=>900000000, "speed"=>260); // ���-�� �����, ����� ����, "�������� ��������" �����/�
$config['modes'][1]['limit'][] = array("min"=>5, "bytes"=>75000000, "speed"=>260); // ���-�� �����, ����� ����, "�������� ��������" �����/�
$config['modes'][1]['limit'][] = array("min"=>1, "bytes"=>30000000, "speed"=>260); // ���-�� �����, ����� ����, "�������� ��������" �����/�

$config['modes'][2]['smb'] = 1;
$config['modes'][2]['ftp'] = 1;
$config['modes'][2]['fullspeed'] = 6000; // �����/�
$config['modes'][2]['disablespeed'] = 10; // �����/�
$config['modes'][2]['limit'][] = array("min"=>60, "bytes"=>1800000000, "speed"=>500); // ���-�� �����, ����� ����, "�������� ��������" �����/�
$config['modes'][2]['limit'][] = array("min"=>5, "bytes"=>1800000000, "speed"=>500); // ���-�� �����, ����� ����, "�������� ��������" �����/�
$config['modes'][2]['limit'][] = array("min"=>1, "bytes"=>1800000000, "speed"=>500); // ���-�� �����, ����� ����, "�������� ��������" �����/�

$config['modes'][3]['smb'] = 1;
$config['modes'][3]['ftp'] = 1;
$config['modes'][3]['fullspeed'] = 10000; // �����/�
$config['modes'][3]['disablespeed'] = 10; // �����/�
$config['modes'][3]['limit'][] = array("min"=>60, "bytes"=>36000000000, "speed"=>10000); // ���-�� �����, ����� ����, "�������� ��������" �����/�
$config['modes'][3]['limit'][] = array("min"=>5, "bytes"=>36000000000, "speed"=>10000); // ���-�� �����, ����� ����, "�������� ��������" �����/�
$config['modes'][3]['limit'][] = array("min"=>1, "bytes"=>36000000000, "speed"=>10000); // ���-�� �����, ����� ����, "�������� ��������" �����/�


//������ ������������� (������������� ���������)
$GUEST = 0;
$DEFAULT_USER = 1;
$MODERATOR = 2;
$ADMIN = 3;

//���������� ����� ����������� �� ��� ������������ ���������� (/templates/default/download.php) (1/0)
$config['ftp_license'] = 1;

//������� �� ���������� � �������� (imdb.com, ozon.ru � �.�.)
$config['connection_timeout'] = 5;

//�� ������������ ftp-������ ��� Internet Explorer
$config['do_not_escape_link_for_ie'] = 1;

//��������� ��� ��������� ������
//������� �������� ������������� ����������� ��������� ������ http://docs.lanmediaservice.com/index.php/���������_������_��_�������
$config['count_frames'] = 8;       //���������� ������������ ������, ��-��������� 8
$config['escape_style'] = "u";     //����� ������������� ��������� ������ ("u" -- Unix / "w" -- Windows), ��-��������� "u"
$config['small_frame_width'] = 80; //������ ������������ �����, ��-��������� 80 ��������
$config['tempdir'] = '/tmp';       //��������� ���������� ��� �������� ������������� ������, ��-��������� /tmp
$config['mencoder'] = 'mencoder';  //���� ��� ������� mencoder (��������, c:/mplayer/mencoder.exe), ��-��������� mencoder; � ���� �� ������ ���� �������� 
$config['mplayer'] = 'mplayer';    //���� ��� ������� mplayer (��������, c:/mplayer/mplayer.exe), ��-��������� mplayer; � ���� �� ������ ���� ��������
$config['vcodec'] = 'mpeg4';       //�����, ��-��������� mpeg4, ������������� ffvhuff



//��������� �������/�������� (��������� ��������� ��������� ������ ��� ������� ������ GD)

//������������ ������ ������������� (������� ���������) �������,
//�� ������� ������ ����� ������������� ����������� ��� ������� �������.
//���� �� ������ ����������� �� �������� � ozon.ru ���������� �������� 160
$config["covers"]["undesirable_size"] =  0; 

// * ����������� ������� � ���� ��������� ���������� � ������
$config["covers"]["defaultcovers"]["width"]    = 160;   // ����������� ������ �������, 
                                                        // ������������� ������� �������� ����� 160 (����������� 
                                                        // ������� �� ozon.ru) � 300
$config["covers"]["defaultcovers"]["maxwidth"] = 240;   // ��������� ���������� ������ ������� (��� ������� ��������
                                                        // ����� ��������� �� ����������� ������), 0 - ��� �����������
$config["covers"]["defaultcovers"]["zoom"]     = false; // �������������� �������� ������� �� ����������� ������,
                                                        // ���� ������ ������ �����������

// * ����������� ����� ������� � ������ ������� � ��������
$config["covers"]["smallcovers"]["width"]    = 60;    // ����������� ������
$config["covers"]["smallcovers"]["maxwidth"] = 60;    // ��������� ���������� ������ ������� (��� ������� �������� 
                                                      // ����� ��������� �� ����������� ������), 0 - ��� �����������
$config["covers"]["smallcovers"]["zoom"]     = true;  // �������������� �������� ������� �� ����������� ������,
                                                      // ���� ������ ������ �����������

// * ������ ������ ������� ��� ���������. 
//�� �������� ������������, ������������ ������ ��� ������ �������� ������� ������� ����������� ������
$config["covers"]["bigcovers"]["width"]    = 300;   //����������� ������
$config["covers"]["bigcovers"]["maxwidth"] = 0;     // ��������� ���������� ������ ������� (��� ������� �������� 
                                                    // ����� ��������� �� ����������� ������), 0 - ��� �����������
$config["covers"]["bigcovers"]["zoom"]     = false; //���������������


/** 
 * ����� ����� ������ �� ����������:
 * 0 (��-���������) - 1 ������������������ ������������ + x ������ = 1 ���������� 
 * 1 - x ������ = x ���������� (�� �������������)
 * 2 - 1 IP-����� + x ������ = 1 ����������
 * 3 - 1 ������ + x ������ = 1 ����������
 */
$config['hitmethod'] = 0;


//�������� � �����������
$config['translation_options'][] = "";
$config['translation_options'][] = "�� ���������";
$config['translation_options'][] = "������";
$config['translation_options'][] = "���������������� ������������";
$config['translation_options'][] = "������������ ������������";
$config['translation_options'][] = "�� ����� ���������";
$config['translation_options'][] = "�����������";
$config['translation_options'][] = "���������������� �����������";
$config['translation_options'][] = "��������";
$config['translation_options'][] = "Lostfilm";
$config['translation_options'][] = "�� ����� ��������� + �����������";
$config['translation_options'][] = "�� ����� ��������� + ������";
$config['translation_options'][] = "�� ����� ��������� + ������������ ������������";
$config['translation_options'][] = "�� ����� ��������� + ���������������� ������������";
$config['translation_options'][] = "Tycoon Studio";
$config['translation_options'][] = "������ (����������)";
$config['translation_options'][] = "������ (�������)";
$config['translation_options'][] = "����������� ������";
$config['translation_options'][] = "�������� ������";
$config['translation_options'][] = "����� ϸ��";
$config['translation_options'][] = "����� ����";


$config['quality_options'][] = "";
$config['quality_options'][] = "�� ����������";
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
$config['typeofmovie_options'][] = "�� ���������";
$config['typeofmovie_options'][] = "�������������� ������";
$config['typeofmovie_options'][] = "�������������� �����";
$config['typeofmovie_options'][] = "������ - ���������� �����";
$config['typeofmovie_options'][] = "�������";
$config['typeofmovie_options'][] = "���������������� �����";
$config['typeofmovie_options'][] = "�����������";
$config['typeofmovie_options'][] = "�������-�����";
$config['typeofmovie_options'][] = "�������������� ����������";
$config['typeofmovie_options'][] = "������� ������������";
$config['typeofmovie_options'][] = "���������� ��������������";
$config['typeofmovie_options'][] = "������������� ���������";
$config['typeofmovie_options'][] = "������������";
$config['typeofmovie_options'][] = "���. ���������";
$config['typeofmovie_options'][] = "���. ����������";
$config['typeofmovie_options'][] = "���. ���������";

/*
 * ����������� ��������� �������� ������
 * $config['websites']['default']... - ����� ���������
 * $config['websites'][www.xxx.yy]... - �������������� ���������
 * ������������� ���������: proxy, user_agent, connection_timeout
 * ������:
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

//������ ������������� ����� � ������������:
$config['include_extensions'] = array('avi','vob','mpg','mpeg','mpe','m1v','m2v','asf','mov','dat','wmv','wm','rm','rv','divx','mp4','mkv','qt','ogm');

//��������������� ����� � ������������:
$config['exclude_extensions'] = array('sub');

//������� � ������� mplayer
$config['mplayer_extensions'] = array('mp4');

//��� unix-������ iso8601-������ ���� ������� ls
$config['ls_dateformat_in_iso8601'] = false;


//��������� ��������� ����������� ������� ������ >4��:
$config['disable_4gb_support'] = false;

//����������������� �������:
//�������� ��������� symlink
$config['follow_symlink'] = false;

//������� ���� ������������ �� ��������� ����� ftp
//$config['ftp_passive_mode'] = true;

//���������� ������������ ������ ������ (��� ����������� 
// ����������� ������ �� HD-�������), 0 - �� ������������
$config['max_frame_width_px'] = 720;

//��������� ����� ������ "����� ������ �� FTP"
$config['ftpfolder_disable'] = false;

/**
 * ������� ��� ��������� ������ ��� ������ ������� �� ������� �������� (�� ��������� �������):
 * �������:
 * $config['external_search_engines'][] = '<a href="http://torrents.ru/forum/tracker.php?nm=%s&f[]=-1&o=4" target="_blank"><img border=0 src="http://static.torrents.ru/favicon.ico" align="middle"></a>';
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
?>