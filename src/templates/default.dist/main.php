<?php require_once "header.php"; ?>
<!-- ������� ���� -->
<div id="PageSelector"></div>
<!-- /������� ���� -->
<div id="CatalogPage" style="display:none;">
<table style="margin:0"  border="0" cellspacing="0" cellpadding="0"><tr>
<td width='1px'><img width='1px' height='500px' src='images/min.gif' border='0'></td>
<td width="10%" valign="top" style="background : #F5F5F5; padding:0.5em;">
<div id="FilterBox">
	<span class='sectionheader'>������� ������:</span><br>
	<table>
	<tr><td>����:</td><td><select id="GenreFilter" style='width:150px' onChange="FillCountries(this.value,document.getElementById('TypeFilter').value); FillTypes(this.value,document.getElementById('CountryFilter').value); DrawCatalog(0);"><option value="0">��� �����</select></td></tr>
	<tr><td>������:</td><td><select id="CountryFilter" style='width:150px' onChange="FillGenres(this.value,document.getElementById('TypeFilter').value); FillTypes(document.getElementById('GenreFilter').value,this.value); DrawCatalog(0);"><option value="0">��� ������</select></td></tr>
	<tr><td>���:</td><td><select id="TypeFilter" style='width:150px' onChange="FillCountries(document.getElementById('GenreFilter').value,this.value); FillGenres(document.getElementById('CountryFilter').value,this.value); DrawCatalog(0);"><option value="">��� ����</select></td></tr>
	</table><br>
	<span class='sectionheader'>����������:</span><br>
	<input type="radio" name="SortField" id="SortField0" checked onClick="DrawCatalog(0);"><label for="SortField0">�� ���� ����������</label><br>
	<input type="radio" name="SortField" id="SortField1" onClick="DrawCatalog(0);"><label for="SortField1">�� ���� �������</label><br>
	<input type="radio" name="SortField" id="SortField2" onClick="DrawCatalog(0);"><label for="SortField2">�� �������� imdb.com</label><br>
	<input type="radio" name="SortField" id="SortField3" onClick="DrawCatalog(0);"><label for="SortField3">�� ���������� ��������</label><br>
	<input type="radio" name="SortField" id="SortField4" onClick="DrawCatalog(0);"><label for="SortField4">�� ������������� ��������</label><br>
	<input type="radio" name="SortField" id="SortField7" onClick="DrawCatalog(0);"><label for="SortField7" title="����������� ��������� ������������ ���� ������� ������ � ����������� ���������">�� ������������</label><br>
	<input type="radio" name="SortField" id="SortField8" onClick="DrawCatalog(0);"><label for="SortField8" title="�� ��������������� ���������� ���������">�� �����������. ������������</label><br>
	<input type="radio" name="SortField" id="SortField6" onClick="DrawCatalog(0);"><label for="SortField6" title="�� ������ ���������� ���������">�� ���������� ������������</label><br>
	<input type="radio" name="SortField" id="SortField5" onClick="DrawCatalog(0);"><label for="SortField5">�� ��������</label><br>
	<input type="checkbox" name="SortFieldDesc" id="SortFieldDesc"  onClick="DrawCatalog(0);" checked><label for="SortFieldDesc">�� ��������</label><br>
<div id="RndTextFilm"></div>
<div id="TopListBox"></div>
<div id="RecommendedBox"></div>
<div id="LastCommentsBox"></div>
<div id="LastRatingsBox"></div>
<div id="RndTextBox"></div>
</div>
</td><td width="*" valign="top" style="padding:0.5em;">
<div id="CatalogBox"></div>
</td></tr></table>
</div>
<div id="SearchPage" style="display:none;">
<br>
<table width="99%" border="0" cellspacing="0" cellpadding="0">
 <tr>
  <td width='1px'><img width='1px' height='500px' src='images/min.gif' border='0'></td>
  <td valign="top">
	<div id="SimpleSearchPage" style="padding-left:1em;">
		<div>�������� ��� ������ � ������� ������ ������:</div>
		<input type="radio" name="whatresult" id="byfilms" checked><label for="byfilms">������</label>
		<input type="radio" name="whatresult" id="bypersones"><label for="bypersones">����</label><br>
		<input type="text" size="50" onKeypress ="search_num++; setTimeout('Search('+search_num+',0);',1500);" id="textsearch">
		<div id="resultsearch" width="100%" style="padding:5px;">
		</div>
	</div>
  </td>
 </tr>
</table>
</div>
<div id="FilmsPage" style="display:none;">
<table border="0" cellspacing="0" cellpadding="0" width='95%'><tr>
<td width='1px'><img width='1px' height='500px' src='images/min.gif' border='0'></td>
<td width="15%" valign="top" style="background : #F5F5F5; padding:0.5em;">
<div id="FilmsListBox">&nbsp;
</div>&nbsp;
</td><td width="*" valign="top">
<div id="FilmBox"><table border="0" width="100%" style="height:500px;"><tr><td width="*" valign="middle"><table border="0" width="100%"><tr><td align="center"><b>�������� ����� � �������� ��� �� ��������</b></td></tr></table></td></tr></table></div>&nbsp;
</td></tr></table>
</div>
<?php require_once "footer.php"; ?>

