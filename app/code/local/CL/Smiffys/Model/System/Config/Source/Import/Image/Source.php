<?php
class CL_Smiffys_Model_System_Config_Source_Import_Image_Source{

    public function toOptionArray()
  {
    return array(
      array('value' => 'remote', 'label' => 'Remote'),
      array('value' => 'local', 'label' => 'Local'),
      
    );
  }
}
