<?php

class CL_Smiffys_Adminhtml_ImporterController extends Mage_Adminhtml_Controller_Action {

    protected function _initAction() {
        $this->loadLayout()
                ->_setActiveMenu('smiffys')
                ->_addBreadcrumb(Mage::helper('adminhtml')->__('Smiffys webservices'), Mage::helper('adminhtml')->__('Smiffys webservices'));
        
        return $this;
    }
    
    
    public function indexAction(){
        $this->_initAction();    
        $this->renderLayout();
    }
    
    public function batchAction(){
        $result = array();
        $batch = $this->getRequest()->getParam('item');
        $batchAction = $batch.'Action';
        try{
            $this->$batchAction();
            $result['ok'] = true;
        }catch(Exception $e){
            $result['ok'] = false;
            $result['err'] = true;
            $result['msg'] = $e->getMessage();
        }
        
        die( json_encode( $result ) );
    }
    
// batches itself    
    public function categoryAction(){
        exec( 'php '.Mage::getBaseDir().'/Smiffys/cimport.php > '.Mage::getBaseDir().'/var/status/products.exec'.' &' );
    }
    
    public function productsAction(){
        exec( 'php '.Mage::getBaseDir().'/Smiffys/import.php > '.Mage::getBaseDir().'/var/status/import.exec'.' &' );
    }
    
    public function s2cAction(){
//        die('php '.Mage::getBaseDir().'/Smiffys/s2c.php > '.Mage::getBaseDir().'/var/status/s2c.exec'.' &');
        exec( 'php '.Mage::getBaseDir().'/Smiffys/s2c.php > '.Mage::getBaseDir().'/var/status/s2c.exec'.' &' );
    }
    public function p2cAction(){
        exec( 'php '.Mage::getBaseDir().'/Smiffys/p2c.php > '.Mage::getBaseDir().'/var/status/p2c.exec'.' &' );
    }
    
    public function inventoryAction(){
        exec( 'php '.Mage::getBaseDir().'/Smiffys/inventory.php > '.Mage::getBaseDir().'/var/status/inventory.exec'.' &' );
    }
    
    public function priceAction(){
        exec( 'php '.Mage::getBaseDir().'/Smiffys/price.php > '.Mage::getBaseDir().'/var/status/price.exec'.' &' );
    }
    
    public function mediaAction(){
        exec( 'php '.Mage::getBaseDir().'/Smiffys/media.php > '.Mage::getBaseDir().'/var/status/media.exec'.' &' );
    }
    
    public function namesAction(){
        exec( 'php '.Mage::getBaseDir().'/Smiffys/names.php > '.Mage::getBaseDir().'/var/status/media.exec'.' &' );
    }
    
//***************************************************
        public function checkStatusAction(){
        $status = new CL_Smiffys_Model_Api_V1_Status( $this->getRequest()->getParam('status') );
        die( json_encode( $status->getState() ) );
    }

    
}
