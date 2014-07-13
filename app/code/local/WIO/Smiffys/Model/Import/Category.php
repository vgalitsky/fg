<?php
/**
 * @package Smiffys Webservices
 * @autor Victor Galitsky
 * @copyright (c) 2013, Victor Galitsky
 */
class WIO_Smiffys_Model_Import_Category {

    
    protected $_transport = null;
    protected $_rootId = 2;
    protected $_rootCategory;
    protected $_storeId;
    protected $_parentMap = array();

    public function __construct() {
        $this->_init();
    }
    
    protected function _init(){
        $this->_storeId = Mage::getStoreConfig('wiosmiffys/general/import_store');
        $this->prepareRootCategory();
    }

    public function getTransport() {
        if (!$this->_transport) {
            $transport = Mage::getSingleton('wiosmiffys/transport_curl');
            $transport->setCmd($transport::CMD_GET_CATEGORIES_LIST);
            $this->_transport = $transport;
        }
        return $this->_transport;
    }

    public function import() {
        $this->getTransport()->exec();
        $this->_import($this->getTransport()->getResponse());
        return $this;
    }

    protected function _import($_categories) {
        foreach ($_categories as $category) {
            $category = $this->prepareCategory($category);
            $this->_importCategory($category);
        }
        return $this;
    }

    protected function prepareCategory($category) {
        $cat = array();
        foreach ($category as $k => $v) {
            $cat[$k] = trim($v);
        }
        return $cat;
    }

    protected function _importCategory($_category) {
        if ($_category['Level'] <= 1) {
            $category['Parent'] = $this->_rootId;
        }
        $category = Mage::getModel('catalog/category');

        $category->setStoreId($this->_storeId);

        $categoryData['name'] = $_category['Name'];
        $categoryData['path'] = $this->getPath($_category);
        $categoryData['description'] = $_category['LongDesc'];
        $categoryData['smiffys_code'] = $_category['Code'];
        $categoryData['is_active'] = 1;
        $categoryData['is_anchor'] = 1;

        $category->addData($categoryData);

        $category->save();

        $this->parentMap[$_category['ID']] = $category->getId();
        return $this;
    }

    public function getPath($_category) {
        if ($_category['Parent']) {
            $category = Mage::getModel('catalog/category')
                    ->load($this->parentMap[$_category['Parent']]);
        } else {
            $category = $this->_rootCategory;
        }
        return $category->getPath();
    }

    public function prepareRootCategory() {
        $rootName = Mage::getSingleton('wiosmiffys/config')->getValue('wiosmiffys/import_category/root_category');
        //$rootName = 'Default Category';
        $this->_rootCategory = Mage::getModel('catalog/category')->loadByAttribute('name', $rootName);
        if ($this->_rootCategory && $this->_rootCategory->getId()) {
            throw new Exception("Warning: Such root category already exists.Please specify unique name. Also make sure configuration is saved.");
        }

        $category = Mage::getModel('catalog/category');

        $category->setStoreId( $this->_storeId );

        $categoryData['name'] = $rootName;
        $categoryData['path'] = '1';
        $categoryData['description'] = 'Smiffys root category';
        $categoryData['is_active'] = 1;

        $category->addData( $categoryData );

        $category->save();

        if ( !$this->_rootId = $category->getId() ) {
            throw new Exception('Failed to create root category');
        }
        $this->_rootCategory = $category;

        return $this;
    }

}