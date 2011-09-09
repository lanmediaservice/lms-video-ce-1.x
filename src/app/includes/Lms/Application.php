<?php
/**
 * »нициализаци€ приложени€
 * 
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: Application.php 700 2011-06-10 08:40:53Z macondos $
 */

class Lms_Application
{
    private static $_config;

    /**
     * @var Lms_Api_Controller
     */
    private static $_apiController = null;
    /**
     * @var Zend_Controller_Front
     */
    private static $_frontController = null;
    /**
     * @var Zend_Translate
     */
    private static $_translate;
    /**
     * @var Zend_Acl
     */
    private static $_acl;
    /**
     * @var Lms_User
     */
    private static $_user;
    /**
     * @var Lms_MultiAuth
     */
    private static $_auth;
    /**
     * @var Zend_Controller_Request_Http
     */
    private static $_request;

    /**
     * “екущий €зык
     * @var string
     */
    private static $_lang;
    /**
     * “екущий макет
     * @var string
     */
    private static $_layout;
    /**
     * Ѕазовый URL без учета модификатора €зыка
     * http://examle.com/root/Url/ru/blah/blah ($_rootUrl = /root/Url)
     * @var string
     */
    private static $_rootUrl;

    /**
     * ћассив директорий скриптов шаблона (.phtml)
     * @var array
     */
    private static $_scriptsTemplates;

    /**
     * ћассив реальных путей и соответствующих относительных URL
     * публичных файлов шаблона (.css, .js и т.д.)
     * ѕример:
     * Array(
     *      [0] => Array
     *          (
     *              [path] => C:/www/english/public/templates/user/ru
     *              [url] => /public/templates/user/ru
     *          )
     *
     *      [1] => Array
     *          (
     *              [path] => C:/www/english/public/templates/user
     *              [url] => /public/templates/user
     *          )
     *
     *      [2] => Array
     *          (
     *              [path] => C:/www/english/public/templates/default.dist/ru
     *              [url] => /public/templates/default.dist/ru
     *          )
     *
     *      [3] => Array
     *          (
     *              [path] => C:/www/english/public/templates/default.dist
     *              [url] => /public/templates/default.dist
     *          )
     *
     *  )
     *
     * @var array
     */
    private static $_publicTemplates;

    /**
     * ¬рем€ начало работы скрипта
     * @var float
     */
    private static $_mainTimer;
    
    private static $_httpClient;
    
    private static function detectLang(Zend_Controller_Request_Http &$request)
    {
        self::$_lang = self::$_config['langs']['default'];
        if (isset($_COOKIE['lang']) && array_key_exists($_COOKIE['lang'], self::$_config['langs']['supported'])) {
            self::$_lang = $_COOKIE['lang'];
        } else {
            $langs = Zend_Locale::getBrowser();
            asort($langs);
            foreach (array_keys($langs) as $lang) {
                if (array_key_exists($lang, self::$_config['langs']['supported'])) {
                    self::$_lang = $lang;
                }
            }
        }
        $locale = new Zend_Locale(self::$_lang);
        Zend_Registry::set('Zend_Locale', $locale);
        return self::$_lang;
    }
    
    public static function setRequest()
    {
        self::$_request = new Zend_Controller_Request_Http();
    }
    
    public static function runApi()
    {
        self::setRequest();
        self::prepareApi();
        self::$_apiController->exec();
        self::close();
    }

    public static function prepareApi()
    {
        /**
         * –азъ€снение комментариев:
         * self::initYYY()//зависит от XXX
         * Ёто значит перед запуском, метода YYY, должен отработать метод XXX
         * self::initYYY()//требует XXX
         * Ёто значит, что дл€ корректной работы сущностей определ€емых
         * методом YYY, должен быть проинизиализирован метод XXX (место
         * инициализации не имеет важного значени€)
         */

        self::initEnvironmentApi();
        self::initConfig();//зависит от initEnvironment
        self::initDebug();//зависит от initConfig
        self::initErrorHandler();//зависит от initDebug
        self::initDb(); //зависит от initConfig, требует initDebug
        self::initVariables();//зависит от initDb
        self::initConfigFromDb();//зависит от initDb
        self::initApiController();//зависит от initVariables
//        self::initTranslate();//зависит от initApiRequest, initDebug
        self::initAcl();//зависит от initConfig, initVariables, initDb
    }

    public static function initApiController()
    {
        self::$_apiController = Lms_Api_Controller::getInstance();
        self::$_apiController->analyzeHttpRequest();
        self::$_lang = self::$_apiController->getLang();
        if (!self::$_lang) {
            self::$_lang = self::$_config['langs']['default'];
        }

    }


    public static function run()
    {
        self::setRequest();
        $response = new Zend_Controller_Response_Http();
        $channel = Zend_Wildfire_Channel_HttpHeaders::getInstance();
        $channel->setRequest(self::$_request);
        $channel->setResponse($response);
        // Start output buffering
        ob_start();
        try { 
            self::prepare();
            Lms_Debug::debug('Request URI: ' . $_SERVER['REQUEST_URI']);
            try {
                self::$_frontController->dispatch(self::$_request);
            } catch (Exception $e) {
                Lms_Debug::crit($e->getMessage());
                Lms_Debug::crit($e->getTraceAsString());
            }
            self::close();
        } catch (Exception $e) {
            Lms_Debug::crit($e->getMessage());
            Lms_Debug::crit($e->getTraceAsString());
        }
        // Flush log data to browser
        $channel->flush();
        $response->sendHeaders();
    }

    public static function prepare()
    {
        /**
         * –азъ€снение комментариев:
         * self::initYYY()//зависит от XXX
         * Ёто значит перед запуском, метода YYY, должен отработать метод XXX
         * self::initYYY()//требует XXX
         * Ёто значит, что дл€ корректной работы сущностей определ€емых
         * методом YYY, должен быть проинизиализирован метод XXX (место
         * инициализации не имеет важного значени€)
         */

        self::initEnvironment();
        self::initConfig();//зависит от initEnvironment
        self::initSessions();//зависит от initConfig
        self::initDebug();//зависит от initConfig
        self::initErrorHandler();//зависит от initDebug
        self::initDb(); //зависит от initConfig, требует initDebug
        self::initVariables();//зависит от initDb
        self::initConfigFromDb();//зависит от initDb
        self::initFrontController();//зависит от initConfig
        self::initTranslate();//зависит от initFrontController, initDebug
        self::initRoutes();//зависит от initFrontController
        self::initAcl();//зависит от initConfig, initVariables, initDb
        self::initView();//зависит от initConfig, initFrontController,
                         //initAcl, initTranslate
    }
    
    public static function initEnvironmentApi()
    {
        ini_set('max_execution_time', 1000);
    }

    public static function initEnvironment()
    {
        ini_set('max_execution_time', 1000);
        header("Content-type:text/html;charset=windows-1251");
        if(get_magic_quotes_runtime())
        {
            // Deactivate
            set_magic_quotes_runtime(false);
        }
        static $alreadyStriped = false;
        if (get_magic_quotes_gpc() || !$alreadyStriped) {
            $_COOKIE = Lms_Array::recursiveStripSlashes($_COOKIE);
            $_GET = Lms_Array::recursiveStripSlashes($_GET);
            $_POST = Lms_Array::recursiveStripSlashes($_POST);
            $_REQUEST = Lms_Array::recursiveStripSlashes($_REQUEST);
            $alreadyStriped = true;
        } 
    }
    
    public static function initConfig()
    {

        include_once APP_ROOT . "/../config.php";
        include_once APP_ROOT . "/default.settings.php";
        include_once APP_ROOT . "/local.settings.php";
        self::$_config = $config;
    }

    public static function initConfigFromDb()
    {
        if ($params = self::getConfig('db_config')) {
            $db = Lms_Db::get($params['alias']);
            $rows = $db->select('SELECT * FROM ?#', $params['table']);
            foreach ($rows as $row) {
                switch ($row['type']) {
                    case 'array': 
                        $value = unserialize($row['value']);
                        break;
                    case 'scalar': 
                    default: 
                        $value = $row['value'];
                        break;
                }
                $keys = preg_split('{/}', $row['key']);
                switch (count($keys)) {
                    case 1:
                        self::$_config[$keys[0]] = $value;
                        break;
                    case 2: 
                        self::$_config[$keys[0]][$keys[1]] = $value;
                        break;
                    case 3: 
                        self::$_config[$keys[0]][$keys[1]][$keys[2]] = $value;
                        break;
                    case 4: 
                        self::$_config[$keys[0]][$keys[1]][$keys[2]][$keys[3]] = $value;
                        break;
                    case 5: 
                        self::$_config[$keys[0]][$keys[1]][$keys[2]][$keys[3]][$keys[4]] = $value;
                        break;
                    default: 
                        throw new Lms_Exception("DB-config keys not support deep more 5 subitems");
                        break;
                }
            }
        }
    }
    
    public static function initSessions()
    {
        Zend_Session::start();
    }
    
    public static function initDebug()
    {
        Lms_Debug::setLogger(self::$_config['logger']);
        self::$_mainTimer = new Lms_Timer();
        self::$_mainTimer->start();
    }
    
    public static function initErrorHandler()
    {
        Lms_Debug::initErrorHandler();
    }

    public static function initDb()
    {
        foreach (self::$_config['databases'] as $dbAlias => $dbConfig) {
            Lms_Db::addDb(
                $dbAlias,
                $dbConfig['connectUri'],
                $dbConfig['initSql'],
                $dbConfig['debug']
            );
        }
    }

    public static function initVariables()
    {
        if (self::$_request) {
            self::$_rootUrl = self::$_request->getBaseUrl();
        }
        if (preg_match('{\.php$}i', self::$_rootUrl)) {
            self::$_rootUrl = dirname(self::$_rootUrl);
        }

        Lms_Text::setEncoding('CP1251');
        Lms_Text::enableMultiByte();
        Lms_Api_Formatter_Ajax::setEncoding('CP1251');
        Lms_Api_Formatter_Json::setEncoding('CP1251');
        
        Lms_Thumbnail::setHttpClient(self::getHttpClient());
        Lms_Thumbnail::setThumbnailScript(self::getConfig('thumbnail', 'script'), self::getConfig('thumbnail', 'key'));
        Lms_Thumbnail::setImageDir(
            rtrim($_SERVER['DOCUMENT_ROOT'] . self::$_rootUrl, '/\\') . '/media/images'
        );
        Lms_Thumbnail::setThumbnailDir(
            rtrim($_SERVER['DOCUMENT_ROOT'] . self::$_rootUrl, '/\\') . '/media/thumbnails'
        );
        Lms_Thumbnail::setCache(self::getConfig('thumbnail', 'cache'));
        
    }

    public static function initAcl()
    {
        self::$_auth = Lms_MultiAuth::getInstance();

        $cookieManager = new Lms_CookieManager(
            self::$_config['auth']['cookie']['key']
        );
        $authStorage = new Lms_Auth_Storage_Cookie(
            $cookieManager,
            self::$_config['auth']['cookie']
        );
        self::$_auth->setStorage($authStorage);

        self::$_acl = new Zend_Acl();
        self::$_acl->addRole(new Zend_Acl_Role('guest'))
                   ->addRole(new Zend_Acl_Role('user'), 'guest')
                   ->addRole(new Zend_Acl_Role('moder'), 'user')
                   ->addRole(new Zend_Acl_Role('admin'));

        self::$_acl->add(new Zend_Acl_Resource('film'))
                   ->add(new Zend_Acl_Resource('comment'))
                   ->add(new Zend_Acl_Resource('bookmark'))
                   ->add(new Zend_Acl_Resource('rating'))
                   ->add(new Zend_Acl_Resource('user'));
                   

        self::$_acl->allow('admin')
                   ->allow('moder', array('film', 'comment'))
                   ->allow('user', array('bookmark', 'rating', 'user'))
                   ->allow('user', array('comment'), 'post')
                   ->allow('guest', array('film'), 'view');
                   
        Lms_User::setAcl(self::$_acl);
        self::$_user = Lms_User::getUser();
    } 

    public static function close()
    {
        if (self::getConfig('optimize', 'classes_combine')) {
            Lms_NameScheme_Autoload::compileTo(APP_ROOT . '/includes/All.php');
        }
        
        foreach (self::$_config['databases'] as $dbAlias => $dbConfig) {
            if (Lms_Db::isInstanciated($dbAlias)) {
                $db = Lms_Db::get($dbAlias);
                $sqlStatistics = $db->getStatistics();
                $time = round(1000 * $sqlStatistics['time']);
                $count = $sqlStatistics['count'];
                Lms_Debug::debug(
                    "Database $dbAlias time: $time ms ($count queries)"
                );
            }
        }
        foreach (Lms_Timers::getTimers() as $name => $timer) {
            $time = round(1000 * $timer->getSumTime());
            Lms_Debug::debug(
                'Profiling "' . $name . '": ' . $time . ' ms (' . $timer->getCount() . ')'
            );
        }
        Lms_Debug::debug(
            'Used memory: ' . round(memory_get_usage()/1024) . ' KB'
        );
        self::$_mainTimer->stop();
        $time = round(1000 *self::$_mainTimer->getSumTime());
        Lms_Debug::debug("Execution time: $time ms");
    }
    
    public static function getLang()
    {
        return self::$_lang;
    }

    public static function getTranslate()
    {
        return self::$_translate;
    }

    public static function getRequest()
    {
        return self::$_request;
    }

    public static function getConfig($param = null)
    {
        $params = func_get_args();
        $result = self::$_config;
        foreach($params as $param) {
            if (!array_key_exists($param, $result)) {
                return null;
            }
            $result = $result[$param];
        }
        return $result;
    }

    public static function getHttpClient()
    {
        if (!self::$_httpClient) {
            $httpOptions = Lms_Application::getConfig('http_client');
            self::$_httpClient = new Zend_Http_Client(
                null,
                $httpOptions
            );
        }
        return self::$_httpClient;
    }    

    public static function getLeechProtectionCode($array)
    {
        $str = implode("-", $array);
        $str .= self::getConfig('antileechkey')? self::getConfig('antileechkey') : "secret";
        return md5($str);
    }
    
    public static function thumbnail($imgPath, &$width=0, &$height=0, $defer = false)
    {
        if (!preg_match('{^https?://}i', $imgPath)) {
            $imgPath = dirname(APP_ROOT) . '/' . $imgPath;
        }
        return Lms_Thumbnail::thumbnail($imgPath, $width, $height, $tolerance = 0.00, $zoom = true, $force = true, $deferDownload = $defer, $deferResize = $defer);
    }
    
    public static function getSuggestion($query, $limit = 6)
    {
        $db = Lms_Db::get('main');

        $wheres = array();
        $wheres[] = "`word` LIKE '" . mysql_real_escape_string($query) . "%'";
        $wheres[] = "`type` = 'film'";
        $wheres[] = " f.Hide=0 ";

        $sql = "SELECT DISTINCT f.`id` as film_id "
             . "FROM `films` f "
             . "INNER JOIN suggestion s ON (s.id = f.ID ) "
             . "WHERE " . implode(' AND ', $wheres) . " "
             . "ORDER BY rank DESC LIMIT ?d";
        $films = $db->selectCol($sql, $limit);

        $wheres = array();
        $wheres[] = "`word` LIKE '" . mysql_real_escape_string($query) . "%'";
        $wheres[] = "`type` = 'person'";

        $sql = "SELECT DISTINCT p.`id` as person_id "
             . "FROM persones p "
             . "INNER JOIN `suggestion` s ON ( s.id = p.ID ) "
             . "WHERE " . implode(' AND ', $wheres) . " "
             . "ORDER BY rank DESC LIMIT ?d";

        $persones = $db->selectCol($sql, $limit);

        $count = count($films)+count($persones);
        if ($count> $limit) {
            $proportion = count($films)/$count;
            $countFilms = ceil($proportion * count($films));
            $countPersones = $limit - $countFilms;
            $films = array_slice($films, 0, $countFilms);
            $persones = array_slice($persones, 0, $countPersones);
        }
        
        return array(
            'films' => $films,
            'persones' => $persones,
        );
        
    }
    
    public static function getAuthData(&$login, &$pass) 
    {
        session_start();
        if (self::getConfig('auth','1.0','cookie','crypt')) {
            $crypter = new Lms_Crypt(
                self::getConfig('auth','1.0','cookie','mode'),
                self::getConfig('auth','1.0','cookie','algorithm'),
                self::getConfig('auth','1.0','cookie','key')
            );
            $login = isset($_SESSION['login'])? $_SESSION['login'] : (isset($_COOKIE['login'])? trim($crypter->decrypt(base64_decode($_COOKIE['login']))) : "");
            $pass = isset($_SESSION['pass'])? $_SESSION['pass'] : (isset($_COOKIE['pass'])? trim($crypter->decrypt(base64_decode($_COOKIE['pass']))) : "");
        } else {
            $login = isset($_SESSION['login']) ? $_SESSION['login'] : (isset($_COOKIE['login'])? $_COOKIE['login'] : "");
            $pass = isset($_SESSION['pass']) ? $_SESSION['pass'] : (isset($_COOKIE['pass'])? $_COOKIE['pass'] : "");
        }
    }
    
    public static function hitFilm($filmId) 
    {
	if ($filmId){
            $db = Lms_Db::get('main');
            $method = self::getConfig('hitmethod');
            switch ($method){
                case 1:
                    $db->query('UPDATE films SET Hit=Hit+1 WHERE films.ID=?', $filmId);
                break;
                case 2:
                    $ip = ip2long(Lms_Ip::getIp());
                    $c = $db->selectCell("SELECT count(*) FROM hits WHERE FilmID=?d AND UserID=?d", $filmId, $ip);
                    if ($c==0){
                        $db->query("INSERT INTO hits(FilmID,UserID,DateHit) VALUES(?d, ?d, NOW())", $filmId, $ip);
                        $db->query("UPDATE films SET Hit=Hit+1 WHERE films.ID=?d", $filmId);
                    }
                break;
                case 3:
                    if (!isset($_SESSION['films'][$filmId])) {
                        $db->query("UPDATE films SET Hit=Hit+1 WHERE films.ID=?d", $filmId);
                        $_SESSION['films'][$filmId] = 1;	
                    }
                break;
                default:
                    $userId = Lms_User::getUser()->getId();
                    $db->query("UPDATE users SET PlayActivity=PlayActivity+1 WHERE ID=?d", $userId);
                    $c = $db->selectCell("SELECT count(*) FROM hits WHERE FilmID=?d AND UserID=?d", $filmId, $userId);
                    if ($c==0) {
                        $db->query("INSERT INTO hits(FilmID,UserID,DateHit) VALUES(?d, ?d, NOW())", $filmId, $userId);
                        $db->query("UPDATE films SET Hit=Hit+1 WHERE films.ID=?d", $filmId);
                    }
            }
	}
    }

}