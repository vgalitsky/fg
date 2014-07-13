<?php
abstract class CL_Smiffys_Model_Transport_Abstract extends Mage_Core_Model_Abstract {

    const CMD_GET_FULL_DATA_SET                  =  'GetFullDataSet';
    const CMD_GET_STOCK_QUANTITIES_LIGHT_XML    =  'GetStockQuantities_LightVersion_XML';
    const CMD_GET_RELATED_PRODUCTS              =  'GetProduct_CatalogueAccessories';
    const CMD_GET_UPSELL_PRODUCTS              =  'GetProduct_CatalogueAlternatives';
    const CMD_ORDER_SUBMIT                      =  'SubmitOrder';
    const CMD_ORDER_GET_STATUS                  =  'GetStatus';
    
    
    const RESULT_CODE_SUCCESS   = 'Successful';
    const RESULT_CODE_ERROR     = 'Error';
    
    public $_credentials = array();
    
    public function __construct( $_queryArr = array() ){
        
        $this->_credentials = array_merge( $_queryArr,
                array(
                    'clientId' => Mage::getStoreConfig( 'smiffys/credentials/client_id' ),
                    'apiKey' => Mage::getStoreConfig( 'smiffys/credentials/api_key' ),
                    'LanguageCode' => 'DE',
                ));
        $this->setCredentials( $this->_credentials );
        $this->setWebservice( Mage::getStoreConfig( 'smiffys/credentials/webservice' ) );
        
    }
    
    public function getCredentials(){
        return $this->_credentials;
    }
    
    abstract public function prepareRequest();
    abstract public function exec();
    abstract public function getResult();
    
}