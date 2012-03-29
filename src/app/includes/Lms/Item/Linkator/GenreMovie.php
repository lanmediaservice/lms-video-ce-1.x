<?php

class Lms_Item_Linkator_GenreMovie extends Lms_Item_Abstract 
{
       
    static public function getTableName() 
    {
        return '?_movies_genres';
    }
    
    public function _customInitStructure($struct, $masterDb, $slaveDb)
    {
        parent::_customInitStructure($struct, $masterDb, $slaveDb);
        $struct->addIndex('genre_id', array('genre_id'));
        $struct->addIndex('movie_id', array('movie_id'));
    }
}
