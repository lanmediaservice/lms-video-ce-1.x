<script>
//Здесь, если нужно, можно переопределить javascript-функции из index.php
<?php if (isset($config['bestsellers_enable']) && $config['bestsellers_enable']): ?> 
        var pagescontent = new Array('page:0','page:1','page:2','page:3'); 
        var pagesrealcontent = new Array('page:0','','',''); 
        var pagestitle = new Array(SITE_TITLE, SITE_TITLE + ": Поиск", SITE_TITLE, SITE_TITLE); 
        var MyPages = new Pages( 
                Array("CatalogPage", "SearchPage", "FilmsPage", "StartPage"), 
                Array("Каталог", "Поиск", "Кино", "<span style='color:#887700;'>Бестселлеры</span>"), 
                Array("if (document.getElementById('backbox')) document.getElementById('backbox').innerHTML = ''","if (document.getElementById('backbox')) document.getElementById('backbox').innerHTML = ''", "", ""), 
                "PageSelector", 
                "MyPages" 
        ); 
    <?php if (isset($_GET['all_bestsellers']) || ($user['Login']=='guest')): ?> 
        function updateUI(newLocation, historyData) { 
            action = newLocation.split(":"); 
            for (i=0;i<3;i++){ 
                if (pagesrealcontent[i].length && (pagesrealcontent[i]==newLocation)){ 
                    document.title = pagestitle[i]; 
                    MyPages.select(i); 
                    return; 
                } 
            } 
            switch (action[0]){ 
                case '': 
                        window.location = "#" + pagescontent[3]; 
                break; 
                case 'page': 
                    document.title = pagestitle[i]; 
                    MyPages.select(action[1]); 
                    pagescontent[action[1]] = newLocation; 
                    pagesrealcontent[action[1]] = newLocation; 
                    setCookie ("page"+action[1], newLocation); 
                break; 
                case 'film': 
                    DrawFilm(action[1],action[2],action[3]); 
                    pagescontent[2] = newLocation; 
                    pagesrealcontent[2] = newLocation; 
                    setCookie ("page2", newLocation); 
                break; 
            } 
        } 
    <?php endif; ?>
<?php endif;?>
</script>
<div id="waiticon" style="position:absolute; top:0px; left:0px; display:none;"><img src="images/wait.gif" border="0"></div>
<?php if (isset($config['topmenu_links']) && is_array($config['topmenu_links']) && count($config['topmenu_links'])): ?>
    <div class="topmenu topmenu-links">
    <?php foreach ($config['topmenu_links'] as $key=>$menuItem):?>
        <?php if ($key>0) echo " | ";?>
        <?php if (@$menuItem['selected']):?>
            <span class="selected"><?php echo $menuItem['text'];?></span>
        <?php else:?>
            <a href="<?php echo htmlspecialchars($menuItem['url']);?>" target="_blank"><?php echo $menuItem['text'];?></a>
        <?php endif;?>
    <?php endforeach;?>
    </div>
<?php endif; ?>
<div class="topmenu" align="right" style="margin-left:-20px; margin-right:-20px;">
Привет, <?php echo $_SESSION['login'];?> |
 <a target='_blank' href='settings.php'>Настройки</a> | <a target='_blank' href='faq/'>FAQ</a> |
<?php
if (getRights("admin_view",$user)){
        echo "<a href='admin.php'>Панель управления</a> |";
}
?>
 <a href='javascript:Exit();'>Выход</a> &nbsp;&nbsp;&nbsp;
</div><br>
<?php if (isset($config['announce_enable']) && $config['announce_enable']): ?> 
    <div id='announce_box' style='background-color:white; border:1px solid silver;margin-top:5px;margin-bottom:10px;'>
        <span style='float:left;padding-left:15px;font-weight:bold;font-family:Verdana;color:#666666'>Ожидаемые фильмы:</span>
        <a href='javascript:Hide("announce_box")'><img src='images/delete_16.gif' border=0 style='float:right;margin-right:3px;margin-top:3px;background:#F5F5F5;'></a>
        <span style='padding-left:3px;padding-rigth:3px;border:1px solid silver;color:gray;float:right;margin-right:20px;cursor:pointer;' onclick='if ((window.announce_frame.document.body.scrollHeight-getScrollY(window.announce_frame))>395) window.announce_frame.scrollTo(0, 245*Math.round(getScrollY(window.announce_frame)/245) + 245);'>&rarr;</span>
        <span style='padding-left:3px;padding-rigth:3px;border:1px solid silver;color:gray;float:right;margin-right:10px;cursor:pointer;' onclick='window.announce_frame.scrollTo(0, 245*Math.round(getScrollY(window.announce_frame)/245) - 245);'>&larr;</span><br>
        <iframe name='announce_frame' style='margin-top:3px;' scrolling=no frameborder=0 src="announce/announce.php" width="100%" height="160px" allowtransparency="true"></iframe>
    </div>
<?php endif; ?>