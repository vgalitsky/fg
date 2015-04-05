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

class IG_NamedImages_Helper_Image extends Mage_Catalog_Helper_Image
{
	protected function _getModel()
    {
		if (Mage::helper('ig_namedimages')->getIsEnabled() && !$this->_model->getProduct())
		{
			$this->_model->setProduct($this->getProduct());
		}
		
        return $this->_model;
    }
}
