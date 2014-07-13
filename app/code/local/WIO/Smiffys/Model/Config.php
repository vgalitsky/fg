<?php
/**
 * @package Smiffys Webservices
 * @autor Victor Galitsky
 * @copyright (c) 2013, Victor Galitsky
 */
class WIO_Smiffys_Model_Config{
    
    
    public function getValue( $path, $store = null ){
        return Mage::getStoreConfig($path, $store);
    }
    
}