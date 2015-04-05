<?php
/**
 * @package Smiffys Webservices
 * @autor Victor Galitsky
 * @copyright (c) 2013, Victor Galitsky
 */
abstract class WIO_Smiffys_Model_Transport_Abstract extends Mage_Core_Model_Abstract
//implements WIO_Smiffys_Model_Transport_Interface
{
    
    const CMD_GET_FULL_DATA_SET                 =  'GetFullDataSet';
    const CMD_GET_STOCK_QUANTITIES_LIGHT_XML     =  'GetStockQuantities_LightVersion_XML';
    
    const CMD_GET_CATEGORIES_LIST                = 'GetCategoryList';
    
    const CMD_ORDER_SUBMIT                      =  'SubmitOrder'bg_nav;
    const CMD_ORDER_GET_STATUS                  =  'GetStatus';
    
    const SERVICE_PRODUCTS                      = 'products.asmx';
    const SERVICE_ORDERS                        = 'orders.asmx';
    
    const RESULT_CODE_SUCCESS   = 'Successful';
    const RESULT_CODE_ERROR     = 'Error';

    protected $_webservice;
    protected $_cmd;
    protected $_response;
    protected $_rawResponse;
    
    protected $_credentials = array();
    
    
    
    
    
    public function getConfig(){
        return Mage::getSingleton('wiosmiffys/config');
    }

    public function setWebservice( $_webservice ){
        $this->_webservice = $_webservice;
        return $this;
    }
    public function getWebservice(){
        return $this->_webservice;
    }
    
    public function setCmd( $_cmd ){
        $this->_cmd  = $_cmd;
        return $this;
    }
    
    public function getCmd(){
        return $this->_cmd;
    }
    
    public function setCredentials( $_credentials ){
        $this->_credentials = $_credentials;
        return $this;
    }
    
    public function getCredentials(){
        return $this->_credentials;
    }
    
    public function setResponse( $_response ){
        $this->_response = $_response;
        return $this;
    }
    
    public function getResponse(){
        return $this->_response;
    }
    
    public function setRawResponse( $_raw ){
        $this->_rawResponse = $_raw;
    }
    
    public function getRawResponse(){
        return $this->_rawResponse;
    }
    
    
    
    public function exec(){
        $this->_exec();
        if(method_exists($this, 'logAll')){
            $this->logAll();
        }
        return $this;
    }
    
    
    
}