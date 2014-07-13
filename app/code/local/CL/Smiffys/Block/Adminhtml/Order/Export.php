<?php
class CL_Smiffys_Block_Adminhtml_Order_Export extends Mage_Adminhtml_Block_Abstract
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_sorders';
        $this->_blockGroup = 'export';
        $this->_headerText = Mage::helper('smiffys')->__('Smiffys webservice - Orders Export');
        
        //$this->_addButtonLabel = Mage::helper('locator')->__('Add Location');
        parent::__construct();
        
    }
}