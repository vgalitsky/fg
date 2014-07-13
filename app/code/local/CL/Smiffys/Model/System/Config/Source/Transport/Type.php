<?php
class CL_Smiffys_Model_System_Config_Source_Transport_Type{

    public function toOptionArray()
  {
    return array(
      array('value' => 'http', 'label' => 'HTTP GET'),
      array('value' => 'curl', 'label' => 'HTTP Curl'),
      
    );
  }
}
