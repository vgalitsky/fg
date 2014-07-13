<?php
class CL_Smiffys_Model_Observer{
    
    public function exportOrder( $observer ){
        $order = $observer->getEvent()->getOrder();
        $exportOrder = Mage::getModel('smiffys/order_export')->load($order->getId());
        if( $exportOrder->canExport() ){ //export only with configured statuses
        
            $exportOrder->export();
        }
    }
    
    static function trackOrders(  ){
        
        CL_Smiffys_Model_Order_Tracker::trackAll();
        
    }
    
    static function updateInventory(  ){
        $instance = Mage::getModel('smiffys/product_inventory');
        $instance->updateInventory();
    }
    
    static function updatePrices(){
        $instance = Mage::getModel('smiffys/api_v1_smiffys');
        $instance->updateAllPrice();
    }
}