<?php require_once "header.php"; ?>
<!-- ������� ���� -->
<div id="PageSelector"></div>
<!-- /������� ���� -->
<div id="NewsPage" style="display:none;"></div>
<?php if (isset($config['bestsellers_enable']) && $config['bestsellers_enable']): ?> 
    <div id="StartPage" style="display:none;padding:10px;"> 
        <div id="StartBox" style='margin1:10px;'> 
        <?php 
            $itemFilter = isset($config['item_filter'])? $config['item_filter'] : '';
            $result2 = mysql_query("SELECT FilmID, count(*) as c FROM hits WHERE DateHit>(NOW()-INTERVAL 70 DAY) GROUP BY FilmID"); 
            $filmMonthPopularity = array(); 
            while ($result2 && $field2 = mysql_fetch_assoc($result2)) { 
                $filmMonthPopularity[$field2["FilmID"]] = $field2["c"]; 
            } 
            $sql = "SELECT ID, 
                       Name, 
                       OriginalName, 
                       Year, 
                       Poster, 
                       TypeOfMovie 
                   FROM films WHERE films.Hide=0 " . ($itemFilter? "AND $itemFilter" : ''); 
           $result = mysql_query($sql); 
           while ($result && $field = mysql_fetch_assoc($result)){ 
               $films[$field['ID']] = $field; 
           } 
    
           $result2 = mysql_query("SELECT FilmID, GenreID FROM filmgenres"); 
           $filmgenres = array(); 
           while ($result2 && $field2 = mysql_fetch_assoc($result2)){ 
               $filmgenres[$field2["FilmID"]][] = $field2["GenreID"]; 
           } 
    
           $result2 = mysql_query("SELECT FilmID, CountryID FROM filmcountries"); 
           $filmcountries = array(); 
           while ($result2 && $field2 = mysql_fetch_assoc($result2)){ 
               $filmcountries[$field2["FilmID"]][] = $field2["CountryID"]; 
           } 
    
           $result2 = mysql_query("SELECT ID, Name FROM genres"); 
           $genres = array(); 
           while ($result2 && $field2 = mysql_fetch_assoc($result2)){ 
               $genres[$field2["ID"]] = $field2["Name"]; 
           } 
    
           $result2 = mysql_query("SELECT ID, Name FROM countries"); 
           $countries = array(); 
           while ($result2 && $field2 = mysql_fetch_assoc($result2)){ 
               $countries[$field2["ID"]] = $field2["Name"]; 
           } 
    
           $optionSkipDownloaded = (isset($_GET['all_bestsellers']) || $user['Login']=='guest')? false : true;
           $downloadedFilms = array(); 
           if ($optionSkipDownloaded) { 
               $result2 = mysql_query("SELECT FilmID FROM hits WHERE UserID={$user['ID']}"); 
               while ($result2 && $field2 = mysql_fetch_assoc($result2)){ 
                   $downloadedFilms[$field2["FilmID"]] = $field2["FilmID"]; 
               } 
           } 
    
          //calculate categories 
          $categoryIndex = array(); 
          define('CATEGORY_SERIES',1); 
          define('CATEGORY_COMEDY',2); 
          define('CATEGORY_ACTION',3); 
          define('CATEGORY_DOCUMENTARY',4); 
          define('CATEGORY_ANIME',5); 
          define('CATEGORY_ANIMATION',6); 
          define('CATEGORY_HORROR',7); 
          define('CATEGORY_FANTASY_SCFI',8); 
          define('CATEGORY_DRAMA',9); 
    
          $categoryNames = array( 
              CATEGORY_SERIES => '�������', 
              CATEGORY_COMEDY => '�������', 
              CATEGORY_ACTION => '�������', 
              CATEGORY_DOCUMENTARY => '��������������', 
              CATEGORY_ANIME => '�����', 
              CATEGORY_ANIMATION => '�����������', 
              CATEGORY_HORROR => '�����', 
              CATEGORY_FANTASY_SCFI => '����������/�������', 
              CATEGORY_DRAMA => '�����/���������' 
          ); 
    
          foreach ($films as $id => $film) { 
              $weight = isset($filmMonthPopularity[$id])? $filmMonthPopularity[$id] : 0; 
              if (!isset($downloadedFilms[$id])){ 
                  if ($film['TypeOfMovie']=='���. ����������') { 
                          $categoryIndex[CATEGORY_SERIES][$id] = $weight; 
                  } elseif (@in_array(23, $filmgenres[$id])) { 
                          $categoryIndex[CATEGORY_DOCUMENTARY][$id] = $weight; 
                  } elseif (@in_array(25, $filmgenres[$id])) { 
                          $categoryIndex[CATEGORY_ANIME][$id] = $weight; 
                  } elseif (@in_array(13, $filmgenres[$id])) { 
                          $categoryIndex[CATEGORY_ANIMATION][$id] = $weight; 
                  } elseif (@in_array(4, $filmgenres[$id]) && !@in_array(3, $filmgenres[$id]) && !@in_array(7, $filmgenres[$id])) { 
                          $categoryIndex[CATEGORY_COMEDY][$id] = $weight; 
                  } elseif ((@in_array(10, $filmgenres[$id]) || @in_array(5, $filmgenres[$id]))) { 
                          $categoryIndex[CATEGORY_FANTASY_SCFI][$id] = $weight; 
                  } elseif (@in_array(7, $filmgenres[$id])) { 
                          $categoryIndex[CATEGORY_HORROR][$id] = $weight; 
                  } elseif (@in_array(6, $filmgenres[$id]) || @in_array(3, $filmgenres[$id]) || @in_array(18, $filmgenres[$id])) { 
                          $categoryIndex[CATEGORY_ACTION][$id] = $weight; 
                  } elseif ((@in_array(2, $filmgenres[$id]) || @in_array(17, $filmgenres[$id])) && !@in_array(18, $filmgenres[$id])) { 
                          $categoryIndex[CATEGORY_DRAMA][$id] = $weight; 
                  } 
              } 
          } 
    
           $selectedFilms = array(); 
           $categoriesWeight = array(); 
           foreach ($categoryIndex as $categoryId => $categoryContent) { 
               arsort($categoryContent); 
               $c = 0; 
               foreach ($categoryContent as $id=>$weight) { 
                   $c++; 
                   if ($c>5) break; 
                   @$categoriesWeight[$categoryNames[$categoryId]] += $weight; 
                   $film = $films[$id]; 
                   $OriginalName = $film["OriginalName"]; 
                   $str = ""; 
                   for ($i=0;$i<strlen($OriginalName);$i++) { 
                       $str .= "&#".ord($OriginalName{$i}).";"; 
                   } 
                   $posters = preg_split("/(\r\n|\r|\n)/", $film["Poster"]); 
                   $film["Poster"] = $posters[0] ? $posters[0] : "templates/{$config['template']}/images/noposter.jpg"; 
                   $film["OriginalName"] = $str; 
                   $film["OriginalName1252"] = $OriginalName; 
                   $thisFilmCountries = array(); 
                   if (isset($filmcountries[$id])) { 
                       foreach ($filmcountries[$id] as $counryId) { 
                           $thisFilmCountries[] = $countries[$counryId]; 
                       } 
                   } 
                   $film["countries"] = implode(" / ", $thisFilmCountries); 
                   $selectedFilms[$categoryNames[$categoryId]][] = $film; 
               } 
           } 
           arsort($categoriesWeight); 
       ?> 
        <br><table border='0' width='100%' cellspacing='15'> 
        <?php foreach (array_keys($categoriesWeight) as $categoryName): ?> 
            <tr>
            <td colspan=5><span class='sectionheader' ><?php echo $categoryName; ?></span><br><img src='images/hr2.gif' width='327' height='1'><br><td> 
            </tr> 
            <tr> 
            <?php foreach ($selectedFilms[$categoryName] as $film): ?> 
                <td align='center' width='20%' style='padding-top:10px; border: 1px dotted silver; background-color:#F5F5F5;' valign='top'> 
                <a href='#film:<?php echo $film['ID'];?>:1:0'>
                    <img width='100px' height='150px' src='<?php echo $film['Poster'];?>' border='0'><br>
                    <b><?php echo $film['Name'];?></b>
                    <p style='margin:2px;margin-bottom:5px;color:gray'><?php echo $film['OriginalName'];?> (<?php echo $film['Year'];?>)</p>
                </a> 
                </td> 
            <?php endforeach;?> 
            </tr> 
        <?php endforeach; ?> 
        </table>
        <?php if (!isset($_GET['all_bestsellers']) && ($user['Login']!='guest')):?>
            <br><div style='text-align:center;color:gray'>�� ������ ������ ������, ������� �� ��� ������. ����� ����������� ������ ��������� ������� <a href='?all_bestsellers' target='_blank'>�����</a></div>
        <?php endif;?> 
        </div> 
    </div>
<?php endif;?>
<div id="CatalogPage" style="display:none;">
<table style="margin:0"  border="0" cellspacing="0" cellpadding="0"><tr>
<td width="10%" valign="top" class="left-panel" style="padding:0.5em;">
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
<div id="PopListBox"></div>
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
         <input type="button" onClick="Search(search_num);" value="������!">
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
<td width="15%" valign="top" class="left-panel"  style="padding:0.5em;">
<div id="FilmsListBox">&nbsp;
</div>&nbsp;
</td><td width="*" valign="top">
<div id="FilmBox"><table border="0" width="100%" style="height:500px;"><tr><td width="*" valign="middle"><table border="0" width="100%"><tr><td align="center"><b>�������� ����� � �������� ��� �� ��������</b></td></tr></table></td></tr></table></div>&nbsp;
</td></tr></table>
</div>
<?php require_once "footer.php"; ?>

