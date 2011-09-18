<?php
/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: User.php 700 2011-06-10 08:40:53Z macondos $
 */

class Lms_Item_User
{
    private $_acl;
    private $_data;    

    public function __construct($data = null)
    {
        $this->setData($data);
    }

    public function loadFromDb($userName, $password = false)
    {
        $db = Lms_Db::get('main');
        $row = $db->selectRow(
            'SELECT * FROM users WHERE `Login`=? {AND `Password`=?}', 
            $userName,
            $password!==false? md5($password) : DBSIMPLE_SKIP
        );
        if ($row) {
            $this->setData($row);
        }
        return $this;
    }

    public function setData($data)
    {
        $this->_data = is_array($data)? array_change_key_case($data, CASE_LOWER) : null;
    }

    public function __call($method, $arguments = null)
    {      
        $operation = substr($method, 0, 3);
        $subject  = strtolower(substr($method, 3));
        switch ($operation) {
        case "get":
            return $this->_get($subject); 
            break;        
        case "set":
            return $this->_set($subject, $arguments[0]);
            break;
        default:
            throw new Lms_Item_Exception(
                "Unsupported method: $method; operation: $operation"
            );
        }
    }
    
    public function _get($key)
    {
        if (!is_array($this->_data)) {
            return null;
        }
        return array_key_exists($key, $this->_data) ? $this->_data[$key] : null;
    }
      
    /**
     * Get usergroup
     * @return array
     */
    function getUserGroup()
    {
        if (!$this->_data || !$this->getEnabled() || !$this->getBalans()) {
            return 'guest';
        }
        switch ($this->_data['usergroup']) {
            case 0: 
                return 'guest';
                break;
            case 1: 
                return 'user';
                break;
            case 2: 
                return 'moder';
                break;
            case 5: 
                return 'moder';
                break;
            case 3: 
                return 'admin';
                break;
            default: 
                throw new Lms_Exception('Unknown user group');
        }
    }
    

    public function setAcl($acl)
    {
        $this->_acl = $acl;
    }

    public function isAllowed($resource, $privelege = '')
    {
        return $this->_acl->isAllowed(
            $this->getUserGroup(), $resource, $privelege
        );
    }
   
}
