<?php
class CL_Smiffys_Model_Product_TransportFactory Extends Mage_Core_Model_Abstract{

    static function getTransport( $_constructParam = array() ){
        $transport = CL_Smiffys_Model_Transport_Factory::getTransport(  $_constructParam );
        $transport->setApi( 'products.asmx' );
        return $transport;
    }
    
}