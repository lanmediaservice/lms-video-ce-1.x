<?php

class Lms_Item_Hit extends Lms_Item_Abstract {
    
    static public function getTableName()
    {
        return '?_hits';
    }
    
    public function _customInitStructure($struct, $masterDb, $slaveDb)
    {
        parent::_customInitStructure($struct, $masterDb, $slaveDb);
        $struct->addIndex('movie_id', array('movie_id'));
    }
    
}
