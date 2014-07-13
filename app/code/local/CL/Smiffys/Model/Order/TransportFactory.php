<?php
class CL_Smiffys_Model_Order_TransportFactory Extends Mage_Core_Model_Abstract{

    static function getTransport( $_constructParam = array() ){
        $_constructParam = count( $_constructParam ) ? $_constructParam :
            array(
                'clientId' => Mage::getStoreConfig( 'smiffys_orders/credentials/client_id' ),
                'apiKey' => Mage::getStoreConfig( 'smiffys_orders/credentials/api_key' )
            );
        $transport = CL_Smiffys_Model_Transport_Factory::getTransport(  $_constructParam );
        $transport->setApi( 'orders.asmx' );
        return $transport;
    }
    
}