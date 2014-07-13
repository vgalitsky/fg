<?php
/**
 * @package Smiffys Webservices
 * @autor Victor Galitsky
 * @copyright (c) 2013, Victor Galitsky
 */

class WIO_Smiffys_Model_System_Config_Source_Product_Attributeset {

    public function toOptionArray() {
        $entityTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
        $attributeSetCollection = Mage::getModel('eav/entity_attribute_set')
                ->getCollection()
                ->setEntityTypeFilter( $entityTypeId );
        $options = array();
        foreach ( $attributeSetCollection as $item ) {
            $options[] = array(
                        'value' => $item->getAttributeSetId(),
                        'label' => $item->getAttributeSetName(),
                );

        }
        
        return $options;
    }

}
