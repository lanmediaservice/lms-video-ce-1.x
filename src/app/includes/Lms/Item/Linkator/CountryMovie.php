<?php

class Lms_Item_Linkator_CountryMovie extends Lms_Item_Abstract {
    static public function getTableName() 
    {
        return '?_movies_countries';
    }
    
    public function _customInitStructure($struct, $masterDb, $slaveDb)
    {
        parent::_customInitStructure($struct, $masterDb, $slaveDb);
        $struct->addIndex('country_id', array('country_id'));
        $struct->addIndex('movie_id', array('movie_id'));
    }
}