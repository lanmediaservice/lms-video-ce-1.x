<?php
/**
 * Видео-каталог
 * (C) 2006-2009 Ilya Spesivtsev, macondos@gmail.com
 *
 * Интерфейс видео-каталога
 *
 * @author Ilya Spesivtsev 
 * @version 1.07
 */
header('Expires: -1');
require_once "config.php"; 
require_once "jshttprequest/php.php"; 
require_once "functions.php"; 
session_set_cookie_params(86400); 
session_start();
require_once isset($config['logon.php']) ? $config['logon.php'] : "logon.php" ;
$gd_loaded = function_exists('imagecreatefromgif');
?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo isset($config['sitetitle']) ? $config['sitetitle'] : "Видео-каталог"; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<link rel="stylesheet" href="<?php echo "templates/{$config['template']}/styles.css" ?>">
<link rel="alternate" type="application/rss+xml" title="Последние поступления" href="rss_films.php" />
<?php 
    $lessFile = dirname(__FILE__) . "/templates/{$config['template']}/css/styles.less";
    if (file_exists($lessFile)): ?>
        <link rel="stylesheet/less" type="text/css" href="<?php echo "templates/{$config['template']}/css/styles.less?v=" . filemtime($lessFile); ?>">
        <script language="JavaScript" src="js/less-1.1.3.min.js"></script>
<?php endif;
    $favicon = "templates/{$config['template']}/img/favicon.ico";
    if (file_exists($favicon)): ?>
        <link rel="shortcut icon" type="image/x-icon" href="<?php echo $favicon; ?>" />
<?php endif; ?>
        
<script language="JavaScript" src="js/prototype-1.7.0.0.js"></script>
<script language="JavaScript" src="js/jquery-1.6.2.min.js"></script>
<script>
    var $j = jQuery.noConflict();
</script>
<script language="JavaScript" src="js/scriptaculous/scriptaculous.js"></script>
<script language="JavaScript" src="js/scriptaculous/effects.js"></script>
<script language="JavaScript" src="jshttprequest/JsHttpRequest.js"></script>
<script language="JavaScript" src="common/jhr_controller.js"></script>
<script language="JavaScript" src="js/rsh.js?v=4"></script>
<script language="JavaScript" src="js/trimpath/template.js"></script>
<script language="JavaScript" src="klayers.js"></script>
<script language="JavaScript" src="strings.js"></script>
<script language="JavaScript" src="dropDownList.js"></script>
<script language="JavaScript" src="js/lms-jsf/JSAN.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Connector.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Signalable.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Widgets.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Widgets/Factory.js"></script>
<script>
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
    //Константы
    var USER_GROUP =  <?php echo $user['UserGroup']; ?>;
    var DEFAULT_FAVICON = "<?php echo $favicon; ?>";
    var SITE_URL = "<?php echo $config['siteurl']; ?>";
    var SITE_TITLE = "<?php echo isset($config['sitetitle']) ? $config['sitetitle'] : "Видео-каталог"; ?>";

    var FIXED_WIDTH_POSTER = <?php echo ($gd_loaded) ? 0 : (isset($config["covers"]["defaultcovers"]["width"]) ? $config["covers"]["defaultcovers"]["width"] : 160); ?>;

    var RIGHTS_SETBOOKMARK = <?php echo getRights("setbookmark",$user); ?>;
    var RIGHTS_POSTCOMMENT = <?php echo getRights("postcomment",$user); ?>;
    var RIGHTS_SETRATING = <?php echo getRights("setrating",$user); ?>;

    var CAN_NOT_SETBOOKMARK = "<?php echo isset($config['can_not_setbookmark']) ? $config['can_not_setbookmark'] : "Только зарегистрированные пользователи могут создавать закладки.<br> <a href='?register=1' class='alert_link'>Зарегистрируйтесь</a><br>или войдите под своим логином<br><form action='?' method='post'><input type='hidden' name='logon' value='1'><table border='0' width='100%'><tr><td>Логин:</td><td><input name='login'></td></tr><tr><td>Пароль:</td><td><input name='pass' type='password'></td></tr><tr><td colspan='2'><input id='remember' type='checkbox' value='1' name='remember'><label for='remember'>Автоматически входить</label></td></tr><tr><td colspan='2' align='center'><input type='submit' value='OK'></td></tr></table></form>"; ?>";
    var CAN_NOT_POSTCOMMENT = "<?php echo isset($config['can_not_postcomment']) ? $config['can_not_postcomment'] : "Только зарегистрированные пользователи могут оставлять отзывы.<br> <a href='?register=1' class='alert_link'>Зарегистрируйтесь</a> или <a href='javascript:Exit();' class='alert_link'>войдите</a> под своим логином"; ?>";
    var CAN_NOT_SETRATING = "<?php echo isset($config['can_not_setrating']) ? $config['can_not_setrating'] : "<a href='?register=1' class='alert_link'>Зарегистрируйтесь</a> или <a href='javascript:Exit();' class='alert_link'>войдите</a> под своим логином,<br> чтобы ставить рейтинги"; ?>";
    var COMMENT_RULES = "<?php echo isset($config['comment_rules']) ? $config['comment_rules'] : "Администрация сервера оставляет за собой право удалять сообщения по собственному усмотрению"; ?>";

    var SMALL_FRAME_WIDTH = <?php echo isset($config['small_frame_width']) ? $config['small_frame_width'] : 80; ?>;

    var SCROLL_COMMENTS = <?php echo isset($config['scroll_comments']) ? $config['scroll_comments'] : 0; ?>;

    var loadings = 0;
    var PORTRAIT_HEIGHT = "170px";
    
    var MAX_FRAME_WIDTH_PX = <?php echo isset($config['max_frame_width_px']) ? $config['max_frame_width_px'] : 0; ?>;
    
    var prefilms = new Array();
    var showhintplayers = 1;
    var preferences = {'players': '1,2,3'};
    var postponedfilms = new Array();
    var current_film = 0;
    var search_num = 0;    
    var BackPage = -1;    
    var BackPosition = -1;    
    var CurrentPage = -1;    
    var MyBlur1 = 0;    
    var MyBlur2 = 0;
    var dropDownLists = new Object();
        
    var pagescontent = new Array('page:0','page:1','page:2','page:3');
    var pagesrealcontent = new Array('page:0','','','');
    var pagestitle = new Array(SITE_TITLE, SITE_TITLE + ": Поиск",SITE_TITLE,SITE_TITLE);

    var uid = <?php echo $user[0]; ?>;

    var acovers = new Array();
    var abigcovers = new Array();

    var translation_options = <?php
    if (!isset($config['translation_options'])) {
        $config['translation_options'] = array("","Не определен","Дубляж","На языке оригинала","Профессиональный многоголосый","Любительский многоголосый","Одноголосый","Профессиональный одноголосый","Субтитры","Tycoon Studio","Гоблин (правильный)","Гоблин (смешной)","Визгунов Сергей","Володарский Леонид","Гаврилов Андрей","Гланц Пётр","Гранкин Евгений","Живов Юрий","Кузнецов Сергей","Малашевич Андрей","Мазур","Михалёв Алексей","Немахов","Первомайский Александр","Рудой Евгений","Сербин Юрий"); 
    }
    echo "[";
    $tmp = array();
    foreach ($config['translation_options'] as $option) $tmp[] = "\"".addslashes($option)."\"";
    echo implode(",",$tmp);
    echo "]";
    ?>

    var quality_options =  <?php
    if (!isset($config['quality_options'])) {
        $config['quality_options'] = array("","Не определено","CamRip","Telesync","DVDScr","Promo DVDrip","VHSrip","Promo VHSrip","SATrip","TVrip","Telecine","DVDrip"); 
    }
    echo "[";
    $tmp = array();
    foreach ($config['quality_options'] as $option) $tmp[] = "\"".addslashes($option)."\"";
    echo implode(",",$tmp);
    echo "]";
    ?>

    var typeofmovie_options = <?php
    if (!isset($config['typeofmovie_options'])) {
        $config['typeofmovie_options'] = array("Не определен","Документальный сериал","Документальный фильм","Научно - популярный фильм","Концерт","Короткометражный фильм","Мультсериал","Мьюзикл-опера","Полнометражный мультфильм","Сборник мультфильмов","Спортивная видеопрограмма","Телевизионный спектакль","Телепередача","Худ. кинофильм","Худ. телесериал","Худ. телефильм"); 
    }
    echo "[";
    $tmp = array();
    foreach ($config['typeofmovie_options'] as $option) $tmp[] = "\"".addslashes($option)."\"";
    echo implode(",",$tmp);
    echo "]";
    ?>

    function message(text,domid){
        document.getElementById(domid).innerHTML = text;
    }

    function user_message(text){
            message(text,'message');
    }

    function sys_message(text){
            message(text,'sysmessage');
    }

    JsHttpRequest.JHRController.content = '<img src="images/progbar.gif" border="0">';
    JsHttpRequest.JHRController.DebugMessenger = sys_message;
    JsHttpRequest.JHRController.SysMessenger = sys_message;
    JsHttpRequest.JHRController.UserMessenger = user_message;

    function showsysmessage(){
            document.getElementById('sysmessagebox').style.display = '';
    }

<?php if (isset($config['enable_console']) && $config['enable_console']) { ?>
    var owner = window.HTMLElement? window : document;
    var prevKeydown = owner.onkeydown;
    owner.onkeydown = function(e) {
        if (!e) e = window.event;
        if (e.ctrlKey && (e.keyCode == 192 || e.keyCode == 96)) {
            showsysmessage();
            return false;
        }
        if (prevKeydown) {
            __prev = prevKeydown;
            return __prev(e);
        }
    }
<?php } ?>

    function MyXMLEncode(str){
        if (!str) return "";
    //    str = str.replace(/&/g, "&amp;");
        str = str.replace(/</g, "&lt;");
        str = str.replace(/>/g, "&gt;");
        str = str.replace(/'/g,"&#39;");//"&apos;");
        str = str.replace(/\"/g,"&#34;");//"&quot;");
          return str;
    }

    function RenderComboBox_old(boxid,id,fieldname,updatefunction,value,options,size){
        selectid = boxid + "Select";
        myoptions = "";
        for(var i=0; i<options.length;i++){
            myoptions += "<option value='"+MyXMLEncode(options[i])+"'>"+MyXMLEncode(options[i])+"</option>"
        }
        combobox = "";
        c = Math.min(options.length,20);
        combobox += "<span><input type='text' id='"+boxid+"' onChange='"+updatefunction+"("+id+",\""+fieldname+"\",this)' onClick='document.getElementById(\""+selectid+"\").style.display=\"\"' onFocus='document.getElementById(\""+selectid+"\").style.display=\"\"' onBlur='MyBlur"+boxid+"_2=1; setTimeout(\"if (MyBlur"+boxid+"_2) document.getElementById(\\\""+selectid+"\\\").style.display=\\\"none\\\"\",100)' style='font-size:8pt;height:20px;z-index:10;' size='"+size+"' value='"+MyXMLEncode(value)+"'><br>";
        combobox += "<div id='"+selectid+"' style='position:relative;display:none;z-index:15;'><select  onChange='"+updatefunction+"("+id+",\""+fieldname+"\",this);document.getElementById(\""+boxid+"\").value=this.value;setTimeout(\"MyBlur"+boxid+"_2=1;document.getElementById(\\\""+selectid+"\\\").style.display=\\\"none\\\"\",200);' size='"+c+"' onFocus='MyBlur"+boxid+"_2=0;document.getElementById(\""+boxid+"\").focus();' onBlur='MyBlur"+boxid+"_2=0;' style='font-size:8pt;position:absolute;top:0px;border:1px solid gray;z-index:20;' value='"+MyXMLEncode(value)+"'>";
        combobox += myoptions;
        combobox += "</select></div></span>";
        return combobox;
    }    

    function RenderComboBox(boxId, id, fieldName, updateFunctionName, value, optionsList, size){
        dropDownLists[boxId] = new dropDownList(boxId, null, optionsList);
        focusedVar = 'combobox_is_focused' + boxId;
        combobox = "<input type='text' id='"+boxId+"' onChange='"+updateFunctionName+"("+id+",\""+fieldName+"\",this)' onClick='"+focusedVar+"=1; dropDownLists[&#34;"+boxId+"&#34;].show();' onFocus='"+focusedVar+"=1;dropDownLists[&#34;"+boxId+"&#34;].show();' onBlur='"+focusedVar+"=0; setTimeout(\"if (!"+focusedVar+") dropDownLists[\\\""+boxId+"\\\"].hide();\", 100)' style='font-size:8pt;height:20px;z-index:10;' size='"+size+"' value='"+MyXMLEncode(value)+"'>";
        return combobox;
    }  
    
    function setCookie (name, value, expires, path, domain, secure) {
        document.cookie = name + "=" + escape(value) +
        ((expires) ? "; expires=" + expires : "") +
        ((path) ? "; path=" + path : "") +
        ((domain) ? "; domain=" + domain : "") +
        ((secure) ? "; secure" : "");
    }

    function getCookie(name) {
        var dc = document.cookie;
        var prefix = name + "=";
        var begin = dc.indexOf("; " + prefix);
        if (begin == -1) {
            begin = dc.indexOf(prefix);
            if (begin != 0) return null;
        } else {
            begin += 2;
        }
        var end = document.cookie.indexOf(";", begin);
        if (end == -1) {
            end = dc.length;
        }
        return unescape(dc.substring(begin + prefix.length, end));
    }


    function getPosition(oElem){
        var pos={x:0,y:0}
        for(;oElem;oElem=oElem.offsetParent){
            pos.x+=oElem.offsetLeft;
            pos.y+=oElem.offsetTop;
        }
        return pos;
    }
    function Pages(ids, labels, onselects, PageSelectorId, name){
        this.ids = ids;
        this.labels = labels;
        this.onselects = onselects;
        this.PageSelectorId = PageSelectorId;
        this.name = name;
    }
    
    Pages.prototype.select = function (num){
        eval(this.onselects[num]);
        CurrentPage = num;
        menustr = "<ul>";
        for (i=0; this.ids[i]; i++){
            if (i==num){
                document.getElementById(this.ids[i]).style.display="";
                menustr += "<li id='current'><a id='"+this.name+"_"+i+"' >"+this.labels[i]+"</a></li>";
            }
            else {
                document.getElementById(this.ids[i]).style.display="none"
                menustr += "<li><a id='"+this.name+"_"+i+"' href='#"+MyXMLEncode(pagescontent[i])+"'>"+this.labels[i]+"</a></li>";
            }
        }
        menustr += "</ul>";
        document.getElementById(this.PageSelectorId).innerHTML = menustr;
    }

    
    var MyPages = new Pages(
        Array("CatalogPage","SearchPage","FilmsPage"),
        Array("Каталог","Поиск","Фильмы"),
        Array("if (document.getElementById('backbox')) document.getElementById('backbox').innerHTML = ''","if (document.getElementById('backbox')) document.getElementById('backbox').innerHTML = ''",""),
        "PageSelector",
        "MyPages"
    )
        
    var SearchPages = new Pages(
        Array("SimpleSearchPage"),
        Array("Простой поиск"),
        Array(""),
        "SearchSelector",
        "SearchPages"
    )

    function Init() {
        for (var i=0;i<4;i++){
            url = getCookie("page"+i);
            if (url) pagescontent[i] = url;
        }
        getPreferences();
        getBookmarks();
        var matches = window.location.hash.match(/#\/movie\/id\/(\d+)/);
        if (matches) {
            window.location.hash = 'film:' + matches[1] + ':0:0';
        }
        initialize();
        preloadImage('images/progbar.gif');
        preloadImage('images/delete_16.gif');
        preloadImage('images/favadd_16.gif');
        preloadImage('images/pd.gif');
        preloadImage('images/hr2.gif');
        preloadImage('images/hr.gif');
        preloadImage('images/min.gif');
        FillGenres(0,"");
        FillCountries(0,"");
        FillTypes(0,0);
        DrawCatalog(0);
        getTopList();
        getPopList();
        getRecommended();
        getLastComments();
        getLastRatings();
        document.getElementById('SimpleSearchPage').style.display = '';
        iamlife();
    }    

function initialize() {
  // initialize RSH
  dhtmlHistory.initialize();
  
  // add ourselves as a listener for history
  // change events
  dhtmlHistory.addListener(handleHistoryChange);
  
  // determine our current location so we can
  // initialize ourselves at startup
  var initialLocation = 
                dhtmlHistory.getCurrentLocation();
  
  // if no location specified, use the default
  if (initialLocation == null)
    initialLocation = "";
  
  // now initialize our starting UI
  updateUI(initialLocation, null);
}

/** A function that is called whenever the user
    presses the back or forward buttons. This
    function will be passed the newLocation,
    as well as any history data we associated
    with the location. */
function handleHistoryChange(newLocation,
                             historyData) {
  // use the history data to update our UI
  updateUI(newLocation, historyData);                           
}

/** A simple method that updates our user
    interface using the new location. */
function updateUI(newLocation,
                  historyData) {
    action = newLocation.split(":");
    for (i=0;i<3;i++){
        if (pagesrealcontent[i].length && (pagesrealcontent[i]==newLocation)){
            document.title = pagestitle[i];
            MyPages.select(i);
            return;
        }
    }
    switch (action[0]){
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
        default: 
            window.location = "#" + pagescontent[0];
    }
}

function LoadControl(incr){
    loadings = loadings + incr;
    if (loadings>0) {
        document.getElementById('waiticon').style.display = "block";
        document.getElementById('waiticon').style.top = getScrollY()+"px";
    }
    else{
        document.getElementById('waiticon').style.display = "none";    
    }
}
    function Show(obj) {
        document.getElementById(obj).style.display = "";
    }
    
    function Hide(obj) {
        document.getElementById(obj).style.display = "none";
    }


    function Exit() {
        JsHttpRequest.query(
            'actions.php?action=exit', // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    setCookie ("login", "");
                    setCookie ("pass", "");
                    window.location = SITE_URL + "?exit=1";
                };
            },
            true
        )
    }

    function inc_hit(filmid){
        JsHttpRequest.query(
            'actions.php?action=inc_hit&filmid='+filmid, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
            },
            true
        )
    }


    function iamlife(){
        getRndFilm();
        getRndText();
        setTimeout('iamlife()',600000);
    }

    function CustomUpdateField(id,field,o,action) {
        value = o.value;
        JsHttpRequest.query(
            'actions.php?action='+action+'&id='+id+'&field='+field, // backend
            {'value':value},
            function(result, errors) {
                if (errors.length) sys_message(errors);
            },
            true
        )
    }

    function UpdateFilmField(id,field,o) {
        CustomUpdateField(id,field,o,'updatefilmfield');
        
    }

    function GoBack() {
        history.go(-1);
        scrollTo(0,BackPosition);
        document.getElementById("backbox").innerHTML = "";
    }
    
    function getRndFilm() {
        if (document.getElementById("RndTextFilm")){
            JsHttpRequest.query(
                'actions.php?action=getrndfilm', // backend
                {},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                    if (result){
                        film = result;
                        outstr = ""
                        outstr = "<br><span class='sectionheader'>Случайный фильм:</span><table class='recomended' width='100%'><tr><td align='center'><a href='#film:"+film.ID+":1:0'><img width='80px' src='"+film.Poster+"' border='0'><br><b>" + film.Name + "</b><p style='margin:2px;margin-bottom:5px;color:gray'>" + film.OriginalName + " (" + film.Year + ")</p></a><p style='margin:2px;margin-bottom:5px;'>" + film.genres + "</p><p style='margin:2px;'>" + film.countries + "</p></td></tr></table>"
                        document.getElementById("RndTextFilm").innerHTML = outstr;
                    };
                },
                true
            )
        }
    }

    function getTopList() {
        if (document.getElementById("TopListBox")){
            JsHttpRequest.query(
                'actions.php?action=gettoplist&count=10', // backend
                {},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                    if (result && result.films) {
                           films = result.films;
                        outstr = "";
                        outstr += "<br><b><a title='Обновить' href='javascript:getTopList()'>Топ 10 закачек</a></b><br><table class='lastrating'>";
                        for (i=0;i<films.length;i++){
                            outstr += "<tr><td>" + (i+1) + ".</td><td><a href='#film:" + films[i].ID + ":0:0'>" + films[i].Name + "</a></td><td>" + films[i].Hit + "</td></tr>";
                        }
                        outstr += "</table>";
                        document.getElementById("TopListBox").innerHTML = outstr;         
                    }
                },
                true
            )
        }
    }

    function getPopList() {
        if (document.getElementById("PopListBox")){
            JsHttpRequest.query(
                'actions.php?action=getpoplist&count=10', // backend
                {},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                    if (result && result.films) {
                           films = result.films;
                        outstr = "";
                        outstr += "<br><b><a title='Обновить' href='javascript:getPopList()'>Самое популярное</a></b><br><table class='lastrating'>";
                        for (i=0;i<films.length;i++){
                            outstr += "<tr><td>" + (i+1) + ".</td><td><a href='#film:" + films[i].ID + ":0:0'>" + films[i].Name + "</a></td><td></td></tr>";
                        }
                        outstr += "</table>";
                        document.getElementById("PopListBox").innerHTML = outstr;         
                    }
                },
                true
            )
        }
    }

    function getBookmarks() {
        if (RIGHTS_SETBOOKMARK){
            JsHttpRequest.query(
                'actions.php?action=getbookmarks', // backend
                {},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                    if (result && result.films){
                        for (i=0;i<result.films.length;i++){
                            postponedfilms.push(result.films[i]);
                        }
                        DrawFilmsListBox();
                    }
                },
                true
            )
        }
    }

    function getPreferences() {
        JsHttpRequest.query(
            'actions.php?action=getpreferences', // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    if (result.preferences){
                        preferences = result.preferences;
                          showhintplayers = 0;
                    }
                    if (!preferences.players){
                        preferences.players = "1,2,3";
                    }
                }
            },
            true
        )
    }

    function DrawFilmsListBox() {
        outstr = "";
        if (prefilms.length){
            outstr += "<b>Фильмы сессии</b>";
            outstr += "<table border='0' cellspacing='0' cellpadding='0' width='100%'>";
            for (i=0;i<prefilms.length;i++){
                myclass = (prefilms[i].ID==current_film) ? "filmlistsel" : "filmlist";
            outstr += "<tr onMouseOver='Show(\"_rf" + prefilms[i].ID + "\");Show(\"_sf" + prefilms[i].ID + "\")' onMouseOut='Hide(\"_rf" + prefilms[i].ID + "\");Hide(\"_sf" + prefilms[i].ID + "\")'><td class='" + myclass + "'><a href='#film:" + prefilms[i].ID + ":0:0'>" + prefilms[i].Name + "</a></td><td width='1%' align='right' valign='top'><div style='position: relative;'><a title='Убрать'  href='javascript:RemoveFilm(" + prefilms[i].ID + ")'><img border='0' height='16' width='16' src='images/delete_16.gif' style='position:absolute;left:-12px;display:none' id='_rf" + prefilms[i].ID + "'></a> <a title='Добавить в закладки' style='position:absolute;left:-30px;display:none' id='_sf" + prefilms[i].ID + "' href='javascript:SaveFilm(" + prefilms[i].ID + ")'><img border='0' height='16' width='16' src='images/favadd_16.gif'></a></div></td></tr>";    
            }
            outstr += "</table><br>";
        }
        if (postponedfilms.length){
            outstr += "<b>Закладки</b>";
            outstr += "<table border='0' cellspacing='0' cellpadding='0' width='100%'>";
            for (i=0;i<postponedfilms.length;i++){
                myclass = (postponedfilms[i].ID==current_film) ? "filmlistsel" : "filmlist";
            outstr += "<tr onMouseOver='Show(\"_rf" + postponedfilms[i].ID + "\")' onMouseOut='Hide(\"_rf" + postponedfilms[i].ID + "\")'><td class='" + myclass + "'><a href='#film:" + postponedfilms[i].ID + ":0:0'>" + postponedfilms[i].Name + "</a></td><td width='1%' align='right' valign='top'><div style='position: relative;'><a title='Убрать' href='javascript:RemoveFilm(" + postponedfilms[i].ID + ")'><img border='0' height='16' width='16' src='images/delete_16.gif' style='position:absolute;left:-12px;display:none' id='_rf" + postponedfilms[i].ID + "'></a></div></td></tr>";    
            }
            outstr += "</table><br>";
        }
        if (!RIGHTS_SETBOOKMARK) outstr += "<b>Закладки</b><br>" + CAN_NOT_SETBOOKMARK;

        document.getElementById("FilmsListBox").innerHTML = outstr;
    }

    function getRndText() {
        if (document.getElementById("RndTextBox")){
            JsHttpRequest.query(
                'actions.php?action=getrndtext', // backend
                {},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                       if (result && result.rndtext) {
                        document.getElementById("RndTextBox").innerHTML = "<br><span class='sectionheader'>Случайная фраза:</span><table class='recomended'><tr><td>" + result.rndtext + "</td></tr></table>";
                       }
                },
                true
            )
        }
    }

    function getSimilarFilms(filmid) {
        if (document.getElementById("SimilarBox")){
            JsHttpRequest.query(
                'actions.php?action=similar_films&id='+filmid, // backend
                {},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                    if (result){
                        if (document.getElementById("gen_time")) document.getElementById("gen_time").innerHTML = Math.round(eval(result.gen_time)*100)/100 + " сек.";
                        outstr = "";
                        for (i=0; i<result.films.length; i++){
                            film = result.films[i];
                            if (film.FilmID!=filmid){
                                outstr += "<a href='#film:" + film.FilmID + ":0:0'>" + film.Name + "</a> (его смотрели "+Math.round(parseFloat(film.c)*100)+"%, а всего это "+Math.round(parseFloat(film.rank)*100)+"% аудитории)" + "<br>";
                            }
                        }
                        document.getElementById("SimilarBox").innerHTML = outstr.length ? outstr : "ничего похожего не найдено";
                    }
                },
                true
            )
        }
    }

    function getLastComments() {
        JsHttpRequest.query(
            'actions.php?action=lastcomments', // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result && result.comments2) {
                    comments2 = result.comments2;
                    outstr = "";
                    outstr += "<br><b><a title='Обновить' href='javascript:getLastComments()'>Последние отзывы</a></b><br><table class='lastcomments'>";
                    for (i=0;i<comments2.length;i++){ 
                    outstr += "<tr><td>" + (i+1) + ".</td><td><a title=\"" + comments2[i].Login + ": "+ comments2[i].Text.replace(/\"/gi, "&quot;") + "\" href='#film:" + comments2[i].FilmID + ":1:1'> " + comments2[i].Name + "</a></td></tr>";    
                    }
                    outstr += "</table>";
                    document.getElementById("LastCommentsBox").innerHTML = outstr;
                }
            },
            true
        )
    }

    function getRecommended() {
        JsHttpRequest.query(
            'actions.php?action=recommended', // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result && result.films) {
                    films = result.films;
                    outstr = "";
                    outstr += "<br><b><a title='Обновить' href='javascript:getRecommended()'>Рекомендуем:</a></b><br><table class='recomended'>";
                    for (i=0;i<films.length;i++){
                        outstr += "<tr><td>" + (i+1) + ".</td><td><a href='#film:" + films[i].FilmID + ":0:0'> " + films[i].Name + "</a></td></tr>";    
                    }
                    outstr += "</table>";
                    document.getElementById("RecommendedBox").innerHTML = outstr;        
                }
            },
            true
        )
    }

    function getLastRatings() {
        JsHttpRequest.query(
            'actions.php?action=lastratings', // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result && result.ratings) {
                    ratings = result.ratings;
                    outstr = "";
                    outstr += "<br><b><a title='Обновить' href='javascript:getLastRatings()'>Последние оценки</a></b><br><table class='lastrating'>";
                    for (i=0;i<ratings.length;i++){
                        outstr += "<tr><td>" + (i+1) + ".</td><td><a href='#film:" + ratings[i].FilmID + ":0:0'>" + ratings[i].Name + "</a></td><td>" + ratings[i].Rating + "</td></tr>";
                    }
                    outstr += "</table>";
                    document.getElementById("LastRatingsBox").innerHTML = outstr;         
                }
            },
            true
        )
    }

    function IsFilmPostponed(film) {
        found = 0;
        for (i=0; i<prefilms.length;i++){
            if (prefilms[i].ID==film) found = 1;
        }
        for (i=0; i<postponedfilms.length;i++){
            if (postponedfilms[i].ID==film) found = 1;
        }
        return found;
    }


    function AddToPreFilms(film,name) {
        if (!IsFilmPostponed(film)){
            prefilms.unshift({'ID':film,'Name':name});
        }
        if (document.getElementById("_catpostlink"+film)) document.getElementById("_catpostlink"+film).style.display = "none";
       if (document.getElementById("_fspostlink"+film)) document.getElementById("_fspostlink"+film).style.display = "none";
       if (document.getElementById("_personpostlink"+film)) document.getElementById("_personpostlink"+film).style.display = "none";
        DrawFilmsListBox();
    }

    function SaveFilm(film) {
        if (RIGHTS_SETBOOKMARK){
            for (i=0; i<prefilms.length;i++){
                if (prefilms[i].ID==film){
                    prefilms.splice(i, 1);
                }
            }
            found = 0;
            for (i=0; i<postponedfilms.length;i++){
                if (postponedfilms[i].ID==film) found = 1;
            }
            if (!found){
                JsHttpRequest.query(
                    'actions.php?action=setbookmark&entity=film&id=' + film, // backend
                    {},
                    function(result, errors) {
                        if (errors.length) sys_message(errors);
                        if (result){
                            postponedfilms.unshift({'ID':result.ID,'Name':result.Name});
                            if (document.getElementById("_catpostlink"+film)) document.getElementById("_catpostlink"+film).style.display = "none";
                               if (document.getElementById("_fspostlink"+film)) document.getElementById("_fspostlink"+film).style.display = "none";
                               if (document.getElementById("_personpostlink"+film)) document.getElementById("_personpostlink"+film).style.display = "none";
                            DrawFilmsListBox();
                        };
                    },
                    true
                )
            }
        }
    }

    function RemoveFilm(film) {
        for (i=0; i<prefilms.length;i++){
            if (prefilms[i].ID==film){
                prefilms.splice(i, 1);
                DrawFilmsListBox();
            }
        }
        found = -1;
        for (i=0; i<postponedfilms.length;i++){
            if (postponedfilms[i].ID==film) found = i;
        }
        if (found>=0){
            JsHttpRequest.query(
                'actions.php?action=removebookmark&entity=film&id=' + film, // backend
                {},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                        if (result && result.ok) {
                            postponedfilms.splice(found, 1);
                            DrawFilmsListBox();
                        }
                },
                true
            )
            if (document.getElementById("_catpostlink"+film)) document.getElementById("_catpostlink"+film).style.display = "";
               if (document.getElementById("_fspostlink"+film)) document.getElementById("_fspostlink"+film).style.display = "";
               if (document.getElementById("_personpostlink"+film)) document.getElementById("_personpostlink"+film).style.display = "";
        }
    }

    function ShowComments(filmid,andgoto) {
        JsHttpRequest.query(
            'actions.php?action=getcomments&id=' + filmid, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    outstr = "";
                    commstr = (RIGHTS_POSTCOMMENT ) ? "<div style='margin:2em;padding:0;border:0px solid silver;width:90%'><table cellpadding='0' cellspacing='0' width='100%' style='margin:0;'><tr><td width='5px' class='hcomment_left'>&nbsp;</td><td class='hcomment_center' align='left' width='15%'><b>Отзыв:</b></td><td class='hcomment_center' align='right' width='*' valign='top'><input type='checkbox' id='formoder'> <label for='formoder'>только для модератора</label></td><td width='5px' class='hcomment_right'>&nbsp;</td></tr></table><table cellpadding='0' cellspacing='0' width='100%' style='margin:0;padding:0;'><tr><td><textarea id='commenttext' style='margin:0;width:100%;padding:0;border:1px solid silver;' rows='6'></textarea></td></tr></table><div style='float:right;'><input type='button' onClick='javascript:PostComment(" + filmid + ")' value='Отправить'></div><span style='font-size:8pt;'>"+COMMENT_RULES+"</span></div>" : CAN_NOT_POSTCOMMENT;
                    if (eval(result.count)>0) {
                        outstr += (SCROLL_COMMENTS && (eval(result.count)>SCROLL_COMMENTS)) ? "<div style='padding-bottom:10px; height: 400px; overflow: auto; border: 1px solid silver; background-color:#F8F8F8'>" : "";
                        for (var i=0; comment = result.comments[i];i++){
                            if (eval(result.moder)==1) outstr += "<div style='margin:2em;padding:0;border:0px solid silver;width:90%;' id='_comment"+comment.ID+"'><table cellpadding='0' cellspacing='0' width='100%' style='margin:0;'><tr><td width='5px' class='hcomment_left'>&nbsp;</td><td class='hcomment_center' align='left' width='10%' nowrap><b>" + comment.Login + "</b> ("+comment.ip+") (" + comment.Date + ") " + ((comment.ToUserLogin) ? "-> " + comment.ToUserLogin + " (личное сообщение)" : "") + "</td><td width='*' class='hcomment_center' align='right' nowrap><a href='javascript:BeginEditComment("+comment.ID+","+filmid+")'>редактировать</a> | <a href='javascript:DeleteComment("+comment.ID+")'>удалить</a></td><td width='5px' class='hcomment_right'>&nbsp;</td></tr></table><table cellpadding='0' cellspacing='0' width='100%' style='border:1px solid silver;margin:0;padding:0;background:#F9F9F9;font-size:8pt;'><tr><td id='_commenttext"+comment.ID+"'>"+comment.Text+"</td></tr></table></div>";
                             else outstr += "<div style='margin:2em;padding:0;border:0px solid silver;width:90%;'><table cellpadding='0' cellspacing='0' width='100%' style='margin:0;'><tr><td width='5px' class='hcomment_left'>&nbsp;</td><td class='hcomment_center' align='left' width='*'><b>" + comment.Login + "</b> (" + comment.Date + ") " + ((comment.ToUserLogin) ? "-> " + comment.ToUserLogin + " (личное сообщение)" : "") + "</td><td width='5px' class='hcomment_right'>&nbsp;</td></tr></table><table cellpadding='0' cellspacing='0' width='100%' style='border:1px solid silver;margin:0;padding:0;background:#F9F9F9;font-size:8pt;'><tr><td>"+comment.Text+"</td></tr></table></div>";
                        }
                        outstr += (SCROLL_COMMENTS && (eval(result.count)>SCROLL_COMMENTS)) ? "</div>" : "";
                        outstr += commstr;
                           document.getElementById("comments").innerHTML = outstr;
                           if (andgoto==1){
                               scrollTo(0,getPosition(document.getElementById("comments")).y);
                        }
                    } else{
                        outstr += commstr;
                           document.getElementById("comments").innerHTML = outstr;
                           if (andgoto==1){
                               scrollTo(0,getPosition(document.getElementById("comments")).y);
                           }
                    }
                }
            },
            true
        )
    }

    function BeginEditComment(commentid,filmid) {
        if (!document.getElementById("_editcommenttext"+commentid)){
            commstr = "<textarea id='_editcommenttext"+commentid+"' style='margin:0;width:100%;padding:0;border:1px solid silver;' rows='6'></textarea><div style='float:right;'><input type='button' onClick='javascript:EditComment(" + commentid + "," + filmid + ")' value='Отправить'></div>";
            oldcomment = document.getElementById("_commenttext"+commentid).innerHTML;
            oldcomment = oldcomment.replace(/<br\/?>/gi,"\r\n")
            document.getElementById("_commenttext"+commentid).innerHTML = commstr;
            document.getElementById("_editcommenttext"+commentid).innerHTML = oldcomment;
        }
    }

    function PostComment(filmid) {
        if (loadings==0) {
            text = document.getElementById("commenttext").value;
            myformoder = document.getElementById("formoder").checked ? 1 : 0;
            JsHttpRequest.query(
                'actions.php?action=postcomment&id=' + filmid + '&formoder=' + myformoder, // backend
                {'text':text},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                       ShowComments(filmid,0);
                       busy = 0;
                },
                true
            )
        }
    }

    function EditComment(commentid,filmid) {
        text = document.getElementById("_editcommenttext"+commentid).value;
        JsHttpRequest.query(
            'actions.php?action=editcomment&id=' + commentid, // backend
            {'text':text},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                   ShowComments(filmid,0);
            },
            true
        )
    }

    function DeleteComment(commentid) {
        if (confirm("Удалить комментарий?")){
            JsHttpRequest.query(
                'actions.php?action=editcomment&id=' + commentid + '&delete=1', // backend
                {},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                    if (result){
                        document.getElementById("_comment"+commentid).innerHTML = "";
                    };
                },
                true
            )
        }
    }

    function setRating(filmid,value) {
        JsHttpRequest.query(
            'actions.php?action=setrating&id=' + filmid + '&rating=' + value, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    document.getElementById("_myrating").innerHTML = result.PersonalRating;
                    document.getElementById("_localrating").innerHTML = result.LocalRating + " <span style='color:gray'>"+result.CountLocalRating+"</span>";
                    Hide("_myratingbox");
                }
            },
            true
        )
    }

    function FillGenres(mycountry,mytypeofmovie) {
        GenreFilterValue = document.getElementById("GenreFilter").value;
        url = 'actions.php?action=getgenres';
        if (mycountry>0) url += '&country='+mycountry;
        JsHttpRequest.query(
            url, // backend
            (mytypeofmovie.length>0) ? {'typeofmovie':mytypeofmovie} :{},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    for (i=document.getElementById("GenreFilter").length-1;i>0;i--){
                        document.getElementById("GenreFilter").options[i]=null
                    }
                    si = 0;
                    for(var i=0; 
                        genre = result.genres[i];
                        i++) {
                        document.getElementById("GenreFilter").options[i+1] = new Option(genre.Name+" ("+genre.Count+")", genre.ID, false, (GenreFilterValue == genre.ID));
                    }
                }
            },
            true
        )
    }

    function FillCountries(mygenre,mytypeofmovie) {
        CountryFilterValue = document.getElementById("CountryFilter").value;
        url = 'actions.php?action=getcountries';
        if (mygenre>0) url += '&genre='+mygenre;
        JsHttpRequest.query(
            url, // backend
            (mytypeofmovie.length>0) ? {'typeofmovie':mytypeofmovie} :{},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    for (i=document.getElementById("CountryFilter").length-1;i>0;i--){
                        document.getElementById("CountryFilter").options[i]=null
                    }
                    si = 0;
                    for(var i=0; 
                        country = result.countries[i];
                        i++) {
                        document.getElementById("CountryFilter").options[i+1] = new Option(country.Name+" ("+country.Count+")", country.ID, false, (CountryFilterValue == country.ID));
                    }
                }
            },
            true
        )
    }

    function FillTypes(mygenre,mycountry) {
        TypeFilterValue = document.getElementById("TypeFilter").value;
        url = 'actions.php?action=gettypesofmovie';
        if (mycountry>0) url += '&country='+mycountry;
        if (mygenre>0) url += '&genre='+mygenre;
        JsHttpRequest.query(
            url, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    for (i=document.getElementById("TypeFilter").length-1;i>0;i--){
                        document.getElementById("TypeFilter").options[i]=null
                    }
                    si = 0;
                    for(var i=0; 
                        type = result.types[i];
                        i++) {
                        document.getElementById("TypeFilter").options[i+1] = new Option(type.Type+" ("+type.Count+")", type.Type, false, (TypeFilterValue == type.Type));
                    }
                }
            },
            true
        )
    }

    function RenderFilmShort(num,film){
        var outstr = new StringBuilder();
        filmid = film.ID;
        outstr.append ("<tr>");
        outstr.append ("<td valign='top' width='1%'>"+num+". </td>");
        outstr.append ("<td valign='top' width='*'>");
        outstr.append ("<a href='#film:" + filmid + ":1:0'>" + film.Name + "</a> ");
        outstr.append ("<span style='color:gray'>/" + film.OriginalName + " (" + film.Year + ")/</span>&nbsp;&nbsp;&nbsp; <a id='_fspostlink" + filmid + "' title='Список отложенных фильмов находится в меню Фильмы' href='javascript:AddToPreFilms(" + filmid + ",\"" + film.Name + "\");'>Отложить</a>");
        outstr.append ("</td>");
        outstr.append ("</tr>");
        return outstr.toString();
    }
    
    function replacePlaceholder(template, placeholderName, value)
    {
        var out = '';
        if (value.length>0){
            var re = new RegExp('%' + placeholderName + 'BEGIN%|%' + placeholderName + 'END%', 'gi');
            out = template.replace(re, '');
            var re = new RegExp('%' + placeholderName + '%', 'gi');
            out = out.replace(re, value);
        } else {
            var re = new RegExp('%' + placeholderName + 'BEGIN%.*%' + placeholderName + 'END%', 'gi');
            out = template.replace(re, '');
        }
        return out;
    }

    function RenderFilm(num, film) {
        var template = "<?php echo Prep(implode ("", file ("templates/{$config['template']}/catalog_film.htm"))); ?>";
        template = template.replace(/%NUMBER%/gi,num);
        template = template.replace(/%FILM_POSTER%/gi,film.Poster);
        template = template.replace(/%FILMID%/gi,film.ID);
        template = template.replace(/%FILMNAME%/gi,film.Name);
        template = template.replace(/%POPULAR%/gi,film.popular);
        template = template.replace(/%FILMNAMESAFE%/gi,film.Name.replace(/\"/gi,"\\\""));
        template = template.replace(/%IMDBRATING%/gi,film.ImdbRating);
        template = template.replace(/%LOCALRATING%/gi,film.LocalRating);
        template = template.replace(/%COUNTLOCALRATING%/gi,film.CountLocalRating);
        template = template.replace(/%PERSONALRATING%/gi,film.PersonalRating);
        template = template.replace(/%DESCRIPTION%/gi,film.Description);
        if (film.CommentsCount>0){
            template = template.replace(/%COMMENTSBEGIN%|%COMMENTSEND%/gi,"");
            template = template.replace(/%COMMENTSCOUNT%/gi,film.CommentsCount);
        }
        else {
            template = template.replace(/%COMMENTSBEGIN%.*%COMMENTSEND%/gi,"");
        }
        template = template.replace(/%HIDE%/gi,(film.Hide=='1' ? "(скрыт)": ""));
        template = template.replace(/%ORIGINALFILMNAME%/gi,film.OriginalName);
        template = template.replace(/%YEAR%/gi,film.Year);
        template = template.replace(/%GENRES%/gi,film.genres);
        template = template.replace(/%COUNTRIES%/gi,film.countries);
        if (film.director.length>0){
            template = template.replace(/%DIRECTORBEGIN%|%DIRECTOREND%/gi,"");
            template = template.replace(/%DIRECTOR%/gi,film.director);
        }
        else {
            template = template.replace(/%DIRECTORBEGIN%.*%DIRECTOREND%/gi,"");
        }
        if (film.actors.length>0){
            template = template.replace(/%ACTORSBEGIN%|%ACTORSEND%/gi,"");
            template = template.replace(/%ACTORS%/gi,film.actors);
        }
        else {
            template = template.replace(/%ACTORSBEGIN%.*%ACTORSEND%/gi,"");
        }
        template = replacePlaceholder(template, 'TYPEOFMOVIE', film.TypeOfMovie);
        template = replacePlaceholder(template, 'QUALITY', film.Quality);
        template = replacePlaceholder(template, 'TRANSLATION', film.Translation);
        template = replacePlaceholder(template, 'SHORTTRANSLATION', film.ShortTranslation);
        return template;
    }

    function DrawCatalog(offset, letter) {
        TypeOfMovie = document.getElementById("TypeFilter").value;
        country = document.getElementById("CountryFilter").value;
        genre = document.getElementById("GenreFilter").value;
        order = 0;
        dir = "DESC";
        for (i=0; i<9;i++){
            if (document.getElementById("SortField"+i).checked)    order=i;
        }
        if (document.getElementById("SortFieldDesc").checked){
            dir = "DESC";
           } else dir = "ASC";
        url = 'actions.php?action=filmlist';
        if (country>0) url += '&country='+country;
        if (genre>0) url += '&genre='+genre;
        url += '&order='+order;
        url += '&dir='+dir;
        url += '&offset='+offset;
        url += '&count='+RESULT_ON_PAGE;
        if (letter) url += '&letter='+escape(letter);
                
        JsHttpRequest.query(
            url, // backend
            (TypeOfMovie.length>0) ? {'typeofmovie':TypeOfMovie} : {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    if (document.getElementById("gen_time")) document.getElementById("gen_time").innerHTML = Math.round(eval(result.gen_time)*100)/100 + " сек.";
                    var resultcount = result.count ? result.count : 0;
                    var currentpage = Math.ceil((offset+1)/RESULT_ON_PAGE);
                    var pages = new StringBuilder();
                    if (order==5){
                        pages.append("<div style='font-size:8pt;'>")
                        if (letter) {
                            pages.append("<a href='javascript:scrollTo(0,0);DrawCatalog(0)'>Все</a> ");
                        } else {
                            pages.append("<span style='font-weight:bold; background:#E1E1E1; padding:2px;'>Все</span> ");
                        }
                        if (eval(result.letters.count[30])>0){
                            if (letter=='0') {
                                pages.append("<span style='font-weight:bold; background:#E1E1E1; padding:2px;'>0-9</span> ");
                            } else {
                                pages.append("<a title='"+result.letters.count[30]+"' href='javascript:scrollTo(0,0);DrawCatalog(0,\"0\")'>0-9</a> ");
                            }
                        } else {
                            pages.append("0-9 ");
                        }
                        for (i=65; i<256; i++){
                            if (((i>=65) && (i<65+26)) || ((i>=192) && (i<192+32))){
                                var cnt = result.letters.count[i];
                                var mychr = result.letters.char1[i];
                                if (mychr==letter){
                                    pages.append("<span style='font-weight:bold; background:#E1E1E1; padding:2px;'>"+mychr+"</span> ")
                                } else if (eval(cnt)>0){
                                    pages.append("<a title='"+cnt+"' href='javascript:DrawCatalog(0,\""+mychr+"\")'>"+mychr+"</a> ");
                                } else pages.append(mychr+" ");
                            }
                        }
                        pages.append("</div>");
                    }
                    
                    window.selectPage = function(offset)
                    {
                        scrollTo(0,0);
                        DrawCatalog(offset, letter);
                    }

                    var pageIndexBox = LMS.Widgets.Factory('PageIndexBoxOld');
                    pageIndexBox.beforePagesText = "Страницы: ";
                    pageIndexBox.prevPageText = "<span class='arrow'>&larr; предыдущая</span>";
                    pageIndexBox.nextPageText = "<span class='arrow'>следующая &rarr;</span>";
                    pageIndexBox.setCount(resultcount);
                    pageIndexBox.setPageSize(RESULT_ON_PAGE);
                    pageIndexBox.setOffset(offset);
                    LMS.Connector.connect(pageIndexBox, 'valueChanged', window, 'selectPage');

                    var pageIndexBox2 = LMS.Widgets.Factory('PageIndexBoxOld');
                    pageIndexBox2.beforePagesText = "Страницы: ";
                    pageIndexBox2.prevPageText = "<span class='arrow'>&larr; предыдущая</span>";
                    pageIndexBox2.nextPageText = "<span class='arrow'>следующая &rarr;</span>";
                    pageIndexBox2.setCount(resultcount);
                    pageIndexBox2.setPageSize(RESULT_ON_PAGE);
                    pageIndexBox2.setOffset(offset);
                    LMS.Connector.connect(pageIndexBox2, 'valueChanged', window, 'selectPage');


                    var outstr = new StringBuilder();
                    var films = result.films;
                    outstr.append ("<table>");
                    for (var j=0; film = films[j];j++){
                        outstr.append(RenderFilm((currentpage-1)*RESULT_ON_PAGE+j+1,film));
                    }
                    outstr.append ("</table>");

                    var wrapper = LMS.Widgets.Factory('LayerBox');
                    wrapper.setDOMId('CatalogBox');

                    wrapper.addWidget(pageIndexBox);
                    wrapper.addHTML(pages.toString());

                    wrapper.addHTML(outstr.toString());

                    wrapper.addWidget(pageIndexBox2);
                    wrapper.paint();
                }
            },
            true
        )
    }

    function SelectPersonShort(mainprefix, subprefix, personid){
        if (document.getElementById(subprefix+personid).style.display==""){
                document.getElementById(mainprefix+personid).style.border = "0px";
                document.getElementById(mainprefix+personid).style.background = "white";
        }
        else{
            document.getElementById(mainprefix+personid).style.border = "1px solid silver";
            document.getElementById(mainprefix+personid).style.background = "#F5F5F5";
        }
    }

    function RenderPersonShort(num,person){
        var outstr = new StringBuilder();
        outstr.append ("<tr>");
        outstr.append ("<td valign='top' width='1%'>"+num+". </td>");
        outstr.append ("<td valign='top' width='*'>");
        outstr.append ("<div id='_mperson2" + person.ID + "' style='border : 0px solid Black; padding-left:3px;'><a href='javascript: SelectPersonShort(\"_mperson2\",\"_person2\"," + person.ID + "); ExpandPerson(\"none\",\"_person2\"," + person.ID + ",1);'><img border='0' width='10' height='10' src='images/pd.gif'> " + person.RusName + ((person.OriginalName) ? " <span style='margin:1px;color:gray'>/" + person.OriginalName + "/</span>" : "") + "</a><span id='_person2" + person.ID + "' style='display:none;font-size:8pt'></span></div>");
        outstr.append ("</td>");
        outstr.append ("</tr>");
        return outstr.toString();
    }

    function SwapCovers(num) {
        mycover = "";
        if (abigcovers[num]){
            mycover += "<a title='В новом окне' href='imageviewer.php?imgurl="+escape(abigcovers[num])+"' target='_blank'><img "+((FIXED_WIDTH_POSTER)?"width='"+FIXED_WIDTH_POSTER+"px'":"")+" src='"+acovers[num]+"' style='border-style:double; border-color:blue;'></a>";
        }
        else{
            mycover += "<img "+((FIXED_WIDTH_POSTER)?"width='"+FIXED_WIDTH_POSTER+"px'":"")+" src='"+acovers[num]+"' border='0'>";
        }
        document.getElementById("cover").innerHTML = mycover;
    }

    function CoverSwitch(num){
        for (var i=0; i<acovers.length;i++){
            if ((i+1)==num) img = "images/num_a.gif"; else img = "images/num_p.gif";
            document.getElementById("coversw"+(i+1)).style.backgroundImage = "url('"+img+"')";
        }

    }

    function OptReplace(blockname,text,value){
        if (value && (value.length>0)){
            text = text.replace(RegExp("%"+blockname+"BEGIN%|%"+blockname+"END%","gi"),"");
            text = text.replace(RegExp("%"+blockname+"%","gi"),value);
        }
        else text = text.replace(RegExp("%"+blockname+"BEGIN%.*%"+blockname+"END%","gi"),"");
        return text;
    }

    var mustshow = 0;

    function FrameMouseOver(num){
        if (mustshow){
            document.getElementById("frame"+num).style.display = "";
        }
    }
    
    function FrameMouseOut(num){
        document.getElementById("frame"+num).style.display = "none";
    }

    function renderFiles(film)
    {
        var filmid = film.ID;
        var files = "";
        if (showhintplayers) files += " <a href='settings.php' target='_blank'>(настроить)</a>";
        myplayers = new Array();
        tmp = preferences.players.split(",");
        for (i=0; i<tmp.length;i++) myplayers[tmp[i]] = 1;
        if (film.files.length>0){
            files += "<table>";
            files += "<tr><td>";
            if (eval(film.smb)==1){
                if (myplayers[1]) files += "<a title='Light Alloy' href='pl.php?player=la&uid="+uid+"&filmid=" + filmid + "'><img border='0' height='24' width='24' src='images/la24.gif'></a> ";
                if (myplayers[2]) files += "<a title='Windows Media Player' href='pl.php?player=mp&uid="+uid+"&filmid=" + filmid + "'><img border='0' height='24' width='24' src='images/mp24.gif'></a> ";
                if (myplayers[3]) files += "<a title='Media Player Classic' href='pl.php?player=mpcpl&uid="+uid+"&filmid=" + filmid + "'><img border='0' height='24' width='24' src='images/mpcpl24.gif'></a> ";
                if (myplayers[4]) files += "<a title='BSPlayer' href='pl.php?player=bsl&uid="+uid+"&filmid=" + filmid + "'><img border='0' height='24' width='24' src='images/bsl24.gif'></a> ";
                if (myplayers[5]) files += "<a title='Crystal Player' href='pl.php?player=crp&uid="+uid+"&filmid=" + filmid + "'><img border='0' height='24' width='24' src='images/mls24.gif'></a> ";
                if (myplayers[6]) files += "<a title='xine' href='pl.php?player=tox&uid="+uid+"&filmid=" + filmid + "'><img border='0' height='24' width='24' src='images/tox24.gif'></a>";
                if (myplayers[7]) files += "<a title='kaffeine' href='pl.php?player=kaf&uid="+uid+"&filmid=" + filmid + "'><img border='0' height='24' width='24' src='images/kaf24.gif'></a>";
                if (myplayers[8]) files += "<a title='totem' href='pl.php?player=totem&uid="+uid+"&filmid=" + filmid + "'><img border='0' height='24' width='24' src='images/totem24.gif'></a>";
                if (myplayers[9]) files += "<a title='Winamp/Mplayer' href='pl.php?player=pls&uid="+uid+"&filmid=" + filmid + "'><img border='0' height='24' width='24' src='images/pls24.gif'></a>";
                if (myplayers[10]) files += "<a title='VLC media player' href='pl.php?player=xspf&uid="+uid+"&filmid=" + filmid + "'><img border='0' height='24' width='24' src='images/vlc24.gif'></a>";
            }
            files += "</td>";
            if (film.ftpfolderpath && film.ftpfolderpath.length>1) files += "<td>&nbsp; <a title='Папка c фильмом' onClick='inc_hit(" + filmid + ")' href=\"" + film.ftpfolderpath + "\" target='_blank'><img border='0' height='24' width='24' src='images/folder_24.gif'></a></td>";
            files += "</tr>";
            files += "</table>";

            files += "<table class='playfilm'>";
            cntmrr = 0;
            mirror = "";
            for (var i=0; file = film.files[i];i++){
                if (eval(film.countmirrors)>1 && (mirror!=film.files[i].mirror)){
                    cntmrr++
                    mirror = film.files[i].mirror;
                    files += "<tr><td align='center' colspan='4'><b>Зеркало "+mirror+"</b></td></tr>";
                }
                files += "<tr>";
                mylink = (file.ftp) ?  "<a title='Скачать по FTP' onClick='inc_hit(" + filmid + ")' href=\"" + file.ftp + "\">" + file.Name + "</a>" : ((file.ftp_license) ? "<a title='Скачать' target='_blank' href=\"" + file.ftp_license + "\">" + file.Name + "</a>" : file.Name);
                files += "<td>" + mylink +" &nbsp;</td>";
                files += (file.ed2kLink.length) ?  "<td>&nbsp;<a onClick='inc_hit(" + filmid + ")' title='Скачать через ed2k' href=\"" + file.ed2kLink + "\">ed2k</a>&nbsp;</td>" : "";
                files += (file.dcppLink.length) ?  "<td>&nbsp;<a onClick='inc_hit(" + filmid + ")' title='Скачать через DC++' href=\"" + file.dcppLink + "\">DC++</a>&nbsp;</td>" : "";
                files += "<td>&nbsp; " + Math.round(file.Size/1048576) + " МБт&nbsp;</td>";
                
                smb = "";
                if (eval(film.smb)==1){
                    if (myplayers[1]) smb += "<a title='Light Alloy' href='pl.php?player=la&uid="+uid+"&filmid=" + filmid + "&fileid=" + file.ID + "'><img border='0' height='16' width='16' src='images/la.gif'></a> ";
                    if (myplayers[2]) smb += "<a title='Windows Media Player' href='pl.php?player=mp&uid="+uid+"&filmid=" + filmid + "&fileid=" + file.ID + "'><img border='0' height='16' width='16' src='images/mp.gif'></a> ";
                    if (myplayers[3]) smb += "<a title='Media Player Classic' href='pl.php?player=mpcpl&uid="+uid+"&filmid=" + filmid + "&fileid=" + file.ID + "'><img border='0' height='16' width='16' src='images/mpcpl.gif'></a> ";
                    if (myplayers[4]) smb += "<a title='BSPlayer' href='pl.php?player=bsl&uid="+uid+"&filmid=" + filmid + "&fileid=" + file.ID + "'><img border='0' height='16' width='16' src='images/bsl.gif'></a> ";
                    if (myplayers[5]) smb += "<a title='Crystal Player' href='pl.php?player=crp&uid="+uid+"&filmid=" + filmid + "&fileid=" + file.ID + "'><img border='0' height='16' width='16' src='images/mls.gif'></a> ";
                    if (myplayers[6]) smb += "<a title='xine' href='pl.php?player=tox&uid="+uid+"&filmid=" + filmid + "&fileid=" + file.ID + "'><img border='0' height='16' width='16' src='images/tox.gif'></a> ";
                    if (myplayers[7]) smb += "<a title='kaffeine' href='pl.php?player=kaf&uid="+uid+"&filmid=" + filmid + "&fileid=" + file.ID + "'><img border='0' height='16' width='16' src='images/kaf.gif'></a> ";
                    if (myplayers[8]) smb += "<a title='totem' href='pl.php?player=totem&uid="+uid+"&filmid=" + filmid + "&fileid=" + file.ID + "'><img border='0' height='16' width='16' src='images/totem.gif'></a> ";
                    if (myplayers[9]) smb += "<a title='Winamp/Mplayer' href='pl.php?player=pls&uid="+uid+"&filmid=" + filmid + "&fileid=" + file.ID + "'><img border='0' height='16' width='16' src='images/pls.gif'></a> ";
                    if (myplayers[10]) smb += "<a title='VLC media player' href='pl.php?player=xspf&uid="+uid+"&filmid=" + filmid + "&fileid=" + file.ID + "'><img border='0' height='16' width='16' src='images/vlc.gif'></a> ";
                }
                if (smb.length) files += "<td>&nbsp;" + smb + "</td>";
                files += "</tr>";
            }
            files += "</table>";
        }
        return files;
    }

    function renderCovers(film)
    {
        acovers = film.Poster;
        abigcovers = film.BigPosters;
        var covers = "";
        if (acovers.length>1){
            for (var i=0; i<acovers.length;i++){
                covers += "<div id='coversw"+(i+1)+"' onMouseOver='SwapCovers("+i+");CoverSwitch("+(i+1)+")' style='cursor:hand;float:left;text-align:center;font-size:10pt;font-weight:bold;width:24px;margin-right:3px;height:20px;background-image:url(\"images/"+((0==i) ? "num_a.gif" : "num_p.gif")+"\");'>"+(i+1)+"</div>";
            }
        }
        covers += "<div id='cover'>";
        if (abigcovers.length && abigcovers[0]){
            covers += "<a title='В новом окне' id='coverlink' href='imageviewer.php?imgurl="+escape(abigcovers[0])+"' target='_blank'><img "+((FIXED_WIDTH_POSTER)?"width='"+FIXED_WIDTH_POSTER+"px'":"")+" src='"+acovers[0]+"' style='clear:both;border-style:double; border-color:blue;'></a>";
        }
        else{
            covers += "<img style='clear:both;' "+((FIXED_WIDTH_POSTER)?"width='"+FIXED_WIDTH_POSTER+"px'":"")+" src='"+acovers[0]+"' border='0'>";
        }
        covers += "</div>";
        return covers;
    }

    function renderFrames(film)
    {
        var myframes = "";    
        if (film.smallframes && film.smallframes.length){
            for (var i=0; frame = film.smallframes[i];i++){
                myframes += "<a title='В новом окне' target='_blank' href='imageviewer.php?imgurl="+escape(film.frames[i])+"'><img width='"+SMALL_FRAME_WIDTH+"px' onMouseOver='mustshow=1;setTimeout(\"FrameMouseOver("+i+");\",100);' onMouseOut='mustshow=0;setTimeout(\"FrameMouseOut("+i+");\",100);' style='float:left; margin:5px' src='"+frame+"' border='0'></a>";
            }
            myframes += "<div style='float:left;position:relative;clear:both;'>";
            for (var i=0; frame = film.frames[i];i++){
                var widthStyle = '';
                if (MAX_FRAME_WIDTH_PX) {
                    widthStyle = 'max-width:' + MAX_FRAME_WIDTH_PX + 'px; width: expression(this.width > ' + MAX_FRAME_WIDTH_PX + '? ' + MAX_FRAME_WIDTH_PX + ': true);';
                }
                myframes += "<div id='frame"+i+"' style='position:absolute; display:none; border:1px solid silver; background-color:#F5F5F5'><img style='margin:5px;"+widthStyle+"' src='"+frame+"' border='0'></div>";
            }
            myframes += "</div>";
        }
        return myframes;
    }

    function renderPersones(film)
    {
        var persones = "";
        for (var i=0; person = film.persones[i];i++){
            roles = "";
            for (var j=0; role = person.Roles[j];j++){
                if (j>0) roles += ", ";
                roles += role.Role + ((role.RoleExt.length>0) ? ": " + role.RoleExt : "");
            }
            persones += "<div id='_mperson" + person.ID + "' class='PersonUnselected'><div style='float:right; width:1px; height:" + PORTRAIT_HEIGHT + ";'></div><a href='javascript:ExpandPerson(\"_mperson\",\"_person\"," + person.ID + ",0)'><img style='border : 1px solid Silver; margin:5px;' width='60' src='" + person.Image + "'><p style='margin:1px;'>" + person.RusName + "</p><p style='margin:1px;color:gray'>" + person.OriginalName + "</p><p style='margin:1px; color:gray;'>" + roles + "</p></a><span id='_person" + person.ID + "' style='display:none;'></span></div>";
        }
        return persones;
    }
    
    function DrawFilm(filmid,from,to) {
        JsHttpRequest.query(
            'actions.php?action=getfilm&film='+filmid, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    if (document.getElementById("gen_time")) document.getElementById("gen_time").innerHTML = Math.round(eval(result.gen_time)*100)/100 + " сек.";
                    film = result;
                    moderblock = "";
                    if (film.modermode==1){
                        moderblock = "<br><a title='Перейти на страницу редактирования фильма' href='admin.php?film="+filmid + "' target='_admin'>Редактировать</a>";
                        moderblock += "<table style='font-size:8pt'>";

                        combobox = RenderComboBox("QualityBox2",filmid,'Quality','UpdateFilmField',film.Quality,quality_options,40);
                        moderblock += "<tr><td>Качество видео:</td><td>"+combobox+"</td></tr>";

                        combobox = RenderComboBox("TranslationBox2",filmid,'Translation','UpdateFilmField',film.Translation,translation_options,40);
                        moderblock += "<tr><td>Озвучивание:</td><td>"+combobox+"</td></tr>"

                        combobox = RenderComboBox("TypeOfMovieBox2",filmid,'TypeOfMovie','UpdateFilmField',film.TypeOfMovie,typeofmovie_options,40);
                        moderblock += "<tr><td>Тип фильма:</td><td>"+combobox+"</td></tr></table>"
                    }
                    var files = renderFiles(film);
                    var covers = renderCovers(film);
                    var myframes = renderFrames(film);
                    var persones = renderPersones(film);
                    
                    document.title = SITE_TITLE + ": " + film.Name + " /" + film.OriginalName1252 + "/";
                    pagestitle[2] = document.title;
                    template ="<?php echo Prep(implode ("", file ("templates/{$config['template']}/film.htm"))); ?>";
                    template = template.replace(/%FILM_POSTER%/gi,"");
                    template = template.replace(/%POSTERS%/gi,covers);
                    template = template.replace(/%FILMID%/gi,film.ID);
                    template = template.replace(/%FILMIMDBID%/gi,film.imdbID);
                    template = template.replace(/%MODERATOR%/gi,film.Moderator);
                    template = template.replace(/%FILMNAME%/gi,film.Name);
                    template = template.replace(/%HIDE%/gi,(film.Hide=='1' ? "(скрыт)": ""));
                    template = template.replace(/%POPULAR%/gi,film.popular);
                    template = template.replace(/%IMDBRATING%/gi,film.ImdbRating);
                    template = template.replace(/%LOCALRATING%/gi,film.LocalRating);
                    template = template.replace(/%COUNTLOCALRATING%/gi,film.CountLocalRating);
                    template = template.replace(/%PERSONALRATING%/gi,film.PersonalRating);
                    
                    cmax = 1;
                    csum = 0;
                    for (var i=1;i<=10;i++){
                        c = film.LocalRatingDetail[i] ? parseInt(film.LocalRatingDetail[i]) : 0;
                        if (c>cmax) cmax = c;
                        csum += c;
                        
                    }
                    if (!csum) csum = 1;
                    for (var i=1;i<=10;i++){
                        c = film.LocalRatingDetail[i] ? parseInt(film.LocalRatingDetail[i]) : 0;
                        re = new RegExp("%LR"+i+"%","gi");
                        template = template.replace(re,c);
                        re = new RegExp("%LR"+i+"W%","gi");
                        template = template.replace(re,(100*c/cmax)+"%");
                        re = new RegExp("%LR"+i+"P%","gi");
                        template = template.replace(re,Math.round(100*c/csum)+"%");
                    }
                    
                    template = template.replace(/%PERSONALRATING%/gi,film.PersonalRating);

                    if (film.CommentsCount>0){
                        template = template.replace(/%COMMENTSBEGIN%|%COMMENTSEND%/gi,"");
                        template = template.replace(/%COMMENTSCOUNT%/gi,film.CommentsCount);
                    }
                    else {
                        template = template.replace(/%COMMENTSBEGIN%.*%COMMENTSEND%/gi,"");
                    }
                    template = template.replace(/%SITEURL%/gi,SITE_URL);
                    template = template.replace(/%ORIGINALFILMNAME%/gi,film.OriginalName);
                    template = template.replace(/%YEAR%/gi,film.Year);
                    template = template.replace(/%GENRES%/gi,film.genres);
                    template = template.replace(/%COUNTRIES%/gi,film.countries);
                    template = template.replace(/%DIRECTOR%/gi,film.director);
                    template = template.replace(/%RUNTIME%/gi,film.RunTime);
                    template = template.replace(/%CREATEDATE%/gi,film.CreateDate);
                    template = template.replace(/%FILES%/gi,files);
                    template = template.replace(/%HIT%/gi,film.Hit);
                    template = template.replace(/%PERSONES%/gi,persones);
                    template = template.replace(/%DESCRIPTION%/gi,film.Description);
                    template = OptReplace("FRAMES",template,myframes);

                    template = template.replace(/%GOTOCOMMENTS%/gi,"<a href='javascript:ShowComments(" + film.ID + ",1)'> " + ( (film.CommentsCount!=0) ?  "Отзывы (" + film.CommentsCount + ")" : "Оставить отзыв") + "</a>");
                    template = template.replace(/%COMMENTSBLOCK%/gi,"<div style='margin-top:2em; margin-left:3em; margin-bottom:1em' id='comments'><a href='javascript:ShowComments(" + film.ID + ",0)' style='font-weight:bold'>" + ( (film.CommentsCount!=0) ?  "Отзывы (" + film.CommentsCount + ")" : "Оставить отзыв") + "</a></div>");
            
                    if (film.Resolution.length>0){
                        template = template.replace(/%RESOLUTIONBEGIN%|%RESOLUTIONEND%/gi,"");
                        template = template.replace(/%RESOLUTION%/gi,film.Resolution);
                        template = template.replace(/%VIDEOINFO%/gi,film.VideoInfo);
                    }
                    else template = template.replace(/%RESOLUTIONBEGIN%.*%RESOLUTIONEND%/gi,"");
            
        
                    template = OptReplace("AUDIOINFO",template,film.AudioInfo);
                    template = replacePlaceholder(template, 'TYPEOFMOVIE', film.TypeOfMovie);
                    template = replacePlaceholder(template, 'QUALITY', film.Quality);
                    template = replacePlaceholder(template, 'TRANSLATION', film.Translation);
                    template = replacePlaceholder(template, 'SHORTTRANSLATION', film.ShortTranslation);

                    template = OptReplace("MPAA",template,film.MPAA);
            
                    if (film.modermode==1){
                        template = template.replace(/%MODERBLOCK%/gi,moderblock);
                    }
                    else template = template.replace(/%MODERBLOCK%/gi,"");

                    template = OptReplace("LINKS",template,film.Links);
                    template = OptReplace("PRESENT",template,film.Present);
                    otherfilms = "";
                    if (film.otherfilms.length){
                        str = new Array();
                        for (i=0;i<film.otherfilms.length;i++){
                            str[i] = "<a href='#film:" + film.otherfilms[i].ID + ":0:0'>"+film.otherfilms[i].Name+" ("+film.otherfilms[i].Year+")</a>"    
                        }
                        otherfilms = str.join("<br>");
                    }
                    template = OptReplace("TRAILER",template,film.Trailer);
                    template = OptReplace("SOUNDTRACK",template,film.SoundTrack);
                    template = template.replace(/%OTHERFILMS%/gi,otherfilms);

                    template = OptReplace("SEEALSO",template,otherfilms+film.Trailer+film.SoundTrack);

                    current_film = filmid;
                    AddToPreFilms(filmid,film.Name);
                    
                    if (from==1){
                        BackPosition = getScrollY();
                        template += "<span style='margin-left:3em;' id='backbox'><a href='javascript:GoBack()'>Вернуться</a></span>";
                    }
                    document.getElementById("FilmBox").innerHTML = template; //outstr.toString();
                    if (film.PersonalRating>0){
                         document.getElementById("ratingbox").value = film.PersonalRating;
                    }
                    else{
                        document.getElementById("_myratingbox").style.display = "";
                    }
        
    
                    if (!RIGHTS_SETRATING) {
                        document.getElementById("_myratingbox").style.display = "none";
                        document.getElementById("_myratingbox").innerHTML = CAN_NOT_SETRATING;
                    }

                    scrollTo(0,0);
                    MyPages.select(2);
        
                    if (to==1){
                        setTimeout('ShowComments('+filmid+',1)',200);
                    }
                }
            },
            true
        )
    }

    function ExpandPerson(mainprefix, subprefix,personid, allphoto) {
        if (document.getElementById(subprefix+personid).style.display==""){
            document.getElementById(subprefix+personid).style.display = "none";
            if (document.getElementById(mainprefix+personid)){
                document.getElementById(mainprefix+personid).className = "PersonUnselected";
            }
        }
        else{
            JsHttpRequest.query(
                'actions.php?action=getperson&person='+personid, // backend
                {},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                    if (result){
                        if (document.getElementById("gen_time")) document.getElementById("gen_time").innerHTML = Math.round(eval(result.gen_time)*100)/100 + " сек.";
                        person = result;
                        outstr = "";
                        if (document.getElementById(mainprefix+personid)){
                            document.getElementById(mainprefix+personid).className = "PersonSelected";
                        }
                        document.getElementById(subprefix+personid).style.display = "";
                        if (person.Description){
                            outstr += "<br><span class='sectionheader'>Информация</span><br><img src='images/hr2.gif' width='327' height='1'><br>" + person.Description;
                        }
                        if ((person.Images.length>1) || allphoto){
                            outstr += "<span class='sectionheader'>Фотографии</span><br><img src='images/hr2.gif' width='327' height='1'><br>";
                            for (i=0; i<person.Images.length;i++){
                                outstr += "<img style='border : 1px solid Silver; margin:5px;' width='60' src='" + person.Images[i] + "'> ";
                            }
                        }
                        if (person.films.length){
                            outstr += "<br><span class='sectionheader'>Фильмы</span><br><img src='images/hr2.gif' width='327' height='1'><p>";
                            for (i=0; i<person.films.length;i++){
                                outstr += "<a href='#film:" + person.films[i].ID + ":0:0'>" + person.films[i].Name + " (" + person.films[i].Year + ")</a> "
                                outstr += "<span style='color:gray'>(";
                                for (j=0; j<person.films[i].Roles.length;j++){
                                    outstr += (j) ? ", " : "";
                                    outstr += person.films[i].Roles[j];
                                }
                                outstr += ")</span> <a id='_personpostlink" + person.films[i].ID + "' href='javascript:AddToPreFilms(" + person.films[i].ID + ",\"" + person.films[i].Name + "\");'>Отложить</a><br>";
                            }
                            outstr += "</p>";
                        }
                        document.getElementById(subprefix+personid).innerHTML = outstr;
                    };
                },
                true
            )
        }
    }
    
    
    function Search(mysearch_num,offset) {
        if (search_num==mysearch_num){
            if (document.getElementById("byfilms").checked) {what = 'films';} else {what = 'persones';};
            text = document.getElementById("textsearch").value;
            if (text.length>1){
                JsHttpRequest.query(
                    'actions.php?action=simplesearch&what='+what, // backend
                    {'text':text},
                    function(result, errors) {
                        if (errors.length) sys_message(errors);
                        if (result){
                            if (document.getElementById("gen_time")) document.getElementById("gen_time").innerHTML = Math.round(eval(result.gen_time)*100)/100 + " сек.";
                            if (result.fcount){
                                resultcount = result.fcount;
                                var outstr = new StringBuilder();
                                resn = 0; 
                                outstr.append("<table>");
                                if (result.films_exact.length) outstr.append ("<tr><td colspan='2' style='font-weight:bold'>Точное совпадение</td></tr>"); 
                                for (var j=0; film = result.films_exact[j];j++){
                                    outstr.append (RenderFilmShort(++resn,film));
                                }
                                if (result.films_part.length) outstr.append ("<tr><td colspan='2' style='font-weight:bold'>Частичное совпадение</td></tr>"); 
                                for (var j=0; film = result.films_part[j];j++){
                                    outstr.append (RenderFilmShort(++resn,film));
                                }
                                if (result.films_approx.length) outstr.append ("<tr><td colspan='2' style='font-weight:bold'>Похожий результат</td></tr>"); 
                                for (var j=0; film = result.films_approx[j];j++){
                                    outstr.append (RenderFilmShort(++resn,film));
                                }
                                outstr.append("</table>");
                            }
                            if (result.pcount){
                                resultcount = result.pcount;
                                var outstr = new StringBuilder();
                                resn = 0; 
                                outstr.append("<table>");
                                if (result.persones_exact.length) outstr.append ("<tr><td colspan='2' style='font-weight:bold'>Точное совпадение</td></tr>"); 
                                for (var j=0; person = result.persones_exact[j];j++){
                                    outstr.append (RenderPersonShort(++resn,person));
                                }
                                if (result.persones_part.length) outstr.append ("<tr><td colspan='2' style='font-weight:bold'>Частичное совпадение</td></tr>"); 
                                for (var j=0; person = result.persones_part[j];j++){
                                    outstr.append (RenderPersonShort(++resn,person));
                                }
                                if (result.persones_approx.length) outstr.append ("<tr><td colspan='2' style='font-weight:bold'>Похожий результат</td></tr>"); 
                                for (var j=0; person = result.persones_approx[j];j++){
                                    outstr.append (RenderPersonShort(++resn,person));
                                }
                                outstr.append("</table>");
                            }
    
                            if (resn) document.getElementById("resultsearch").innerHTML = "Найдено "+resultcount+":<br>" + outstr.toString();
                                else document.getElementById("resultsearch").innerHTML = "Ничего не найдено";
                        }
                    },
                    true
                )
            }
        }
    }
    
    $j(document).ready(function() {
        Init();
    });
</script>
<style>
select.dropDownList
{ 
    margin: 0;
    padding: 0;
    border: 1px solid #93aaba;
    width: 200px;
    overflow: hidden;
    position: absolute;
    left: 0;
    background-color: #fff;
    z-index:999;
    display: block; 
    font-size: 8pt;
}

</style>
    <?php 
        $headFile = dirname(__FILE__) . "/templates/{$config['template']}/head.php";
        if (file_exists($headFile)) {
            require_once $headFile;
        }
    ?>
</head>
<!--[if lt IE 7 ]> <body class="ie6"> <![endif]-->
<!--[if IE 7 ]>    <body class="ie7"> <![endif]-->
<!--[if IE 8 ]>    <body class="ie8"> <![endif]-->
<!--[if IE 9 ]>    <body class="ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <body> <!--<![endif]-->
    
<div id="sysmessagebox" style="margin:0px;padding:5px;border:1px solid silver; background-color:#F5F5C0; width:100%; display:none;">
<div style='float:right;'><a href='javascript:Hide("sysmessagebox")'>Закрыть</a></div>
<span id="sysmessage"></span>
</div>

<div id="messagebox" style="margin:0px;padding:5px;border:1px solid silver; background-color:#F5F5C0; width:100%; display:none;">
<div style='float:right;'><a href='javascript:Hide("messagebox")'>Закрыть</a></div>
<span id="message"></span>
</div>
<?php require_once "templates/{$config['template']}/main.php"; ?>
</body>
</html>
