<?php
/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: User.php 700 2011-06-10 08:40:53Z macondos $
 */

class Lms_Item_User extends Lms_Item_Abstract {

    private $_acl;

    public function getTableName()
    {
        return '?_users';
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
            $this->storeData(array_change_key_case($row, CASE_LOWER));
        }
        return $this;
    }

    /**
     * Get usergroup
     * @return array
     */
    function getGroup()
    {
        if (!$this->getId() || !$this->getEnabled() || !$this->getBalans()) {
            return 'guest';
        }
        switch ($this->getUserGroup()) {
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
            $this->getGroup(), $resource, $privelege
        );
    }
    
    public static function selectAsStruct(&$total, $offset, $size, $sort, $order, $filter)
    {
        $db = Lms_Db::get('main');
        if ($order<0) {
            $order = 'DESC';
        } else if ($order>0) {
            $order = 'ASC';
        } else {
            $order = '';
        }
        $rows = $db->selectPage(
            $total,
            "SELECT 
                ID, Login  
            FROM users 
            WHERE 1=1 
                {AND Login LIKE ?}
                {AND IP LIKE ?}
            ORDER BY ?# $order {LIMIT ?d, }{?d}", 
            !empty($filter['login'])? "%{$filter['login']}%" : DBSIMPLE_SKIP,
            !empty($filter['ip'])? "%{$filter['ip']}%" : DBSIMPLE_SKIP,
            $sort,
            $offset!==null? $offset : DBSIMPLE_SKIP, 
            $size!==null? $size : DBSIMPLE_SKIP
        );
        return $rows;
    }    
   
}
