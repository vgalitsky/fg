<?php
/**
 * @package Smiffys Webservices
 * @autor Victor Galitsky
 * @copyright (c) 2013, Victor Galitsky
 */
class WIO_Smiffys_Adminhtml_WiosmiffysController extends Mage_Adminhtml_Controller_Action{
    

    
    public function importCategoriesAction(){
        try{
            $importer = Mage::getSingleton('wiosmiffys/import_category');
            $result = $importer->import();
            
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('wiosmiffys')->__('Success') );
            $this->_redirect('*/catalog_category/');
        } catch ( Exception $e ){
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('wiosmiffys')->__('Failed: '). $e->getMessage());           
            $this->_redirect('*/system_config/edit/section/wiosmiffys');
        }
        
    }
    
    public function importProductsAction(){
        try{
            $importer = Mage::getSingleton('wiosmiffys/import_product');
            $result = $importer->import();
            
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('wiosmiffys')->__('Success') );
            $this->_redirect('*/catalog_product/');
        } catch ( Exception $e ){
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('wiosmiffys')->__('Failed: '). $e->getMessage());           
            $this->_redirect('*/system_config/edit/section/wiosmiffys');
        }
        
    }
    
    public function ajaxImportProductsAction(){
        exec( 'php '.dirname(__FILE__).'/../../shell/importProducts.php > '.Mage::getBaseDir().'/var/log/product_import.log'.' &' );
    }
    
    public function productImportStatusAction(){
        $pattern = $this->getRequest()->getParam('pattern');
        $status = Mage::helper('wiosmiffys/status');
        $pattern = json_decode($pattern, true);
        $res = $status->getStatus( $pattern );
        die( json_encode( $res ) );
    }
}