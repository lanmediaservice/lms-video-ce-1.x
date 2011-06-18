<?php

if (isset($_GET['exit'])){
	setcookie ("announce_login", "");
	setcookie ("announce_pass", "");
	$_SESSION['announce_login'] = "";
	$_SESSION['announce_pass'] = "";
}

if (isset($_REQUEST['logon'])){
	if (isset($_POST['login']) && isset($_POST['pass'])){
		$_SESSION['announce_login'] = $_POST['login'];
		$_SESSION['announce_pass'] = $_POST['pass'];
	}
}

$login = isset($_SESSION['announce_login']) ? $_SESSION['announce_login'] : "";
$pass = isset($_SESSION['announce_pass']) ? $_SESSION['announce_pass'] : "";

$valid = ($login==$config["login"]) && ($pass==$config["pass"]);

if (!$valid)
{
?>
<html>
<head>
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<style type="text/css">
* {font-size:100.01%; font-family: Arial;}
body {font-size:0.8em}
form {margin:0}
table table td {padding:0.25em}
</style>
</head>
<body>
<table border="0" width="100%" height="100%">
<tr>
<td align="center">
<?php
		echo "<div style='width:23em; text-align:left; border: 1px solid silver; background: #F5F5F5; padding:15px;'><span style='font-size:150%; font-weight:bold; color:black;'>Вход</span><br><span style='font-size:85%; color:gray'>для зарегистрированных пользователей</span>";
		echo "<form action='?' method='post' id='myform'>";
		echo "<input type='hidden' name='logon' value='1'>";
		echo "<table border='0' width='100%'>";
		echo "<tr><td>Логин:</td><td><input name='login'></td></tr>";
		echo "<tr><td>Пароль:</td><td><input name='pass' type='password'></td></tr>";
		echo "<tr><td colspan='2' align='center'><input type='submit' value='OK'></td></tr>";
		echo "</table>";
		echo "</form></div><br>";
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
