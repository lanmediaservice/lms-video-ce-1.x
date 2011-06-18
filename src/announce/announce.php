<?php
header("Expires: Sat, 28 May 1999 22:27:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Cache-Control: post-check=0, pre-check=0");
ini_set('display_errors',0);
error_reporting (E_ALL ^ E_NOTICE);
@include "data.inc.php";
?>
<html>
<head>
<title>Ожидаемые фильмы</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<style>
BODY{
	margin:3px;
	padding:0px;
}

BODY, INPUT, DIV, TABLE{
	font-family: Tahoma, Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10pt;
}
TABLE.announce{
	font-size: 8pt;
}

TABLE.announce TD{
	border:1px solid silver;
	vertical-align : top;
}
</style>
<script>
function mouseWheel(event){ 
   var delta = 0; 
   if (!event) event = window.event; 
   if (event.preventDefault) event.preventDefault(); 
   event.returnValue = false; 
} 

if (window.addEventListener) { 
   window.addEventListener('DOMMouseScroll',mouseWheel,false); 
} 
window.onmousewheel = document.onmousewheel = mouseWheel;
</script>
</head>
<body style="background-color: white;">
<table1 class="announce"><tr1>
<?php
        if (!isset($data["films"]))  $data["films"] = array();
        $films = $data["films"];
	$sortarray = array();
        foreach($films as $id=>$film){
		$sortarray[$id] = rand(1,100)-$film['hit'];
	}	
	array_multisort($sortarray,$films);
        foreach($films as $id=>$film){
		if ($film['visible'] || isset($_REQUEST['test'])){
                	echo "<div style='background-color: white;float:left;margin:10px;margin-bottom:100px;margin-top:0px;border:1px solid silver;text-align:center;'>";
			if (@$film['image']) echo "<img src=".htmlspecialchars($film['image'])." height=105><br>";
			echo "<div style='font-size: 8pt;text-align:center;'>";
			if (@$film['name']) echo htmlspecialchars($film['name']);
			echo "<br>";
			if (@$film['originalname']) echo htmlspecialchars($film['originalname']);
			echo "<br>";
			if (@$film['trailer']) echo  "<a href='".htmlspecialchars($film['trailer'])."'>трейлер</a>";
			if (@$film['trailer_label']) echo  "&nbsp;(".htmlspecialchars($film['trailer_label']).")";
			if (@$film['trailer'] && @$film['infourl']) echo " | ";
			if (@$film['infourl'])  echo  "<a target='_blank' href='".htmlspecialchars($film['infourl'])."'>инфо</a>";
			echo "</div>";
			echo "</div>";
		}
        }
?>
</tr1></table1>
</body>
</html>
