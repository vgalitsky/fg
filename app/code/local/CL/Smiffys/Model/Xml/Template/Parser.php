<?php
class CL_Smiffys_Model_Xml_Template_Parser extends Mage_Core_Model_Abstract {
    
    static function parse( $xml, $data ){
        preg_match_all('/\{\{(.*?)\}\}/sumix', $xml, $matches );
        if ( count( $matches ) ){
            foreach ( $matches[0] as $i => $match){
                $xml = preg_replace( '/'.preg_quote( $match ).'/sumix', $data[$matches[1][$i]], $xml );
            }
        }
        return $xml;
    }
    
}