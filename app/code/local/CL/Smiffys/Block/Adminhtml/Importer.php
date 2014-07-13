<?php
class CL_Smiffys_Block_Adminhtml_Importer extends Mage_Adminhtml_Block_Abstract
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_importer';
        $this->_blockGroup = 'importer';
        $this->_headerText = Mage::helper('smiffys')->__('Smiffys webservice Importer');
        
        //$this->_addButtonLabel = Mage::helper('locator')->__('Add Location');
        parent::__construct();
        
    }
}