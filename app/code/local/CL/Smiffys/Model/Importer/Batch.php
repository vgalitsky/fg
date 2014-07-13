<?php
class CL_Smiffys_Model_Importer_Batch extends Varien_Object{
    
    public function __construct() {
        parent::__construct();
        $this->setStatus( new CL_Smiffys_Model_Api_V1_Status('import-batch') );
    }
    
    public function addActions( $_actions ){
        //Zend_Debug::dump($_actions);
        $this->setActions( array_merge( $_actions ), $this->getActions()  );
        return $this;
    }
    
    public function execute(){
         
        foreach ( $this->getActions() as $action => $onoff ){
            $action = $action.'Batch';
            try{
                $this->$action();
            }
            catch (Exception $e){
                $this->getStatus()->add("Action {$action} terminated with message: " . $e->getMessage());
            }
        }
    }
    
    //--------------------------------------------------------------------------
    
    public function categoriesBatch(){
        
    }
    
}