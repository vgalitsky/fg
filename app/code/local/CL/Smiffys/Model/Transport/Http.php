<?php
class CL_Smiffys_Model_Transport_Http extends CL_Smiffys_Model_Transport_Abstract {

    public function prepareRequest(){
        $http = $this->getWebservice()
              .'/'
              . $this->getApi()
              .'/'
              . $this->getCmd();
        
        $this->setHttpPostUrl( $http );
        $this->setHttpPostVars( http_build_query($this->getCredentials()) );
        
        $http.='?'. http_build_query($this->getCredentials());
        
        $this->setHttpGetUrl( $http );
        return $this;
    }
    
    public function exec(){
        $this->prepareRequest();
        $rawResult = file_get_contents( $this->getHttpGetUrl() );
        $this->setRawData( $rawResult );
        $xml = simplexml_load_string( $rawResult );
        $this->setResultData( $xml );
        return $this;
        
    }
    
    public function getResult(){
        return $this->getResultData();
    }
    public function getRawResult(){
        return $this->getRawData();
    }
    
}