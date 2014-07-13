<?php

class CL_Smiffys_Model_Product_Inventory extends CL_Smiffys_Model_Product {

    const STATUS_ID = 'inventory';
    
        public function __construct(){
        parent::__construct();
        $this->setStatusHandler( new CL_Smiffys_Model_Api_V1_Status( self::STATUS_ID /*, array('total','imported','exists','errors', 'warnings')*/) );
    }

    
    protected function _setAllProductsOutOfStock(){
        $this->getStatusHandler()->add('Setting All products to Out of stock...');
        /*
        $collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToFilter('is_in_stock',1);
        */
        
        $stockCollection = Mage::getModel('cataloginventory/stock_item')->getCollection()
            ->addFieldToFilter('is_in_stock', 1);
        
        
        foreach( $stockCollection as $stockItem ){
            //$this->getStatusHandler()->add("[clean]:Processing:({$product->getSku()})");
            //$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());
            if ( $stockItem->getProductTypeId() != 'simple' ){
                return $this;
            }
            
            $stockItem->setData('qty', 0);
            $stockItem->setIsInStock(false)->setStockStatusChangedAutomaticallyFlag(true);
            $stockItem->save();
            /*
            $product->setData('is_in_stock',0);
            $product->setQty(0);
            $product->save();
             */
        }
        $this->getStatusHandler()->add('Done');
        return $this;
    }
    
    protected function _updateProductInventory($_xmlProduct) {
        $updated    = $this->getUpdated();
        $updatedIds = $this->getUpdatedIds();
        $skipped    = $this->getSkipped();
        
        $this->getStatusHandler()->add("Processing:({$_xmlProduct->Product_Code})...");
        
        if (($productId = Mage::getResourceModel('catalog/product')->getIdBysku(trim($_xmlProduct->Product_Code)))) {
            $stockItem = Mage::getModel('cataloginventory/stock_item');
            $stockItem->loadByProduct($productId);
            $qty = intval($_xmlProduct->Available_Stock);
            if (intval($stockItem->getQty()) != $qty ) {
                $inStock = ($qty > 0) ? 1 : 0;
                $stockItem->setData('is_in_stock', $inStock);
                $stockItem->setQty($qty);
                $stockItem->save();

                //echo "<hr>Updating product:{$productId} with qty: {$qty}";
                //$txtResult.= "<hr>Updating product:{$productId} with qty: {$qty}";
                $updated++;
                $updatedIds[$productId] = $qty;
                $this->getStatusHandler()->add("Updated qty:".trim($qty));
            } else {
                $this->getStatusHandler()->add("Skipped. Qty:".trim($qty));
                $skipped++;
            }
        }else{
            $this->getStatusHandler()->add("Product not found");
        }
        
        $this->setUpdated($updated);
        $this->setSkipped($skipped);
        $this->setUpdatedIds($updatedIds);
        
        //$this->setTxtResult( $txtResult );
        return $this;
    }

    public function updateInventory() {
        
        $this->getStatusHandler()->add('{START}Getting XML...');
        $transport = $this->_getTransport();
        $transport->exec();
        if ( !is_object( $transport->getResultData() ) ){
            $this->getStatusHandler()->add('{ERR}Wrong data');
            return false;
        }
        $this->getStatusHandler()->add('{}Cleaning inventory...');
        $this->_setAllProductsOutOfStock();
        $this->getStatusHandler()->add('{DONE:CLEAN}Inventory clean done');
        
        
        //Zend_Debug::dump($transport);
        foreach ($transport->getResult() as $product) {
            $this->_updateProductInventory($product);
        }
        $this->getStatusHandler()->add('{DONE}');
        $this->getStatusHandler()->finish('{DONE}');
        return $this;
    }

    protected function _getTransport() {
        $factory = Mage::getModel('smiffys/product_transportFactory');
        $transport = $factory::getTransport(array('filterCode' => '', 'FilterDescription' => ''));
        $transport->setCmd(CL_Smiffys_Model_Transport_Abstract::CMD_GET_STOCK_QUANTITIES_LIGHT_XML);
        return $transport;
    }

}