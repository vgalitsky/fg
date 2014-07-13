<?php

class CL_Smiffys_Adminhtml_SordersController extends Mage_Adminhtml_Controller_Action {

    protected function _init() {
        $this->loadLayout()
                ->_setActiveMenu('smiffys')
                ->_addBreadcrumb(Mage::helper('adminhtml')->__('Smiffys webservices'), Mage::helper('adminhtml')->__('Smiffys webservices'));
        
        return $this;
    }
    
    
    public function exportAllAction(){
        $this->_init();
        $this->renderLayout();
    }
    
    public function exportAction(){
        $orderId = $this->getRequest()->getParam('order_id');
        if ( $orderId ){
            $exportOrder = Mage::getModel('smiffys/order_export')->load( $orderId );
            $exportOrder->export();
        }
        $this->_redirectReferer();
    }
    
    public function trackAction(){
        $orderId = $this->getRequest()->getParam('order_id');
        if ( $orderId ){
            $exportOrder = Mage::getModel('smiffys/order_tracker')->load( $orderId );
            $exportOrder->track();
        }
        $this->_redirectReferer();
    }
    
    public function trackAllAction(){
        die('asdasd');;
        CL_Smiffys_Model_Order_Tracker::trackAll();
    }
    
}
