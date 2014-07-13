<?php

class CL_Smiffys_Model_Adminhtml_Observer {

    public function onBlockHtmlBefore(Varien_Event_Observer $observer) {
//return;
        $block = $observer->getBlock();
        if (!isset($block))
            return;
$collection = Mage::getModel('catalog/category')->getCollection()->addAttributeToSelect('name');
$options = array();
foreach ($collection as $item){
    if($item->getId() != ''){
    $options[$item->getId()] = $item->getName();
}
}
        switch ($block->getType()) {

            case 'adminhtml/catalog_product_grid':
                /* @var $block Mage_Adminhtml_Block_Catalog_Product_Grid */
                $block->addColumn('category_id', array(
                    'position'=>'3',
                    'order' => '2',
                    'type' => 'options',
                    'header' => Mage::helper('smiffys')->__('Category'),
                    'index' => 'category_id',
                    'options'=>$options,
                    'renderer'  => 'CL_Smiffys_Block_Adminhtml_Catalog_Product_Grid_Render_Category'
                ),'sku');
                break;
        }
    }

    public function onProductCollectionAfter(Varien_Event_Observer $observer) {
        $collection = $observer->getCollection();
        if (!isset($collection))
            return;
        if (is_a($collection, 'Mage_Catalog_Model_Resource_Product_Collection')) {
            $collection->addCategoryIds();
        }
        return $this;
    }
    
    public function onEavLoadBefore(Varien_Event_Observer $observer) {
        $collection = $observer->getCollection();
        if (!isset($collection)) return;
        if (is_a($collection, 'Mage_Catalog_Model_Resource_Product_Collection')) {
        //if (is_a($collection, 'Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection')) {
            /* @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
            // Manipulate $collection here to add a COLUMN_ID column
//            die('after');
        }
    }
    

}


    
