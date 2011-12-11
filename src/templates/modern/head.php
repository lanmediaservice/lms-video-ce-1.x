<!--[if lte IE 6]>
    <script src="js/pngFix/jquery.pngFix.pack.js"></script>
    <script>
        /*$j(document).ready(function(){ 
            $j(document).pngFix({blankgif:'js/pngFix/blank.gif'}); 
        });*/
    </script>
<![endif]-->
<script type="text/javascript">
    //<![CDATA[
    var API_URL = 'api.php?format=ajax';
    var TEMPLATES = {};
    var SETTINGS = {};
    var LANG = 'ru';
    less = { env: 'development' };
    JSAN.includePath = ['js/lms-jsf', 'js'];
    SETTINGS.DOWNLOAD_DEFAULTS = {
        'smb': <?php echo @$config['download']['defaults']['smb']? 1 : 0 ?>,
        'dcpp': <?php echo @$config['download']['defaults']['dcpp']? 1 : 0 ?>,
        'ed2k': <?php echo @$config['download']['defaults']['ed2k']? 1 : 0 ?>
    };
    SETTINGS.DOWNLOAD_PLAYER = {
        SELECTABLE : <?php echo (isset($config['download']['players']['selectable']) && count(array_filter($config['download']['players']['selectable'])))? 1 : 0; ?>,
        DEFAULT: "<?php echo @$config['download']['players']['default'];?>"
    };
    //]]>
</script>
<link rel="stylesheet" href="<?php echo "templates/{$config['template']}/css/reset.css" ?>">
<link rel="stylesheet" href="<?php echo "templates/{$config['template']}/css/content.css?v=2" ?>">
<link rel="stylesheet" href="<?php echo "templates/{$config['template']}/css/icons.css" ?>">
<link rel="stylesheet" href="<?php echo "templates/{$config['template']}/css/layout.css?v=2" ?>">
<link rel="stylesheet" href="<?php echo "templates/{$config['template']}/css/form.css" ?>">
<link rel="stylesheet" href="<?php echo "templates/{$config['template']}/css/menu.css" ?>">
<link rel="stylesheet" href="<?php echo "templates/{$config['template']}/css/overlay.css" ?>">

<script type="text/javascript" src="js/json2.js"></script>

<link rel="stylesheet" href="js/jquery.plugins/fancybox/jquery.fancybox-1.3.4.css" type="text/css" media="screen" />
<script type="text/javascript" src="js/jquery.plugins/fancybox/jquery.fancybox-1.3.4.js"></script>
<script type="text/javascript" src="js/jquery.plugins/fancybox/jquery.easing-1.3.pack.js"></script>
<script type="text/javascript" src="js/jquery.plugins/fancybox/jquery.mousewheel-3.0.4.pack.js"></script>

<link rel="stylesheet" href="js/jquery.plugins/tipsy/tipsy.css" type="text/css" media="screen" />
<script type="text/javascript" src="js/jquery.plugins/tipsy/jquery.tipsy.js"></script>
<script>
    $j.fn.tipsy.defaults.opacity = 1;
</script>

<script type="text/javascript" src="js/jquery.plugins/jquery.placeholder.min.js"></script>
<script>
    $j(document).ready(function(){ 
        $j('input[placeholder], textarea[placeholder]').placeholder();
    })
</script>

<script type="text/javascript" src="js/jquery.plugins/jquery.storage.js"></script>

<script language="JavaScript" src="js/modernizr-1.5.min.js"></script>

<script language="JavaScript" src="js/lms-jsf/LMS/Widgets/Generic.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Widgets/BlockGeneric.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Widgets/LayerBox.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Widgets/PageIndexBox.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Widgets/AnchorBox.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Widgets/ListItemBox.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Widgets/UnorderedListBox.js"></script>
<script language="JavaScript" src="js/LMS/Ajax.js"></script>
<script language="JavaScript" src="js/LMS/Action.js"></script>
<script language="JavaScript" src="js/LMS/UI.js"></script>
<script language="JavaScript" src="js/LMS/Router.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/i18n.js"></script>
<script language="JavaScript" src="js/LMS/i18n/ru.js"></script>
<script language="JavaScript" src="js/LMS/i18n/ru/Main.js"></script>
<script language="JavaScript" src="js/LMS/Text.js"></script>
<script language="JavaScript" src="js/LMS/Date.js"></script>
<script language="JavaScript" src="js/LMS/DateFormat.js"></script>
<script language="JavaScript" src="js/LMS/LiveDatetime.js"></script>
<script language="JavaScript" src="js/LMS/Widgets/Overlay.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Utils.js"></script>
<script type="text/javascript">
    var ajax = new LMS.Ajax();
    ajax.setApiUrl(API_URL);
    var action = new LMS.Action();
    action.setQueryMethod(function(requestParams, callback){ajax.exec(requestParams, callback)});
    var ui = new LMS.UI();
    LMS.Connector.connect('userError', ui, 'showUserError');
    LMS.Connector.connect('userMessage', ui, 'showUserMessage');
    LMS.Connector.connect('highlightElement', ui, 'highlightElement');
    JsHttpRequest.JHRController.SysMessenger = function(text) {
        ui.showUserError(500, text, 'warn');
    }
    var router = new LMS.Router(); 
</script>
<script language="JavaScript" src="js/LMS/Video/Action.js?v=1.1.6"></script>
<script language="JavaScript" src="js/LMS/Video/UI.js?v=1.1.10"></script>
<script type="text/javascript">
    LMS.Action.addMethods(LMS.Video.Action);
    LMS.UI.addMethods(LMS.Video.UI);
    LMS.Connector.connect('drawCatalog', ui, 'drawCatalog');
    LMS.Connector.connect('drawBookmarks', ui, 'drawBookmarks');
    LMS.Connector.connect('drawGenres', ui, 'drawGenres');
    LMS.Connector.connect('drawCountries', ui, 'drawCountries');
    LMS.Connector.connect('drawLastComments', ui, 'drawLastComments');
    LMS.Connector.connect('drawLastRatings', ui, 'drawLastRatings');
    LMS.Connector.connect('drawRandomFilm', ui, 'drawRandomFilm');
    LMS.Connector.connect('drawPopFilms', ui, 'drawPopFilms');
    LMS.Connector.connect('drawFilm', ui, 'drawFilm');
    LMS.Connector.connect('drawMoviePerson', ui, 'drawMoviePerson');
    LMS.Connector.connect('drawComments', ui, 'drawComments');
    LMS.Connector.connect('drawSuggestion', ui, 'drawSuggestion');
    LMS.Connector.connect('drawBestsellers', ui, 'drawBestsellers');
    LMS.Connector.connect('drawSearch', ui, 'drawSearch');
    LMS.Connector.connect('drawPerson', ui, 'drawPerson');
    LMS.Connector.connect('unstarBookmark', ui, 'unstarBookmark');
    LMS.Connector.connect('starBookmark', ui, 'starBookmark');
    LMS.Connector.connect('updateRating', ui, 'updateRating');
    LMS.Connector.connect('postDeleteComment', ui, 'postDeleteComment');
    LMS.Connector.connect('postChangePassword', ui, 'postChangePassword');
</script>
<script>
    function Init() {
        window.ui.init()
    }    
</script>
<script type="text/javascript">
    //<![CDATA[
    TEMPLATES.CATALOG = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/catalog.jhtml'));?>";
    TEMPLATES.BOOKMARKS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/bookmarks.jhtml'));?>";
    TEMPLATES.RECENTLY_VIEWED = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/recently-viewed.jhtml'));?>";
    TEMPLATES.LAST_COMMENTS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/last-comments.jhtml'));?>";
    TEMPLATES.LAST_RATINGS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/last-ratings.jhtml'));?>";
    TEMPLATES.RANDOM_FILM = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/random-film.jhtml'));?>";
    TEMPLATES.POP_FILMS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/pop-films.jhtml'));?>";
    TEMPLATES.FILM = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/film.jhtml'));?>";
    TEMPLATES.PERSON = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/person.jhtml'));?>";
    TEMPLATES.FILM_COMMENTS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/film-comments.jhtml'));?>";
    TEMPLATES.SEARCH_SUGGESTION = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/search-suggestion.jhtml'));?>";
    TEMPLATES.BESTSELLERS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/bestsellers.jhtml'));?>";
    TEMPLATES.SEARCH = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/search.jhtml'));?>";
    //]]>
</script>
<?php
if (file_exists(dirname(__FILE__) . '/head.after.php')) {
    include_once(dirname(__FILE__) . '/head.after.php');
}
?>