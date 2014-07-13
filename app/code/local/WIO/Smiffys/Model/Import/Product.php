<?php
/**
 * @package Smiffys Webservices
 * @autor Victor Galitsky
 * @copyright (c) 2013, Victor Galitsky
 */
class WIO_Smiffys_Model_Import_Product extends Mage_Core_Model_Abstract {

    
    
    public function __construct(){
        $this->init();
    }
    
    protected function init(){
        $this->getTransport( WIO_Smiffys_Model_Transport_Abstract::CMD_GET_FULL_DATA_SET );    
    }
    
    public function getTransport( $_CMD = null ) {
        if (!$this->_transport) {
            if ( !$_CMD ){
                throw new Exception(' getTrasport(): missing argument: CMD');
            }
            $transport = Mage::getSingleton('wiosmiffys/transport_curl');
            $transport->setCmd( $_CMD );
            $this->_transport = $transport;
        }
        return $this->_transport;
    }
    
    public function import(){
        try{
        $this->initStatus();
        $this->getTransport()->exec();
        $response = $this->getTransport()->getResponse();
//        $response = simplexml_load_file('var/log/smiffys_product.xml');
        $importer = Mage::getModel('wiosmiffys/import_api_v1_smiffys');//new WIO_Smiffys_Model_Api_V1_Smiffys();
        $importer->setXml( $response );
        $importer->import();
        //$this->_import( $response );
        }catch( Exception $e ){
            $statusData = array(
                'product_import' => array(
                    'totals'=>Zend_Json::encode(
                        array(
                        'current'   => $e->getMessage(),
                        'lock'      => 0,
                        ))
            ));
            $status = Mage::helper('wiosmiffys/status');
            $status->setStatus( $statusData );
        }
        return $this;
    }
    
    
    public function initStatus(){
        $_statusData = array(
            'product_import' => array(
                'totals'=>Zend_Json::encode(
                        
                    array(
                    'current'   => 'Smiffys Data has beed requested...<br/>Waiting for response...',
                    'lock' => 1
                    ))
            ));
        $status = Mage::helper('wiosmiffys/status');
        $status->setStatus( $_statusData );
        return $this;
    }
//******************************************************************************    
// DEPRECATED    
//******************************************************************************    
    public function _import( $_response ){
        $productList = $_response->Product;
        foreach ( $productList as $_product ){
            
            $_product = $this->_trimValues( $_product );
            $this->importProduct( $_product );
        }
    }
    
    public function importProduct($_product){
        $productModel = $this->initModel( $_product );
        $productModel = $this->addCategory( $productModel, $_product );
        $productModel = $this->addMedia( $productModel, $_product );
        Zend_Debug::dump($productModel->getData());
    }
    
    public function initModel( $_product ){
        $model = Mage::getModel( 'catalog/product' );
        
        $model = $this->initDefaultModelAttributes( $model, $_product );
        
        
        $model->setData( $_product );
        
        $model = $this->applyAttrMap( $model, $_product );
        return $model;
    }
    
    public function initDefaultModelAttributes($model, $_product){
        
        $model->setAttributeSetId(  )
                ->setTaxClassId(  )
                ->setType( 'simple' )
                ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
                ->setWebsiteIds( array( $this->getImportWebsite() ) )
                ->setStoreId( $this->getImportStore() )
                ->setStatus(  )
                ->setStockData(array(
                            'is_in_stock' => ($_product['StockQuantity'] > 0) ? 1 : 0,
                            'qty' => $_product['StockQuantity']
                ))
                ->setData('is_in_stock', ($_product['StockQuantity'] > 0) ? 1 : 0);


        
        return $model;
    }
    
    public function applyAttrMap( $_model, $_product ){
        foreach( $this->getAttrMap() as $attr => $map ){
            $_model->setData( $attr, $_product[$map] );
        }
        return $_model;
    }
    
    public function addCategory( $_model, $_product ){
        $category = Mage::getModel('catalog/category')
                ->loadByAttribute('smiffys_code', $_product['CatalogueCode'] );
        if($category && $category->getId()){
            $_model->setCategoryIds( $category->getId() );
        }else{
            $catId = Mage::getSingleton('wiosmiffys/config')->getValue('wiosmiffys/import_product/drop_category');
            $_model->setCategoryIds( $catId );
        }
        return $_model;
    }
    
    public function addMedia( $_model, $_product ){
        return $_model;
    }
    
    public function getAttrMap(){
        return array(
          'sku'         => 'ProductCode',
          'name'        => 'ProductName',
          'description' => 'WebDescription',
          'qty'         => 'StockQuantity',
          'price'       => 'Price1',
        );
    }
    
    protected function _trimValues( $_src ){
        $arr = array();
        foreach( $_src as $k => $v ){
            $arr[$k] = trim($v);
        }
        return $arr;
    }
    
    
}