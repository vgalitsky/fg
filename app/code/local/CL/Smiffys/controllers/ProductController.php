<?php
class CL_Smiffys_ProductController extends Mage_Core_Controller_Front_Action {
    
    
    public function sizeGuideAction(){
        $this->loadLayout();
        $this->getLayout()->getBlock('smiffys.sizeguide')->setProductId( $this->getRequest()->getParam('product_id') );
        $this->renderLayout();
    }
    
}