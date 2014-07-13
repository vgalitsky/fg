<?php
class CL_Smiffys_Block_Adminhtml_Product_Import extends Mage_Adminhtml_Block_Abstract
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_sproducts';
        $this->_blockGroup = 'import';
        $this->_headerText = Mage::helper('smiffys')->__('Smiffys webservice - Products import');
        
        //$this->_addButtonLabel = Mage::helper('locator')->__('Add Location');
        parent::__construct();
        
    }
}