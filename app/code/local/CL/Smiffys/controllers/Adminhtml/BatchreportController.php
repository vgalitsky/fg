<?php

class CL_Smiffys_Adminhtml_BatchreportController extends Mage_Adminhtml_Controller_Action {

    protected function _initAction() {
        $this->loadLayout()
                ->_setActiveMenu('smiffys')
                ->_addBreadcrumb(Mage::helper('adminhtml')->__('Smiffys webservices'), Mage::helper('adminhtml')->__('Smiffys webservices'));
        
        return $this;
    }
    
    
    public function indexAction(){
        //$this->_initAction();    
        $item = $this->getRequest()->getParam('item');
        $content = file_get_contents( CL_Smiffys_Model_Api_V1_Status::getPath().'/'.$item );
        //echo CL_Smiffys_Model_Api_V1_Smiffys::getDataPath().'/'.$item;
        echo nl2br($content);
        die();
        //$this->renderLayout();
    }
}