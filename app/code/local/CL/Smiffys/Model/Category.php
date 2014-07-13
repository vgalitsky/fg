<?php   
class CL_Smiffys_Model_Category extends Mage_Core_Model_Abstract{
    
    const STATUS_ID = 'category';
    
    public function __construct(){
        $this->setStatus( new CL_Smiffys_Model_Api_V1_Status( self::STATUS_ID ) );
    }
    
    public function getDefaultFileName(){
        return CL_Smiffys_Model_Api_V1_Smiffys::getDataPath().'category_tree.csv';
    }
    
    public function exportTree( $filename ){
        $filename = $filename ? $filename : $this->getDefaultFileName();
        $tree = Mage::getModel('catalog/category')->getTreeModel()->load();
        $ids = $tree->getCollection();
        $csv = '';
        foreach( $ids as $id ){
            $header = implode(',',array_keys($id->getData()));
            if ( !$csv ){
                 $csv.=$header."\n";
            }
            $csv .= implode(',', $id->getData() )."\n";
        }
        file_put_contents( $filename, $csv );
        return $this;
    }
    
    public function importTree( $filename ){
        $filename = $filename ? $filename : $this->getDefaultFileName();
        $fh = fopen( $filename ,'rt' );
        $header =   fgetcsv($fh);
        $i=0;
        while( $csv = fgetcsv($fh) ){
            $categoryData = array();
            
            foreach( $header as $k => $field ){
                $categoryData[$field] = $csv[$k];
            }
            
            if ( $categoryData['entity_id'] <=2 ) continue;
            
            $i++;
            //unset($categoryData['children_count']);
            unset($categoryData['created_at']);
            unset($categoryData['updated_at']);
            //unset($categoryData['path']);
            
            $categories[$categoryData['entity_id']] = $categoryData;
        }
        $this->setCategories( $categories );
        
        fclose($fh);
        
        foreach ( $categories as $categoryId => $category ){
            $this->importCategoryWithParents( $categoryId );
        }
        
        $this->getStatus()->add('*********************************');
        $this->getStatus()->add('{DONE}');
        $this->getStatus()->finish();
        
        return $this;
        
    }
    
    public function importCategoryWithParents( $categoryId ){
            $categories = $this->getCategories();
            $categoryData = $categories[ $categoryId ];
            
            if( $categoryData['parent_id'] ){
                $parent = Mage::getModel('catalog/category')->load( $categoryData['parent_id'] );
                if ( !$parent->getId() ){
                    $this->importCategoryWithParents( $categoryData['parent_id'] );
                }
            }
            
            $category = Mage::getModel('catalog/category')->load( $categoryData['entity_id'] );
            
            if ( $category->getId()) return;
            
            $category->setStoreId( 1 );
            $category->setData( $categoryData );
            
            
            //$category->setData( $categoryData );
            //Zend_Debug::dump($category->getData());
            //$category->setId( $categoryData['entity_id'] );
            //$category->setParentId( $categoryData['parent_id'] );
            //$category->setName( $categoryData['name'] );
            
            $category->setIsAnchor( 1 );
            //$category->setIsActive( 0 );
            
            $this->getStatus()->add('Importing category:['.$category->getId().'] '.$category->getName() );
            try{
                $category->save();    
                $this->getStatus()->add('{OK} Success');
                
            } catch(Exception $e){
                $this->getStatus()->add('{ERROR} FAILED: '.$e->getMessage());
                
            }
            
        
    }
}
