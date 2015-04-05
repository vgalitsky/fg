<?php
/**
 * IDEALIAGroup srl
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.idealiagroup.com/magento-ext-license.html
 *
 * @category   IG
 * @package    IG_NamedImages
 * @copyright  Copyright (c) 2010-2011 IDEALIAGroup srl (http://www.idealiagroup.com)
 * @license    http://www.idealiagroup.com/magento-ext-license.html
 */
 
class IG_NamedImages_Model_Product_Image extends Mage_Catalog_Model_Product_Image
{
	public function setBaseFile($file)
	{
		if (!Mage::helper('ig_namedimages')->getIsEnabled())
			return parent::setBaseFile($file);
		
		$product = $this->getProduct();
		$imageName = strtolower(preg_replace('/\W+/', '_', $product->getName())).'.jpg';
		
		parent::setBaseFile($file);

		$this->_newFile = preg_replace('/\/[^\/]+\.\w+$/', '/'.$imageName, $this->_newFile);
		
		return $this;
	}
}