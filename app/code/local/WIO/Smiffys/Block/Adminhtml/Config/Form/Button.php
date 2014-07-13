<?php
/**
 * @package Smiffys Webservices
 * @autor Victor Galitsky
 * @copyright (c) 2013, Victor Galitsky
 */
class WIO_Smiffys_Block_Adminhtml_Config_Form_Button
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        
        $this->setElement($element);
        $originalData = $element->getOriginalData();
        //Zend_Debug::dump( $this->_configData );die('asdasd');
        //Zend_Debug::dump( $element->getOriginalData() );die('asdasd');
        $url = $this->getUrl( isset($originalData['url'] ) ? $originalData['url'] : false ); //
        $click =  @isset( $originalData['click'] ) ? $originalData['click'] : false; //

        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('scalable')
                    ->setLabel( $originalData['text'] )
                    ->setOnClick( $click ? $click : "setLocation('$url')" )
                    ->toHtml();

        return $html;


        $html = '<button id="'.$element->getHtmlId().'" name="'.$element->getName()
             .'" value="'.$element->getEscapedValue().'" '.$this->serialize($element->getHtmlAttributes())
                .'>'
                .$element->getLabel().'</button>'
                ."\n";
        $html.= $element->getAfterElementHtml();
        return $html;
        
        //return $element->getElementHtml();
    }


}
