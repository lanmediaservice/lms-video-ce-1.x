<div id="container">
    <?php require_once "header.php"; ?>
    <div id="main">
        <div id="toolbar" class="toolbar">
            <ul class="breadcrumb">
                <li class="first-child">
                    <a href="#" title="� ������"><span class="icon home"></span></a>
                </li>
                <li>
                    <a id="breadcrumb_level_2" onclick="window.ui.breadcrumbLevel2Handler()">�������</a>
                </li>
            </ul>
            <?php if (in_array($user['UserGroup'], array(2,3,5))): ?>
            <ul class="built-in clickable" id="movie_moder_menu_item">
                <li>
                    <a onclick="window.ui.showMovieModerMenu();" title="��������������"><span class="icon change"></span></a>
                    <div id="movie_moder_wrapper" class="" style="display:none">
                        <table>
                            <tr>
                                <td>��������: </td>
                                <td>
                                    <div class="selectbox-wrapper">
                                        <input id="quality_select" type="text" autocomplete="off">
                                        <select id="quality_options" style="display: none" size="15">
                                            <option>
                                                <?php echo implode("</option><option>", $config['quality_options']); ?>
                                            </option>
                                        </select>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>�������:</td>
                                <td>
                                    <div class="selectbox-wrapper">
                                        <input id="translate_select" type="text" autocomplete="off">
                                        <select id="translate_options" style="display: none" size="15">
                                            <option>
                                                <?php echo implode("</option><option>", $config['translation_options']); ?>
                                            </option>
                                        </select>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td style="text-align: right"><a id="movie_moder_edit_button" class="minibutton" target="_blank"><span>�������������</span></a></td>
                            </tr>
                        </table>
                    </div>
                </li>
            </ul>
            <script>
            (function( $ ){
                $.fn.comboBox = function() {
                    this.each(function() {
                        var timeout;
                        var input = $('input', $(this));
                        var select = $('select', $(this));
                        input.click(function(){
                            select.show();
                            if (timeout) clearTimeout(timeout);
                        }).focus(function(){
                            select.show();
                            if (timeout) clearTimeout(timeout);
                        }).blur(function(){
                            timeout = setTimeout(function(){
                                select.hide();
                            }, 100);
                        });
                        select.focus(function(){
                            if (timeout) clearTimeout(timeout);
                        }).click(function(){
                            input.val(select.val()).focus();
                            input.change();
                            setTimeout(function(){
                                select.hide();
                            }, 100);
                        }).blur(function(){
                            timeout = setTimeout(function(){
                                select.hide();
                            }, 100);
                        });
                    });
                };
            })(jQuery); 
            $j(document).ready(function() {
                $j('.selectbox-wrapper').comboBox();
            });
            </script>            
            <?php endif; ?>
            <ul class="built-in clickable" id="sort_menu_item">
                <li>
                    <a onclick="window.ui.showSortMenu();" title="����������"><span id="sort">���������</span> &#9660;</a>
                    <ul id="sort_wrapper" class="right" style="display:none">
                        <li><a onclick="window.ui.setOrder(0)" data-order="0" data-dir="desc" data-short-text="���������">�� ���� ����������</a></li>
                        <li><a onclick="window.ui.setOrder(1)" data-order="1" data-dir="desc" data-short-text="�������">�� ���� �������</a></li>
                        <li><a onclick="window.ui.setOrder(2)" data-order="2" data-dir="desc" data-short-text="������ (IMDB)">�� �������� imdb.com</a></li>
                        <li><a onclick="window.ui.setOrder(3)" data-order="3" data-dir="desc" data-short-text="������ (��������)">�� ���������� ��������</a></li>
                        <li><a onclick="window.ui.setOrder(4)" data-order="4" data-dir="desc" data-short-text="������ (���)">�� ������������� ��������</a></li>
                        <li><a onclick="window.ui.setOrder(8)" data-order="8" data-dir="desc" data-short-text="� ������ ��������">�� ������������� ������������</a></li>
                        <li><a onclick="window.ui.setOrder(6)" data-order="6" data-dir="desc" data-short-text="����">�� ���������� ������������</a></li>
                    </ul>
                </li>
            </ul>
            <ul class="built-in clickable" id="countries_menu_item">
                <li>
                    <a onclick="window.ui.showCountries();" title="������"><span id="country">����� ������</span> &#9660;</a>
                    <div id="countries_wrapper" class="filter-wrapper" style="display:none">
                        <ul id="countries" class="filter pop"></ul>
                        <ul id="countries_all" class="filter all" style="display:none"></ul>
                        <a id="countries_switcher" class="switcher" onclick="window.ui.switchFilter(this)" style="display:none" data-default-text="�������� ���" data-back-text="�����">�������� ���</a>
                    </div>
                </li>
            </ul>
            <ul class="built-in clickable" id="genres_menu_item">
                <li>
                    <a onclick="window.ui.showGenres();" title="����"><span id="genre">����� ����</span> &#9660;</a>
                    <div id="genres_wrapper" class="filter-wrapper" style="display:none">
                        <ul id="genres" class="filter pop"></ul>
                        <ul id="genres_all" class="filter all" style="display:none"></ul>
                        <a id="genres_switcher" class="switcher" onclick="window.ui.switchFilter(this)" style="display:none" data-default-text="�������� ���" data-back-text="�����">�������� ���</a>
                    </div>
                </li>
            </ul>
        </div>
        <div id="main_wrapper" class="wrapper">
            <div class="sidebar a">
                <div id="random_film" class="inside random-film"></div>
                <div id="pop_films" class="inside"></div>
                <div id="last_comments" class="inside"></div>
                <div id="last_ratings" class="inside"></div>
            </div>  
            <div class="content" id="catalog_wrapper">
                <div class="paginator" id="paginator"></div>
                <div id="catalog" ></div>
            </div>  
            <div class="content" id="bestsellers"></div>
            <div class="content" id="search_results"></div>
        </div>
        <div id="movie_wrapper" class="wrapper"></div>
        <div id="person_wrapper" class="wrapper"></div>
        <div id="settings_wrapper" class="wrapper">
            <div class="sidebar a">
                <ul class="menu-vertical">
                    <?php if ($user['UserGroup']!=0): ?>
                        <li class="menu-item selected" data-page="password-change"><a href="#/settings/page/password-change">����� ������</a></li>
                    <?php endif;?>
                    <?php if (isset($config['download']['selectable']) && count(array_filter($config['download']['selectable']))): ?>
                        <li class="menu-item" data-page="links"><a href="#/settings/page/links">������</a></li>
                    <?php endif; ?>
                    <?php if (isset($config['smb']) && $config['modes'][$user['Mode']]['smb'] && (isset($config['download']['players']['selectable']) && count(array_filter($config['download']['players']['selectable'])))): ?>
                        <li class="menu-item" data-page="videoplayer"><a href="#/settings/page/videoplayer">������������� �����</a></li>
                    <?php endif; ?>
                </ul>
            </div>  
            <div class="password-change content">
                <table>
                    <tr><td>������ ������</td><td><input id="password_old" type='password'></td></tr>
                    <tr><td>����� ������</td><td><input id="password_new" type='password'></td></tr>
                    <tr><td>��������� ����� ������</td><td><input id="password_repeat" type='password'></td></tr>
                </table>
                <a class="minibutton" onclick="window.ui.changePassword()"><span>�������</span></a>
            </div>
            <div id="videoplayer_settings" class="videoplayer content" style="display: none">
                <ul>
                    <?php if (@$config['download']['players']['selectable']['la']): ?>
                        <li><label><input type="radio" name="videoplayer" value="la"> <img border='0' height='24' width='24' src='images/la24.gif'> Light Alloy</label></li>
                    <?php endif;?>
                    <?php if (@$config['download']['players']['selectable']['mp']): ?>
                        <li><label><input type="radio" name="videoplayer" value="mp"> <img border='0' height='24' width='24' src='images/mp24.gif'> Windows Media Player</label></li>
                    <?php endif;?>
                    <?php if (@$config['download']['players']['selectable']['mpcpl']): ?>
                        <li><label><input type="radio" name="videoplayer" value="mpcpl"> <img border='0' height='24' width='24' src='images/mpcpl24.gif'> Media Player Classic</label></li>
                    <?php endif;?>
                    <?php if (@$config['download']['players']['selectable']['bsl']): ?>
                        <li><label><input type="radio" name="videoplayer" value="bsl"> <img border='0' height='24' width='24' src='images/bsl24.gif'> BSPlayer</label></li>
                    <?php endif;?>
                    <?php if (@$config['download']['players']['selectable']['crp']): ?>
                        <li><label><input type="radio" name="videoplayer" value="crp"> <img border='0' height='24' width='24' src='images/mls24.gif'> Crystal Player</label></li>
                    <?php endif;?>
                    <?php if (@$config['download']['players']['selectable']['tox']): ?>
                        <li><label><input type="radio" name="videoplayer" value="tox"> <img border='0' height='24' width='24' src='images/tox24.gif'> xine</label></li>
                    <?php endif;?>
                    <?php if (@$config['download']['players']['selectable']['kaf']): ?>
                        <li><label><input type="radio" name="videoplayer" value="kaf"> <img border='0' height='24' width='24' src='images/kaf24.gif'> kaffeine</label></li>
                    <?php endif;?>
                    <?php if (@$config['download']['players']['selectable']['pls']): ?>
                        <li><label><input type="radio" name="videoplayer" value="pls"> <img border='0' height='24' width='24' src='images/pls24.gif'> Winamp/Mplayer</label></li>
                    <?php endif;?>
                    <?php if (@$config['download']['players']['selectable']['xspf']): ?>
                        <li><label><input type="radio" name="videoplayer" value="xspf"> <img border='0' height='24' width='24' src='images/vlc24.gif'> VLC media player</label></li>
                    <?php endif;?>
                </ul>
                <a class="minibutton" onclick="window.ui.saveVideoplayerSettings()"><span>��������� �����</span></a>
            </div>
            <div id="links_settings" class="links content" style="display: none">
                <ul>
                    <?php if (@$config['download']['selectable']['smb']): ?>
                        <li><label><input type="checkbox" name="links" value="smb"> ��������</label></li>
                    <?php endif;?>
                    <?php if (@$config['download']['selectable']['dcpp']): ?>
                        <li><label><input type="checkbox" name="links" value="dcpp"> DirectConnect (DC++)</label></li>
                    <?php endif;?>
                    <?php if (@$config['download']['selectable']['ed2k']): ?>
                        <li><label><input type="checkbox" name="links" value="ed2k"> eDonkey2000 (eD2k)</label></li>
                    <?php endif;?>
                </ul>
                <a class="minibutton" onclick="window.ui.saveLinksSettings()"><span>���������</span></a>
            </div>
        </div>
    </div>
    <a class="scroll-up" style="display:none" onclick="Effect.ScrollTo($('container'), {duration: 0.15});" title="������"><span class="icon up"></span></a>
    <?php require_once "footer.php"; ?>
</div>
<?php
if (file_exists(dirname(__FILE__) . '/main.after.php')) {
    include_once(dirname(__FILE__) . '/main.after.php');
}
?>