<?php
class CL_Smiffys_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View {
    public function  __construct() {
        parent::__construct();

        $order = $this->getOrder();
        $this->_addButton('smiffys_export', array(
            'label'     => Mage::helper('smiffys')->__('Smiffys Export'),
            'onclick'   => 'setLocation(\''.$this->getUrl('/sorders/export/').'\')',
            'class'     => 'go'
        ), 0, 100, 'header', 'aheader');
                
        if ( $order->getStatus() == 'ready_to_ship'  ){
            $this->_addButton('tracker', array(
                'label'     => Mage::helper('smiffys')->__('Request Track Code'),
                'onclick'   => 'setLocation(\''.$this->getUrl('/sorders/track/').'\')',
                'class'     => 'gol'
            ), 0, 101, 'header', 'aheader');
        }
    }
}