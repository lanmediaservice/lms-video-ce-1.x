<?php

class Lms_Item_MovieUserRating extends Lms_Item_Abstract {
    
    public static function getTableName()
    {
        return '?_movies_users_ratings';
    }
    
    public function _customInitStructure($struct, $masterDb, $slaveDb)
    {
        parent::_customInitStructure($struct, $masterDb, $slaveDb);
        $struct->addIndex('movie_id', array('movie_id'));
    }
    
    protected function _preInsert() 
    {
        if (!$this->getCreatedAt()) {
            $this->setCreatedAt(date('Y-m-d H:i:s'));
        }
        if (!$this->getUserId()) {
            $this->setUserId(Lms_User::getUser()->getId());
        }
    }
    
    public static function replaceRating($movieId, $rating)
    {
        $db = Lms_Db::get('main');

        $sql = "INSERT INTO " . self::getTableName() . " SET user_id=?d, movie_id=?d, rating=?d, `created_at`=NOW() ON DUPLICATE KEY UPDATE rating=?d, `created_at`=NOW()";
        $db->query(
            $sql,
            Lms_User::getUser()->getId(),
            $movieId,
            $rating,
            $rating
        );
    }
    
    public static function deleteRating($movieId)
    {
        $db = Lms_Db::get('main');
        $sql = "DELETE FROM " . self::getTableName() . " WHERE user_id=?d AND movie_id=?d";
        $db->query($sql, Lms_User::getUser()->getId(), $movieId);
    }

    public static function getRating($movieId)
    {
        $db = Lms_Db::get('main');
        $sql = "SELECT rating FROM " . self::getTableName() . " WHERE user_id=?d AND movie_id=?d";
        return $db->selectCell($sql, Lms_User::getUser()->getId(), $movieId);
    }

}
