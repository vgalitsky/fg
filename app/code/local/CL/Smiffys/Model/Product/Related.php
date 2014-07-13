<?php

class CL_Smiffys_Model_Product_Related extends CL_Smiffys_Model_Product {

    const STATUS_ID = 'related';
    
        public function __construct(){
        parent::__construct();
       // $this->setStatusHandler( new CL_Smiffys_Model_Api_V1_Status( self::STATUS_ID /*, array('total','imported','exists','errors', 'warnings')*/) );
    }

    


    public function updateRelated() {

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
                $relatedIds = array();
                foreach( $transport->getResult()->Product as $related ){
                    $relatedId = Mage::getSingleton('catalog/product')->getIdBySku( $related->Code );

                    if ( $relatedId ) {
                        $relatedIds[$relatedId] = true;
                        echo $relatedId.',';
                    }
                }
                $product->setRelatedLinkData($relatedIds)
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
        $transport->setCmd(CL_Smiffys_Model_Transport_Abstract::CMD_GET_RELATED_PRODUCTS);
        return $transport;
    }

}