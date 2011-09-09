<?php
/**
 * Видео-каталог
 * (C) 2006-2009 Ilya Spesivtsev, macondos@gmail.com
 *
 * Back-offic'ные задачи
 * Интерфейс администратора
 *
 * @author Ilya Spesivtsev
 * @version 1.07
 */
require_once "config.php";
header('Expires: -1');
require_once "functions.php";
session_start();
require_once isset($config['logon.php']) ? $config['logon.php'] : "logon.php" ;
if (!getRights("admin_view", $user)){
    echo "У вас недостаточно прав для того, чтобы войти на эту страницу";
    exit;
}

$idSQLConnection = mysql_connect($config['mysqlhost'], $config['mysqluser'], $config['mysqlpass']);
if ( !$idSQLConnection ) {
    echo "Критическая ошибка на сервере. Ошибка при подключении к базе данных.";
    exit;
}
$result = mysql_select_db( $config['mysqldb'], $idSQLConnection );
if ( !$result ) {
    echo "Критическая ошибка на сервере. Ошибка при выборе базы данных.";
    exit;
}
if (isset($config['mysql_set_names'])) mysql_query($config['mysql_set_names']);

?>
<html>
<head>
<title>Администратор видео-каталога</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<link id="luna-tab-style-sheet" type="text/css" rel="stylesheet" href="tabpane/css/luna/tab.css" />
<script language="JavaScript" src="js/prototype-1.6.0.3.js"></script> 
<script language="JavaScript" src="jshttprequest/JsHttpRequest.js"></script>
<script language="JavaScript" src="common/jhr_controller.js"></script>
<script language="JavaScript" src="strings.js"></script>
<script language="JavaScript" src="klayers.js"></script>
<script language="JavaScript" src="dropDownList.js"></script>
<script>
    var loadings = 0;
    var maxloadings = 1;
    var incomings = new Array();
    var queue = new Array();
    var posters = new Array();
    var films_to_update = new Array();
    var reduceposters = new Array();
    var persones = new Array();
    var resolvepersones = new Array();
    var filmsforframegenerate = new Array();
    var photos = new Array();
    var current_film = 0;
    var module = 'video';
    var dropDownLists = new Object();
    var current_person = 0;
    var CtrlUp = false;
    var Key13 = false;
    var postponedpersones = new Array();

    var HIDE_NODES = <?php echo @$config['hide_nodes'] ? 1 : 0; ?>;

    var GMI_ENABLE = <?php echo @$config['gmi_enable'] ? 1 : 0; ?>;

    var EXT_SEARCH_ENGINES = [<?php if (isset($config['external_search_engines'])){
        $searchEnginesArray = array();
        foreach ($config['external_search_engines'] as $searchEngine) {
            $searchEnginesArray[] = '"' . addslashes($searchEngine) . '"';
        } 
        echo implode(',', $searchEnginesArray);
    }?>];
    
    var MyBlur1 = 0;
    var MyBlur2 = 0;
    var MyBlur3 = 0;

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
        if (e.keyCode == 17) CtrlUp = true;
        if (prevKeydown) {
            __prev = prevKeydown;
            return __prev(e);
        }
    }

    owner.onkeyup = function(e) {CtrlUp = false;};

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
        $config['typeofmovie_options'] = array("", "Не определен","Документальный сериал","Документальный фильм","Научно - популярный фильм","Концерт","Короткометражный фильм","Мультсериал","Мьюзикл-опера","Полнометражный мультфильм","Сборник мультфильмов","Спортивная видеопрограмма","Телевизионный спектакль","Телепередача","Худ. кинофильм","Худ. телесериал","Худ. телефильм"); 
    }
    echo "[";
    $tmp = array();
    foreach ($config['typeofmovie_options'] as $option) $tmp[] = "\"".addslashes($option)."\"";
    echo implode(",",$tmp);
    echo "]";
    ?>
    
    var help_place = "http://docs.lanmediaservice.com/pages.php?id=";
    var help_context = {
        1 : {
            0: "",
            1: "lms_video_generate"
        },
        2 : {
            0: ""
        },
        3 : {
            0: "",
            1: 'lms_video_covers'
        },
        4 : {
            0: '',
            1: 'lms_video_gd'
        }
    };

    var MyPages = new Pages(
        Array("IncomingPage","DBFilmsPage", "EditorPage","PersonesEditorPage", "DBEditorPage","UtilsPage","ServicePage"),
        Array("Поступления","Закачка дополнений", "Редактор фильмов","Редактор персоналий", "Редактор БД","Утилиты","Сервис"),
        Array("","","","","","",""),
        Array("getIncomingList();","","ListFilms(0);","ListPersones(0);","MyDBPages.select(0);","","MyServicePages.select(0);"),
        "PageSelector",
        "MyPages"
    )

    var MyDBPages = new Pages(
        Array("UsersPage"),
        Array("Пользователи"),
        Array(""),
        Array("ListUsers(0);"),
        "DBPageSelector",
        "MyDBPages"
    )

    var MyServicePages = new Pages(
        Array("UpdatePage"),
        Array("Обновление"),
        Array(""),
        Array(""),
        "ServicePageSelector",
        "MyServicePages"
    )


    function Help(section,article){
        window.open(help_place + help_context[section][0]+help_context[section][article],"",'location=yes,scrollbars=yes,status=yes');
    }


    function MyXMLEncode(str){
        if (!str) return "";
    //    str = str.replace(/&/g, "&amp;");
        str = str.replace(/</g, "&lt;");
        str = str.replace(/>/g, "&gt;");
        str = str.replace(/'/g,"&#39;");//"&apos;");
        str = str.replace(/\"/g,"&#34;");//"&quot;");
          return str;
    }
    
    function Init() {
        <?php     if (isset($_GET["film"])) {
                echo "MyPages.select(2)\r\n";
                echo "DrawFilm({$_GET['film']})\r\n";
            }
            else{
                echo "MyPages.select(0)\r\n";
            }
        ?>
        preloadImage('images/wait.gif');
        preloadImage('tabpane/css/luna/tab.png');
        preloadImage('tabpane/css/luna/tab.active.png');
        preloadImage('tabpane/css/luna/tab.hover.png');
        QueueManager();
    }
    
    function RenderComboBox_old(boxid,id,fieldname,updatefunction,value,options,size){
        selectid = boxid + "Select";
        myoptions = "";
        for(var i=0; i<options.length;i++){
            myoptions += "<option value='"+MyXMLEncode(options[i])+"'>"+MyXMLEncode(options[i])+"</option>"
        }
        combobox = "";
        c = Math.min(options.length,20);
        combobox += "<span><input type='text' id='"+boxid+"' onChange='"+updatefunction+"("+id+",\""+fieldname+"\",this)' onClick='document.getElementById(\""+selectid+"\").style.display=\"\"' onFocus='document.getElementById(\""+selectid+"\").style.display=\"\"' onBlur='MyBlur"+boxid+"_2=1; setTimeout(\"if (MyBlur"+boxid+"_2) document.getElementById(\\\""+selectid+"\\\").style.display=\\\"none\\\"\",100)' style='height:20px;z-index:10;' size='"+size+"' value='"+MyXMLEncode(value)+"'><br>";
        combobox += "<div id='"+selectid+"' style='position:relative;display:none;z-index:15;'><select  onChange='"+updatefunction+"("+id+",\""+fieldname+"\",this);document.getElementById(\""+boxid+"\").value=this.value;setTimeout(\"MyBlur"+boxid+"_2=1;document.getElementById(\\\""+selectid+"\\\").style.display=\\\"none\\\"\",200);' size='"+c+"' onFocus='MyBlur"+boxid+"_2=0;document.getElementById(\""+boxid+"\").focus();' onBlur='MyBlur"+boxid+"_2=0;' style='position:absolute;top:0px;border:1px solid gray;z-index:20;' value='"+MyXMLEncode(value)+"'>";
        combobox += myoptions;
        combobox += "</select></div></span>";
        return combobox;
    }    

    
    function RenderComboBox(boxId, id, fieldName, updateFunctionName, value, optionsList, size){
        dropDownLists[boxId] = new dropDownList(boxId, null, optionsList);
        focusedVar = 'combobox_is_focused' + boxId;
        combobox = "<input type='text' id='"+boxId+"' onChange='"+updateFunctionName+"("+id+",\""+fieldName+"\",this)' onClick='"+focusedVar+"=1; dropDownLists[&#34;"+boxId+"&#34;].show();' onFocus='"+focusedVar+"=1;dropDownLists[&#34;"+boxId+"&#34;].show();' onBlur='"+focusedVar+"=0; setTimeout(\"if (!"+focusedVar+") dropDownLists[\\\""+boxId+"\\\"].hide();\", 100)' style='height:20px;z-index:10;' size='"+size+"' value='"+MyXMLEncode(value)+"'>";
        //alert(combobox);
        return combobox;
    }   

    function QueueManager(){
        if ((queue.length>0) && (JsHttpRequest.JHRController.loadings_counter<maxloadings)){
            shifted = queue.shift();
            setTimeout(shifted,0);
        }
        if (queue.length>0){
            document.getElementById("QueueBox").innerHTML = "Выполняется: " + loadings + " Очередь запросов: " + queue.length + " <a href='javascript:var queue =  new Array();'>(очистить)</a>"
        } else document.getElementById("QueueBox").innerHTML = "";
        setTimeout('QueueManager()',500);
    }

    function Pages(ids, labels, onselects, onfirstselects, PageSelectorId, name){
        this.ids = ids;
        this.labels = labels;
        this.onselects = onselects;
        this.onfirstselects = onfirstselects;
        this.PageSelectorId = PageSelectorId;
        this.name = name;
        this.index = 0;
    }

    Pages.prototype.select = function (num){
        menustr = "<div class='tab-row'>";
        //     <h2 class="tab hover"><a href='#'>Privacy</a></h2>
        this.index = num;
        for (i=0; this.ids[i]; i++){
        //    if (i) menustr += " | ";
            if (i==num){
                document.getElementById(this.ids[i]).style.display="";
                menustr += "<h2 class='tab selected'>"+this.labels[i]+"</h2>";
            }
            else {
                document.getElementById(this.ids[i]).style.display="none"
                menustr += "<h2 class='tab' onMouseOver='this.className=\"tab hover\"' onMouseOut='this.className=\"tab\"' onClick='"+this.name+".select("+i+");'><a href='javascript:"+this.name+".select("+i+");'>"+this.labels[i]+"</a></h2>";
            }
        }
        menustr += "</div>";
        document.getElementById(this.PageSelectorId).innerHTML = menustr;
        setTimeout(this.onselects[num],0);
        if (this.onfirstselects[num].length){
            setTimeout(this.onfirstselects[num],0);
            this.onfirstselects[num] = "";
        }
    }

    function ModerShowFilm(filmid){
        JsHttpRequest.query(
            'actions.php?action=showfilm&film=' + filmid, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result) DrawFilm(filmid);
            },
            true
        )
    }

    function InstallUpdate(product_code,myrev,sqlrev){
        JsHttpRequest.query(
            'actions.php?action=update&product_code=' + product_code + '&myrev=' + myrev + '&sqlrev=' + sqlrev, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    outstr = "";
                    if (result.errors){
                        myerrors = result.errors;
                        outstr += "<b>Возникли ошибки во время обновления:</b><br>";
                        for (i=0;i<myerrors.length;i++){
                            outstr += myerrors[i] + "<br>";
                        }
                    }
                    outstr += "<b>Журнал обновления:</b> (сохранено в UPDATE.LOG)<br>";
                    if (result.log){
                        mylog = result.log;
                        for (i=0;i<mylog.length;i++){
                            outstr += mylog[i] + "<br>";
                        }
                    }
                    document.getElementById("UpdateBox").innerHTML = outstr;
                }
            },
            true
        )
    }

    function GetLastVersion(){
        document.getElementById("UpdateBox").innerHTML = "Поиск новой версии...пожалуйста, ждите";
            JsHttpRequest.query(
                'actions.php?action=check_update', // backend
                {},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                    if (result && result.products){
                        products = result.products;
                        outstr = "";
                        for(product in products){
                            outstr    += "<b>" + product + ":</b><br>";
                            if (products[product].errors && products[product].errors.length){
                                outstr += "Во время поиска обновлений произошли следующие ошибки:<br>";
                                for (i=0;i<products[product].errors.length;i++) outstr += products[product].errors[i] + "<br>";
                            }
        
                            outstr    += "<table class='incoming'><tr><th>&nbsp;</th><th>Текущая версия</th> <th>Последняя версия</th></tr>";
                            if (eval(products[product].last_revision)){
                                if (eval(products[product].curent_revision)<eval(products[product].last_revision)){
                                    outstr += "<tr style='font-weight:bold;'><td>Скриптовая часть</td> <td>"+products[product].curent_revision+"</td><td>"+products[product].last_revision+"</td></tr>";
                                    outstr += "<tr><td colspan='3'>";
                                    outstr    += "<table width='100%' class='incoming'><tr><th>Версия</th><th>Дата</th> <th>Комментарий</th> <th>Что нового</th></tr>";
                                    for (i=0;i<products[product].revision_history.length;i++){
                                        outstr += "<tr>";
                                        outstr += "<td valign='top'>" + products[product].revision_history[i][0] + "." + products[product].revision_history[i][1] + "</td>";
                                        outstr += "<td valign='top'>" + products[product].revision_history[i][2] + "</td>";
                                        outstr += "<td valign='top'>" + products[product].revision_history[i][3] + "</td>";
                                        outstr += "<td valign='top'><pre>" + products[product].revision_history[i][4] + "</pre></td>";
                                        outstr += "</tr>";
                                    }
                                    outstr    += "</table>";
                                    outstr    += "</td></tr>";
                                } else outstr += "<tr><td>Скриптовая часть</td><td colspan='2'>Новых версий не обнаружено</td></tr>";
                            } else outstr += "<tr><td>Скриптовая часть</td><td colspan='2'>Новых версий не обнаружено</td></tr>";
        
                            if (eval(products[product].last_sqlrevision)){
                                if (eval(products[product].curent_sqlrevision)<eval(products[product].last_sqlrevision)){
                                    outstr    += "<tr style='font-weight:bold;'><td>База данных</td> <td>"+products[product].curent_sqlrevision+"</td><td>"+products[product].last_sqlrevision+"</td></tr>";
                                    outstr += "<tr><td colspan='3'>";
                                    outstr    += "<table width='100%' class='incoming'><tr><th>Версия</th><th>Дата</th> <th>Комментарий</th></tr>";
                                    for (i=0;i<products[product].sql_updates.length;i++){
                                        outstr += "<tr>";
                                        outstr += "<td valign='top'>" + products[product].sql_updates[i][1] + "</td>";
                                        outstr += "<td valign='top'>" + products[product].sql_updates[i][4] + "</td>";
                                        outstr += "<td valign='top'>" + products[product].sql_updates[i][3] + "</td>";
                                        outstr += "</tr>";
                                    }
                                    outstr    += "</table>";
                                    outstr    += "</td></tr>";
                                } else outstr += "<tr><td>База данных</td><td colspan='2'>Новых версий не обнаружено</td></tr>";
                            } else outstr += "<tr><td>База данных</td><td colspan='2'>Новых версий не обнаружено</td></tr>";
        
                            outstr    += "</table><br>";
        
                            if (products[product].revision_history || products[product].sql_updates){
                                myrev = (products[product].curent_revision!=products[product].last_revision) ? products[product].last_revision : 0;
                                sqlrev = (products[product].curent_sqlrevision!=products[product].last_sqlrevision) ? products[product].last_sqlrevision : 0;
                                outstr    += "<button onclick='InstallUpdate(\""+product+"\","+myrev+","+sqlrev+")'>Установить</button><br><br>";
                            }
                        }
                        document.getElementById("UpdateBox").innerHTML = outstr;
                    }
                    else{
                        outstr    = "Извините, произошел сбой при поиске.<br>";
                        if (result.errors) outstr += result.errors.join("<br>");
                        document.getElementById("UpdateBox").innerHTML = outstr;
                    }
                },
                true
            )
    
    }



    function DeleteBadPhotos(){
            if (confirm('Действительно удалить все внешние ссылки на фотографии? Эту операцию нельзя будет отменить.')){
                JsHttpRequest.query(
                    'actions.php?action=deletebadphotos', // backend
                    {},
                    function(result, errors) {
                        if (errors.length) sys_message(errors);
                        if (result){
                             user_message('Фотографии удалены');
                        }
                    },
                    true
                )
            }
    }

    function Cleaning(really){
        if (!really || (really && confirm('Действительно очистить каталоги *posters/ photos/ от неиспользуемых файлов? Эту операцию нельзя будет отменить.'))){
            JsHttpRequest.query(
                'actions.php?action=cleaning&really=' + really, // backend
                {},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                    if (result){
                        mess = really ? "Удалено: \r\n" : "Не используется: \r\n";
                        mess += result.deleted + " файл(а)(ов) \r\n";
                        mess += result.deleted_size + " байт";
                        alert(mess);
                    }
                },
                true
            )
        }
    }

    function CalcLocalRating(){
        JsHttpRequest.query(
            'actions.php?action=calclocalrating', // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    mess = "Пересчетом затронуто: <br>" ;
                    mess += result.updated + " рейтингов";
                    user_message(mess);
                    
                }
            },
            true
        )
    }

    function ModerHideFilm(filmid){
        JsHttpRequest.query(
            'actions.php?action=hidefilm&film=' + filmid, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    DrawFilm(filmid);
                }
            },
            true
        )
    }

    function UpdateFiles(filmid){
        JsHttpRequest.query(
            'actions.php?action=updatefilesinfo&id=' + filmid, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    DrawFilm(filmid);
                }
            },
            true
        )
    }

    function DeleteFilm(filmid,all){
        JsHttpRequest.query(
            'actions.php?action=deletefilm&id=' + filmid+((all)?'&all=1':''), // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    document.getElementById("FilmBox").innerHTML = "";
                    ListFilms(0);
                }
            },
            true
        )
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
                    if (result.ok == 1) {
                        window.location.reload();
                    }
                }
            },
            true
        )
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

    function UpdateField(id,field,o) {
        CustomUpdateField(id,field,o,'updatefield');
    }
    
    function UpdateFilmField(id,field,o) {
        CustomUpdateField(id,field,o,'updatefilmfield');
        
    }
    
    function UpdateFileField(id,field,o) {
            CustomUpdateField(id,field,o,'updatefilefield');
    }
    
    function UpdatePersonField(id,field,o) {
        CustomUpdateField(id,field,o,'updatepersonfield');
    }

    function UpdateUserField(id,field,o) {
        CustomUpdateField(id,field,o,'updateuserfield');
    }

    function UpdatePersonField(id,field,o) {
        CustomUpdateField(id,field,o,'updatepersonfield');
    }
    
    function SetNode(id) {
        o = {value:1};    
        field = 'IsNode';
        CustomUpdateField(id,field,o,'updatefield');
        queue.push("getIncomingList()");
    }

    function HideFile(id) {
        if (confirm("Скрыть (навсегда)?")){
            JsHttpRequest.query(
                'actions.php?action=updatefield&id='+id+'&field=Hide&value=1', // backend
                {},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                    document.getElementById("_incom"+id).style.display = "none";
                    document.getElementById("ex"+id).style.display = "none";
                    for (i=0;i<incomings.length;i++){
                        if (incomings[i].id==id) incomings.splice(i,1);
                    }
                },
                true
            )
        }
    }


    function SetPassword(userid) {
        mynewpass1 = escape(document.getElementById("newpass1_"+userid).value);
        mynewpass2 = escape(document.getElementById("newpass2_"+userid).value);
        JsHttpRequest.query(
            'actions.php?action=changepassword&userid='+userid+'&oldpass=&newpass1='+mynewpass1+'&newpass2='+mynewpass2, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    if (eval(result.ok)==0){
                        document.getElementById("passmess_"+userid).innerHTML = "Ошибка<br>";
                    }
                    if (result.errors){
                        outstr = ""
                        for (i=0; i<result.errors.length;i++){
                            outstr += result.errors[i] + "<br>";
                        }
                        document.getElementById("passmess_"+userid).innerHTML = outstr;
                    }
                    if (eval(result.ok)==1){
                        document.getElementById("newpass1_"+userid).value = "";
                        document.getElementById("newpass2_"+userid).value = "";
                        document.getElementById("passmess_"+userid).innerHTML = "Пароль был успешно сменен";
                    }
                }
            },
            true
        )
    }

    function DeleteUser(userid) {
        if (confirm("Удалить пользователя (и его комментарии, закладки, оценки) и пересчитать рейтинг?")){
            JsHttpRequest.query(
                'actions.php?action=deleteuser&userid='+userid, // backend
                {},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                    if (result){
                        CalcLocalRating();
                        ListUsers(0);
                    }
                },
                true
            )
        }
    }

    function SetOk(site,id) {
        document.getElementById("_"+site+"find"+id).innerHTML = "ok";
        for (i=0;i<incomings.length;i++){
            if (incomings[i].id==id){
                incomings[i].RusUrlParse = "1";
                incomings[i].ImdbUrlParse = "1";
            }
        }
    }

    function Search(id) {
        JsHttpRequest.query(
            'actions.php?action=searchinfo&id='+id, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    if (document.getElementById("ex"+id).style.display=="none"){
                        document.getElementById("ex"+id).style.display = "";
                    }
                    getDetail(id,1);
                }
            },
            true
        )
    }

    function AutoSearch() {
        for (i=0; file=incomings[i]; i++){
            if (incomings[i].Validity=="0%" && !eval(file.IsNode)){
                queue.push("Search("+incomings[i].id+");");
            }
        }
    }

    function Parse(id,over,expand) {
        JsHttpRequest.query(
            'actions.php?action=parse&over='+over+'&id='+id, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    for (i=0;i<incomings.length;i++){
                        if (incomings[i].id==id){
                            incomings[i].rusParsed = eval(result.rusParsed);
                            if (incomings[i].rusParsed) document.getElementById("_rusParsed"+id).innerHTML = "ok";
    
                            incomings[i].imdbParsed = eval(result.imdbParsed);
                            if (incomings[i].imdbParsed) document.getElementById("_imdbParsed"+id).innerHTML = "ok";
                        }
                    }
                    if (expand) getDetail(id,1);
                }
            },
            true
        )
}

    function AutoParse() {
        for (i=0; file=incomings[i]; i++){
            if ((incomings[i].ImdbUrlParse.length>0) && (incomings[i].RusUrlParse.length>0) && (incomings[i].imdbParsed==0 || incomings[i].rusParsed==0) && !eval(file.IsNode)){
                queue.push("Parse("+incomings[i].id+",0,0);");
            }
        }
        queue.push("getIncomingList();");
    }

    function ParseAvi(id,expand) {
        JsHttpRequest.query(
            'actions.php?action=parseavi&id='+id, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    for (i=0;i<incomings.length;i++){
                        if (incomings[i].id==id){
                            incomings[i].Resolution = result.Resolution;
                            document.getElementById("_rescell"+id).innerHTML = result.Resolution;
                        }
                    }
                    if (expand) getDetail(id,2);
                }
            },
            true
        )
    }    

    function AutoParseAvi() {
        for (i=0; file=incomings[i]; i++){
            if (!file.Resolution.length && !eval(file.IsNode)) queue.push("ParseAvi("+incomings[i].id+",0);");
        }
        queue.push("getIncomingList();");
    }

    function Commit(id) {
        parsed = 1;
        parsedavi = 1;
        for (i=0;i<incomings.length;i++){
            if (incomings[i].id==id){
                if (!((incomings[i].imdbParsed==1) && (incomings[i].rusParsed==1))){
                    parsed = 0;
                }
                if (!incomings[i].Resolution.length){
                    parsedavi = 0;
                }
            }
        }
        if ((parsedavi || confirm("Не спарсена AVI-информация. Продолжить импортирование?")) && (parsed || confirm("Не спарсена информация сайтов. Продолжить импортирование?"))){
            JsHttpRequest.query(
                'actions.php?action=commitincoming&id='+id, // backend
                {},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                    if (result && eval(result.ok)){
                        document.getElementById("_incom"+id).style.display = "none";
                        document.getElementById("ex"+id).style.display = "none";
                        for (i=0;i<incomings.length;i++){
                            if (incomings[i].id==id) incomings.splice(i,1);
                        }
                    }
                },
                true,
                250000
            )
        }
    }
    
    function Attach(fromid,toid) {
        JsHttpRequest.query(
            'actions.php?action=attach&fromid='+fromid+'&toid='+toid, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result && eval(result.ok)){
                    document.getElementById("_incom"+fromid).style.display = "none";
                    document.getElementById("ex"+fromid).style.display = "none";
                    for (i=0;i<incomings.length;i++){
                        if (incomings[i].fromid==fromid) incomings.splice(i,1);
                    }
                    UpdateFiles(toid);
                }
            },
            true
        )
    }

    function AutoCommit() {
        for (i=0; file=incomings[i]; i++){
            if ((incomings[i].imdbParsed==1) && (incomings[i].rusParsed==1) && (incomings[i].Resolution.length) && !eval(file.IsNode)){
                queue.push("Commit("+incomings[i].id+");");
            }
        }
        queue.push("getIncomingList();");
    }

    function UpdateFilms(id) {
        if (id==0){
            for (i=0; i<films_to_update.length; i++){
                queue.push("UpdateFilms("+films_to_update[i]+");");
            }
            queue.push(function(){
                    document.getElementById("films_to_updatecount").innerHTML = '. Обновлено.';
                }
            );
        }
        else{
            JsHttpRequest.query(
                'actions.php?action=update_imdbrating&film='+id, // backend
                {},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                },
                true
            )
        }
    }

    function DownloadPoster(id,andRefresh) {
        JsHttpRequest.query(
            'actions.php?action=downloadposter&id='+id, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    if (andRefresh) DrawFilm(id);
                }
            },
            true
        )
    }

    function DownloadAllPosters(nextstep) {
        for (i=0; i<posters.length; i++){
            queue.push("DownloadPoster("+posters[i]+");");
        }
        queue.push("PostersCount();");
        if (nextstep) {
            queue.push("PostersReduceCount();");
            queue.push("ReduceAllPosters();");
        }
    }

    function ReduceAllPosters() {
        for (i=0; i<reduceposters.length; i++){
            queue.push("DownloadPoster("+reduceposters[i]+");");
        }
        queue.push("PostersReduceCount();");
    }


    function ResolveOzonUlrPerson(id) {
        JsHttpRequest.query(
            'actions.php?action=resolveozonulrperson&id='+id, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    document.getElementById("_person"+id).style.display = "none";
                    DrawPerson(id);
                }
            },
            true
        )
    }

    function SearchPersonOnOzon(id) {
        JsHttpRequest.query(
            'actions.php?action=searchpersononozon&id='+id, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    founds = result;
                    outstr = "<div>Варианты на ozon.ru</div>";
                    for (var i=0; variant = founds[i];i++){
                            outstr += "<a href='javascript:document.getElementById(\"_OzonUrl\").value=\"" + MyXMLEncode(variant[0]) + "\";document.getElementById(\"_OzonUrl\").onchange(); DownloadPerson("+id+",1)'>" + variant[1] + "</a><br>";
                    }
                    outstr += "<a href='javascript:Hide(\"ozonpersonsearch\")'>Отмена</a>";
                    document.getElementById("ozonpersonsearch").innerHTML = outstr;
                    document.getElementById("ozonpersonsearch").style.display = "";
                }
            },
            true
        )
    }


    function GenerateFilmFrames(id,andshow) {
        JsHttpRequest.query(
            'actions.php?action=generate_screenshots&film='+id, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    if (andshow) DrawFilm(id);
                }
            },
            true
        )
    }

    function DownloadPerson(id) {
        JsHttpRequest.query(
            'actions.php?action=downloadperson&id='+id, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
            },
            true
        )
    }

    function DownloadAllPersones(nextstep) {
        for (i=0; i<persones.length; i++){
            queue.push("DownloadPerson("+persones[i]+");");
        }
        queue.push("PersonesCount();");
        if (nextstep) {
            queue.push("PhotosCount();");
            queue.push("DownloadAllPhotos();");
        }
    }

    function ResolveAllPersones(nextstep) {
        for (i=0; i<resolvepersones.length; i++){
            queue.push("ResolveOzonUlrPerson("+resolvepersones[i]+");");
        }
        queue.push("ResolvePersonesCount();");
        if (nextstep) {
            queue.push("PhotosCount();");
            queue.push("DownloadAllPhotos();");
        }
    }


    function GenerateAllFilmsFrames(nextstep) {
        for (i=0; i<filmsforframegenerate.length; i++){
            queue.push("GenerateFilmFrames("+filmsforframegenerate[i]+");");
        }
        queue.push("FramesCount();");
    }

    function DownloadPhotos(id) {
        JsHttpRequest.query(
            'actions.php?action=downloadphotos&id='+id, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
            },
            true
        )
    }

    function DownloadAllPhotos() {
        for (i=0; i<photos.length; i++){
            queue.push("DownloadPhotos("+photos[i]+");");
        }
        queue.push("PhotosCount();");
    }

    function ToggleDetail(id) {
        if (document.getElementById("ex"+id).style.display=="none"){
            document.getElementById("ex"+id).style.display = "";
            getDetail(id,1);
        }
        else{
            document.getElementById("ex"+id).style.display = "none";
        document.getElementById("detail"+id).innerHTML = "";
        }
    }


    function UnsetNode(id) {
        JsHttpRequest.query(
            'actions.php?action=unsetnode&id='+id, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                getIncomingList();
            },
            true
        )
    }

    function getIncomingList() {
        JsHttpRequest.query(
            "actions.php?action=getincominglist&hide_nodes="+HIDE_NODES, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                    if (result.logon == 1) {
                        var outstr = new StringBuilder();
                        if (result.files){
                            incomings = result.files;
                            minlevel = eval(result.files[0].Level);
                            maxlevel = 0;
                            for (var i=0; file = result.files[i];i++){
                                if (eval(file.Level)<minlevel) minlevel = eval(file.Level);
                                if (eval(file.Level)>maxlevel) maxlevel = eval(file.Level);

                            }
                            sh = "";
                            for (var i=0; i<=(maxlevel-minlevel);i++){
                                sh += "<th rowspan='2' style='width:16px;'>&nbsp;</th>";
                            }

                            outstr.append ("<table class='incoming' style='font-size:xx-small'><tr>"+sh+"<th rowspan='2'>Имя файла</th><th rowspan='2'>Размер</th><th rowspan='2'>Англ. назв.</th><th rowspan='2'>Русское назв.</th><th>Вид. инф.</th><th>найдено в imdb</th><th>найдено в ozon/etc</th><th>найдено постеров</th><th>выбр. imdb</th><th>выбр. rus</th><th>парсено imdb</th><th>парсено rus</th></tr>");
                            outstr.append ("<tr><th><a href='javascript:AutoParseAvi();'>спарсить</a></th><th colspan='2'><a href='javascript:AutoSearch();'>автопоиск</a></th><th><a href='javascript:AutoImgSearch();'>автопоиск</a></th><th colspan='2'><a href='javascript:AutoParse();'>автопарсинг</a></th><th colspan='2'><a href='javascript:AutoCommit();'>принять готовые</a></th></tr>");
                            for (var i=0; file = result.files[i];i++){
                                if (file.dir){
                                    imgsrc = eval(file.SubDir) ? 'images/dir_sub.gif' : 'images/dir.png';
                                }
                                else{
                                    switch (file.path.substr(file.path.length-4).toLowerCase()){
                                        case '.avi':
                                        case '.mpg':
                                            imgsrc = 'images/avi.png';
                                        break;
                                        case '.mp3':
                                        case '.ogg':
                                        case '.wma':
                                            imgsrc = 'images/mp3.jpg';
                                        break;
                                        default : imgsrc = 'images/unknown.png';
                                    }
                                }
                                if (eval(file.IsNode)) {
                                    if (!HIDE_NODES) outstr.append ("<tr id='_incom"+file.id+"'><td colspan='"+(1+eval(file.Level)-minlevel)+"' align='right'><a title='Свернуть узел' href='javascript:UnsetNode("+file.id+")'><img height='16' width='16' border='0' src='images/tree_minus.bmp'></a></td><td colspan='"+(12+maxlevel-eval(file.Level))+"'><img height='16' width='16' border='0' src='"+imgsrc+"'> "+file.path+"</td></tr>");
                                }
                                else{
                                    if (HIDE_NODES) file.Level = minlevel;
                                    if ((eval(file.Level)-minlevel)>0){
                                        filenames = file.path.split("<br>");
                                        for(var j=0; j<filenames.length;j++) filenames[j] = filenames[j].match(/[^\/]*$/);
                                        filename = filenames.join("<br>");
                                    } else filename = file.path;

                                    outstr.append ("<tr id='_incom"+file.id+"'><td colspan='"+(1+eval(file.Level)-minlevel)+"' align='right'>"+( (file.dir) ? "<a title='Сделать узлом' href='javascript:SetNode("+file.id+")'><img height='16' width='16' border='0' src='images/tree_plus.bmp'></a>" : "&nbsp;")+"</td><td colspan='"+(1+maxlevel-eval(file.Level))+"'> <table class='borderno'><tr><td nowrap><img height='16' width='16' border='0' src='"+imgsrc+"'> <a href='javascript:HideFile("+file.id+")' title='Скрыть (навсегда)'><img height='16' width='16' border='0' src='images/delete_16.gif'></a> </td><td><a "+((file.Doubles.length)?"style='color:red' title='Найдены дубликаты, нажмите чтобы узнать подробности'":"")+" href='javascript:ToggleDetail("+file.id+")'>"+filename+"</a></td></tr></table> </td><td>"+file.size+"</td><td><input id='_en"+file.id+"' onChange='UpdateField("+file.id+",\"EngName\",this)' type='text' size='20' value='"+MyXMLEncode(file.EngName)+"'></td><td><input  id='_ru"+file.id+"' onChange='UpdateField("+file.id+",\"RusName\",this)' type='text' size='20' value='"+MyXMLEncode(file.RusName)+"'></td>");
                                    outstr.append ("<td title='"+(file.VideoInfo)+" | "+(file.AudioInfo)+" | "+(file.Runtime)+" сек.' id='_rescell"+file.id+"'>"+(file.Resolution)+"&nbsp;</td>");
                                    outstr.append ("<td id='_imdbsearch"+file.id+"'>"+((file.ImdbSearch==-1)?"?":file.ImdbSearch)+"</td><td id='_russearch"+file.id+"'>"+((file.RusSearch==-1)?"?":file.RusSearch)+"</td><td id='_imgsearch"+file.id+"'>"+file.GoogleImageSearch+"</td><td id='_imdbfind"+file.id+"'>"+((file.ImdbUrlParse)?"ok":"&nbsp;")+"</td><td id='_rusfind"+file.id+"'>"+((file.RusUrlParse)?"ok":"&nbsp;")+"</td><td id='_imdbParsed"+file.id+"'>"+((file.imdbParsed==1)?"ok":"&nbsp;")+"</td><td id='_rusParsed"+file.id+"'>"+((file.rusParsed==1)?"ok":"&nbsp;")+"</td></tr>")
                                    outstr.append ("<tr id='ex"+file.id+"' style='display:none;'><td colspan='15'>");
                                    outstr.append ("<div id='detail"+file.id+"' style='padding:3px'></div>");
                                    outstr.append ("</td></tr>");
                                }
                            }
                        } else incomings = null;
                        outstr.append ("</table>");
                        document.getElementById("IncomingList").innerHTML = outstr.toString();
                    }
            },
            true  // do not disable caching
        )
    }

    function insertValue(to,value,field,id) {
        o = document.getElementById(to)
        o.value = value;
        UpdateField(id,field,o);
    }

    function addValue(to,value,field,id) {
        o = document.getElementById(to)
        if (o.value.length) o.value += '\r\n';
        o.value += value;
        UpdateFilmField(id,field,o);
    }
    function AdvSearch(id,where) {
        name = escape(document.getElementById(where+"advsearchtext"+id).value);
        qwhere = "";
        if (where=='rus'){
            if (document.getElementById("OZRU"+id).checked) qwhere += "&where[]=ozon";
            if (document.getElementById("WARU"+id).checked) qwhere += "&where[]=worldart";
            if (document.getElementById("SRRU"+id).checked) qwhere += "&where[]=sharereactor";
            if (document.getElementById("KPRU"+id).checked) qwhere += "&where[]=kinopoisk";
        }
        else{
            qwhere += "&where[]="+where;
        }
            JsHttpRequest.query(
                'actions.php?action=advsearchinfo&id='+id+'&name='+name+qwhere, // backend
                {},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                    getDetail(id,1);
                },
                true
            )
    }

    function AdvFilmImgSearchGo(id){
        text = document.getElementById('AdvFilmImgSearchText').value;
        AdvFilmImgSearch(id,text);
    }

    function AdvFilmImgSearch(id,name) {
        text = (name) ? '&name='+name : '';
        JsHttpRequest.query(
            'actions.php?action=imgfilmsearchinfo&id='+id + text, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    founds = result;
                    outstr = "<div style='float:left;color:#1845AD;font-weight:bold;'>G<span style='color:red'>o</span><span style='color:#C69E00'>o</span>g<span style='color:green'>l</span><span style='color:red'>e</span> Images Search</div> <div style='text-align:right;'> <a href='javascript:Hide(\"filmpostersearch\")'>Закрыть</a></div>";
                    outstr += "<div style='margin-bottom:10px;'><input id='AdvFilmImgSearchText' type='text' size='70' onKeyPress='if (event.keyCode==13) AdvFilmImgSearchGo("+id+");'> <a style='border:1px solid black;' href='javascript:AdvFilmImgSearchGo("+id+")'>Искать!</a></div>";
                    for (var i=0; givariant = founds[i];i++){
                        outstr += "<div style='float:left;width:140px;height:170px;'><a href='" + givariant.coverurl + "' target='_blank'><img src='" + givariant.imgsmall + "'></a><br>" + givariant.w + "x" + givariant.h + " <a href='javascript:addValue(\"FilmPosters\",\"" + givariant.coverurl + "\",\"Poster\","+id+")'>Добавить</a></div>";
                    }
                    document.getElementById("filmpostersearch").innerHTML = outstr;
                    document.getElementById("filmpostersearch").style.display = "";
                    document.getElementById('AdvFilmImgSearchText').value = (name) ? name : ''
                }
            },
            true
        )
    }

    function AdvImgSearch(id) {
        name = escape(document.getElementById("googleimgsearchtext"+id).value);
        JsHttpRequest.query(
            'actions.php?action=imgsearchinfo&id='+id+'&name='+name, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                getDetail(id,5);
            },
            true
        )
    }

    function ImgSearch(id) {
        JsHttpRequest.query(
            'actions.php?action=imgsearchinfo&id='+id, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    getDetail(id,5);
                }
            },
            true
        )
    }

    function GenerateMetainfo(filmId) {
        JsHttpRequest.query(
            'actions.php?action=generate_metainfo&film='+filmId, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    alert('Готово');
                }
            },
            true
        )
    }

    function AutoImgSearch() {
        for (i=0; file=incomings[i]; i++){
            if (eval(incomings[i].GoogleImageSearch)==0){
                queue.push("ImgSearch("+incomings[i].id+");");
            }
        }
    }

    function getDetail(id,page) {
        JsHttpRequest.query(
            'actions.php?action=getdetail&id='+id, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    document.getElementById("_imdbsearch"+id).innerHTML = ((result.ImdbSearch==-1)?"?":result.ImdbSearch);
                    document.getElementById("_russearch"+id).innerHTML = ((result.RusSearch==-1)?"?":result.RusSearch);

                    document.getElementById("_en"+id).value = result.EngName;
                    document.getElementById("_ru"+id).value = result.RusName;

                    comandsstr = "<div style='text-align:right'><a href='javascript:Search("+id+",1)'>Автопоиск</a> | <a href='javascript:ImgSearch("+id+")'>Автопоиск постеров</a> |<a href='javascript:ParseAvi("+id+",1)'>Парсить AVI</a> | <a href='javascript:Parse("+id+",1,1)'>Парсить сайты</a> | <a href='javascript:if (confirm(\"Импортировать фильм?\")) Commit("+id+")'>Импортировать в БД</a></div>";

                    outstr = "<a href='javascript:SelectMode("+id+",\"_DS\")' id='_DS"+id+"' class='"+((page==1)?"selected":"unselected")+"'>Поиск</a> <a href='javascript:SelectMode("+id+",\"_DAI\")' id='_DAI"+id+"' class='"+((page==2)?"selected":"unselected")+"'>AVI-Инфо</a> <a href='javascript:SelectMode("+id+",\"_DII\")' id='_DII"+id+"' class='"+((page==3)?"selected":"unselected")+"'>Imdb-Инфо</a> <a href='javascript:SelectMode("+id+",\"_DOI\")' id='_DOI"+id+"' class='"+((page==4)?"selected":"unselected")+"'>Ozon/etc-Инфо</a> <a href='javascript:SelectMode("+id+",\"_DGI\")' id='_DGI"+id+"' class='"+((page==5)?"selected":"unselected")+"'>Google Images</a><br>";
                    outstr += "<div class='detailpage' id='P_DS"+id+"' style='"+((page!=1)?"display:none":"")+"'>";
                    outstr += comandsstr;
                    outstr += "<table style='font-size:8pt' width='100%' class='borderno'>";

                    if (result.Doubles.length){
                        outstr += "<tr><td colspan='2' style='color:red'>Возможные дубликаты:<br>";
                        for (var i=0; dfilm = result.Doubles[i];i++){
                            outstr += "<a style='color:red' href='index.php#film:"+dfilm.ID+":0:0' target='_blank' title='В новом окне'>"+dfilm.Name+" /"+dfilm.OriginalName+"/ ("+dfilm.Year+")</a> <a href='javascript:if (confirm(\"Присоединить файлы как дополнение/зеркало к этому фильму?\")) Attach("+id+","+dfilm.ID+")'> &lt;- Присоединить файлы как дополнение/зеркало к этому фильму</a></div><br>";
                        }
                        outstr += "</td></tr>";
                    }

                    outstr += "<tr><td valign='top' nowrap>";
                    outstr += "imdb.com&nbsp;поиск:<br><input id='imdbadvsearchtext"+id+"' type='text' size='65' onKeyPress='if (event.keyCode==13) AdvSearch("+id+",\"imdb\");'> <a href='javascript:AdvSearch("+id+",\"imdb\")' style='border:1px solid black;'>Искать!</a><br>";
                    outstr += "imdb.com&nbsp;url:<br><input onChange='UpdateField("+id+",\"ImdbUrlParse\",this)' id='imdbURL"+id+"' value='"+result.ImdbUrlParse+"' size='65' style='background:#FAFAFA;'><br><Ol>";
                    for (var i=0; imdbvariant = result.ImdbVariants[i];i++){
                        outstr += "<Li><a href='javascript:insertValue(\"imdbURL"+id+"\",\""+imdbvariant[1]+"\",\"ImdbUrlParse\","+id+");SetOk(\"imdb\","+id+")'>"+imdbvariant[0]+"</a></Li>";
                    }
                    outstr += "</Ol></td><td valign='top'>";
                    outstr += "<input type='checkbox' id='OZRU"+id+"' checked><label for='OZRU"+id+"'>ozon.ru</label> <input type='checkbox' id='WARU"+id+"' checked><label for='WARU"+id+"'>world-art.ru</label> <input type='checkbox' id='SRRU"+id+"' checked><label for='SRRU"+id+"'>sharereactor.ru</label> <input type='checkbox' id='KPRU"+id+"' checked><label for='KPRU"+id+"'>kinopoisk.ru</label>:<br><input id='rusadvsearchtext"+id+"' type='text' size='65' onKeyPress='if (event.keyCode==13) AdvSearch("+id+",\"rus\");'> <a href='javascript:AdvSearch("+id+",\"rus\")' style='border:1px solid black;'>Искать!</a><br>";
                    outstr += "url:<br><input onChange='UpdateField("+id+",\"RusUrlParse\",this)' id='rusURL"+id+"' value='"+result.RusUrlParse+"' size='65' style='background:#FAFAFA;'><br><Ol>";
                    for (var i=0; rusvariant = result.RusVariants[i];i++){
                        outstr += "<Li><table><tr><td valign='top'><a href='"+rusvariant[1]+"' target='_blank'><img border='0' src='"+((rusvariant[4] && rusvariant[4].length) ? rusvariant[4] : "images/noposter.gif")+"'></a></td><td valign='top'><a href='javascript:insertValue(\"rusURL"+id+"\",\""+rusvariant[1]+"\",\"RusUrlParse\","+id+");SetOk(\"rus\","+id+")'>"+rusvariant[0]+" ("+rusvariant[2]+")</a><blockquote style='margin-top:0px;margin-bottom:0px;'>"+rusvariant[3]+"</blockquote></td></tr></table></Li>";
                    }
                    outstr += "</Ol>";
                    outstr += "</td></tr></table>";
                    outstr += "</div>";

                    outstr += "<div class='detailpage' id='P_DAI"+id+"' style='display:"+((page==2)?"":"none")+"'>";
                    outstr += comandsstr;
                    outstr += "<table class='borderno'>";
                    outstr += "<tr><td>Разрешение:</td><td><input onChange='UpdateField("+id+",\"Resolution\",this)' value='"+result.Resolution+"' size='100'></td></tr>";
                    outstr += "<tr><td>Видео:</td><td><input onChange='UpdateField("+id+",\"VideoInfo\",this)' value='"+MyXMLEncode(result.VideoInfo)+"' size='100'></td></tr>";
                    outstr += "<tr><td>Аудио:</td><td><input onChange='UpdateField("+id+",\"AudioInfo\",this)' value='"+MyXMLEncode(result.AudioInfo)+"' size='100'></td></tr>";
                    outstr += "<tr><td>Длительность, сек:</td><td><input onChange='UpdateField("+id+",\"Runtime\",this)' value='"+result.Runtime+"' size='100'></td></tr>";

                    combobox = RenderComboBox("QualityBox2"+id,id,'Quality','UpdateField',result.Quality,quality_options,70);
                    outstr += "<tr><td>Качество видео:</td><td>"+combobox+"</td></tr>";

                    combobox = RenderComboBox("TranslationBox2"+id,id,'Translation','UpdateField',result.Translation,translation_options,70);
                    outstr += "<tr><td>Озвучивание:</td><td>"+combobox+"</td></tr>";


                    outstr += "</table>";
                    outstr += "</div>";
                    outstr += "<div class='detailpage' id='P_DII"+id+"' style='display:"+((page==3)?"":"none")+"'>";
                    outstr += comandsstr;
                    outstr += "Внимание! Информация имеет служебную структуру.<br>";
                    outstr += "<table class='borderno'>";
                    outstr += "<tr><td>Название в оригинале:</td><td>* <input onChange='UpdateField("+id+",\"imdbOriginalName\",this)' value='"+MyXMLEncode(result.imdbOriginalName)+"' size='150'></td></tr>";
                    outstr += "<tr><td>Год:</td><td>* <input onChange='UpdateField("+id+",\"imdbYear\",this)' value='"+result.imdbYear+"' size='150'></td></tr>";
                    outstr += "<tr><td>URL постера:</td><td><input onChange='UpdateField("+id+",\"imdbPosterUrl\",this)' value='"+result.imdbPosterUrl+"' size='150'></td></tr>";
                    outstr += "<tr><td>Рейтинг MPAA:</td><td>!! <input onChange='UpdateField("+id+",\"imdbMPAA\",this)' value='"+result.imdbMPAA+"' size='150'></td></tr>";
                    outstr += "<tr><td>Описание:</td><td><textarea onChange='UpdateField("+id+",\"imdbDesription\",this)' rows='8' cols='150'>"+MyXMLEncode(result.imdbDesription)+"</textarea></td></tr>";
                    outstr += "<tr><td>Рейтинг:</td><td>!! <input onChange='UpdateField("+id+",\"imdbRating\",this)' value='"+result.imdbRating+"' size='3'></td></tr>";
                    outstr += "<tr><td>Страны:</td><td>* <input onChange='UpdateField("+id+",\"imdbCountries\",this)' value='"+result.imdbCountries+"' size='150'></td></tr>";
                    outstr += "<tr><td>Персоналии:</td><td><textarea onChange='UpdateField("+id+",\"imdbPersones\",this)' rows='8' cols='150'>"+result.imdbPersones+"</textarea></td></tr>";
                    outstr += "<tr><td>Жанры:</td><td>!! <input onChange='UpdateField("+id+",\"imdbGenres\",this)' value='"+result.imdbGenres+"' size='150'></td></tr>";
                    outstr += "<tr><td>Спарсено?(0/1):</td><td><input onChange='UpdateField("+id+",\"imdbParsed\",this)' value='"+result.imdbParsed+"' size='1'></td></tr>";
                    outstr += "</table>";
                    outstr += "Обозначения: !! - уникальное поле, * - приоритетное поле(imdb/(ozon|etc))<br>";
                    outstr += "</div>";

                    outstr += "<div class='detailpage' id='P_DOI"+id+"' style='display:"+((page==4)?"":"none")+"'>";
                    outstr += comandsstr;
                    outstr += "Внимание! Информация имеет служебную структуру.<br>";
                    outstr += "<table class='borderno'>";
                    outstr += "<tr><td>Русское название:</td><td>!! <input onChange='UpdateField("+id+",\"rusRusName\",this)' value='"+MyXMLEncode(result.rusRusName)+"' size='150'></td></tr>";
                    outstr += "<tr><td>Название в оригинале:</td><td><input onChange='UpdateField("+id+",\"rusOriginalName\",this)' value='"+MyXMLEncode(result.rusOriginalName)+"' size='150'></td></tr>";
                    outstr += "<tr><td>URL постера:</td><td>* <input onChange='UpdateField("+id+",\"rusPosterUrl\",this)' value='"+result.rusPosterUrl+"' size='150'></td></tr>";
                    outstr += "<tr><td>Год:</td><td><input onChange='UpdateField("+id+",\"rusYear\",this)' value='"+result.rusYear+"' size='150'></td></tr>";
                    outstr += "<tr><td>Страны:</td><td><input onChange='UpdateField("+id+",\"rusCountries\",this)' value='"+result.rusCountries+"' size='150'></td></tr>";
                    outstr += "<tr><td>Жанры</td><td><input onChange='UpdateField("+id+",\"rusGenres\",this)' value='"+result.rusGenres+"' size='150'></td></tr>";
                    outstr += "<tr><td>Кинокомпании:</td><td>!! <input onChange='UpdateField("+id+",\"rusCompanies\",this)' value='"+MyXMLEncode(result.rusCompanies)+"' size='150'></td></tr>";
                    combobox = RenderComboBox("FilmTypeOfMovie1",id,'rusTypeOfMovie','UpdateField',result.rusTypeOfMovie,typeofmovie_options,150);
                    outstr += "<tr><td>Тип видео:</td><td>!!"+combobox+"</td></tr>";
                    outstr += "<tr><td>Описание:</td><td>* <textarea onChange='UpdateField("+id+",\"rusDescription\",this)' rows='8' cols='150'>"+result.rusDescription+"</textarea></td></tr>";
                    outstr += "<tr><td>Персоналии:</td><td>* <textarea onChange='UpdateField("+id+",\"rusPersones\",this)' rows='8' cols='150'>"+result.rusPersones+"</textarea></td></tr>";
                    outstr += "<tr><td>Спарсено?(0/1):</td><td><input onChange='UpdateField("+id+",\"rusParsed\",this)' value='"+result.rusParsed+"' size='1'></td></tr>";
                    outstr += "</table>";
                    outstr += "Обозначения: !! - уникальное поле, * - приоритетное поле(imdb/(ozon|etc))<br>";
                    outstr += "</div>";

                    outstr += "<div class='detailpage' id='P_DGI"+id+"' style='display:"+((page==5)?"":"none")+"'>";
                    outstr += comandsstr;
                    outstr += "<table class='borderno'><tr><td>";
                    outstr += "<span style='color:#1845AD;font-weight:bold;'>G<span style='color:red'>o</span><span style='color:#C69E00'>o</span>g<span style='color:green'>l</span><span style='color:red'>e</span> Images Search</span><br><input id='googleimgsearchtext"+id+"' type='text' size='45' onKeyPress='if (event.keyCode==13) AdvImgSearch("+id+");'> <a href='javascript:AdvImgSearch("+id+")' style='border:1px solid black;'>Искать!</a><br>";
                    outstr += "</td></tr><tr><td>";
                    for (var i=0; givariant = result.GoogleImageVariants[i];i++){
                        outstr += "<div style='float:left;width:140px;height:170px;'><a href='"+givariant[0]+"' target='_blank'><img src='"+givariant[1]+"'></a><br><input type='checkbox' id='chkbx"+id+"_"+i+"' onChange='setImage("+id+","+i+")' " + ((eval(givariant[4])==1)?"checked":"") + "><label for='chkbx"+id+"_"+i+"'>"+givariant[2]+"x"+ givariant[3] +"</label></div>";
                    }
                    outstr += "</td></tr></table>";
                    outstr += "</div>";

                    document.getElementById("detail"+id).innerHTML = outstr;
                }
            },
            true
        )
    }

    function SelectMode(id,e) {
        document.getElementById("_DS"+id).className = "unselected";
        document.getElementById("_DAI"+id).className = "unselected";
        document.getElementById("_DII"+id).className = "unselected";
        document.getElementById("_DOI"+id).className = "unselected";
        document.getElementById("_DGI"+id).className = "unselected";

        document.getElementById("P_DS"+id).style.display = "none";
        document.getElementById("P_DAI"+id).style.display = "none";
        document.getElementById("P_DII"+id).style.display = "none";
        document.getElementById("P_DOI"+id).style.display = "none";
        document.getElementById("P_DGI"+id).style.display = "none";

        document.getElementById("P"+e+id).style.display = "";
        document.getElementById(e+id).className = "selected";
    }


    function DownloadAll() {
        queue.push("PostersCount();");
        queue.push("DownloadAllPosters(0);");
        queue.push("PersonesCount();");
        queue.push("DownloadAllPersones(1);");
    }

    function PostersCount() {
        posters = null;
        JsHttpRequest.query(
            'actions.php?action=postersfordownload', // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    posters = result.posters;
                    document.getElementById("posterscount").innerHTML = posters.length + ((posters.length>0)?" <a href='javascript:DownloadAllPosters(0)'>закачать</a>":"");
                }
            },
            true
        )
    }

    function FilmsToUpdateCount() {
        days = prompt('Выбрать фильмы не обновлявшиеся дней',30)
        films_to_update = null;
        JsHttpRequest.query(
            'actions.php?action=update_imdbrating&days=' + days, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    films_to_update = result.films_to_update;
                    document.getElementById("films_to_updatecount").innerHTML = '. Обновлено из сервисного кеша ' + result.updated + ' рейтингов. Осталось обновить : ' + films_to_update.length + ((films_to_update.length>0)?" <a href='javascript:UpdateFilms(0)'>обновить</a>":"");
                }
            },
            true
        )
    }

    function PostersReduceCount() {
        reduceposters = null;
        JsHttpRequest.query(
            'actions.php?action=postersforreduce', // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    reduceposters = result.posters;
                    document.getElementById("postersreducecount").innerHTML = reduceposters.length + ((reduceposters.length>0)?" <a href='javascript:ReduceAllPosters()'>начать</a>":"");
                }
            },
            true
        )
    }

    function PersonesCount() {
        persones = null;
        JsHttpRequest.query(
            'actions.php?action=personesfordownload', // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    persones = result.persones;
                    document.getElementById("personescount").innerHTML = persones.length + ((persones.length>0)?" <a href='javascript:DownloadAllPersones(0)'>закачать</a>":"");
                }
            },
            true
        )
    }

    function ResolvePersonesCount() {
        resolvepersones = null;
        JsHttpRequest.query(
            'actions.php?action=personesforresolve', // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    resolvepersones = result.persones;
                    document.getElementById("resolvepersonescount").innerHTML = resolvepersones.length + ((resolvepersones.length>0)?" <a href='javascript:ResolveAllPersones(0)'>определить</a>":"");
                }
            },
            true
        )
    }

    function FramesCount() {
        filmsforframegenerate = null;
        JsHttpRequest.query(
            'actions.php?action=filmsforframegenerate', // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    filmsforframegenerate = result.filmsforframegenerate;
                    document.getElementById("framescount").innerHTML = filmsforframegenerate.length + ((filmsforframegenerate.length>0)?" <a href='javascript:GenerateAllFilmsFrames(0)'>генерировать</a>":"");
                }
            },
            true
        )
    }

    function PhotosCount() {
        photos = null;
        JsHttpRequest.query(
            'actions.php?action=photosfordownload', // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    photos = result.photos;
                    document.getElementById("photopersonescount").innerHTML = photos.length + ((photos.length>0)?" <a href='javascript:DownloadAllPhotos()'>закачать</a>":"");
                }
            },
            true
        )
    }

    function setGenre(filmid,genre) {
        mychecked = (document.getElementById("genre"+genre).checked) ? 1 : 0;
        JsHttpRequest.query(
            'actions.php?action=setfilmgenre&filmid='+filmid+'&genre='+genre+'&what='+mychecked, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
            },
            true
        )
    }

    function setImage(id,num) {
        mychecked = (document.getElementById("chkbx"+id+"_"+num).checked) ? 1 : 0;
        JsHttpRequest.query(
            'actions.php?action=setimage&id='+id+'&num='+num+'&what='+mychecked, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
            },
            true
        )
    }

    function setCountry(filmid,country) {
        mychecked = (document.getElementById("country"+country).checked) ? 1 : 0;
        JsHttpRequest.query(
            'actions.php?action=setfilmcountry&filmid='+filmid+'&country='+country+'&what='+mychecked, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
            },
            true
        )
    }

    function setRoleExt(filmid,roleid,personid,o) {
        value = escape(o.value).replace(/\x2B/g,"%2B");
        JsHttpRequest.query(
            'actions.php?action=setroleext&filmid='+filmid+'&roleid='+roleid+'&personid='+personid+'&value='+value, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
            },
            true
        )
    }

    function setRole(filmid,roleid,personid,o) {
        value = o.value;
        JsHttpRequest.query(
            'actions.php?action=setrole&filmid='+filmid+'&roleid='+roleid+'&personid='+personid+'&value='+value, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
            },
            true
        )
    }

    function DeleteFilmPerson(filmid,roleid,personid,num) {
        JsHttpRequest.query(
            'actions.php?action=deletefilmperson&filmid='+filmid+'&roleid='+roleid+'&personid='+personid, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result) document.getElementById("_filmperson"+num).style.display = "none";
            },
            true
        )
    }

    function DeleteFileRecord(fileid,num) {
        if (confirm("Удалить запись о файле?")){
            JsHttpRequest.query(
                'actions.php?action=deletefilerecord&fileid='+fileid, // backend
                {},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                    if (result) document.getElementById("_filmfile"+num).style.display = "none";
                },
                true
            )
        }
    }

    function AddFileRecord(filmid) {
        JsHttpRequest.query(
            'actions.php?action=addfilerecord&filmid='+filmid, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result) DrawFilm(filmid);
            },
            true
        )
    }

    function importPersones(filmid) {
        MyImportedGroup = document.getElementById("importedGroup").value;
        value = document.getElementById("importedPersones").value;
        JsHttpRequest.query(
            'actions.php?action=importpersones&filmid='+filmid+'&roleid='+MyImportedGroup, // backend
            {'value':value},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result) DrawFilm(filmid);
            },
            true
        )
    }


    function ListFilms(offset) {
        var myff_text = document.getElementById("ff_text").value;
        var myff_noposters = (document.getElementById("ff_noposters").checked) ? 1 : 0;
        var myff_sortby = (document.getElementById("ff_sortbyname").checked) ? 5 : 100;
        var myff_translation = document.getElementById("ff_translation").value;
        var myff_quality = document.getElementById("ff_quality").value;
        var myff_typeofmovie = document.getElementById("ff_typeofmovie").value;
        var myff_hide = (document.getElementById("ff_hide").checked) ? 1 : 0;
        
        JsHttpRequest.query(
            'actions.php?action=filmlist&order='+myff_sortby+'&offset='+offset+'&count=100&dir=DESC', // backend
            {namefilter: myff_text, noposters: myff_noposters, translation: myff_translation, quality: myff_quality, typeofmovie: myff_typeofmovie, hide: myff_hide},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    RESULT_ON_PAGE = 100;
                    resultcount = result.count ? result.count : 0;
                    var pages = new StringBuilder();
                    currentpage = Math.ceil((offset+1)/RESULT_ON_PAGE);
                    if (resultcount>RESULT_ON_PAGE){
                        pages.append("<div style='padding:5px;'>Страницы: ");
                        prevpage = "<span >&larr;</span>";
                        nextpage = "<span >&rarr;</span>";
    
                        pagescount = Math.ceil(resultcount/RESULT_ON_PAGE);
                        pages.append(( (currentpage>1) ? "<a href='javascript:ListFilms("+(currentpage-2)*RESULT_ON_PAGE+")'>"+prevpage+"</a>" : prevpage) + "  " + ((currentpage<pagescount) ? "<a href='javascript:ListFilms("+(currentpage*RESULT_ON_PAGE)+")'>" + nextpage + "</a>" : nextpage) + "<br>");
                        sp = "";
                        for (i=1;i<=pagescount;i++){
                            if ((i==pagescount) || (i==1) || (Math.abs(i-currentpage)<=5) || ((i/10)==Math.round(i/10))){
                                p = i;
                                sp = " ";
                            }
                            else{
                                p = "<span style='font-size:8pt;'>.</span>";
                                sp = "";
                            }
                            if (i!=currentpage) pages.append (sp + "<a href='javascript:ListFilms("+(i-1)*RESULT_ON_PAGE+")'>"+p+"</a>"+sp);
                                else pages.append ("<span style='font-weight:bold; background:#E1E1E1; padding:2px;'>"+p+"</span> ");
                        }
                        pages.append("</div>");
                    }
                    document.getElementById("FilmListPages").innerHTML = pages.toString();
    
                    for (i=document.getElementById("FilmListBox").length-1;i>=0;i--){
                        document.getElementById("FilmListBox").options[i]=null
                    }
                    si = 0;
                    for(var i=0;
                        films = result.films[i];
                        i++) {
                        document.getElementById("FilmListBox").options[i] = new Option(films.ID+": "+films.Name+"", films.ID, false, false);
                    }
                }
            },
            true
        )
    }

    function ListPersones(offset) {
        mypersonfilter = escape(document.getElementById("pf_text").value);
        myonlynoozon = (document.getElementById("pf_onlynoozon").checked) ? 1 : 0;
        JsHttpRequest.query(
            'actions.php?action=personeslist&offset='+offset+'&count=100&personfilter='+mypersonfilter+'&onlynoozon='+myonlynoozon, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    RESULT_ON_PAGE = 100;
                    resultcount = result.count ? result.count : 0;
                    var pages = new StringBuilder();
                    currentpage = Math.ceil((offset+1)/RESULT_ON_PAGE);
                    if (resultcount>RESULT_ON_PAGE){
                        pages.append("<div style='padding:5px;'>Страницы: ");
                        prevpage = "<span >&larr; предыдущая</span>";
                        nextpage = "<span >следующая &rarr;</span>";
    
                        pagescount = Math.ceil(resultcount/RESULT_ON_PAGE);
                        pages.append(( (currentpage>1) ? "<a href='javascript:ListPersones("+(currentpage-2)*RESULT_ON_PAGE+")'>"+prevpage+"</a>" : prevpage) + "  " + ((currentpage<pagescount) ? "<a href='javascript:ListPersones("+(currentpage*RESULT_ON_PAGE)+")'>" + nextpage + "</a>" : nextpage) + "<br>");
                        sp = "";
                        for (i=1;i<=pagescount;i++){
                            if ((i==pagescount) || (i==1) || (Math.abs(i-currentpage)<=5) || ((i/10)==Math.round(i/10))){
                                p = i;
                                sp = " ";
                            }
                            else{
                                p = "<span style='font-size:8pt;'>.</span>";
                                sp = "";
                            }
                            if (i!=currentpage) pages.append (sp + "<a href='javascript:ListPersones("+(i-1)*RESULT_ON_PAGE+")'>"+p+"</a>"+sp);
                                else pages.append ("<span style='font-weight:bold; background:#E1E1E1; padding:2px;'>"+p+"</span> ");
                        }
                        pages.append("</div>");
                    }
                    outstr = pages.toString();
    
                    outstr += "<table class='incoming' width='100%'>";
                    outstr += "<tr><th>ID</th><th>Рус.</th><th>Англ.</th><th>URL</th><th>Фото</th><th>Обновление</th></tr>";
                    for(var i=0;
                        person = result.persones[i];
                        i++) {
                        outstr += "<tr><td>"+person.ID+"</td><td><a href='javascript:DrawPerson("+person.ID+")'>"+person.RusName+"</a></td><td><a href='javascript:DrawPerson("+person.ID+")'>"+person.OriginalName+"</a></td><td>"+person.OzonUrl+"</td><td>"+person.Images+"</td><td>"+person.LastUpdate+"</td></tr>";
                        outstr += "<tr id='_person"+person.ID+"' style='display:none;'><td colspan='6' id='_persondetail"+person.ID+"'></td></tr>";
                    }
                    outstr += "</table>";
                    document.getElementById("PersonesList").innerHTML = outstr;
                }
            },
            true
        )
    }

    function ListUsers(offset) {
        myuserfilter = escape(document.getElementById("uf_text").value);
        myipfilter = escape(document.getElementById("ipf_text").value);
        JsHttpRequest.query(
            'actions.php?action=userslist&offset='+offset+'&count=100&userfilter='+myuserfilter+'&ipfilter='+myipfilter, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    RESULT_ON_PAGE = 100;
                    resultcount = result.count ? result.count : 0;
                    var pages = new StringBuilder();
                    currentpage = Math.ceil((offset+1)/RESULT_ON_PAGE);
                    if (resultcount>RESULT_ON_PAGE){
                        pages.append("<div style='padding:5px;'>Страницы: ");
                        prevpage = "<span >&larr; предыдущая</span>";
                        nextpage = "<span >следующая &rarr;</span>";
    
                        pagescount = Math.ceil(resultcount/RESULT_ON_PAGE);
                        pages.append(( (currentpage>1) ? "<a href='javascript:ListUsers("+(currentpage-2)*RESULT_ON_PAGE+")'>"+prevpage+"</a>" : prevpage) + "  " + ((currentpage<pagescount) ? "<a href='javascript:ListUsers("+(currentpage*RESULT_ON_PAGE)+")'>" + nextpage + "</a>" : nextpage) + "<br>");
                        sp = "";
                        for (i=1;i<=pagescount;i++){
                            if ((i==pagescount) || (i==1) || (Math.abs(i-currentpage)<=5) || ((i/10)==Math.round(i/10))){
                                p = i;
                                sp = " ";
                            }
                            else{
                                p = "<span style='font-size:8pt;'>.</span>";
                                sp = "";
                            }
                            if (i!=currentpage) pages.append (sp + "<a href='javascript:ListUsers("+(i-1)*RESULT_ON_PAGE+")'>"+p+"</a>"+sp);
                                else pages.append ("<span style='font-weight:bold; background:#E1E1E1; padding:2px;'>"+p+"</span> ");
                        }
                        pages.append("</div>");
                    }
                    outstr = pages.toString();
    
                    outstr += "<table class='incoming' width='100%'>";
                    outstr += "<tr><th>ID</th><th>Логин</th><th>Email</th><th>IP</th><th>Группа</th><th>Режим доступа</th><th>View-активность</th><th>Play-активность</th><th>Дата регистрации</th><th>Включен</th></tr>";
                    for(var i=0;
                        user = result.users[i];
                        i++) {
                        outstr += "<tr><td>"+user.ID+"</td><td><a href='javascript:getUserDetail("+user.ID+")'>"+user.Login+"</a></td><td>"+user.Email+"</td><td>"+user.IP+"</td><td>"+user.UserGroup+"</td><td>"+user.Mode+"</td><td>"+user.ViewActivity+"</td><td>"+user.PlayActivity+"</td><td>"+user.RegisterDate+"</td><td>"+user.Enabled+"</td></tr>";
                        outstr += "<tr id='_user"+user.ID+"' style='display:none;'><td colspan='10' id='_userdetail"+user.ID+"'></td></tr>";
                    }
                    outstr += "</table>";
                    document.getElementById("UsersList").innerHTML = outstr;
                }
            },
            true
        )
    }


    function getUserDetail(userid) {
        if (document.getElementById("_user"+userid).style.display==""){
            document.getElementById("_user"+userid).style.display = "none";
        }
        else{
            JsHttpRequest.query(
                'actions.php?action=getuserdetail&user='+userid, // backend
                {},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                    if (result){
                        user = result;
                        outstr = "";
    
                        outstr += "<table class='borderno' width='100%'>"
                        outstr += "<tr><td>Логин</td><td><input onChange='UpdateUserField("+userid+",\"Login\",this)' type='text' id='_Login"+userid+"' size='100'></td></tr>"
                        outstr += "<tr><td>Email</td><td><input onChange='UpdateUserField("+userid+",\"Email\",this)' type='text' id='_Email"+userid+"' size='100'></td></tr>"
                        outstr += "<tr><td>IP</td><td><input onChange='UpdateUserField("+userid+",\"IP\",this)' type='text' id='_IP"+userid+"' size='100'></td></tr>"
                        outstr += "<tr><td>Режим доступа</td><td><input onChange='UpdateUserField("+userid+",\"Mode\",this)' type='text' id='_Mode"+userid+"' size='10'></td></tr>"
                        outstr += "<tr><td>Группа<br><sup>1 - пользователь<br>2 - модератор<br>3 - администратор</sup></td><td><input onChange='UpdateUserField("+userid+",\"UserGroup\",this)' type='text' id='_UserGroup"+userid+"' size='10'></td></tr>"
                        outstr += "<tr><td>Включен</td><td><input onChange='UpdateUserField("+userid+",\"Enabled\",this)' type='text' id='_Enabled"+userid+"' size='10'></td></tr>"
                        outstr += "<tr><td>Установить пароль</td><td>1 раз: <input type='password' id='newpass1_"+userid+"' size='16'> 2 раз: <input type='password' id='newpass2_"+userid+"' size='16'> <button onClick='SetPassword("+userid+")'>Установить</button><div id='passmess_"+userid+"'></div></td></tr>"
                        outstr += "<tr><td colspan=2 align='right'><button onClick='DeleteUser("+userid+")'>Удалить пользователя</button></td></tr>"
    
                        outstr += "</table>"
                        document.getElementById("_user"+userid).style.display = "";
                        document.getElementById("_userdetail"+userid).innerHTML = outstr;
                        document.getElementById("_Login"+userid).value = user.Login;
                        document.getElementById("_Email"+userid).value = user.Email;
                        document.getElementById("_IP"+userid).value = user.IP;
                        document.getElementById("_Mode"+userid).value = user.Mode;
                        document.getElementById("_UserGroup"+userid).value = user.UserGroup;
                        document.getElementById("_Enabled"+userid).value = user.Enabled;
                    }
                },
                true
            )
        }
    }
    function pad(value, length) {
        value = String(value);
        length = parseInt(length) || 2;
        while (value.length < length)
            value = "0" + value;
        return value;
    }    
    function insertCurrentDateTimeInCreateDate()
    {
        var date = new Date();
        var d = date.getDate(),
            m = date.getMonth(),
            y = date.getFullYear(),
            H = date.getHours(),
            M = date.getMinutes(),
            s = date.getSeconds();
        var mm = pad(m + 1);
        var dd = pad(d);
        var HH = pad(H);
        var MM = pad(M);
        var ss = pad(s);
        var dateStr = y + '-' + mm + '-' + dd + ' ';
        dateStr += HH + ':' + MM + ':' + ss;
        document.getElementById('FilmCreateDate').value = dateStr;
        document.getElementById('FilmCreateDate').onchange();
    }
    
    function DrawFilm(filmid) {
        MyPages.select(2);
        JsHttpRequest.query(
            'actions.php?action=getfilmext&film='+filmid, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    film = result;
                    var outstr = new StringBuilder();
    
                    moderblock = "";
    
                    if (film.canshowhide==1){
                        moderblock = "<br><a href='javascript:" + ((film.Hide==0) ? "ModerHideFilm(" : "ModerShowFilm(" ) + filmid + ")'>" + ((film.Hide==0) ? "Закрыть доступ" : "Открыть доступ" ) + "</a>";
                    }
    
                    allgenres = "";
                    for (var i=0; genre = film.allgenres[i];i++){
                        allgenres += "<div style='float:left;font-size:8pt;width:15em;'><input id='genre"+genre.ID+"' onChange='setGenre("+filmid+","+genre.ID+")' type='checkbox' " + ((eval(genre.checked)==1)?"checked":"") + " style='border:0;'><label for='genre"+genre.ID+"' " + ((eval(genre.checked)==1)?"style='background:#EEEEEE'":"") + ">" + genre.imdbGenre + " (" + genre.Name + ")</label></div>";
                    }
    
                    countries1 = "";
                    countries2 = "";
                    for (var i=0; country = film.allcountries[i];i++){
                        c = "<div style='float:left;font-size:8pt;width:15em;'><input id='country"+country.ID+"' onChange='setCountry("+filmid+","+country.ID+")' type='checkbox' " + ((eval(country.checked)==1)?"checked":"") + " style='border:0;'><label for='country"+country.ID+"' " + ((eval(country.checked)==1)?"style='background:#EEEEEE'":"") + ">" + country.imdbCountry + " (" + country.Name + ")</label></div>";
                        if (eval(country.Count)>0) countries1 += c;
                            else countries2 += c;
                    }
    
                    persones = "<table class='incoming'>";
                    persones += "<tr><th>ID</th><th>Имя (рус. /англ./)</th><th>Группа/должность</th><th>Роль</th><th>&nbsp;</th></tr>";
                    for (var i=0; person = film.persones[i];i++){
                        persones += "<tr id='_filmperson"+i +"'>";
                        persones += "<td>" + person.ID + "</td>";
                        persones += "<td>" + person.RusName + " /" + person.OriginalName + "/" + "</td>";
                        allroles = "<select onChange='setRole("+filmid+","+person.RoleID+","+person.ID+",this)'>";
                        for (var j=0; role = film.roles[j];j++){
                            allroles += "<option value='"+role.ID+"' " + ((person.RoleID==role.ID) ? "selected style='background:#EEEEEE'" : "") + ">" + role.Role + "</option>";
                        }
                        allroles += "</select>";
    
                        persones += "<td>" + allroles + "</td>";
                        persones += "<td><input onChange='setRoleExt("+filmid+","+person.RoleID+","+person.ID+",this)' size='50' value='" + person.RoleExt + "'></td>";
                        persones += "<td><a href='javascript:DeleteFilmPerson("+filmid+","+person.RoleID+","+person.ID+","+i+")'>удалить</a></td>";
                        persones += "</tr>";
                    }
                    persones += "</table>";
    
                    persones += "<br><div>Импортировать списком<br>";
                    persones += "Группа/должность: <select id='importedGroup'>";
                    for (var j=0; role = film.roles[j];j++){
                        persones += "<option value='"+role.ID+"' " + ((role.Role=='актер')?"selected":"") + ">" + role.Role + "</option>";
                    }
                    persones += "</select><br>";
                    persones += "<textarea rows='7' style='width:100%' id='importedPersones'></textarea><br>";
                    persones += "<button onClick='importPersones("+filmid+")'>Импортировать</button>";
                    persones += "</div>";
    
                    files = "<table class='incoming' width='100%'>";
                    files += "<tr><th>ID</th><th>Размер</th><th>Наименование (видимое)</th><th>Путь</th><th>Ссылка ed2k</th><th>Ссылка DC++</th><th>&nbsp;</th></tr>";
                    for (var i=0; file = film.files[i];i++){
                        files += "<tr id='_filmfile"+i +"'>";
                        files += "<td>" + file.ID + "</td>";
                        files += "<td width='8%'><input onChange='UpdateFileField("+file.ID+",\"Size\",this)' style='width:100%' value='" + file.Size + "'></td>";
                        files += "<td width='13%'><input onChange='UpdateFileField("+file.ID+",\"Name\",this)' style='width:100%' value='" + MyXMLEncode(file.Name) + "'></td>";
                        files += "<td width='27%'><input onChange='UpdateFileField("+file.ID+",\"Path\",this)' style='width:100%' value='" + MyXMLEncode(file.Path) + "'></td>";
                        files += "<td width='26%'><input onChange='UpdateFileField("+file.ID+",\"ed2kLink\",this)' style='width:100%' value='" + MyXMLEncode(file.ed2kLink) + "'></td>";
                        files += "<td width='26%'><input onChange='UpdateFileField("+file.ID+",\"dcppLink\",this)' style='width:100%' value='" + MyXMLEncode(file.dcppLink) + "'></td>";
                        files += "<td><a title='Удаляет только запись о файле (не физически)' href='javascript:DeleteFileRecord("+file.ID+","+i+")'>удалить</a></td>";
                        files += "</tr>";
                    }
                    files += "</table>";
                    files += "<br><a href='javascript:AddFileRecord("+filmid+")'>Добавить запись</a><br>";
    
                    posters = "";
                    for (var i=0; poster = film.Poster[i];i++){
                        posters += "<img src='"+poster+"'>";
                    }
                    posters += "<br><table width='100%'><tr><td width='33%'><textarea rows='3' onChange='UpdateFilmField("+filmid+",\"SmallPoster\",this)' style='width:100%'>"+MyXMLEncode(film.SmallPoster.join("\r\n"))+"</textarea></td><td width='33%'><textarea id='FilmPosters' rows='3' cols='35' onChange='UpdateFilmField("+filmid+",\"Poster\",this)' style='width:100%'>"+MyXMLEncode(film.Poster.join("\r\n"))+"</textarea></td><td width='33%'><textarea rows='3' cols='35' id='FilmBigPosters' onChange='UpdateFilmField("+filmid+",\"BigPosters\",this)' style='width:100%'></textarea></td></tr></table><br><a href='javascript:AdvFilmImgSearch("+filmid+")'>Поиск...</a> | <a href='javascript:DownloadPoster("+filmid+",1)'>Скачать/уменьшить</a>";
                    
                    generateMetainfoMenuItem = GMI_ENABLE? " | <a href='javascript:GenerateMetainfo("+filmid+");' title='Генерировать метаинформацию'>ГМИ</a>" : "";
                        
                    outstr.append ("<div style='text-align:right'><a href='javascript:if (confirm(\"Подтвердите\")) " + ((film.Hide==0) ? "ModerHideFilm(" : "ModerShowFilm(" ) + filmid + ")'>" + ((film.Hide==0) ? "Закрыть доступ (скрыть)" : "Открыть доступ" ) + "</a> | <a title=' ' href='javascript:if (confirm(\"Подтвердите\")) DeleteFilm("+filmid+",1)'>Удалить фильм и файлы</a> | <a title=' ' href='javascript:if (confirm(\"Подтвердите\")) DeleteFilm("+filmid+",0)'>Удалить фильм только из БД</a> | <a href='javascript:if (confirm(\"Подтвердите\")) UpdateFiles("+filmid+")'>Обновить инф. о файлах</a> | <a href='javascript:if (confirm(\"Подтвердите\")) GenerateFilmFrames("+filmid+",1)'>Генер. кадры</a>" + generateMetainfoMenuItem + " | <a href='javascript:DrawFilm("+filmid+");'>Обновить</a></div>");
                    outstr.append (posters);
                    outstr.append ("<table border=0 width='100%'>");
                    var searchBlockRus = '';
                    var searchBlockOrig = '';
                    for (var i=0; i<EXT_SEARCH_ENGINES.length; i++) {
                        var searchTemplate = EXT_SEARCH_ENGINES[i];
                        searchBlockRus += searchTemplate.replace(/%s/g, film.Name);
                        searchBlockOrig += searchTemplate.replace(/%s/g, film.OriginalName1252);
                    }
                    outstr.append ("<tr><td width='10%'>Русское название:</td><td><input id='FilmName' onChange='UpdateFilmField("+filmid+",\"Name\",this)' type='text' size='70' value=\"" + film.Name.replace(/\"/gi, "&quot;") + "\"> " + searchBlockRus + "</td></tr>");
                    outstr.append ("<tr><td nowrap>Оригинальное название:</td><td><input id='FilmOriginalName' onChange='UpdateFilmField("+filmid+",\"OriginalName\",this)' type='text' size='70' value=\"" + film.OriginalName1252.replace(/\"/gi, "&quot;") + "\"> " + searchBlockOrig + "</td></tr>");
                    outstr.append ("<tr><td>Год:</td><td><input id='FilmYear' onChange='UpdateFilmField("+filmid+",\"Year\",this)' type='text' value='" + film.Year + "'></td></tr>");
                    outstr.append ("<tr><td colspan='2'>Жанр:<br>" + allgenres + "</td></tr>");
                    outstr.append ("<tr><td colspan='2'>Страна:<br>" + countries1 + "</td></tr>");
                    outstr.append ("<tr><td colspan='2'><a href='javascript:Show(\"morecountries\")'>Еще страны &gt;&gt;</a><div id='morecountries' style='display:none'>"+countries2+"</div></td></tr>");
                    outstr.append ("</table>");
                    outstr.append ("<table>");
                    combobox = RenderComboBox("FilmTypeOfMovie2",filmid,'TypeOfMovie','UpdateFilmField',film.TypeOfMovie,typeofmovie_options,70);
                    outstr.append ("<tr><td width='10%'>Тип фильма:</td><td>"+combobox+"</td></tr>");
                
                    outstr.append ("<tr><td>Длительность:</td><td><input id='FilmRunTime' onChange='UpdateFilmField("+filmid+",\"RunTime\",this)' type='text' size='70'  value='" + film.RunTime + "'> сек.</td></tr>");
                    outstr.append ("<tr><td>URL трейлера:</td><td><input id='FilmTrailer' onChange='UpdateFilmField("+filmid+",\"Trailer\",this)' type='text' size='70'  value='" + film.Trailer + "'></td></tr>");
                    outstr.append ("<tr><td>URL саундтрека:</td><td><input id='FilmSoundTrack' onChange='UpdateFilmField("+filmid+",\"SoundTrack\",this)' type='text' size='70'  value='" + film.SoundTrack + "'></td></tr>");
                    outstr.append ("<tr><td>Название группы (произв.):</td><td><input id='FilmGroup' onChange='UpdateFilmField("+filmid+",\"Group\",this)' type='text' size='70'  value='" + film.Group + "'></td></tr>");
                    outstr.append ("<tr><td>Предоставлен:</td><td><input id='FilmPresent' onChange='UpdateFilmField("+filmid+",\"Present\",this)' type='text' size='70'  value='" + film.Present + "'></td></tr>");
                    outstr.append ("<tr><td>Ссылки (html-оформление):</td><td><textarea id='FilmLinks' onChange='UpdateFilmField("+filmid+",\"Links\",this)' type='text' cols='70' rows='4'>" + (film.Links ? film.Links : "") + "</textarea></td></tr>");
                    outstr.append ("<tr><td>IMDBID:</td><td><input id='FilmImdbId' onChange='UpdateFilmField("+filmid+",\"imdbID\",this)' type='text' size='70'  value='" + film.imdbID + "'></td></tr>");
                    outstr.append ("<tr><td>Рейтинг IMDb.com:</td><td><input id='FilmImdbRating' onChange='UpdateFilmField("+filmid+",\"ImdbRating\",this)' type='text' size='70'  value='" + film.ImdbRating + "'></td></tr>");
                    outstr.append ("<tr><td>Рейтинг MPAA:</td><td><input id='FilmMPAA' onChange='UpdateFilmField("+filmid+",\"MPAA\",this)' type='text' size='70'  value='" + film.MPAA + "'></td></tr>");
    
                    combobox = RenderComboBox("QualityBox",filmid,'Quality','UpdateFilmField',film.Quality,quality_options,70);
                    outstr.append ("<tr><td>Качество видео:</td><td>"+combobox+"</td></tr>");
    
                    combobox = RenderComboBox("TranslationBox",filmid,'Translation','UpdateFilmField',film.Translation,translation_options,70);
                    outstr.append ("<tr><td>Озвучивание:</td><td>"+combobox+"</td></tr>");
    
                    outstr.append ("<tr><td>Видео разрешение:</td><td><input id='FilmResolution' onChange='UpdateFilmField("+filmid+",\"Resolution\",this)' type='text' size='70'  value='" + film.Resolution + "'>" + "</td></tr>");
                    outstr.append ("<tr><td nowrap>Информация о видео:</td><td><input id='FilmVideoInfo' onChange='UpdateFilmField("+filmid+",\"VideoInfo\",this)' type='text' size='70'  value='" + film.VideoInfo + "'></td></tr>");
                    outstr.append ("<tr><td>Аудио:</td><td><input id='FilmAudioInfo' onChange='UpdateFilmField("+filmid+",\"AudioInfo\",this)' type='text' size='70'  value='" + film.AudioInfo + "'>" + "</td></tr>");
                    outstr.append ("<tr><td>Добавлен:</td><td><input id='FilmCreateDate' onChange='UpdateFilmField("+filmid+",\"CreateDate\",this)' type='text' size='35'  value='" + film.CreateDate + "'> <a href='javascript:insertCurrentDateTimeInCreateDate();'>Вставить текущее время</a></td></tr>");
                    outstr.append ("</table>");
                    outstr.append ("<p style='font-weight:bold;margin-bottom:1px'>Файлы<br><img src='images/hr.gif' width='327' height='1'></p><br>");
                    var filesAsDir = parseInt(film.AsDir);
                    outstr.append ("Файлы добавлены как папка: <input onChange='this.value=this.checked? 1: 0;UpdateFilmField("+filmid+",\"AsDir\", this)' type='checkbox' value=1 " + (filesAsDir? 'checked' : '') + "><br>");
                    outstr.append ("<div>" + files + "</div>");
                    outstr.append ("<p style='font-weight:bold;margin-bottom:1px'>От издателя (html-оформление)</p><br><img src='images/hr.gif' width='327' height='1'><br>");
                    outstr.append ("<textarea id='FilmDescription' onChange='UpdateFilmField("+filmid+",\"Description\",this)' style='width:100%' cols1='150' rows='20'></textarea>");
                    outstr.append ("<p style='font-weight:bold;margin-bottom:1px'>Творческий коллектив</p><img src='images/hr.gif' width='327' height='1'><br>");
                    outstr.append ("<div>" + persones + "</div>");
                    current_film = filmid;
                    document.getElementById("FilmBox").innerHTML = outstr.toString();
    
                    document.getElementById("FilmBigPosters").value = film.BigPosters.join("\r\n");
                    document.getElementById("FilmOriginalName").value = film.OriginalName1252;
                    document.getElementById("FilmName").value = film.Name;
                    document.getElementById("FilmDescription").value = film.Description;
                }
            },
            true
        )
    }

    function DeleteNotLinkedPersones(){
        if (confirm('Действительно удалить все не связанные с объектами персоналии? Эту операцию нельзя будет отменить.')){
            JsHttpRequest.query(
                'actions.php?action=deletenotlinkedpersones', // backend
                {},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                    if (result){
                        alert("Удалено " + result.count + " персоналий")
                    }
                },
                true
            )
        }
    }
    
    function DrawPersonesBookmarks() {
        outstr = "";
        if (postponedpersones.length){
                outstr += "<table class='incoming' width='100%'>";
                outstr += "<tr><td colspan='2'><b>Закладки (на сеанс)</b></td></tr>";
                for (i=0;i<postponedpersones.length;i++){
                        outstr += "<tr><td><a href='javascript:PersonAbsorption("+postponedpersones[i].ID+")'><img border='0' height='19'width='19' src='images/action_absorption.gif'></a></td><td><a href='javascript:DrawPerson("+postponedpersones[i].ID+")'>" + postponedpersones[i].Name + "</a></td><td width='1%' align='right' valign='top'><a title='Убрать'  href='javascript:RemoveBookmark(" + postponedpersones[i].ID + ")'><img border='0' height='16' width='16' src='images/delete_16.gif' id='_rf" + postponedpersones[i].ID + "'></a></td></tr>";
                }
                outstr += "</table><br>";
        }
        document.getElementById("PersonesBookmarks").innerHTML = outstr;
    }

    function RemoveBookmark(person) {
        for (i=0; i<postponedpersones.length;i++){
                if (postponedpersones[i].ID==person){
                        postponedpersones.splice(i, 1);
                        DrawPersonesBookmarks();
                }
        }
    }

    function IsPersonPostponed(person) {
        found = 0;
        for (i=0; i<postponedpersones.length;i++){
                if (postponedpersones[i].ID==person) found = 1;
        }
        return found;
    }

    function AddToBookmark(person,name) {
        if (!IsPersonPostponed(person)){
                postponedpersones.unshift({'ID':person,'Name':name});
        }
        DrawPersonesBookmarks();
    }
    
    function ListPersones(offset) {
        mypersonfilter = escape(document.getElementById("pf_text").value);
        myonlynoozon = (document.getElementById("pf_onlynoozon").checked) ? 1 : 0;
        mydoubles = (document.getElementById("pf_doubles").checked) ? 1 : 0;
        JsHttpRequest.query(
            'actions.php?action=personeslist&offset='+offset+'&count=100&personfilter='+mypersonfilter+'&onlynoozon='+myonlynoozon+'&doubles='+mydoubles, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    RESULT_ON_PAGE = 100;
                    resultcount = result.count ? result.count : 0;
                    var pages = new StringBuilder();
                    currentpage = Math.ceil((offset+1)/RESULT_ON_PAGE);
                    if (resultcount>RESULT_ON_PAGE){
                        pages.append("<div style='padding:5px;'>Страницы: ");
                        prevpage = "<span >&larr; предыдущая</span>";
                        nextpage = "<span >следующая &rarr;</span>";

                        pagescount = Math.ceil(resultcount/RESULT_ON_PAGE);
                        pages.append(( (currentpage>1) ? "<a href='javascript:ListPersones("+(currentpage-2)*RESULT_ON_PAGE+")'>"+prevpage+"</a>" : prevpage) + "  " + ((currentpage<pagescount) ? "<a href='javascript:ListPersones("+(currentpage*RESULT_ON_PAGE)+")'>" + nextpage + "</a>" : nextpage) + "<br>");
                        sp = "";
                        for (i=1;i<=pagescount;i++){
                            if ((i==pagescount) || (i==1) || (Math.abs(i-currentpage)<=5) || ((i/10)==Math.round(i/10))){
                                p = i;
                                sp = " ";
                            }
                            else{
                                p = "<span style='font-size:8pt;'>.</span>";
                                sp = "";
                            }
                            if (i!=currentpage) pages.append (sp + "<a href='javascript:ListPersones("+(i-1)*RESULT_ON_PAGE+")'>"+p+"</a>"+sp);
                                else pages.append ("<span style='font-weight:bold; background:#E1E1E1; padding:2px;'>"+p+"</span> ");
                        }
                        pages.append("</div>");
                    }
                    document.getElementById("PersonesListPages").innerHTML = pages.toString();

                    for (i=document.getElementById("PersonesListBox").length-1;i>=0;i--){
                            document.getElementById("PersonesListBox").options[i]=null
                    }
                    si = 0;
                    for(var i=0;
                        person = result.persones[i];
                        i++) {
                        document.getElementById("PersonesListBox").options[i] = new Option(person.ID+": "+person.RusName+" /"+person.OriginalName+"/", person.ID, false, false);
                    }
                }
            },
            true
        )
    }

    function PersonAbsorption(from_person) {
        if (!current_person){
                alert("Сначала выберете текущую персоналию из списка слева");
                return;
        }
        if (current_person && confirm('Объединить персоналии (поглощение текущим)?')){
            JsHttpRequest.query(
                'actions.php?action=personabsorption&person1='+current_person+'&person2='+from_person, // backend
                {},
                function(result, errors) {
                    if (errors.length) sys_message(errors);
                    if (result){
                        DrawPerson(current_person);
                    }
                },
                true
            )
        }
    }


    function DrawPerson(personid) {
        JsHttpRequest.query(
            'actions.php?action=getpersondetail&person='+personid, // backend
            {},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    person = result;
                    outstr = "";
                    outstr += "<table class='borderno' width='100%'>"
                    outstr += "<tr><td>ID:</td><td>"+personid+"</td></tr>"
                    outstr += "<tr><td>Имя (рус.):</td><td><input onChange='UpdatePersonField("+personid+",\"RusName\",this)'type='text' id='_RusName"+personid+"' size='100'></td></tr>"
                    outstr += "<tr><td>Имя (англ.):</td><td><input onChange='UpdatePersonField("+personid+",\"OriginalName\",this)' type='text' id='_OriginalName"+personid+"' size='100'></td></tr>"
                    outstr += "<tr><td>Информация:</td><td><textarea onChange='UpdatePersonField("+personid+",\"Description\",this)' id='_Description"+personid+"' rows='10' style='width:100%'></textarea></td></tr>"
                    outstr += "<tr><td>Фотографии:</td><td><textarea onChange='UpdatePersonField("+personid+",\"Photos\",this)' id='_Photos"+personid+"' rows='5' style='width:100%'></textarea></td></tr>"
                    outstr += "<tr><td>Уменьшенные фотографии:</td><td><textarea onChange='UpdatePersonField("+personid+",\"Images\",this)' id='_Images"+personid+"' rows='5' style='width:100%'></textarea></td></tr>"
                    outstr += "<tr><td>Ozon URL:</td><td><input onChange='UpdatePersonField("+personid+",\"OzonUrl\",this)' type='text' id='_OzonUrl' size='100'> <a href='javascript:SearchPersonOnOzon("+personid+")'>Найти на ozon.ru</a><div id='ozonpersonsearch' style='padding:8px; z-index:1;position:relative;display:none;background-color:#F0F0F0;border:1px solid silver;'></div></td></tr>"
                    outstr += "<tr><td width='1%' nowrap>Обновление:</td><td><input onChange='UpdatePersonField("+personid+",\"LastUpdate\",this)' type='text' id='_LastUpdate"+personid+"' size='100'></td></tr>"
                    outstr += "</table>"
                    if (result.films.length){
                            outstr += "<b>Фильмы:</b><br><blockquote>";
                            for (var i=0; i<result.films.length; i++) {
                                    myfilm = result.films[i];
                                    outstr += myfilm.Name+" ("+myfilm.Year+") ("+myfilm.Roles.join(", ")+")<br>";
                            }
                            outstr += "</blockquote>";
                    }

                    if (result.albums.length){
                            outstr += "<b>Альбомы:</b><br><blockquote>";
                            for (var i=0; i<result.albums.length; i++) {
                                    myalbum = result.albums[i];
                                    outstr += myalbum.Name+" ("+myalbum.Year+")<br>";
                            }
                            outstr += "</blockquote>";
                    }

                    if (result.masters.length){
                            outstr += "<b>Участник групп(ы):</b><br><blockquote>";
                            for (var i=0; i<result.masters.length; i++) {
                                    myperson = result.masters[i];
                                    outstr += "<a href='javascript:DrawPerson("+myperson.ID+")'>" + myperson.ID + ": " + myperson.RusName+" /"+myperson.OriginalName+"/</a><br>";
                            }
                            outstr += "</blockquote>";
                    }

                    if (result.slaves.length){
                            outstr += "<b>В группу входят:</b><br>";
                            outstr += "<table class='incoming'>";
                            outstr += "<tr><th>ID</th><th>Имя (рус. /англ./)</th><th>Комментарий</th><th>&nbsp;</th></tr>";
                            for (var i=0; i<result.slaves.length; i++) {
                                    myperson = result.slaves[i];
                                    outstr += "<tr id='_groupperson"+i +"'>";
                                    outstr += "<td>" + myperson.ID + "</td>";
                                    outstr += "<td><a href='javascript:DrawPerson("+myperson.ID+")'>" + myperson.RusName+" /"+myperson.OriginalName+"/</a></td>";
                                    outstr += "<td><input onChange='setGroupMemberComment("+personid+","+myperson.ID+",this)'size='50' value='" + MyXMLEncode(myperson.Comment) + "'></td>";
                                    outstr += "<td><a href='javascript:DeleteGroupMember("+personid+","+myperson.ID+","+i+")'>удалить</a></td>";
                                    outstr += "</tr>";
                            }
                            outstr += "</table>";
                    }
                    if (module=='music'){
                        outstr += "<br><div>Импортировать списком (по строчкам, через \",\" или \";\", формат имени \"ИмяАнгл/ИмяРус [/ИмяРус/ИмяАнгл/|(ИмяРус/ИмяАнгл)]\")<br>";
                        outstr += "<textarea rows='7' style='width:100%' id='importedGroupMembers'></textarea><br>";
                        outstr += "<button onClick='importGroupMembers("+personid+")'>Импортировать</button>";
                        outstr += "</div>";
                    }
                    outstr2 = "";
                    if (eval(result.pcount)){
                            outstr2 += "<table class='incoming' width='100%'>";
                            outstr2 += "<tr><td colspan='2'><b>Дубликаты/Похожие</b></td></tr>";
                            for (var i=0; i<result.persones_exact.length; i++) {
                                    myperson = result.persones_exact[i];
                                    outstr2 += "<tr><td><a href='javascript:PersonAbsorption("+myperson.ID+")'><img border='0' height='19' width='19' src='images/action_absorption.gif'></a></td><td><a href='javascript:DrawPerson("+myperson.ID+")'>" + myperson.ID + ": " + myperson.RusName+" /"+myperson.OriginalName+"/</a></td></tr>";
                            }
                            for (var i=0; i<result.persones_part.length; i++) {
                                    myperson = result.persones_part[i];
                                    outstr2 += "<tr><td><a href='javascript:PersonAbsorption("+myperson.ID+")'><img border='0' height='19' width='19' src='images/action_absorption.gif'></a></td><td><a href='javascript:DrawPerson("+myperson.ID+")'>" + myperson.ID + ": " + myperson.RusName+" /"+myperson.OriginalName+"/</a></td></tr>";
                            }
                            for (var i=0; i<result.persones_approx.length; i++) {
                                    myperson = result.persones_approx[i];
                                    outstr2 += "<tr><td><a href='javascript:PersonAbsorption("+myperson.ID+")'><img border='0' height='19' width='19' src='images/action_absorption.gif'></a></td><td><a href='javascript:DrawPerson("+myperson.ID+")'>" + myperson.ID + ": " + myperson.RusName+" /"+myperson.OriginalName+"/</a></td></tr>";
                            }
                            outstr2 += "</table><br>";
                    }
                    document.getElementById("SimilarPersones").innerHTML = outstr2;


                    document.getElementById("PersonesBox").innerHTML = outstr;
                    document.getElementById("_RusName"+personid).value = person.RusName;
                    document.getElementById("_OriginalName"+personid).value = person.OriginalName;
                    document.getElementById("_Description"+personid).value = person.Description;
                    document.getElementById("_Photos"+personid).value = person.Photos;
                    document.getElementById("_Images"+personid).value = person.Images;
                    document.getElementById("_OzonUrl").value = person.OzonUrl;
                    document.getElementById("_LastUpdate"+personid).value = person.LastUpdate;
                    current_person = personid;
                    document.getElementById("PersonesListBox").value = personid;
                }
            },
            true
        )
    }

    function showFilter()
    {
        document.getElementById('ff_wrapper').style.display = '';
        document.getElementById('ff_arrow').onclick = 'hideFilter();';
        document.getElementById('ff_arrow').innerHTML = '-';
    }
    
    function hideFilter()
    {
        document.getElementById('ff_wrapper').style.display = 'none';
        document.getElementById('ff_arrow').onclick = 'showFilter();';
        document.getElementById('ff_arrow').innerHTML = '+';
    }

    function showCheckFilesWizard()
    {
        $('check_files').show();
    }

    function checkFiles(stage)
    {
        if (!stage) {
            stage = 'reset';
            $('check_files_button').disabled = true;
            $('check_files_relocated').innerHTML = '';
            $('check_files_notfound').innerHTML = '';
            $('cf_result').show();
        }
        JsHttpRequest.query(
            'actions.php?action=check_files', // backend
            {stage: stage, relocation: $('cf_relocation').checked, hide: $('cf_hide').checked},
            function(result, errors) {
                if (errors.length) sys_message(errors);
                if (result){
                    if (result.status_text) {
                        $('check_files_status').innerHTML = result.status_text;
                    }
                    if (result.nextstage) {
                        checkFiles(result.nextstage);
                    } else {
                        $('check_files_button').disabled = false;
                        if (result.relocated.length) {
                            var str = new StringBuilder('Найдены:<br>');
                            for (var i=0; i<result.relocated.length; i++) {
                                str.append(result.relocated[i].from + ' -> ' + result.relocated[i].to + '<br>');
                            }
                            $('check_files_relocated').innerHTML = str.toString();
                        }
                        if (result.notfound.length) {
                            var str = new StringBuilder('Не найдены:<br>');
                            for (var i=0; i<result.notfound.length; i++) {
                                str.append(result.notfound[i].path + ' (' + result.notfound[i].size + ')' + '(<a href="javascript:DrawFilm(' + result.notfound[i].filmid + ')">#' + result.notfound[i].filmid + '</a>)<br>');
                                var variants = result.notfound[i].variants;
                                for (var j=0; j<variants.length; j++) {
                                    str.append('<span class="variant">&nbsp;&nbsp;&nbsp;&nbsp;' + variants[j].path + ' (' + variants[j].size + ')' + '?</span><br>');
                                }
                            }
                            $('check_files_notfound').innerHTML = str.toString();
                        }
                    }
                }
            },
            true
        )

    }

</script>
<style>
BODY{
    margin:3px;
    padding:0px;
}
BODY, INPUT, TEXTAREA, SELECT, DIV, TABLE{
    font-family: Tahoma, Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 8pt;
}
INPUT, TEXTAREA, SELECT{
    font-size: 8pt;
    border : 1px solid gray;
}
INPUT[type="checkbox"]{
    font-size: 8pt;
    border : 0;
}


A {
    color: #000066;
    text-decoration: none;
}
A:HOVER {
    text-decoration : underline;
}

A.unselected, A.selected {
    border : 1px solid gray;
    border-bottom : 0px;
    font-weight: normal;
    background : #F5F5F5;
}

A.selected {
    font-weight: bold;
    background : #FFFFFF;
}

DIV.detailpage {
    border : 1px solid gray;
    background : #F5F5F5;
}

TABLE.incoming{
    border-top : 1px solid silver;
    border-left : 1px solid silver;
    border-right : 0px;
    border-bottom : 0px;
    border-collapse: collapse;
}
TABLE.incoming TD,TH{
    border-top : 0px;
    border-left : 0px;
    border-right : 1px solid silver;
    border-bottom : 1px solid silver;
}
TABLE.incoming TH{
    background : #F0F0F0;
}

TABLE.borderno TD{
    border-right : 0px;
    border-bottom : 0px;
}

.warning, .info
{
    border-top: dotted 1px black;
    border-left: dotted 1px black;
    border-right: solid 2px black;
    border-bottom: solid 2px black;
    background: rgb(240,220,170);
    padding: 0 0.12in;
    margin: 0.5in;
}
.info
{
    color: black;
    background: rgb(240,240,240);
}

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
    z-index:99;
    display: block; 
}

#ff_arrow {
    padding:  0 3px 0 3px;
}

.monospace {
    font-family: "Courier New" Courier monospace;
}

#check_files_relocated, #check_files_notfound {
    max-height: 200px; 
    overflow: auto;
}

#check_files_relocated {
    color: green;
}

#check_files_notfound {
    color: red;
}

#check_files_notfound .variant{
    color: black;
}

</style>
</head>
<body onLoad='Init();'>
<div id="sysmessagebox" style="margin:0px;padding:5px;border:1px solid silver; background-color:#F5F5C0; width:100%; display:none;">
<div style='float:right;'><a href='javascript:Hide("sysmessagebox")'>Закрыть</a></div>
<span id="sysmessage"></span>
</div>

<div id="messagebox" style="margin:0px;padding:5px;border:1px solid silver; background-color:#F5F5C0; width:100%; display:none;">
<div style='float:right;'><a href='javascript:Hide("messagebox")'>Закрыть</a></div>
<span id="message"></span>
</div>

<div id='filmpostersearch' style='padding:8px; z-index:20;position:absolute;display:none;top:110px;left:170px;background-color:#F0F0F0;border:1px solid silver;'></div>
<div id="waiticon" style="position:absolute; z-index:20; top:0px; left:0px; display:none;"><img src="images/progbar.gif" border="0"></div>

<div class='dynamic-tab-pane-control tab-pane'>
<!-- Главное меню -->
<div id="PageSelector" align="left" width="100%"></div>
<!-- /Главное меню -->
<div class="tab-page">
<div id="QueueBox"></div>

<div id="IncomingPage" style="display:none;">
<a href="javascript:getIncomingList();">Обновить</a> |
<input type='checkbox' onclick='HIDE_NODES=this.checked; getIncomingList();' id='hide_nodes_switch' <?php echo @$config['hide_nodes'] ? "checked" : ""; ?> ><label for='hide_nodes_switch'>Скрывать узловые папки</label>

<div id="IncomingList"></div>

</div>
<div id="DBFilmsPage" style="display:none;">
<table border='1'>
<tr><td colspan='2'><a href='javascript:DownloadAll()'>Закачать все</a></td><tr>
<tr><td><a href='javascript:PostersCount()'>Постеров на закачку:</a></td><td id='posterscount'>?</td><tr>
<tr><td><?php echo (!function_exists('imagecreatefromgif')) ? "Для уменьшения постеров требуется библиотека GD <a href='javascript:Help(4,1);' style='cursor:help' title='Показать справку'><img src='images/action_help.gif' border='0'></a>" : "<a href='javascript:PostersReduceCount()'>Постеров для уменьшения:</a>";?></td><td id='postersreducecount'>?</td><tr> 
<tr><td><a href='javascript:PersonesCount()'>Персоналий на закачку:</a></td><td id='personescount'>?</td><tr>
<tr><td><a href='javascript:PhotosCount()'>Фотогр. персоналий на закачку:</a></td><td id='photopersonescount'>?</td><tr>
</table>
<br>
<table border='1'>
<tr><td><a href='javascript:ResolvePersonesCount()'>Неизвестных персоналий:</a></td><td id='resolvepersonescount'>?</td><tr>
</table>

<table border='1'>
<tr><td><a href='javascript:FramesCount()'>Кадров из фильмов:</a> <a href='javascript:Help(1,1);' style='cursor:help' title='Показать справку'><img src='images/action_help.gif' border='0'></a></td><td id='framescount'>?</td><tr>
</table>

</div>
<div id="DBEditorPage" style="display:none;" class='dynamic-tab-pane-control tab-pane'>
    <div id="DBPageSelector" align="left" width="100%"></div>
    <div class="tab-page">
        <div id="UsersPage" style='display:none'>
            <div style='border:1px solid silver; background:#F5F5F5;'>
                Фильтр:<br>
                Логин содержит <input id="uf_text" type="text"> 
                IP содержит <input id="ipf_text" type="text">
                <button onClick="ListUsers(0)" style='font-size:8pt'>Применить</button>
            </div><br>
            <div id="UsersList"></div>
        </div>
        <div id="CommentsPage" style='display:none'></div>
        <div id="RolesPage" style='display:none'></div>
    </div>
</div>

<div id="EditorPage" style="display:none;" >
    <div style='border:1px solid silver; background:#F5F5F5;'>
        Фильтр:
        <button id="ff_arrow" onclick="showFilter();">+</button>
        <span id="ff_wrapper" style='display:none'>
        <br>
        Имя содержит: <input id="ff_text" type="text">
        <input id="ff_noposters" type="checkbox" style='border:0;'><label for="ff_noposters">С незакаченными постерами</label>
        <input id="ff_sortbyname" type="checkbox" style='border:0;'><label for="ff_sortbyname">Сортировка по названию</label>
        <br>
        Качество видео: <select id="ff_quality" style="width:150px;" value='all'>
        <option value="all">Любое</option>
        <?php
            $result = mysql_query("SELECT Quality, count(*) as c FROM films GROUP BY Quality ORDER BY c DESC");
            while ($result && ($field = mysql_fetch_assoc($result))){
                echo "<option value='" . htmlspecialchars($field['Quality']) . "'>" . htmlspecialchars($field['Quality']) . " ({$field['c']})</option>";
            }
        ?>
        </select>
        Озвучивание: <select id="ff_translation" style="width:150px;" value='all'>
        <option value="all">Любое</option>
        <?php
            $result = mysql_query("SELECT Translation, count(*) as c FROM films GROUP BY Translation ORDER BY c DESC");
            while ($result && ($field = mysql_fetch_assoc($result))){
                echo "<option value='" . htmlspecialchars($field['Translation']) . "'>" . htmlspecialchars($field['Translation']) . " ({$field['c']})</option>";
            }
        ?>
        </select>
        Тип фильма: <select id="ff_typeofmovie" style="width:150px;" value='all'>
        <option value="all">Любой</option>
        <?php
            $result = mysql_query("SELECT TypeOfMovie, count(*) as c FROM films GROUP BY TypeOfMovie ORDER BY c DESC");
            while ($result && ($field = mysql_fetch_assoc($result))){
                echo "<option value='" . htmlspecialchars($field['TypeOfMovie']) . "'>" . htmlspecialchars($field['TypeOfMovie']) . " ({$field['c']})</option>";
            }
        ?>
        </select>
        <label><input id="ff_hide" type="checkbox" style='border:0;'>Только скрытые</label>
        <button onClick="ListFilms(0)" style='font-size:8pt'>Применить</button>
        </span>
    </div><br>
<table border=0>
<tr><td valign="top">
    <a href="javascript:ListFilms(0);">Обновить список</a> <br>
    <div id="FilmListPages"></div>
    <select id="FilmListBox" size="40" style="width:150px;" onChange="DrawFilm(this.value);"></select>
</td><td valign="top">
    <div id="FilmBox" style="width:100%"></div>
</td></tr>
</table>
</div>

<div id="PersonesEditorPage" style="display:none;width:100%;">
        <div style='border:1px solid silver; background:#F5F5F5;'>
                Фильтр:<br>
                Имя содержит <input id="pf_text" type="text"><br>
                <input id="pf_onlynoozon" type="checkbox" style='border:0;'><label for="pf_onlynoozon">Только без ссылок на ozon.ru</label>
                <input id="pf_doubles" type="checkbox" style='border:0;'><label for="pf_doubles">С похожими/дубликатами</label>
                <button onClick="ListPersones(0)" style='font-size:8pt'>Применить</button>
        </div><br>
        <div id="PersonesListPages" style="width:100%;"></div>
<table class='incoming' width='100%'>
<tr><td valign="top">
        <a href="javascript:ListPersones(0);">Обновить список</a> <br>
        <select id="PersonesListBox" size="40" style="width:150px;" onChange="if (CtrlUp) {AddToBookmark(this.value,this.options[this.selectedIndex].text);} else DrawPerson(this.value);"></select>
</td><td valign="top">
        <div id="PersonesBox" style="width:100%"></div>
</td><td valign="top">
        <div id="PersonesBookmarks"></div>
        <div id="SimilarPersones"></div>
</td></tr>
</table>

</div>


<div id="UtilsPage" style="display:none;" >
<ul>
<li><a href='javascript:DeleteBadPhotos()'>Очистить фотографии с внешними ссылками</a> <span style='color:red'>(используйте только, если есть фотографии персоналий, которые скрипту не удается закачать)</span></li>
<li><a href='javascript:Cleaning(1)'>Очистить каталоги *posters/, photos/ от неиспользуемых файлов</a> (<a href='javascript:Cleaning(0)'> нажмите сюда, чтобы узнать сколько</a>)</li>
<li><a href='javascript:CalcLocalRating()'>Пересчет кеша локальных рейтингов (при изменении настроек или впервые)</a></li>
<li><a href='javascript:FilmsToUpdateCount()'>Обновить рейтинги фильмов imdb</a><span id='films_to_updatecount'></span></li>
<li><a href='javascript:DeleteNotLinkedPersones()'>Удалить не связанные с объектами персоналии</a></li>
<li><a href='javascript:showCheckFilesWizard()'>Проверить файлы</a>
    <div id='check_files' style='display:none'>
        <fieldset>
            <legend>Что это?</legend>
            <p>Данная утилита позволяет проверить все ссылки на файлы.</p>
            <p>Утилита сканирует все файлы в рабочих директориях (rootdir, storages, source), а затем проверяет корректность ссылок в базе данных. Если ссылка на файл не найдена, утилита ищет новое расположение файла в рабочих директориях по названию и размеру файла. Если файл с таким именем и размером не найден, то будут отображены возможные совпадения только по размеру файлов или по названию для возможной коррекции ссылок вручную. При установке опций восстановления утилита автоматически корректирует ссылки на новое расположение и/или скрывает фильмы с оставшимися не найденными файлами.</p>
            <p>Утилиту полезно использовать после реструктуризации файлов (например, после перемещения файлов с заполненных дисков на более свободные) или для скрытия фильмов расположенных на поврежденных дисках (обратите внимание, что на "читаемость" сами файлы не проверяются, для этого нужно использовать системные утилиты проверки дисков).</p>
            <p>Рекомендуется первый проход делать с выключенными опциями восстановления.</p>
        </fieldset>
        <fieldset>
            <legend>Опции восстановления</legend>
            <label><input type="checkbox" id="cf_relocation"> Исправлять ссылки на перемещенные файлы</label><br>
            <label><input type="checkbox" id="cf_hide"> Скрывать фильмы с битыми файлами</label><br>
        </fieldset>
        <button onclick="checkFiles();" id="check_files_button">Проверить</button>
        <fieldset id="cf_result" style="display:none">
            <legend>Результат</legend>
            <div class="monospace" id="check_files_status"></div>
            <div class="monospace" id="check_files_relocated"></div>
            <div class="monospace" id="check_files_notfound"></div>
        </fieldset>
    </div>
</li>
</ul>
</div>

<div id="ServicePage" style="display:none;"  class='dynamic-tab-pane-control tab-pane'>
    <div id="ServicePageSelector" align="left" width="100%"></div>
    <div class="tab-page">
    <div id="UpdatePage" style='display:none'>
        <button onclick='GetLastVersion();'>Проверить сейчас</button><br><br>
        <div id="UpdateBox"></div><br><br>
        <table class="warning"><tr><td>
        <b>Что важно знать:</b><ol>
        <li>Пользователь базы данных должен иметь административные права на эту базу данных (на такие действия как ALTER, CREATE и т.д)</li>
        <li>Пользователь ОС от которого запускается скрипт, должен иметь права на запись в директории скрипта (и рекурсивно во всех вложенных)</li>
        <li>Последняя версия скриптовой части всегда содержит все последние изменения</li>
        <li>Последняя версия обновления базы данных содержит только текущие изменения. При установке будут запущены все SQL-скрипты со времени последнего обновления</li>
        </ol>
        </td></tr></table>

        <table class="info"><tr><td>
        <b>Что полезно знать:</b><ol>
        <li>Текущая версия скриптовой части хранится в файле-метке VERSION</li>
        <li>Текущая версия базы данных хранится в таблице version базы данных</li>
        <li>При обновлении скриптовой части скачиваются только новые или измененные файлы. Определяется это сравнением каждого файла (по хешам) и может использоваться для восстановления целостности</li>
        <li>Файлы скачиваются сжатыми, если установлен модуль zlib (сейчас zlib: <?php echo (extension_loaded('zlib')) ? "" : "не"; ?> установлен)</li>
        <li>Журнал обновления сохраняется в файле UPDATE.LOG</li>
        </ol>
        </td></tr></table>
    </div>
    </div>
</div>
</div>
</div>
</body>
</html>
