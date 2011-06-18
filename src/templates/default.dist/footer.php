<table cellspacing="0" cellpadding="0" width="100%">
<tr height="63px">
	<td width="13px" class="footer1">&nbsp;</td>
	<td width="5%" style="text-align : left;" class="footer2" nowrap>
<?php
$result = mysql_query("SELECT count(*) FROM films WHERE Hide=0");
$field = mysql_fetch_row($result);
$result2 = mysql_query("SELECT sum(Size) FROM films INNER JOIN files ON(files.FilmID = films.ID) WHERE Hide=0");
$field2 = mysql_fetch_row($result2);
echo "Фильмов в базе: ".$field[0]." (".round($field2[0]/1000000000)." ГБт), <a target='_blank' href='film_list.php'>полный список</a>, <a target='_blank' href='rss_films.php'><img style='border:0;' src='images/rss.gif'></a>";
$result = mysql_query("SELECT count(*) FROM comments");
$field = mysql_fetch_row($result);
echo "<br>Отзывов: ".$field[0]. " <a target='_blank' href='rss_comments.php'><img style='border:0;' src='images/rss.gif'></a>";
$result = mysql_query("SELECT count(*) FROM userfilmratings");
$field = mysql_fetch_row($result);
echo "<br>Оценок: ".$field[0];
?>
<!--	</td>
	<td width="5%" style="text-align : left; padding-left:10px"  class="footer2" nowrap>
		<a target='_blank' href='rss_films.php'><table class='rss' cellspacing='1px'><tr><td class='left'>RSS</td><td  class='right' nowrap>Последние поступления</td></tr></table></a>
		<a target='_blank' href='rss_comments.php'><table class='rss' cellspacing='1px'><tr><td class='left'>RSS</td><td  class='right' nowrap>Последние отзывы</td></tr></table></a>
		<a target='_blank' href='film_list.php'><table class='rss' cellspacing='1px'><tr><td class='left'>HTML</td><td  class='right' nowrap>Cписок всех фильмов</td></tr></table></a>
	</td>
-->	
	<td width="*" class="footer2">&copy; 2006, Спесивцев И.А. (macondos)<div style='text-align:right;font-size:8pt;' id="gen_time"></div></td>
	<td width="13px" class="footer3">&nbsp;</td>
</tr>
</table>
