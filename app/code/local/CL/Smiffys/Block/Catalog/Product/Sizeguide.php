<?php
class CL_Smiffys_Block_Catalog_Product_Sizeguide extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }

    
    public function getProduct(){
        if(!$this->hasData('product')){
            $product = Mage::getModel('smiffys/product')->load( $this->getProductId() );
            $this->setData('product',$product);
        }
        return $this->getData('product');
    }
}