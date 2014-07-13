<?php
class CL_Smiffys_Model_Order_Export extends CL_Smiffys_Model_Order {
  


    
    public function canExport(){
        $this->_initConfig();
        foreach ( $this->getExportStatus() as $status ){
            if ( $status == $this->getStatus() ){
                return true;
            }
        }
        return false;
    }
    
    public function export(){
        $this->_initConfig()
                ->prepareXmlData()
                ->prepareExportXml()
        ;
        $transport = $this->_getTransport();
        $transport->exec();
        $result = $transport->getResultData();
        if ( $result->ReturnCode == CL_Smiffys_Model_Transport_Abstract::RESULT_CODE_SUCCESS ){
                $this->addStatusToHistory($this->getSuccessStatus(), Mage::helper('smiffys')->__('Smiffys: Successfully Exported'), false);
                Mage::getSingleton('core/session')->addSuccess(Mage::helper('smiffys')->__('Order was successfully exported'));
            
        } else{
                $this->addStatusToHistory($this->getFailStatus(), Mage::helper('smiffys')->__('Export failed with message').':'.$result->ReturnValue, false);
                Mage::getSingleton('core/session')->addError(Mage::helper('smiffys')->__('Export failed with message').':'.$result->ReturnValue);
        }   
            
        $this->save();
        return $this;
    }
    
    
    
    public function prepareExportXml(){
        $xml = CL_Smiffys_Model_Xml_Template_Parser::parse( $this->getXmlTemplate(), $this->getXmlData() );
        $this->setOrderXml( $xml );
        return $this;
    }
    
        
    public function prepareLinesXml(  ){
        $items = $this->getItemsCollection();
        foreach ( $items as $item ){
            $item->setQtyOrdered( (int)$item->getQtyOrdered() );
            $lines[]= CL_Smiffys_Model_Xml_Template_Parser::parse( $this->getLineXmlTemplate(), $item->getData()) ;
        }
        return implode('',$lines);
    }
    
    public function prepareXmlData(  ){
        $data = array_merge(
                $this->getData(),
                array(
                    'increment_id'  => $this->getIncrementId(),
                    'email'         => $this->getShippingAddress()->getEmail(),
                    'telephone'     => $this->getShippingAddress()->getTelephone(),
                    'recipient'     => $this->getShippingAddress()->getFirstname().' '.$this->getShippingAddress()->getLastname(),
                    'address'       => implode(' ',$this->getShippingAddress()->getStreet()),
                    'city'          => $this->getShippingAddress()->getCity(),
                    'postCode'      => $this->getShippingAddress()->getPostcode(),
                    'country'       => Mage::getModel('directory/country')->load( $this->getShippingAddress()->getCountryId() )->getName(),
                    'countryCode'   => $this->getShippingAddress()->getCountryId(),
                    'deliveryCode'  => Mage::getStoreConfig( 'smiffys_orders/credentials/delivery_code' ),
                    'lines'         => $this->prepareLinesXml(),
        ));
        $this->setXmlData( $data );
//Zend_Debug::dump($data);
        return $this;
    }
    
    protected function _getTransport() {
        $factory = Mage::getModel('smiffys/order_transportFactory');
        $transport = $factory::getTransport( array( 'orderXml' => $this->getOrderXml() ) );
        $transport->setCmd(CL_Smiffys_Model_Transport_Abstract::CMD_ORDER_SUBMIT );
        $this->setTransport( $transport );
        return $transport;
    }
}