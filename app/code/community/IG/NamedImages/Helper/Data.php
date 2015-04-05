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
 
class IG_NamedImages_Helper_Data extends Mage_Core_Helper_Abstract
{
	const XML_PATH_ENABLED = 'ig_namedimages/general/enabled';
	
	/**
	 * Check if component is enabled or not
	 *
	 * @return bool
	 */
	public function getIsEnabled()
	{
		return Mage::getStoreConfig(self::XML_PATH_ENABLED) ? true : false;
	}
}
