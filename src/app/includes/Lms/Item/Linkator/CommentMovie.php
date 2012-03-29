<?php

class Lms_Item_Linkator_CommentMovie extends Lms_Item_Abstract {
    
    static public function getTableName() 
    {
        return '?_movies_comments';
    }
    
    public function _customInitStructure($struct, $masterDb, $slaveDb)
    {
        parent::_customInitStructure($struct, $masterDb, $slaveDb);
        $struct->addIndex('comment_id', array('comment_id'));
        $struct->addIndex('movie_id', array('movie_id'));
    }
}