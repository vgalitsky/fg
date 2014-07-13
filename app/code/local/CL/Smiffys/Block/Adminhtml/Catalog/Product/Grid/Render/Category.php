<?php
class CL_Smiffys_Block_AdminHtml_Catalog_Product_Grid_Render_Category extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract{
    
    static $cacheNames = array();
    
    
    
    public function render(Varien_Object $row){
        if(!is_array(self::$cacheNames)){
            self::$cacheNames = array();
        }
        //Zend_Debug::dump($row);
        $categories = '';
        foreach ( $row->getCategoryIds() as $categoryId ){
            if ( !isset( self::$cacheNames[$categoryId] ) ){
                $categoryName = self::$cacheNames[$categoryId] 
                          =  Mage::getModel( 'catalog/category' )->load( $categoryId )->getName();
            }else{
                $categoryName = self::$cacheNames[$categoryId];
            }
            $categories .= $categories ? ',' : '';
            $categories .= $categoryName;
        }
        return $categories;
    }
}