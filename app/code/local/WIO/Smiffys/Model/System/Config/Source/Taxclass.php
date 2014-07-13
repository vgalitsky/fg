<?php
/**
 * @package Smiffys Webservices
 * @autor Victor Galitsky
 * @copyright (c) 2013, Victor Galitsky
 */
class WIO_Smiffys_Model_System_Config_Source_Taxclass {

    public function toOptionArray() {
        $entityTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
        $collection = Mage::getModel('tax/class')
                ->getCollection()
                ->setClassTypeFilter('PRODUCT')
//                ->setEntityTypeFilter(  );
                ;
        
        $options = array(0=>array('value'=>0,'label'=>'None'));
        foreach ($collection as $item) {
            //Zend_Debug::dump( $item );die();
            
                $options[] = array(
                        'value' => $item->getClassId(),
                        'label' => $item->getClassName(),
                );
        }
        return $options;
    }

}
