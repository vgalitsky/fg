<?php

class CL_Smiffys_Model_Product_S2c extends CL_Smiffys_Model_Product {

    const STATUS_ID = 's2c';
    
    public function __construct() {
        parent::__construct();
        $this->setStatusHandler( new CL_Smiffys_Model_Api_V1_Status( self::STATUS_ID /*, array('total','imported','exists','errors', 'warnings')*/) );
    }
    
    public function process() {
        $this->getStatusHandler()->add('{START}');
        $attr_size = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', 'size');

        $confAttributes = array($attr_size);
        $productCollection = Mage::getModel('catalog/product')
                ->getCollection()
                ->addAttributeToSelect('genericcode')
                ->addAttributeToFilter('type_id', 'simple');

        $gCodes = array();
        foreach ($productCollection as $product) {
            $gCodes[$product->getGenericcode()][] = $product->getId();
        }
//Zend_Debug::dump($gCodes);
        $limit = 0;
        foreach ($gCodes as $simpleSet) {
            if (count($simpleSet) <= 1) {
                continue;
            }
            $this->createConfigurable($simpleSet, $confAttributes);
            $limit++;
//            if($limit>5) break;
        }
        $this->getStatusHandler()->finish();
    }

    function createConfigurable($simpleSet, $confAttributes) {
        
        $updated = $this->getUpdated();
        $skipped = $this->getSkipped();
        
        $productsData = array();
        $simpleProducts = array();
        foreach ($simpleSet as $simpleId) {
            $simpleProducts[] = $simpleProduct = Mage::getModel('catalog/product')->load($simpleId);
            
            
            
            $existsConfigurable = Mage::getModel('catalog/product');
            $sku = $this->makeSku($simpleProduct);
            $existsConfigurable->load( $existsConfigurable->getIdBySKU( $sku ) );
            if ($existsConfigurable->getId()) {
                $skipped++;
                
                $this->setSkipped( $skipped );
                $this->getStatusHandler()->add("{WARNING}Already exists:" . $existsConfigurable->getSku());
                
                return false;
            }
            //$simpleProduct->setStatus( 0 );
            $simpleProduct->setVisibility(1);
            $simpleProduct->save();
        }
        $this->getStatusHandler()->add("Processing: {$sku}");
        
        $configurableProduct = $this->initConfigurableProduct($simpleProducts[0]);
        
        
        $configurableProduct->setCanSaveConfigurableAttributes(true);

        $configurableProductsData = $this->createAssocProductsData($simpleProducts, $confAttributes);

        $configurableProduct->setConfigurableProductsData($configurableProductsData);
        $configurableProduct = $this->confSetAttrsData($configurableProduct, $confAttributes);


        $configurableProduct->setCanSaveConfigurableAttributes(true);
        
        $this->addRelatedProducts($configurableProduct, $simpleProducts);
        $this->addUpSellsProducts($configurableProduct, $simpleProducts);
        
                


        $configurableProduct->save();
        
        $updated++;
        $this->setUpdated( $updated );
        $this->setSkipped( $skipped );
        $this->getStatusHandler()->add("OK");
        
        return $this;
    }

    function createAssocProductsData($simpleProducts, $attributes) {
        $productsData = array();
        foreach ($simpleProducts as $simpleProduct) {
            foreach ($attributes as $attr) {
                $productsData[$simpleProduct->getId()][] = array(
                    'attribute_id' => $attr->getId(),
                    'label' => $attr->getFrontendLabel(),
                    'value_index' => $simpleProduct->getData($attr->getAttributeCode()),
                    'pricing_value' => 0,
                    'is_percent' => 0,
                );
            }
        }
        return $productsData;
    }

    function confSetAttrsData($configurableProduct, $attributes) {
        $usingAttributeIds = array();
        foreach ($attributes as $attr) {
            if ($configurableProduct->getTypeInstance()->canUseAttribute($attr)) {
                array_push($usingAttributeIds, $attr->getAttributeId());
            }
        }
        $configurableProduct->getTypeInstance()->setUsedProductAttributeIds($usingAttributeIds);
        $configurableProduct->setConfigurableAttributesData($configurableProduct->getTypeInstance()->getConfigurableAttributesAsArray());

        $configurableProduct->setCanSaveConfigurableAttributes(true);
        $configurableProduct->setCanSaveCustomOptions(true);
        return $configurableProduct;
    }

    function initConfigurableProduct($product) {
        //  Zend_Debug::dump($product->getData());

        $configurableProduct = Mage::getModel('catalog/product');

        $copyAttrs = array(
            'name',
            'price',
            'weight',
            'description',
            'short_description',
            'attribute_set_id',
            'status',
            'tax_class_id',
            'is_in_stock',
            
            'washinginstructions',
            'gender',
            'packtype',
            'packqty',
            'audience',
            'furtherdetails',
            'colour',
            'BarCode',
            'unit_size',
            'warnings',
            'carton',
            'seasonal',
            'themename',
            'additionaltheme',
            'theme1',
            'groupname',
            'themegroup1',
            'themegroup2',
            'themegroup3',
            'genericcode',
            'rrp',
            'break1',
            'break2',
            'break3',
            'eta',
            'min_qty',
            
            
        );

        foreach ($copyAttrs as $attr) {
            $configurableProduct->setData($attr, $product->getData($attr));
        }


//        $configurableProduct->setStoreId($product->getStockItem()->getStoreId());
//        $configurableProduct->setWebsiteIds(array(Mage::app()->getStore(true)->getWebsite()->getId()));
        
        $configurableProduct->setStoreId($product->getStoreId());
        $configurableProduct->setWebsiteIds($product->getWebsiteIds());
        $configurableProduct->setTypeId('configurable');
        $configurableProduct->setSku($this->makeSKU($product));
        $configurableProduct->setVisibility(4);
        $categoryIds = array();
        if ( Mage::getStoreConfig('smiffys_catalog/s2c/category') == 'same'){
            $categoryIds = $product->getCategoryIds();
            
        } else {
            $categoryIds = array( Mage::getStoreConfig('smiffys_catalog/s2c/default_category') );
        }

        $configurableProduct->setCategoryIds( $categoryIds );
        //$this->getStatusHandler()->add('CagegoryIds:' . $categoryIds );
        
          $stockData['is_in_stock'] = 1;
          $stockData['manage_stock'] = 1;
          $stockData['use_config_manage_stock'] = 0;
          $configurableProduct->setStockData($stockData);
         
        

//Zend_Debug::dump($configurableProduct->getData())    ;

        $this->addMedia($configurableProduct, $product->getSku());

        return $configurableProduct;
    }

    function makeSku($product) {
        return preg_replace('/[^0-9]+/sumix', '', $product->getSku());
    }

    function addMedia($product, $img) {
        $img = CL_Smiffys_Model_Api_V1_Smiffys::getDataPath().'images/' . trim($img) . '.jpg';
        if (@getimagesize($img) !== FALSE) {
            $product->addImageToMediaGallery($img, array('thumbnail', 'small_image', 'image'), FALSE);
        }
    }
    
    function addRelatedProducts( $product, $simples ){
        $product->getLinkInstance()->useRelatedLinks();
        $linkData = array();
        foreach( $simples as $simple ){
            $relatedIds = $simple->getRelatedProductIds();
            $linkData = array_merge( $linkData, $relatedIds );
        }
        $relatedLinkData = array_flip( $linkData );
        if ( !count( $relatedLinkData ) ) return;
        $product->setRelatedLinkData( $relatedLinkData );
        //$product->save();
        $this->getStatusHandler()->add( 'Added related products: '.implode(',', $linkData) );
    }
    
    function addUpSellsProducts( $product, $simples ){
        $product->getLinkInstance()->useUpSellLinks();
        $linkData = array();
        foreach( $simples as $simple ){
            $upsellIds = $simple->getUpsellProductIds();
            $linkData = array_merge( $linkData, $upsellIds );
        }
        $upSellLinkData = array_flip( $linkData );
        if ( !count( $upSellLinkData ) ) return;
//Zend_Debug::dump( $upSellLinkData );

        $product->setUpSellLinkData( $upSellLinkData );
        //$product->save();
        $this->getStatusHandler()->add( 'Added UpSells: '.implode(',', $linkData) );
    }
    
    public function updateSimpleNames(){
        $this->getStatusHandler()->setId('names');
        $collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('type_id', array('eq' => 'configurable'));
        foreach( $collection as $product ){
            $this->getStatusHandler()->add( $product->getName().'('.$product->getSku().')... ' );
            
            
            $conf = Mage::getModel('catalog/product_type_configurable')->setProduct($product);
            $col = $conf->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
            foreach($col as $simple_product){
                $simple_product->setName( $product->getName() );
                $simple_product->save();
                $this->getStatusHandler()->add( $simple_product->getName().'('.$product->getSku().') > '. $product->getName()  );
                //var_dump($simple_product->getId());    
            }
            
        }
        $this->getStatusHandler()->finish();
    }

}