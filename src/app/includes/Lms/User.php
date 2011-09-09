<?php
/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: User.php 700 2011-06-10 08:40:53Z macondos $
 */

class Lms_User
{
    private static $_acl;

    private static $_identified = null;

    /**
     * @var Lms_Item_User
     */
    private static $_userInstance;
   
    public static function getUser()
    {
        if (!self::$_userInstance) {
            self::$_userInstance = new Lms_Item_User();
            self::initUserInstance();
        }
        if (self::$_identified===null) {
            Lms_Application::getAuthData($login, $pass);
            if ($login && $pass) {
                self::$_userInstance->loadFromDb($login, $pass);
            }
            if (!self::$_userInstance->getId()) {
                self::$_userInstance->loadFromDb('guest');
            }
            self::$_identified = true;
        }
        return self::$_userInstance;
    }

    function setAcl($acl)
    {
        self::$_acl = $acl;
    }
    
    private static function initUserInstance()
    {
        self::$_userInstance->setAcl(self::$_acl);
    }
}
