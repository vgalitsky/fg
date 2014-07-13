<?php

class CL_Smiffys_Adminhtml_SproductsController extends Mage_Adminhtml_Controller_Action {

    protected function _init() {
        $this->loadLayout()
                ->_setActiveMenu('smiffys')
                ->_addBreadcrumb(Mage::helper('adminhtml')->__('Smiffys webservices'), Mage::helper('adminhtml')->__('Smiffys webservices'));
        
        return $this;
    }
    
    public function importAction(){
        $this->_init();
        $smiffys = Mage::getModel('smiffys/api_v1_smiffys');
        //$smiffys = New CL_Smiffys_Model_Api_V1_Smiffys();
        Zend_Debug::dump($smiffys);
        die('asdasd');
        
        $this->renderLayout();
    }
    
    public function s2cAction(){
        $this->_init();
        $s2c = Mage::getSingleton('smiffys/product_s2c');
        $post = $this->getRequest()->getPost();
        //if($post){
            
            $s2c->process();
        //}
        
        $this->renderLayout();
    }
    
    public function inventoryAction(){
        $this->_init();
        $inventory = Mage::getSingleton( 'smiffys/product_inventory' );
        $post = $this->getRequest()->getPost();
        if($post){
            
            $inventory->updateInventory();
        }
        $this->renderLayout();

    }
    
    public function indexAction(){
        die('index');
    }
    
    
}
