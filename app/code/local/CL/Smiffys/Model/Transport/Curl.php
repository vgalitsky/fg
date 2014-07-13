<?php
class CL_Smiffys_Model_Transport_Curl extends CL_Smiffys_Model_Transport_Http {

   
    public function exec(){
        $this->prepareRequest();
        
        $c = curl_init(   $this->getHttpPostUrl() );
        curl_setopt($c, CURLOPT_HEADER, 0); 
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_BINARYTRANSFER,1);
        curl_setopt($c, CURLOPT_POST, true);

        curl_setopt($c, CURLOPT_POSTFIELDS, $this->getHttpPostVars() );

//                curl_setopt($c, CURLOPT_TIMEOUT, 20); // sec
//                curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20); // sec
        //curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE); // Follow redirects - error on shared hsotin
//                curl_setopt($c, CURLOPT_USERAGENT, $agent);
        $rawResult = curl_exec($c);
        
                
        $this->setRawData( $rawResult );
        $xml = simplexml_load_string( $rawResult );
        $this->setResultData( $xml );
        return $this;
        
    }
    
}