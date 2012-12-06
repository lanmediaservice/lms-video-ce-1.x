<?php

class Lms_Item_Participant extends Lms_Item_Abstract {
    
    public static function getTableName()
    {
        return '?_participants';
    }

    public function _customInitStructure($struct, $masterDb, $slaveDb)
    {
        parent::_customInitStructure($struct, $masterDb, $slaveDb);
        $struct->addIndex('movie_id', array('movie_id'));
        $struct->addIndex('person_id', array('person_id'));
    }
    
}