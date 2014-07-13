<?php
class CL_Smiffys_Model_Api_V1_Status extends Mage_Core_Model_Abstract {
    
    const STATUS_IN_PROCESS = 'In process';
    const STATUS_FINISHED = 'Finished';
    const STATUS_ON_HOLD = 'On hold';
    
    const ITEM_DELIMITER = "\n";
    
    const PATH = '/var/status/';
    const EXT = '.status';
    
    protected $path;
    
    protected $id;
    
    protected $_collect;
    
    protected $_started;
    //const 
    
    
    public function __construct( $id = null, $_statusHeader = array() ){
        $this->_statusHeader = $_statusHeader;
        $this->setId( $id );
        $this->started = false;
        
    }
    
    public function setId( $_id ){
        $this->id = $_id;
    }
    
    public function getId(){
        return $this->id;
    }
    
    public function start( ){
        
        if ( !$this->getId() ){
            throw new Exception( 'No status ID set' );
        }
        if (file_exists( $this->getLockFileName() ) ){
            throw new Exception('Process already exists');
        }
        
        $fh = fopen( $this->getFileName(),'w+' );
        fclose($fh);
        $fh = fopen( $this->getStatusFileName(),'w+' );
        fclose($fh);
        $fh = fopen( $this->getDoneFileName(),'w+' );
        fclose($fh);
        
        $fh = fopen( $this->getLockFileName(),'w+' );
        fclose($fh);
        $this->updateStatus(  );
        
    }
    
    public function updateStatus( $status = array() ){
        $fh = fopen( $this->getStatusFileName(), 'w+' );
        fputs( $fh, implode(',', $this->_statusHeader )."\n" );
        fputs( $fh, implode(',', $status ) );
        fclose($fh);
        return $this;
    }

    public function getState(){
        return array(
            'content' => @file_get_contents( $this->getFileName() ),
            'status' => @file_get_contents( $this->getStatusFileName() ),
            'done' => @file_get_contents( $this->getDoneFileName() ),
            'content_f' => $this->getFileName() ,
            'status_f' => $this->getStatusFileName(),
            'done_f' => $this->getDoneFileName(),
        );
    }
    
    public function add( $str, $status = array() ){
        if ( !$this->started ) {
            $this->start();
            $this->started = true;
        }
        
        $fh = fopen( $this->getFileName(),'a+' );
        $str = '['.date('d-m-y H:i:s' ).']: '.$str;
        fputs( $fh , $str . self::ITEM_DELIMITER  );
        fclose( $fh );
        if($status){
            $this->updateStatus( $status ) ;
        }
        return $this;
    }
    
    public function addReverce( $str, $status = array() ){
        $content = file_get_contents( $this->getFileName() );
        $fh = fopen( $this->getFileName(),'w+' );
        $str = '['.date('d-m-y H:i:s' ).']: '.$str;
        fputs( $fh , $str . self::ITEM_DELIMITER . $content );
        fclose( $fh );
        if($status){
            $this->updateStatus( $status ) ;
        }
        return $this;
    }
    
    public function finish(){
        file_put_contents( $this->getDoneFileName() , 'done');
        unlink( $this->getLockFileName() );
        return $this;
    }
    
    public function getFileName(){
        $fullPath =  Mage::getBaseDir().self::PATH . $this->getId();
        if (!is_dir( dirname($fullPath) )  ){
            mkdir( dirname($fullPath) );
        }
        return $fullPath;
    }
    
    public function getStatusFileName(){
        return $this->getFileName() . self::EXT;
    }
    
    public function getDoneFileName(){
        return $this->getStatusFileName().'.done';
    }
    public function getLockFileName(){
        return $this->getStatusFileName().'.lock';
    }
    
    static function getPath(){
        return Mage::getBaseDir().self::PATH;
    }
    

    public function collect( $str ){
        $this->_collect .= $str;
        return $this;
    }
    
    public function flushCollected(){
        $this->add( $this->_collect );
        $this->_collect = '';
        return $this;
    }
        
    
}