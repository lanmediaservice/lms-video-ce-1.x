<?php
/**
 * �����-�������
 * (C) 2006-2009 Ilya Spesivtsev, macondos@gmail.com
 *
 * Back-offic'��� ������
 * ��������� ��������������
 *
 * @author Ilya Spesivtsev
 * @version 1.07
 */

//require_once dirname(__DIR__) . '/app/config.php';

require_once dirname(__DIR__) . "/config.php";
header('Expires: -1');
require_once dirname(__DIR__) . "/functions.php";
session_start();
require_once dirname(__DIR__) . "/" . (isset($config['logon.php']) ? $config['logon.php'] : "logon.php") ;
if (!getRights("admin_view", $user)){
    echo "� ��� ������������ ���� ��� ����, ����� ����� �� ��� ��������";
    exit;
}

?>
<!DOCTYPE html>
<html>
    <head>
        <title>������������� �����-��������</title>
        <meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
        <link rel="icon" type="image/png" href="img/favicon.png" />
        
        <script language="JavaScript" src="../js/prototype-1.7.0.0.js"></script>
        <script language="JavaScript" src="../js/jquery-1.6.2.min.js"></script>
        <script>
            var $j = jQuery.noConflict();
        </script>
        <script language="JavaScript" src="../js/scriptaculous/scriptaculous.js"></script>
        <script language="JavaScript" src="../js/scriptaculous/effects.js"></script>
        <script language="JavaScript" src="../jshttprequest/JsHttpRequest.js"></script>
        <script language="JavaScript" src="../common/jhr_controller.js"></script>
        <script language="JavaScript" src="../js/rsh.js?v=2"></script>
        <script language="JavaScript" src="../js/trimpath/template.js"></script>
        <script language="JavaScript" src="../js/lms-jsf/JSAN.js"></script>
        <script language="JavaScript" src="../js/lms-jsf/LMS.js"></script>
        <script language="JavaScript" src="../js/lms-jsf/LMS/Connector.js"></script>
        <script language="JavaScript" src="../js/lms-jsf/LMS/Signalable.js"></script>
        <script language="JavaScript" src="../js/lms-jsf/LMS/Widgets.js"></script>
        <script language="JavaScript" src="../js/lms-jsf/LMS/Widgets/Factory.js"></script>
        <script>
            //<![CDATA[
            window.dhtmlHistory.create({
                toJSON: function(o) {
                    return Object.toJSON(o);
                },
                fromJSON: function(s) {
                    return s.evalJSON();
                }
            }); 

            JSAN.includePath   = ['js/lms-jsf'];
            JSAN.errorLevel = "warn";
            JSAN.require('LMS.Widgets.Factory'); 

            var API_URL = '../api.php?format=ajax';
            var TEMPLATES = {};
            var SETTINGS = {};
            var REFERENCE = {};
            var LANG = 'ru';
            //less = { env: 'development' };
            JSAN.includePath = ['../js/lms-jsf', '../js', 'js'];
            var EXTERNAL_SEARCH_ENGINES = [<?php if (isset($config['external_search_engines'])){
                $searchEnginesArray = array();
                foreach ($config['external_search_engines'] as $searchEngine) {
                    $searchEnginesArray[] = '"' . addslashes($searchEngine) . '"';
                } 
                echo implode(',', $searchEnginesArray);
            }?>];
            //]]>
        </script>

        <script type="text/javascript" src="../js/json2.js"></script>

        <link rel="stylesheet" href="../js/jquery.plugins/fancybox/jquery.fancybox-1.3.4.css" type="text/css" media="screen" />
        <script type="text/javascript" src="../js/jquery.plugins/fancybox/jquery.fancybox-1.3.4.js"></script>
        <script type="text/javascript" src="../js/jquery.plugins/fancybox/jquery.easing-1.3.pack.js"></script>
        <script type="text/javascript" src="../js/jquery.plugins/fancybox/jquery.mousewheel-3.0.4.pack.js"></script>

        <script type="text/javascript" src="../js/jquery.plugins/jquery.storage.js"></script>
        <script type="text/javascript" src="../js/jquery.plugins/jquery.single_double_click.js"></script>

        <link href="../js/jquery.plugins/chosen/chosen.css" media="screen" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="../js/jquery.plugins/chosen/chosen.jquery.min.js"></script>

        <script type="text/javascript" src="../js/jquery.plugins/jquery.select-reference.js"></script>

        <script type="text/javascript" src="../js/jquery.plugins/jquery.autoresize.js"></script>
        <script type="text/javascript" src="../js/jquery.plugins/jquery.combobox.js"></script>

        <link href="../js/jquery.plugins/organize-images/organize-images.css" media="screen" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="../js/jquery.plugins/organize-images/jquery.organize-images.js"></script>

        <script language="JavaScript" src="../js/modernizr-1.5.min.js"></script>

        <script language="JavaScript" src="../js/lms-jsf/LMS/Widgets/Generic.js"></script>
        <script language="JavaScript" src="../js/lms-jsf/LMS/Widgets/BlockGeneric.js"></script>
        <script language="JavaScript" src="../js/lms-jsf/LMS/Widgets/LayerBox.js"></script>
        <script language="JavaScript" src="../js/lms-jsf/LMS/Widgets/PageIndexBox.js"></script>
        <script language="JavaScript" src="../js/lms-jsf/LMS/Widgets/AnchorBox.js"></script>
        <script language="JavaScript" src="../js/lms-jsf/LMS/Widgets/ListItemBox.js"></script>
        <script language="JavaScript" src="../js/lms-jsf/LMS/Widgets/UnorderedListBox.js"></script>
        <script language="JavaScript" src="../js/LMS/Ajax.js"></script>
        <script language="JavaScript" src="../js/LMS/Action.js"></script>
        <script language="JavaScript" src="../js/LMS/UI.js"></script>
        <script language="JavaScript" src="../js/LMS/Router.js"></script>
        <script language="JavaScript" src="../js/lms-jsf/LMS/i18n.js"></script>
        <script language="JavaScript" src="../js/LMS/i18n/ru.js"></script>
        <script language="JavaScript" src="../js/LMS/i18n/ru/Main.js"></script>
        <script language="JavaScript" src="../js/LMS/Text.js"></script>
        <script language="JavaScript" src="../js/LMS/Date.js"></script>
        <script language="JavaScript" src="../js/LMS/DateFormat.js"></script>
        <script language="JavaScript" src="../js/LMS/LiveDatetime.js"></script>
        <script language="JavaScript" src="../js/LMS/Widgets/Overlay.js"></script>
        <script language="JavaScript" src="../js/lms-jsf/LMS/Utils.js"></script>
        <script type="text/javascript">
            var ajax = new LMS.Ajax();
            ajax.setApiUrl(API_URL);
            var action = new LMS.Action();
            action.setQueryMethod(function(requestParams, callback){ajax.exec(requestParams, callback)});
            var ui = new LMS.UI();
            LMS.Connector.connect('userError', ui, 'showUserError');
            LMS.Connector.connect('userMessage', ui, 'showUserMessage');
            LMS.Connector.connect('highlightElement', ui, 'highlightElement');
            JsHttpRequest.JHRController.SysMessenger = function(text, autoHide) {
                ui.showUserError(500, text, 'warn', autoHide);
            }
            
            JsHttpRequest.JHRController.refresh = function(){
		if (!this.created) this.create();
		var el = $j('#' + this.parent_domid);
		if (el) {
                    if (this.loadings_counter>0) {
                        el.delay(1000).fadeIn(1000);
                    } else{
                        el.clearQueue().hide().css('opacity', 0.7);
                    }
		}
            }
            
            
            var router = new LMS.Router();
            
            $j.fn.organizeImages.defaults.imageProxy = '../imageproxy.php';
            $j.fn.organizeImages.defaults.loadImage = 'img/load-image.gif';
            
        </script>
        <script language="JavaScript" src="js/LMS/Cp/Action.js?v=1.2.0"></script>
        <script language="JavaScript" src="js/LMS/Cp/UI.js?v=1.2.0"></script>
        <script language="JavaScript" src="js/LMS/Cp/Incoming/UI.js?v=1.2.0"></script>
        <script type="text/javascript">
            LMS.Cp.Incoming.UI.incoming.pageSize = <?php echo $config['maxincoming']; ?>;
            
            LMS.Action.addMethods(LMS.Cp.Action);
            LMS.UI.addMethods(LMS.Cp.UI);
            LMS.UI.addMethods(LMS.Cp.Incoming.UI);
        </script>
        <script>
            $j(document).ready(function() {
                window.ui.init();
                if ($j.browser.msie) {
                    $j('body').addClass('msie');
                }
                if ($j.browser.webkit) {
                    $j('body').addClass('webkit');
                }
                if ($j.browser.opera) {
                    $j('body').addClass('opera');
                }
                if ($j.browser.mozilla) {
                    $j('body').addClass('mozilla');
                }
            });
        </script>
        <script type="text/javascript">
            //<![CDATA[
            TEMPLATES.INCOMING = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/incoming.jhtml'));?>";
            TEMPLATES.INCOMING_DETAILS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/incoming-details.jhtml'));?>";
            TEMPLATES.INCOMING_DETAILS_SEARCH_RESULTS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/incoming-details-search-results.jhtml'));?>";
            TEMPLATES.INCOMING_DETAILS_PARSED_INFO = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/incoming-details-parsed-info.jhtml'));?>";
            TEMPLATES.INCOMING_DETAILS_FORM = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/incoming-details-form.jhtml'));?>";
            TEMPLATES.INCOMING_DETAILS_FILES_FORM = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/incoming-details-files-form.jhtml'));?>";
            TEMPLATES.INCOMING_DETAILS_LOCAL_SEARCH_RESULTS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/incoming-details-local-search-results.jhtml'));?>";

            TEMPLATES.TASKS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/tasks.jhtml'));?>";

            TEMPLATES.MOVIE = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/movie.jhtml'));?>";
            TEMPLATES.MOVIES = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/movies.jhtml'));?>";
            TEMPLATES.PERSON = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/person.jhtml'));?>";
            TEMPLATES.PERSONES = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/persones.jhtml'));?>";
            TEMPLATES.USER = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/user.jhtml'));?>";
            TEMPLATES.USERS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/users.jhtml'));?>";
            TEMPLATES.IMAGES_SEARCH_RESULTS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/images-search-results.jhtml'));?>";
            TEMPLATES.ATTACH_FILE_FORM = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/attach-file-form.jhtml'));?>";

            TEMPLATES.UPDATES_CHECK = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/updates-check.jhtml'));?>";
            TEMPLATES.UPGRADE_RESULT = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/upgrade-result.jhtml'));?>";

            //]]>
        </script>
        <link href="css/reset.css" media="screen" rel="stylesheet" type="text/css" />
        <link href="css/content.css" media="screen" rel="stylesheet" type="text/css" />
        <link href="css/layout.css" media="screen" rel="stylesheet" type="text/css" />
        <link href="css/form.css" media="screen" rel="stylesheet" type="text/css" />
        <link href="css/menu.css" media="screen" rel="stylesheet" type="text/css" />
        <link href="css/icons.css" media="screen" rel="stylesheet" type="text/css" />
        <link rel="stylesheet/less" type="text/css" href="css/functions.less">
        <link rel="stylesheet/less" type="text/css" href="css/toolbar.less">
        <link rel="stylesheet/less" type="text/css" href="css/paginator.less">
        <link rel="stylesheet/less" type="text/css" href="css/tabs.less">
        <link rel="stylesheet/less" type="text/css" href="css/cp.less">
        <script language="JavaScript" src="../js/less-1.1.3.min.js"></script>        

        <link href="js/jquery-ui/css/smoothness/jquery-ui-1.8.16.custom.css" media="screen" rel="stylesheet" type="text/css" />
        <script language="JavaScript" src="js/jquery-ui/jquery-ui-1.8.16.custom.min.js"></script>
    </head>
    <body>
        <div style="display:none" id="user_message"></div>
        <div id="container">
            <div id="header">
            </div>
            <header>
                <div class="menu">
                    <ul class="float_right">
                        <li><strong><?php echo $_SESSION['login'];?></strong></li>
                        <li><a href="../" target="_blank">�������</a></li>
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
                <div style="clear:both;"></div>
            </header>            
            
            <div id="content">
                <div id="content-indent">
                    <div class="control-panel" id="control_panel">
                        <div class="tab-selector">
                            <ul>
                                <li class="incoming"><a href="#/incoming">�����������</a></li>
                                <li class="movies" ><a href="#/movies">������</a></li>
                                <li class="persones" ><a href="#/persones">����������</a></li>
                                <li class="users" ><a href="#/users">������������</a></li>
                                <li class="settings" style="display: none;"><a href="#/settings">���������</a></li>
                                <li class="utils" ><a href="#/utils">�������</a></li>
                                <li class="updates" ><a href="#/updates">����������</a></li>
                                <li class="tasks" id="tab-caption-tasks" style="display: none;"><a href="#/tasks">������� �������</a></li>
                            </ul>
                        </div>
                        <div class="tab">
                            <div class="incoming" style="display: none;" id="incoming">
                                <div class="navigation">
                                    <div class="panel" data-mode="collapsed">
                                        <div class="group-operations" style="display: none; float: left">
                                            <a onclick="window.ui.incoming.hideSelected()" class="toolbar-button"><span>������</span></a>
                                            <a onclick="window.ui.incoming.unhideSelected()" class="toolbar-button"><span>��������</span></a>
                                        </div>
                                        <a onclick="window.ui.incoming.autoParseFiles()" class="toolbar-button"><span>���������� ������</span></a>
                                        <a onclick="window.ui.incoming.autoSearch()" class="toolbar-button"><span>���������</span></a>
                                        <a onclick="window.ui.incoming.autoParse()" class="toolbar-button"><span>�����������</span></a>
                                        <a onclick="window.ui.incoming.autoImport()" class="toolbar-button"><span>����������</span></a>
                                        
                                        <a class="filter-hidden checkbox" data-checked="0" onclick="setTimeout(function(){window.ui.incoming.refresh();}, 0)">���������� �������</a>
                                        <a onclick="window.ui.incoming.refresh(true)" class="toolbar-button"><span>��������</span></a>
                                    </div>
                                    <div class="paginator" id="paginator_incoming"></div>
                                </div>
                                <div class="incoming-wrapper"></div>
                            </div>
                            <div class="tasks" style="display: none;" id="tasks">
                                <div class="navigation">
                                    <div class="panel">
                                       <a onclick="window.action.resetFilesTasksTries();window.action.getCurrentStatus();" class="toolbar-button"><span>�������� �������� �������</span></a>
                                       <a onclick="if (confirm('�������� ��� �������?')) {window.action.clearFilesTasks();window.action.getCurrentStatus();}" class="toolbar-button"><span>�������� ���</span></a>
                                       <!--<a class="tasks-autoupdate-switch checkbox" data-checked="1">��������������</a>-->
                                       <a onclick="window.action.getCurrentStatus();" class="toolbar-button"><span>��������</span></a>
                                    </div>
                                </div>
                                <div id="tasks-list"></div>
                            </div>
                            <div class="movies" style="display: none;" id="movies">
                                <div class="navigation">
                                    <div class="filter panel" data-mode="collapsed">
                                        <a class="filter-switcher" onclick="window.ui.switchFilter($j(this).parents('.filter'));">������</a>
                                        <div class="filter-form clearfix">
                                            <div class="filter-block">
                                                �������� ��������:<br>
                                                <input class="form filter-name" type="text" style="width: 120px">
                                            </div>
                                            <div class="filter-block">
                                                �������� �����:<br>
                                                <select class="form filter-quality" style="width: 120px">
                                                    <option value="">�����</option>
                                                    <option value="DVDRip">DVDRip (7812)</option><option value="HDRip">HDRip (1345)</option><option value="BDRip">BDRip (1272)</option><option value=""> (1201)</option>
                                                </select>
                                            </div>
                                            <div class="filter-block">
                                                �����������:<br>
                                                <select class="form filter-translation" style="width: 120px">
                                                    <option value="">�����</option>
                                                    <option value="���������������� ������������">���������������� ������������ (3343)</option><option value=""> (2479)</option><option value="�� ����� ���������">�� ����� ��������� (1805)</option><option value="������">������ (905)</option><option value="���������������� �����������">���������������� ����������� (653)</option><option value="������������ �����������">������������ ����������� (630)</option><option value="���������������� �����������">���������������� ����������� (604)</option><option value="������������ ������������">������������ ������������ (219)</option><option value="���������������� ������������ [��������]">���������������� ������������ [��������] (211)</option><option value="������ [��������]">������ [��������] (182)</option><option value="������������ �����������">������������ ����������� (179)</option><option value="�� ���������">�� ��������� (130)</option><option value="���������������� (������������) [��������]">���������������� (������������) [��������] (96)</option><option value="��������� �����������">��������� ����������� (95)</option><option value="��������">�������� (64)</option><option value="�����������">����������� (62)</option><option value="���������������� ������������ + ��������">���������������� ������������ + �������� (57)</option><option value="������������ (�����������)">������������ (�����������) (52)</option><option value="1001 Cinema">1001 Cinema (51)</option><option value="������������� [��������]">������������� [��������] (49)</option><option value="������ + ��������">������ + �������� (30)</option><option value="����������� ����������">����������� ���������� (29)</option><option value="������������ (�����������)">������������ (�����������) (25)</option><option value="�� ���������">�� ��������� (25)</option><option value="���������������� (������������) [��������]">���������������� (������������) [��������] (23)</option><option value="����� � ����">����� � ���� (21)</option><option value="LostFilm.TV">LostFilm.TV (17)</option><option value="������ (����������)">������ (����������) (16)</option><option value="���������������� ����������� + ��������">���������������� ����������� + �������� (15)</option><option value="���������������� ������������ (LostFilm)">���������������� ������������ (LostFilm) (15)</option><option value="�� ����� ��������� [��������]">�� ����� ��������� [��������] (14)</option><option value="Lostfilm">Lostfilm (14)</option>
                                                </select>
                                            </div>
                                            <div class="filter-block" style="padding-top: 28px;">
                                                <a class="checkbox filter-sortbyname" data-checked="0">����������� �� ��������</a>
                                                <a class="checkbox filter-hidden" data-checked="0">������ �������</a>
                                                <a class="toolbar-button" onclick="window.ui.movies.refresh()"><span>���������</span></a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="paginator" id="paginator_movies"></div>
                                </div>
                                <div class="two-panel">
                                    <div id="movies-list" class="list"></div>
                                    <div id="movie" class="item" data-mode="show-main-form"></div>
                                </div>
                            </div>
                            <div class="persones" style="display: none;" id="persones">
                                <div class="navigation">
                                    <div class="filter panel" data-mode="collapsed">
                                        <a class="filter-switcher" onclick="window.ui.switchFilter($j(this).parents('.filter'));">������</a>
                                        <div class="filter-form clearfix">
                                            <div class="filter-block">
                                                ��� ��������:<br>
                                                <input class="form filter-name" type="text" style="width: 120px">
                                            </div>
                                            <div class="filter-block" style="padding-top: 28px;">
                                                <a class="filter-sortbyname checkbox" data-checked="0">����������� �� �����</a>
                                                <a class="toolbar-button" onclick="window.ui.persones.refresh()"><span>���������</span></a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="paginator" id="paginator_persones"></div>
                                </div>
                                <div class="two-panel">
                                    <div id="persones-list" class="list"></div>
                                    <div id="person" class="item" data-mode="show-main-form"></div>
                                </div>
                            </div>
                            <div class="users" style="display: none;" id="users">
                                <div class="navigation">
                                    <div class="filter panel" data-mode="collapsed">
                                        <a class="filter-switcher" onclick="window.ui.switchFilter($j(this).parents('.filter'));">������</a>
                                        <div class="filter-form clearfix">
                                            <div class="filter-block">
                                                ����� ��������:<br>
                                                <input class="form filter-login" type="text" style="width: 120px">
                                            </div>
                                            <div class="filter-block">
                                                IP ��������:<br>
                                                <input class="form filter-ip" type="text" style="width: 120px">
                                            </div>
                                            <div class="filter-block" style="padding-top: 28px;">
                                                <a class="filter-sortbyname checkbox" data-checked="0">����������� �� ������</a>
                                                <a class="toolbar-button" onclick="window.ui.users.refresh()"><span>���������</span></a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="paginator" id="paginator_users"></div>
                                </div>
                                <div class="two-panel">
                                    <div id="users-list" class="list"></div>
                                    <div id="user" class="item" data-mode="show-main-form"></div>
                                </div>
                            </div>
                            <div class="settings" style="display: none;" id="settings">
                                ���������
                            </div>
                            <div class="utils" style="display: none;" id="utils">
                                <div class="utility ratings-update" data-mode="collapse">
                                    <div class="title" title="������������� � ���������� ��������� IMDb � KinoPoisk">
                                        <a class="minibutton start" onclick="window.ui.updateRatings();$j(this).parent().parent().attr('data-mode', 'expanded')"><span>�����</span></a>
                                        <a class="minibutton expand" onclick="$j(this).parent().parent().attr('data-mode', 'expanded')"><span>+</span></a>
                                        <a class="minibutton collapse" onclick="$j(this).parent().parent().attr('data-mode', 'collapse')"><span>-</span></a>
                                        ������������� ������� ���������
                                    </div>
                                    <div class="table-wrapper">
                                        <table class="silver">
                                            <thead>
                                                <tr>
                                                    <th>���� �������</th>
                                                    <th>���� ����������</th>
                                                    <th>���������</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="started_at"></td>
                                                    <td class="ended_at"></td>
                                                    <td class="message"></td>
                                                    <td class="has_report"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div><br>
                                <div class="utility ratings-local-update" data-mode="collapse">
                                    <div class="title" title="�������� ��������� ��������� ��� ��������� ����������">
                                        <a class="minibutton start" onclick="window.ui.updateLocalRatings(); $j(this).parent().parent().attr('data-mode', 'expanded')"><span>�����</span></a>
                                        <a class="minibutton expand" onclick="$j(this).parent().parent().attr('data-mode', 'expanded')"><span>+</span></a>
                                        <a class="minibutton collapse" onclick="$j(this).parent().parent().attr('data-mode', 'collapse')"><span>-</span></a>
                                        �������� ��������� ���������
                                    </div>
                                    <div class="table-wrapper">
                                        <table class="silver">
                                            <thead>
                                                <tr>
                                                    <th>���� �������</th>
                                                    <th>���� ����������</th>
                                                    <th>���������</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="started_at"></td>
                                                    <td class="ended_at"></td>
                                                    <td class="message"></td>
                                                    <td class="has_report"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div><br>
                                <div class="utility persones-fix" data-mode="collapse">
                                    <div class="title" title="�������� �������������� ���������� � ����������� � ����������� ����������">
                                        <a class="minibutton start" onclick="window.ui.fixPersones();$j(this).parent().parent().attr('data-mode', 'expanded')"><span>�����</span></a>
                                        <a class="minibutton expand" onclick="$j(this).parent().parent().attr('data-mode', 'expanded')"><span>+</span></a>
                                        <a class="minibutton collapse" onclick="$j(this).parent().parent().attr('data-mode', 'collapse')"><span>-</span></a>
                                        ������� � ����������� ����������
                                    </div>
                                    <div class="table-wrapper">
                                        <table class="silver">
                                            <thead>
                                                <tr>
                                                    <th>���� �������</th>
                                                    <th>���� ����������</th>
                                                    <th>���������</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="started_at"></td>
                                                    <td class="ended_at"></td>
                                                    <td class="message"></td>
                                                    <td class="has_report"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div><br>
                                <div class="utility files-check" data-mode="collapse">
                                    <div class="title" title="������������ � ����������� ������">
                                        <a class="minibutton start" onclick="window.ui.checkFiles();$j(this).parent().parent().attr('data-mode', 'expanded')"><span>�����</span></a>
                                        <a class="minibutton expand" onclick="$j(this).parent().parent().attr('data-mode', 'expanded')"><span>+</span></a>
                                        <a class="minibutton collapse" onclick="$j(this).parent().parent().attr('data-mode', 'collapse')"><span>-</span></a>
                                        ������������ ������
                                    </div>
                                    <div class="table-wrapper">
                                        <table class="silver">
                                            <thead>
                                                <tr>
                                                    <th>���� �������</th>
                                                    <th>���� ����������</th>
                                                    <th>���������</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="started_at"></td>
                                                    <td class="ended_at"></td>
                                                    <td class="message"></td>
                                                    <td class="has_report"></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="4">
                                                        �� ��������� ���������� ������������:<br>
                                                        <a class="minibutton" onclick="if (confirm('��������� ������ �� ������������ ����� (��. ����� � ���������� ������������)?')) {window.action.relocateLostFiles();}" title="��������� ������ �� ������������ �����"><span>��������� ������</span></a>
                                                        &nbsp;&nbsp;<a class="minibutton" onclick="if (confirm('������ ������ � ������ ������� (��. ����� � ���������� ������������)?')) {window.action.hideBrokenMovies();}" title="������ ������ � ������ �������"><span>������ ������</span></a>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="updates" style="display: none;" id="updates">
                                <div class="navigation">
                                    <div class="panel">
                                       <a onclick="window.action.checkUpdates();" class="toolbar-button"><span>��������� ����������</span></a>
                                        <div class="links">
                                            <a href="http://www.lanmediaservice.com/" target="_blank">���� ��������������</a>
                                            <a href="http://forum.lanmediaservice.com/" target="_blank">�����</a>
                                            <a href="http://support.lanmediaservice.com/" target="_blank">����������� ���������</a>
                                            <a href="https://github.com/lanmediaservice/lms-video-ce-1.x" target="_blank">����������� GitHub</a>
                                        </div>
                                    </div>
                                </div>
                                <div id="updates-info"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer>
                &copy; ��� ���������������, 2006&ndash;<?php echo date('Y');?> <br>
            </footer>
        </div>
        <div id="add_image" style="display:none" title="�������� �����������"  data-mode="by-search">
            <div class="ai-form">
                <ul class="ai-tab-selector">
                    <li class="ai-tab-caption by-search"><a onclick="$j('#add_image').attr('data-mode', 'by-search')">����� � Google Images</a></li>
                    <li class="ai-tab-caption by-url"><a onclick="$j('#add_image').attr('data-mode', 'by-url')">������ ������</a></li>
                </ul>
                <div class="ai-tab by-search" class="by-search">
                    <input type="text" class="form query" style="width:200px" value="16 blocks">
                    + <input type="text" class="form keyword" value="poster" title="�������������� �������� �����" style="width:100px">
                    &nbsp;/&nbsp; <select class="form type" style="width:120px" >
                        <option value="">�����</option>
                        <option value="vertical">������������</option>
                        <option value="horizontal">��������������</option>
                    </select>
                    <a class="minibutton" onclick="window.ui.beginSearchGoogleImages()"><span>�����</span></a>
                    <div class="search-results"></div>
                </div>
                <div class="ai-tab by-url" class="by-url">
                    URL: <input type="text" class="form url">
                    <a class="minibutton" onclick="var input=$j(this).parent().find('.form.url'); window.ui.addImage(input.val()); input.val(''); $j('#add_image').dialog('close');"><span>��������</span></a>
                </div>
            </div>
        </div>
        <div id="attach_file" style="display:none" title="�������������/������ ������" data-mode="single" data-delete="0">
            <div class="af-form">
            </div>
        </div>
        <div id="view_report" style="display:none" title="�������� ������">
            <div class="report">
            </div>
        </div>
        <div id="JHRControllerLoaderBox" style="display: none"><img src="img/wait.gif"></div>
    </body>
</html>
