<?php

class CL_Smiffys_Model_System_Config_Source_Category {

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
//    array(
//      array('value' => 'remote', 'label' => 'Remote'),
//      array('value' => 'local', 'label' => 'Local'),
//      
//    );
    }

}
