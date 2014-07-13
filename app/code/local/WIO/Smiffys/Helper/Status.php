<?php
/**
 * @package Smiffys Webservices
 * @autor Victor Galitsky
 * @copyright (c) 2013, Victor Galitsky
 */
class WIO_Smiffys_Helper_Status extends Mage_Core_Helper_Abstract{
    
    protected $_type = null;
    protected $_dataPath = 'smiffys/status/'; // ralative to var


    public function init(){
        @mkdir( 
                Mage::getBaseDir('var').'/'.$this->_dataPath, 0777, true 
                );
        return $this;
    }
    
    
    public function setStatus( $status ){
       $this->init();
       foreach( $status as $statusName => $status ){
           foreach( $status as $type => $data )
           file_put_contents( $this->getFilePath( $statusName, $type ) , $data);
       }
       return $this;
    }
    
    public function getStatus( $pattern, $_decode = true ){
        $status = array();
        foreach ( $pattern as $statusName => $statusPattern ){
            $status[ $statusName ] = array();
            foreach ( $statusPattern as $type => $data  ){
                if(file_exists( $this->getFilePath( $statusName, $type ) )){
                    $status[ $statusName ][$type] =  file_get_contents( $this->getFilePath( $statusName, $type ) );
                    if($_decode){
                        $status[ $statusName ][$type] = json_decode( $status[ $statusName ][$type], true );
                    }
                }
            }
        }
        return $status;
    }
    
    public function clearStatus( $pattern ){
        foreach ( $pattern as $statusName => $statusPattern ){
            foreach ( $statusPattern as $type  ){
                unlink( $this->getFilePath( $statusName, $type ) );
            }
        }
        return $this;
    }
    
    public function getFilePath( $statusName, $type ){
        //die(Mage::getBaseDir('var'));
        return Mage::getBaseDir('var').'/'.$this->_dataPath.$statusName.'.'.$type;
    }
    
}