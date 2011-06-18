<?php
/**
 * (C) 2006 Ilya Spesivtsev, iljasp@tut.by
 *
 * �����������/����������� � ����������� cookies
 *
 * @author Ilya Spesivtsev
 * @version 1.05
 */

require_once dirname(__FILE__) . "/common/crypt/crypter.php";

$idSQLConnection = mysql_connect($config['mysqlhost'], $config['mysqluser'], $config['mysqlpass']);

if ( !$idSQLConnection )
{
	echo "����������� ������ �� �������. ������ ��� ����������� � ���� ������.";
	exit;
}

$result = mysql_select_db( $config['mysqldb'], $idSQLConnection );
if ( !$result )
{
	echo "����������� ������ �� �������. ������ ��� ������ ���� ������.";
	exit;
}

if (isset($config['mysql_set_names'])) mysql_query($config['mysql_set_names']);

  if (getenv("HTTP_CLIENT_IP")) $IP = getenv("HTTP_CLIENT_IP");
  else if (getenv("HTTP_X_FORWARDED_FOR")) $IP = getenv ("HTTP_X_FORWARDED_FOR");
  else if (getenv("REMOTE_ADDR")) $IP = getenv("REMOTE_ADDR");
  else $IP = "";

$email = "";
$errors = "";

if (isset($_GET['register'])){
	$_SESSION['login'] = "";
	$_SESSION['pass'] = "";
	$_COOKIE['login'] = "";
	$_COOKIE['pass'] = "";
}

if (isset($_POST['register'])){
		$_SESSION['login'] = "";
		$_SESSION['pass'] = "";
		if (isset($_POST['login']) && isset($_POST['pass']) && isset($_POST['pass2'])){
			$login = $_POST['login'];
			$pass = $_POST['pass'];
			$pass2 = $_POST['pass2'];
			$email = addslashes($_POST['email']);
			$error = 0;
			if (strlen($login)<3) {$error = 1; $errors .= "������. ����� �������� ����� 3 ��������.<br>";}
			if (strlen($login)>16) {$error = 1; $errors .= "������. ����� �������� ����� 16 ��������.<br>";}
			if (!preg_match('{^[a-zA-Z0-9][a-zA-Z0-9]*[a-zA-Z0-9]$}',$login)){$error = 1; $errors .= "������. ����� ������ �������� ������ �� ��������� ���� ��� ����.<br>";}
			if (strlen($pass)<3) {$error = 1; $errors .= "������. ������ �������� ����� 3 ��������.<br>";}
			if (strlen($pass)>16) {$error = 1; $errors .= "������. ������ �������� ����� 16 ��������.<br>";}
			if (!preg_match('{^[a-zA-Z0-9][a-zA-Z0-9]*[a-zA-Z0-9]$}',$pass)){$error = 1; $errors .= "������. ������ ������ �������� ������ �� ��������� ���� ��� ����.<br>";}
			if ($pass2!=$pass){$error = 1; $errors .= "������. ������ �� ���������.<br>";}
			if (!$error){
				$result = mysql_query("SELECT * FROM users WHERE Login='$login'");
				if ($result && mysql_num_rows($result)>0) {$error = 1; $errors .= "������. ��������� ���� ����� ��� �����.<br>";}
			}
			if (!$error && $config["register_timeout"]){
				$result = mysql_query("SELECT * FROM users WHERE IP='$IP' AND RegisterDate > (NOW() - INTERVAL {$config['register_timeout']} MINUTE) ");
				if ($result && mysql_num_rows($result)>0) {$error = 1; $errors .= "������. ��� ������ IP: $IP ��� ���������� ������������������ ������������.<br>";}
			}
			if (!$error){
				$usergroup = 1;
				$result = mysql_query("SELECT ID FROM users");
				if ($result && mysql_num_rows($result)==0) $usergroup = 3;
				$passmd5 = md5($pass);
				$result = mysql_query("INSERT INTO users(Login, Password, IP, Email, Balans, UserGroup, RegisterDate,Preferences) VALUES('$login','$passmd5','$IP','$email',1, $usergroup, NOW(),'')");
				if (!$result){
					$errors .=  "������ ��� ��������� ������� � MySQL. ���������� � <a href='mailto:macondos@inbox.ru'>��������������</a><br>";
					$errors .= mysql_errno() . ": " . mysql_error(). "\n";
				}
			}
		}
}


$crypter = new Crypter(MODE_ECB,'blowfish', 'key52345346_change_it');

if (isset($_REQUEST['logon'])){
        if (isset($_POST['login']) && isset($_POST['pass'])){
                $_SESSION['login'] = $_POST['login'];
                $_SESSION['pass'] = $_POST['pass'];
                if (isset($_POST['remember'])){
                        setcookie ("login", base64_encode($crypter->encrypt($_POST['login'])),time()+1209600);
                        setcookie ("pass", base64_encode($crypter->encrypt($_POST['pass'])),time()+1209600);
                }
        }
}

$login = isset($_SESSION['login']) ? $_SESSION['login'] : ( isset($_COOKIE['login']) ? trim($crypter->decrypt(base64_decode($_COOKIE['login']))) : "");
$pass = isset($_SESSION['pass']) ? $_SESSION['pass'] : ( isset($_COOKIE['pass']) ? trim($crypter->decrypt(base64_decode($_COOKIE['pass']))) : "");

$_SESSION['login'] = $login;
$_SESSION['pass'] = $pass;

$user = GetUserID($login,$pass);
$exit = isset($_GET['exit']) ? $_GET['exit'] : 0;
if (!$exit && !$user && !$login && !$pass && !isset($_REQUEST['register'])) {
	$login = "guest";
	$pass = "guest";
	$user = GetUserID($login,$pass);
	if ($user){
		$_SESSION['login'] = $login;
		$_SESSION['pass'] = $pass;
	}
	
}
if (!$user)
{
?>
<html>
<head>
<title>�����-�������</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<style type="text/css">
* {font-size:100.01%; font-family: Arial;}
body {font-size:0.8em}
form {margin:0}
table table td {padding:0.25em}
</style>
</head>
<body <?php if (!$exit && (!isset($_POST['register']) && !isset($_GET['register']))) echo "onLoad='document.getElementById(\"myform\").action = window.location.toString();'"; ?> >
<table border="0" width="100%" height="100%">
<tr>
<td align="center">
<?php
	if (!isset($_POST['register']) && !isset($_GET['register'])){
		if ($errors) echo "<div style='width:40em; text-align:left; border: 1px solid silver; background: #FFAAAA; padding:15px;'>$errors</div><br>";
		echo "<div style='width:23em; text-align:left; border: 1px solid silver; background: #F5F5F5; padding:15px;'><span style='font-size:150%; font-weight:bold; color:black;'>����</span><br><span style='font-size:85%; color:gray'>��� ������������������ �������������</span>";
		echo "<form action='?' method='post' id='myform'>";
		echo "<input type='hidden' name='logon' value='1'>";
		echo "<table border='0' width='100%'>";
		echo "<tr><td>�����:</td><td><input name='login'></td></tr>";
		echo "<tr><td>������:</td><td><input name='pass' type='password'></td></tr>";
		echo "<tr><td colspan='2'><input id='remember' type='checkbox' value='1' name='remember'><label for='remember'>������������� �������</label></td></tr>";
		echo "<tr><td colspan='2' align='center'><input type='submit' value='OK'></td></tr>";
		echo "</table>";
		echo "<a href='?register=1'>�����������</a>";
		echo "</form></div><br>";
	}else{
		if (!$errors && $config["register_timeout"] && ($result = mysql_query("SELECT * FROM users WHERE IP='$IP' AND RegisterDate > (NOW() - INTERVAL {$config['register_timeout']} MINUTE) ")) && (mysql_num_rows($result)>0)){
			echo "<div style='width:40em; text-align:center; border: 1px solid silver; background: #FFAAAA; padding:15px;'>��� ������ IP: $IP ��� ���������� ������������������ ������������. <a href='?exit=1'>����</a></div><br>";
		}else{
			if ($errors) echo "<div style='width:40em; text-align:left; border: 1px solid silver; background: #FFAAAA; padding:15px;'>$errors</div><br>";
			echo "<div style='width:40em; text-align:left; border: 1px solid silver; background: #F5F5F5; padding:15px;'><span style='font-size:150%; font-weight:bold; color:black;'>�����������</span><br><span style='font-size:85%; color:gray'>��� ����� �������������</span><br><br>";
			echo "<form action='?' method='post'  id='myform'>";
			echo "<input type='hidden' name='register' value='1'>";
			echo "<input type='hidden' name='logon' value='1'>";
			echo "<table border='0' width='100%'>";
			echo "<tr><td>�����:</td><td><input name='login' type='text' value='$login'></td><td>";
			echo "<span style='font-size:85%; color:gray'>(����� ������ ���� �� 3 �� 16 ��������� ���� ��� ����)</span></td></tr>";

			echo "<tr><td>������:</td><td><input name='pass' type='password'></td><td rowspan='2'><span style='font-size:85%; color:gray'>(������ ������ ���� �� 3 �� 16 ��������� ���� ��� ����)</span></td></tr>";
			echo "<tr><td>��������� ������:</td><td><input name='pass2' type='password'></td></tr>";

			echo "<tr><td>Email:</td><td><input name='email' type='text' value='$email'></td><td rowspan='3'><span style='font-size:85%; color:gray'></span></td></tr>";
			echo "<tr><td colspan='3'><input id='remember2' type='checkbox' value='1' name='remember'><label for='remember2'>������������� �������</label></td></tr>";
			echo "<tr><td colspan='3' align='center'><input type='submit' value='OK'></td></tr>";
			echo "<tr><td colspan='3' align='left'><span style='font-size:xx-small'>��������! ��� �������������� (avi-�����) ������������ ������������� ��� ������������, ��� ����� ������������� �������������. ����� � ��������� ������� ����������� �� �������� ����������������. ����� ������������ ������������� ��� ���������� �������� ���������������� ���������. ����� ���������������� ��������� ����������� ������������ DVD-���� ��� ������������ � ������������� �������.</span></td></tr>";
			echo "</table>";
			echo "<a href='?exit=1'>����</a>";
			echo "</form></div>";
		}
	}
?>

	</td>
	</tr>
	</table>
	</body>

	</html>

<?php
	exit;
}
?>