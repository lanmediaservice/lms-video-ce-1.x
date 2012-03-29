<div style="display:none" id="user_message"></div>
<div id="JHRControllerLoaderBox" style="display: none"><img src="<?php echo "templates/{$config['template']}";?>/img/wait.gif"></div>
<?php //include_once dirname(__FILE__) . "/misc/help01.php"; ?>
<header>
    <div class="menu">
        <ul class="float_right">
            <?php if ($user['UserGroup']!=0): ?>
                <li><strong><?php echo $_SESSION['login'];?></strong></li>
                <li><a href='#/settings'>���������</a></li>
                <?php if (getRights("admin_view",$user)): ?>
                    <li><a href="cp/">������ ����������</a></li>
                <?php endif; ?>
                <?php //<li><a id="get_help" href="#help">�������</a></li> ?>
                <li><a href='javascript:Exit();'>�����</a></li>
            <?php else: ?>
                <li><a href='#/settings'>���������</a></li>
                <?php //<li><a id="get_help" href="#help">�������</a></li> ?>
                <li><a href='javascript:Exit();'>�����</a></li>
            <?php endif; ?>
        </ul>
        <ul class="">
            <?php if (isset($config['topmenu_links']) && is_array($config['topmenu_links']) && count($config['topmenu_links'])): ?>
                <?php foreach ($config['topmenu_links'] as $key=>$menuItem):?>
                    <li>
                    <?php if (@$menuItem['selected']):?>
                        <span class="selected"><?php echo $menuItem['text'];?></span>
                    <?php else:?>
                        <a href="<?php echo htmlspecialchars($menuItem['url']);?>" target="_blank"><?php echo $menuItem['text'];?></a>
                    <?php endif;?>
                    </li>
                <?php endforeach;?>
            <?php endif; ?>
        </ul>
    </div>
    <div class="toolbar top">
        <ul id="nav">
            <li class="bestsellers"><a onclick="window.ui.routeTo({}, 'bestsellers');">�����������</a></li>
            <li class="catalog"><a onclick="window.ui.catalogButtonHandler();">�������</a></li>
            <li id="search_submenu_item" class="search" style="display:none;"><a onclick="window.ui.searchButtonHandler();">���������� ������</a></li>
        </ul>
        <ul class="search">
            <li>
                <input id="search_query" name="search_text" placeholder="����� ..." autocomplete="off" value="" type="text">
                <div id="search_suggestion" style="display:none"></div>
            </li>
        </ul>
        <?php if ($user['UserGroup']!=0): ?>
            <ul class="built-in clickable" id="bookmarks_menu_item">
                <li>
                    <a title="��� ��������" onclick="window.ui.showBookmarksMenu();"><span class="icon star"></span> &#9660;</a>
                    <ul id="bookmarks" class="right" style="display:none">
                    </ul>
                </li>
            </ul>
        <?php endif; ?>
        <ul class="built-in clickable" id="recently_viewed_menu_item" style="display:none">
            <li>
                <a title="������� �����������" onclick="window.ui.showRecentlyViewedMenu();"><span class="icon view"></span> &#9660;</a>
                <ul id="recently_viewed" class="right" style="display:none"></ul>
            </li>
        </ul>
    </div>
    <div style="clear:both;"></div>
</header>