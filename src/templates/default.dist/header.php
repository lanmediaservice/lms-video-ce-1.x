<script>
//�����, ���� �����, ����� �������������� javascript-������� �� index.php
</script>
<div id="waiticon" style="position:absolute; top:0px; left:0px; display:none;"><img src="images/wait.gif" border="0"></div>
<div align="right" style="width:100%">
������, <?php echo $_SESSION['login'];?> |
 <a target='_blank' href='settings.php'>���������</a> | <a target='_blank' href='faq/'>FAQ</a> |
<?php
if (getRights("admin_view",$user)){
	echo "<a href='admin.php'>������ ����������</a> |";
}
?>
 <a href='javascript:Exit();'>�����</a>
</div>