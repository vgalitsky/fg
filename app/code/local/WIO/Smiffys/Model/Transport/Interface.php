<?php
/**
 * @package Smiffys Webservices
 * @autor Victor Galitsky
 * @copyright (c) 2013, Victor Galitsky
 */
interface WIO_Smiffys_Model_Transport_Interface{
    
    public function exec();
    public function getResponse();
    public function getRawResponse();
    public function setResponse();
    public function setRawResponse();
    
    
}