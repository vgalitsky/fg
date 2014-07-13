<?php
class CL_Smiffys_Model_Product extends Mage_Catalog_Model_Product{

    const SIZEGUIDE_TYPE_MALE       = 'male';
    const SIZEGUIDE_TYPE_FEMALE     = 'female';
    const SIZEGUIDE_TYPE_CHILD      = 'child';
    const SIZEGUIDE_TYPE_TODDLER    = 'toddler';
    const SIZEGUIDE_TYPE_BABY       = 'baby';
    
    
    public function getSizeGuideType(){
        
        $attributeObj = $this->getResource()->getAttribute("gender");
        $genderLabel = $attributeObj->getSource()->getOptionText($this->getData('gender'));
        
        switch( strtolower( $this->getAudience() ) ){
            case 'adult':
                return strtolower( $genderLabel );
                break;
            default:
                return strtolower( $this->getAudience() );
                break;
        }
    }
    
}