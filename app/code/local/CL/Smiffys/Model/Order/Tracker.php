<?php
class CL_Smiffys_Model_Order_Tracker extends CL_Smiffys_Model_Order{
    
    
    static function trackAll(){
        
        $collection = Mage::getResourceModel('sales/order_collection')
            //->addAttributeToSelect('*')        
            ->addAttributeToFilter( 'status', Mage::getStoreConfig( 'smiffys_orders/tracker/traker_status' ) )
            ;
        foreach( $collection as $_order ){
            
            $order = Mage::getModel('smiffys/order_tracker')->load( $_order->getId() );
            $order->track();
        }
    }
    public function track(){
        $this->_initConfig();
        
        $transport = $this->_getTransport();
        $transport->exec();
        $orderStatus = $transport->getResultData();
//debug//
//        $xml = file_get_contents('tmp/orderStatus.xml');
//        $orderStatus = simplexml_load_string($xml);
        //Zend_Debug::dump($this->getData());die();
//-------------------------
        
        if( $orderStatus->ReturnCode ==  CL_Smiffys_Model_Transport_Abstract::RESULT_CODE_SUCCESS ){
            if ( $this->getTrackCreateInvoice() ){
                $this->invoiceIt();
            }
            if ( $this->getTrackCreateShipment() ){
                //@TODO consignments
                //hack? what about multiple consgnments?
                $consignment = is_object($orderStatus->Consignments->Consignment[0]) ? $orderStatus->Consignments->Consignment[0] : $orderStatus->Consignments->Consignment;
//-------
                $trackerData = array(
                    'carrier_code' => $consignment->CourierType,
                    'title' => $consignment->DeliveryCode,
                    'number' => $consignment->Number,
                    'date'=> $orderStatus->ShippingDate,
                );
                $this->shipIt( $trackerData );
            }
            $this->addStatusToHistory($this->getTrackSuccessStatus(), Mage::helper('smiffys')->__('Successfully Tracked').': '. $transport->getRawData(), false);
            
        } else {
            $this->addStatusToHistory($this->getStatus(), Mage::helper('smiffys')->__('Smiffys status request failed with message').': '."({$orderStatus->ReturnCode})".$orderStatus->ReturnValue, false);
        }
        $this->save();
    }
    
    protected function _getTransport() {
        $factory = Mage::getModel('smiffys/order_transportFactory');
        $transport = $factory::getTransport( array( 'orderNumber' => $this->getIncrementId() ) );
        $transport->setCmd(CL_Smiffys_Model_Transport_Abstract::CMD_ORDER_GET_STATUS );
        $this->setTransport( $transport );
        return $transport;
    }
}