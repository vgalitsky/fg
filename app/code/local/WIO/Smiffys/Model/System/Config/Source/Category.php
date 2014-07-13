<?php
/**
 * @package Smiffys Webservices
 * @autor Victor Galitsky
 * @copyright (c) 2013, Victor Galitsky
 */
class WIO_Smiffys_Model_System_Config_Source_Category {

    public function toOptionArray() {
        $collection = Mage::getModel('catalog/category')->getCollection()->addAttributeToSelect('name');
        $options = array();
        foreach ($collection as $item) {
            if ($item->getId() != '') {
                $options[] = array(
                        'value' => $item->getId(),
                        'label' => $item->getName(),
                );
            }
        }
        return $options;
    }

}
