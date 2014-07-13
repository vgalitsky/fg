<?php
class CL_Smiffys_Model_System_Config_Source_Updatereplace{

    public function toOptionArray()
  {
    return array(
      array('value' => 'skip', 'label' => 'Skip'),
      array('value' => 'update', 'label' => 'Update'),
      array('value' => 'replace', 'label' => 'Replace'),
      
    );
  }
}
