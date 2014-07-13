<?php
/**
 * @package Smiffys Webservices
 * @autor Victor Galitsky
 * @copyright (c) 2013, Victor Galitsky
 */
class WIO_Smiffys_Model_Transport_Curl
extends WIO_Smiffys_Model_Transport_Abstract{
    
    
    public function __construct( $_queryArr = array() ){
        
        $this->_credentials = array_merge( $_queryArr,
                array(
                    'clientId' => $this->getConfig()->getValue( 'wiosmiffys/credentials/client_id' ),
                    'apiKey' => $this->getConfig()->getValue( 'wiosmiffys/credentials/api_key' )
                ));
        $this->setCredentials( $this->_credentials );
        $this->setWebservice( $this->getConfig()->getValue( 'wiosmiffys/credentials/webservice_products' ) );
    }
    
    public function _exec(){
       try{
        $this->prepareRequest();
        
        $c = curl_init(   $this->getHttpPostUrl() );
        curl_setopt($c, CURLOPT_HEADER, 0);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_BINARYTRANSFER,1);
        curl_setopt($c, CURLOPT_POST, true);

        curl_setopt($c, CURLOPT_POSTFIELDS, $this->getHttpPostVars() );

        $rawResult = curl_exec($c);
        if (is_bool( $rawResult ) && ! $rawResult){
            throw new Exception( 'Check you internet connection' );
        }
        $this->setRawResponse( $rawResult );
            try{
                $xml = simplexml_load_string( $rawResult );
            }catch(Exception   $e){
                throw new Exception( 'Wrong data received. Please check credentials.' );
            }
        }catch( Exception $e ){
            throw new Exception( $e->getMessage().' : '.$rawResult );
        }
        
        $this->setResponse( $xml );
        return $this;
    }
    
    public function prepareRequest(){
        $url = $this->getWebservice()
                .'/'
                .$this->getCmd();
        $this->setHttpPostUrl( $url );
        $this->setHttpPostVars( http_build_query( $this->getCredentials() ) );
    }
    
    public function logAll(){
        $filename = $this->getCmd().'-'.date('d.m.y H:i:s');
        Mage::log( $this->getRawResponse(), null, $this->getCmd().'-'.date('d.m.y H:i:s').'.log' );
        //die('var/log/smiffys/'.$filename);
        return $this;
    }
    
}