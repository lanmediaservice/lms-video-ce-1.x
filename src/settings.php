<?php
/**
 * Видео-каталог
 * (C) 2006-2009 Ilya Spesivtsev, macondos@gmail.com
 *
 * Интерфейс настройки учетной записи
 *
 * @author Ilya Spesivtsev 
 * @version 1.07
 */
require_once "config.php"; 
require_once "functions.php"; 
session_set_cookie_params(86400); 
session_start();
require_once isset($config['logon.php']) ? $config['logon.php'] : "logon.php" ;
?>
<html>
<head>
<title>Настройка учетной записи</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<link rel="stylesheet" href="<?php echo "templates/{$config['template']}/styles.css" ?>">
<script language="JavaScript" src="jshttprequest/JsHttpRequest.js"></script>
<script language="JavaScript" src="common/jhr_controller.js"></script>
<script language="JavaScript" src="klayers.js"></script>
<script language="JavaScript" src="strings.js"></script>
<script>
    //Константы
    var SITE_URL = "<?php echo $config['siteurl']; ?>";
    var loadings = 0;
    
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
    
    function setCookie (name, value, expires, path, domain, secure) {
          document.cookie = name + "=" + escape(value) +
            ((expires) ? "; expires=" + expires : "") +
            ((path) ? "; path=" + path : "") +
            ((domain) ? "; domain=" + domain : "") +
            ((secure) ? "; secure" : "");
    }

    function Pages(ids, labels, onselects, PageSelectorId, name){
        this.ids = ids;
        this.labels = labels;
        this.onselects = onselects;
        this.PageSelectorId = PageSelectorId;
        this.name = name;
    }
    
    Pages.prototype.select = function (num){
        CurrentPage = num;
        menustr = "<ul>";
        for (i=0; this.ids[i]; i++){
            if (i==num){
                document.getElementById(this.ids[i]).style.display="";
                menustr += "<li id='current'><a>"+this.labels[i]+"</a></li>";
            }
            else {
                document.getElementById(this.ids[i]).style.display="none"
                menustr += "<li><a href='javascript:"+this.name+".select("+i+");'>"+this.labels[i]+"</a></li>";
            }
        }
        menustr += "</ul>";
        document.getElementById(this.PageSelectorId).innerHTML = menustr;
        setTimeout(this.onselects[num],0);
    }
    
    var MyPages = new Pages(
        Array("PreferencesPage"),
        Array("Настройки"),
        Array(""),
        "PageSelector",
        "MyPages"
    )
    
    function Init() {
        MyPages.select(0);
        getPreferences();
        preloadImage('images/progbar.gif');
        iamlife();
    }    


    function getPreferences() {
        JsHttpRequest.query(
            'actions.php?action=getpreferences', // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    myplayers = new Array();
                    if (result.preferences.players){
                        tmp = result.preferences.players.split(",");
                        for (i=0; i<tmp.length;i++) myplayers[tmp[i]] = 1;
                    }
                    for (i=1; i<9;i++){
                        if (myplayers[i]) document.getElementById("pl"+i).checked = true;
                    }
                }
            },
            true
        )
    }

    function SavePreferences() {
        myplayers = new Array();
        j=0;
        for (i=1; i<=10;i++){
            if (document.getElementById("pl"+i) && document.getElementById("pl"+i).checked) myplayers[j++]=i;
        }
        players = myplayers.join(",");
        JsHttpRequest.query(
            'actions.php?action=setpreferences&param=players&value='+players, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
            },
            true
        )
    }

    function ChangePassword() {
        myoldpass = escape(document.getElementById("oldpass").value);
        mynewpass1 = escape(document.getElementById("newpass1").value);
        mynewpass2 = escape(document.getElementById("newpass2").value);
        JsHttpRequest.query(
            'actions.php?action=changepassword&oldpass='+myoldpass+'&newpass1='+mynewpass1+'&newpass2='+mynewpass2, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    if (eval(result.ok)==0){
                        document.getElementById("passmess").innerHTML = "Ошибка<br>";
                    }
                    if (result.errors){
                        outstr = ""
                        for (i=0; i<result.errors.length;i++){
                            outstr += result.errors[i] + "<br>";
                        }
                        document.getElementById("passmess").innerHTML = outstr;
                    }
                    if (eval(result.ok)==1){
                        document.getElementById("oldpass").value = "";
                        document.getElementById("newpass1").value = "";
                        document.getElementById("newpass2").value = "";
                        document.getElementById("passmess").innerHTML = "Пароль был успешно сменен";
                    }
                }
            },
            true
        )
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
                    window.location = SITE_URL;
                }
            },
            true
        )
    }


    function iamlife(){
        JsHttpRequest.query(
            'actions.php', // backend
            {},
            function(result, errors) { },
            true
        )
        setTimeout('iamlife()',600000);
    }
</script>
<style type="text/css">
H1 {font-size:small;}


.PreferencesPage {
    clear:both;
    padding:0;
    border-left:2px solid #808080;
    border-right:2px solid #808080;
    border-bottom:0px;
}

.clear {
    clear:both;
    height:1px;
    overflow:hidden;
}

</style>
</head>
<body onLoad="Init()" >
<div id="waiticon" style="position:absolute; top:0px; left:0px; display:none;"><img src="images/wait.gif" border="0"></div>
<div align="right" style="width:100%"><a href='javascript:window.close()'>Закрыть</a></div>
<!-- Главное меню -->
<div id="PageSelector"></div>
<!-- /Главное меню -->
<div class='PreferencesPage' id="PreferencesPage" style="background-color:white;display:none;padding:10px;">
<table width="95%" border="0" cellspacing="0" cellpadding="0">
 <tr>
  <td width='1px'><img width='1px' height='450px' src='images/min.gif' border='0'></td>
  <td valign="top">
    <b>Сменить пароль</b><br>
    <img src='images/hr.gif' width='327' height='1'><br>
    <table>
        <tr><td>Старый пароль</td><td><input id="oldpass" type='password'></td></tr>
        <tr><td>Новый пароль</td><td><input id="newpass1" type='password'></td></tr>
        <tr><td>Повторите новый пароль</td><td><input id="newpass2" type='password'></td></tr>
    </table>
    <div id="passmess"></div>
    <button onClick="ChangePassword()">Сменить</button><br><br>
<?php if (isset($config['smb']) && $config['modes'][$user['Mode']]['smb']) :?>
    <b>Выбрать плейлисты</b><br>
    <img src='images/hr.gif' width='327' height='1'><br>
    <table>
    <tr><td valign="center"><input id="pl1" type='checkbox'></td><td><label for="pl1"><img border='0' height='24' width='24' src='images/la24.gif'></label></td><td> <label for="pl1">Light Alloy</label></td></tr>
    <tr><td valign="center"><input id="pl2" type='checkbox'></td><td><label for="pl2"><img border='0' height='24' width='24' src='images/mp24.gif'></label></td><td> <label for="pl2">Windows Media Player</label></td></tr>
    <tr><td valign="center"><input id="pl3" type='checkbox'></td><td><label for="pl3"><img border='0' height='24' width='24' src='images/mpcpl24.gif'></label></td><td> <label for="pl3">Media Player Classic</label></td></tr>
    <tr><td valign="center"><input id="pl4" type='checkbox'></td><td><label for="pl4"><img border='0' height='24' width='24' src='images/bsl24.gif'></label></td><td> <label for="pl4">BSPlayer</label></td></tr>
    <tr><td valign="center"><input id="pl5" type='checkbox'></td><td><label for="pl5"><img border='0' height='24' width='24' src='images/mls24.gif'></label></td><td> <label for="pl5">Crystal Player</label></td></tr>
    <tr><td valign="center"><input id="pl6" type='checkbox'></td><td><label for="pl6"><img border='0' height='24' width='24' src='images/tox24.gif'></label></td><td> <label for="pl6">xine</label></td></tr>
    <tr><td valign="center"><input id="pl7" type='checkbox'></td><td><label for="pl7"><img border='0' height='24' width='24' src='images/kaf24.gif'></label></td><td> <label for="pl7">kaffeine</label></td></tr>
    <tr><td valign="center"><input id="pl9" type='checkbox'></td><td><label for="pl9"><img border='0' height='24' width='24' src='images/pls24.gif'></label></td><td> <label for="pl9">Winamp/Mplayer</label></td></tr>
    <tr><td valign="center"><input id="pl10" type='checkbox'></td><td><label for="pl10"><img border='0' height='24' width='24' src='images/vlc24.gif'></label></td><td> <label for="pl10">VLC media player</label></td></tr>
<!--    <tr><td valign="center"><input id="pl8" type='checkbox'></td><td><label for="pl8"><img border='0' height='24' width='24' src='images/totem24.gif'></label></td><td> <label for="pl8">totem</label></td></tr> -->
    </table>
    <button onClick="SavePreferences()">Сохранить</button>
<?php endif;?>
    
 </td>
 </tr>
</table>
</div>
<table cellspacing="0" cellpadding="0" width="100%">
<tr height="63px">
    <td width="13px" class="footer1">&nbsp;</td>
    <td width="*" class="footer2">&copy; 2006 &mdash; 2009</td>
    <td width="13px" class="footer3">&nbsp;</td>
</tr>
</table>
</body>
</html>
