<?php

class CL_Smiffys_Model_Product_Upsells extends CL_Smiffys_Model_Product {

    const STATUS_ID = 'upsell';
    
        public function __construct(){
        parent::__construct();
       // $this->setStatusHandler( new CL_Smiffys_Model_Api_V1_Status( self::STATUS_ID /*, array('total','imported','exists','errors', 'warnings')*/) );
    }

    


    public function updateUpsell() {
        
        //$this->getStatusHandler()->add('{START}');
        $transport = $this->_getTransport();

        $collection = Mage::getModel('catalog/product')->getCollection();
        foreach( $collection as $product ){
            $sku = $product->getSku();
            $transport->_credentials['productCode'] = $sku;

            try{
                echo "\n\r";
                echo "SKU: {$sku} :";
                $transport->exec();
                if (! count($transport->getResult()->Product))continue;
                $upsellIds = array();
                foreach( $transport->getResult()->Product as $upsell ){
                    $upsellId = Mage::getSingleton('catalog/product')->getIdBySku( $upsell->Code );

                    if ( $upsellId ) {
                        $upsellIds[$upsellId] = true;
                        echo $upsellId.',';
                    }
                }
                $product->setUpSellLinkData($upsellIds)
                        ->save();
                echo ' :Ok';
            }catch(Exception $e){ echo 'Error: '.$e->getMessage();}

        }
//        $this->getStatusHandler()->add('{DONE}');
//        $this->getStatusHandler()->finish('{DONE}');
        return $this;
    }

    protected function _getTransport() {
        $factory = Mage::getModel('smiffys/product_transportFactory');
        $transport = $factory::getTransport(array('filterCode' => '', 'FilterDescription' => ''));
        $transport->setCmd(CL_Smiffys_Model_Transport_Abstract::CMD_GET_UPSELL_PRODUCTS);
        return $transport;
    }

}