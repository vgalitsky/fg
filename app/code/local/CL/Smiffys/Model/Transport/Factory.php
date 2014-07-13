<?php
class CL_Smiffys_Model_Transport_Factory Extends Mage_Core_Model_Abstract{

    static function getTransport(  $_constructParam = array(), $_type = null ){
        if(!$_type){
            $_type = Mage::getStoreConfig( 'smiffys/credentials/transport');
        }
        //die('smiffys/transport_'.$_type);
        return Mage::getModel('smiffys/transport_'.$_type, $_constructParam );
    }
    
}