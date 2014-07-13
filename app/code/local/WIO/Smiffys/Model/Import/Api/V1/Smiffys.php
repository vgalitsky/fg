<?php
/**
 * @package Smiffys Webservices
 * @autor Victor Galitsky
 * @copyright (c) 2013, Victor Galitsky
 */
require_once "WIO/Smiffys/Model/Import/Api/V1/CLAttr.php";
class WIO_Smiffys_Model_Import_Api_V1_Smiffys extends Mage_Core_Model_Abstract {


    protected $_imageUrl;
    protected $_xml;
    protected $_existingCount;
    protected $_importedCount;
    protected $_processed;
    protected $_errors;
    protected $_errstrs;
    protected $_warnings;
    
    const STATUS_NAME = 'product_import';

    const DATA_PATH = '/Smiffys/data/';
    const STATUS_ID = 'products';
    const STATUS_PRICE_ID = 'price';

    /**
     * Constructor
     */
    static function getDataPath() {
        return Mage::getBaseDir() . self::DATA_PATH;
    }

    public function __construct() {

        
        $this->_loadConf();
    }

   public function setXml( $_xml ){
       $this->_xml = $_xml;
       return $this;
   }


    protected function _loadConf() {
        $this->setImportLimit(Mage::getStoreConfig('wiosmiffys/import_product/limit'));
        
        $this->setImportWebsite(Mage::getStoreConfig('wiosmiffys/general/import_website'));
        $this->setImportStore(Mage::getStoreConfig('wiosmiffys/general/import_store'));

        $this->setImportTaxClassId(Mage::getStoreConfig('wiosmiffys/import_product/import_tax_class'));
        $this->setImportAttributeSetId(Mage::getStoreConfig('wiosmiffys/import_product/import_attribute_set_id'));
        $this->setDropCategoryId(Mage::getStoreConfig('wiosmiffys/import_product/drop_category_id'));

        $this->setImagesLocalPath(Mage::getStoreConfig('wiosmiffys/import_product/img_path'));
        $this->_imageUrl = Mage::getStoreConfig('wiosmiffys/import_product/images_url');
    }


    /**
     * Import the products
     */
    public function import() {
        $this->_total = count( $this->_xml->Product );
        $this->_imported = 0;
        $this->_existing = 0;
        $this->_processed = 0;
        $this->_errors = 0;
        $this->_warnings = 0;

        $this->_getAttributeMapResource()->loadAll();


        $imported_sku = array();

        $product = null;
        foreach ($this->_xml->Product as $product) {
            if( $this->getImportLimit() && $this->_processed >= $this->getImportLimit()) 
                break;
            
            if ($this->_productExists($product->ProductCode)) {
                $this->_exists++;
                $this->_processed++;
                $this->updateIteratorStatus( $product );
                continue;
            }

            if ( $importedProduct = $this->_importProduct($product)) {
                $imported_sku[] = trim($product->ProductCode);
                $this->_imported++;
                
            } else {
                $this->_errors++;
            }
            $this->_processed++;
            
            $this->updateIteratorStatus( $product, true, $importedProduct );
        }
        
        
        $product->ProductCode = '';
        $product->ProductName = 'Adding Related items and Upsells...';
        
                
        $this->updateIteratorStatus($product, false);
        
        foreach ($this->_xml->Product as $product) {
            /** Exclude from import * */
            if (in_array(trim($product->ProductCode), $imported_sku)) {
                
                $this->_insertRelatedItems($product);
                $this->_insertUpsells($product);
            }
        }
        
        $product->ProductName = 'Finishing...';
        $this->updateIteratorStatus($product, false);
        
    }
    
    

    protected $_attributeMapResource = null;

    
    protected function _getAttributeMapResource() {
        if (is_null($this->_attributeMapResource)) {
            $this->_attributeMapResource = new CL_ImportExport_Model_Mysql4_Attribute_Map();
        }
        return $this->_attributeMapResource;
    }

    /**
     * Get the total amount of products in the feed
     * @return int
     */
    public function getProductCount() {
        return count($this->_xml);
    }

    /**
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     * @return bool
     */
    public function isMultiselectAttribute(Mage_Eav_Model_Entity_Attribute $attribute) {
        return $attribute->getBackendType() == 'varchar' && $attribute->getFrontendInput() == 'multiselect';
    }

    /**
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     * @return bool
     */
    public function isSelectAttribute(Mage_Eav_Model_Entity_Attribute $attribute) {
        return $attribute->getBackendType() == 'int' && $attribute->getFrontendInput() == 'select';
    }

    protected $_optionsResource = null;

    
    public function _getOptionsResource() {
        if (is_null($this->_optionsResource)) {
            $this->_optionsResource = new CL_ImportExport_Model_Mysql4_Attribute_Options();
        }
        return $this->_optionsResource;
    }

    /**
     * Import the product into Magento
     * @return bool
     */

    public function makePrice($EFPrice) {
        return (double) $EFPrice * 7.5 * 2 * 1.25;
    }

    public function makeCost($EFPrice) {
        return (double) $EFPrice * 7.5 * 1.25;
    }

    protected function _importProduct(SimpleXMLElement $product) {
        $productModel = Mage::getModel('catalog/product');
        $productModel->setSku(trim((string) $product->ProductCode));
        $productModel->setAttributeSetId($this->getImportAttributeSetId());
        $productModel->setTaxClassId($this->getImportTaxClassId());
        $productModel->setTypeId('simple');
        $productModel->setName((string) $product->ProductName);
        $productModel->setDescription((string) $product->BrochureDescription); // Use BrochureDescription?
        $productModel->setShortDescription((string) $product->WebDescription);
        $productModel->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
        
        $productModel->setCategoryIds(array($this->getCategoryId( $product )));
        
        $productModel->setWebsiteIds(array($this->getImportWebsite()));
        $productModel->setStoreId($this->getImportStore());

        $productModel->setStatus(1); // Disabled - 1 is Enabled
        $productModel->setCost($this->makeCost($product->EFPrice));
        $productModel->setPrice($this->makePrice($product->EFPrice));
//        $productModel->setVejlpris($product->EFPrice * 7.45 * 2 * 1.25);

        $productModel->setStockData(array(
            'is_in_stock' => ($product->StockQuantity > 0) ? 1 : 0,
            'qty' => $product->StockQuantity
        ));
        $productModel->setData('is_in_stock', ($product->StockQuantity > 0) ? 1 : 0);

        $productModel->setCreatedAt(time());
        $productModel->setWeight($product->Weight ? $product->Weight : 1);
        $productModel->setGender($product->Gender);

        $customFields = array(
            array('product_field' => 'washinginstructions', 'source_field' => 'WashingInstructions', 'options' => array()),
            array('product_field' => 'gender', 'source_field' => 'Gender', 'options' => array()),
            array('product_field' => 'packtype', 'source_field' => 'PackType', 'options' => array()),
            array('product_field' => 'packqty', 'source_field' => 'PackQty', 'options' => array()),
            array('product_field' => 'audience', 'source_field' => 'Audience', 'options' => array()),
            array('product_field' => 'furtherdetails', 'source_field' => 'FurtherDetails', 'options' => array()),
            array('product_field' => 'colour', 'source_field' => 'Colour', 'options' => array()),
            array('product_field' => 'BarCode', 'source_field' => 'barcode', 'options' => array()),
            array('product_field' => 'unit_size', 'source_field' => 'unit_size', 'options' => array()),
            array('product_field' => 'warnings', 'source_field' => 'warnings', 'options' => array()),
            array('product_field' => 'carton', 'source_field' => 'carton', 'options' => array()),
            array('product_field' => 'seasonal', 'source_field' => 'Seasonal', 'options' => array()),
            array('product_field' => 'themename', 'source_field' => 'ThemeName', 'options' => array()),
            array('product_field' => 'additionaltheme', 'source_field' => 'AdditionalTheme', 'options' => array()),
            array('product_field' => 'theme1', 'source_field' => 'Theme1', 'options' => array()),
            array('product_field' => 'groupname', 'source_field' => 'GroupName', 'options' => array()),
            array('product_field' => 'themegroup1', 'source_field' => 'ThemeGroup1', 'options' => array()),
            array('product_field' => 'themegroup2', 'source_field' => 'ThemeGroup2', 'options' => array()),
            array('product_field' => 'themegroup3', 'source_field' => 'ThemeGroup3', 'options' => array()),
            array('product_field' => 'size', 'source_field' => 'Size', 'options' => array()),
            array('product_field' => 'ext_size', 'source_field' => 'Ext_Size', 'options' => array()),
            array('product_field' => 'genericcode', 'source_field' => 'GenericCode', 'options' => array()),
            array('product_field' => 'rrp', 'source_field' => 'RRP', 'options' => array()),
            array('product_field' => 'break1', 'source_field' => 'Break1', 'options' => array()),
            array('product_field' => 'break2', 'source_field' => 'Break2', 'options' => array()),
            array('product_field' => 'break3', 'source_field' => 'Break3', 'options' => array()),
            array('product_field' => 'eta', 'source_field' => 'ETA', 'options' => array()),
            array('product_field' => 'min_qty', 'source_field' => 'EFQty', 'options' => array()),
        );


        foreach ($customFields as $importRule) {
            $sourceField = $importRule['source_field'];
            $productField = $importRule['product_field'];

            $sourceValue = trim(ucwords(strtolower($product->$sourceField)));

            /** @var $attribute Mage_Catalog_Model_Entity_Attribute */
            $attribute = $this->_getAttributeMapResource()->getAttributeByCode($productField);

            if (mb_strlen($sourceValue) && $attribute && $this->isSelectAttribute($attribute)) {
                $optionId = $this->_getOptionsResource()->getAttrOptionId($attribute->getId(), $sourceValue);
                if (null == $optionId) {
                    $this->_getOptionsResource()->insertAttributeOptions($attribute->getId(), $sourceValue);
                    $optionId = $this->_getOptionsResource()->getAttrOptionId($attribute->getId(), $sourceValue);
                }
                $sourceValue = $optionId;
            }
            $productModel->setData($productField, $sourceValue);
        }

        /**
         * Add the images to the product..
         */
        $this->_addImages($productModel, $product);

        try {
//            Zend_Debug::dump($productModel->getData());
//            die();
            $productModel->save();

            return $productModel;
        } catch (Exception $e) {
            $this->_errstrs .= "\n".$e->getMessage();
            $this->_errors++;
            return FALSE;
        }
        $this->updateErrorsStatus();
        return $productModel;
    }

    public function getCategoryId( $product ){
        
        $category = Mage::getModel('catalog/category')
                ->loadByAttribute('smiffys_code', trim( $product->CatalogueCode ) );
        if($category && $category->getId())
            return $category->getId();
        else return $this->getDropCategoryId();
        
    }
    
    /**
     * Insert the upsells from the product xml, insto the Product model
     *
     * @param SimpleXmlElement $product
     */
    protected function _insertUpsells(SimpleXMLElement $productXml) {
        /**
         * Upsell fields in xml
         * @var array
         */
        $tmpupsellArr = array(
            'alt_code1',
            'alt_code2',
            'alt_code3',
            'alt_code4',
            'alt_code5'
        );
        $upsellArr = array();

        foreach ($tmpupsellArr as $tmplabel) :
            if (isset($productXml->$tmplabel) && !empty($productXml->$tmplabel)) :
                $upsellArr[] = $tmplabel;
            endif;
        endforeach;
        if (!count($upsellArr))
            return 0;
        $cnt = 0;
        $productModel = Mage::getModel('catalog/product');
        $_product = $productModel->loadByAttribute('sku', $productXml->ProductCode);
        if ($_product) :
            $data = array();
            $_product->getLinkInstance()->useUpSellLinks();
            $attributes = array();
            foreach ($_product->getLinkInstance()->getAttributes() as $_attribute) {
                if (isset($_attribute['code'])) {
                    $attributes[] = $_attribute['code'];
                }
            }
            foreach ($_product->getUpSellLinkCollection() as $_link) {
                $data[$_link->getLinkedProductId()] = $_link->toArray($attributes);
            }
            foreach ($upsellArr as $label) {
                $productXml->$label = trim($productXml->$label);
                //if (isset($productXml->$label) && ! empty($productXml->$label)) {
                if (($productId = $productModel->getIdBySku($productXml->$label))) :
                    if (!isset($data[$productId])) :
                        $data[$productId] = $productModel->toArray($attributes);
                        $cnt++;
                    endif;
                else :
                //die('Error product not found: [' . $productXml->$label . ']');
                endif;
                //}
            }
            if ($cnt) :
                $_product->setUpSellLinkData($data);
                $_product->save();
            endif;
        endif;
        return $cnt;
    }

    /**
     * Insert Accessories as related items from the XML feed
     *
     * @param SimpleXmlElement $product
     */
    protected function _insertRelatedItems(SimpleXMLElement $productXml) {
        /**
         * Related fields in xml
         * ie accessories.
         * @var array
         */
        $tmprelatedArr = array(
            'acc_code1',
            'acc_code2',
            'acc_code3',
            'acc_code4',
            'acc_code5'
        );
        $relatedArr = array();

        foreach ($tmprelatedArr as $tmplabel) :
            if (isset($productXml->$tmplabel) && !empty($productXml->$tmplabel)) :
                $relatedArr[] = $tmplabel;
            endif;
        endforeach;
        if (!count($relatedArr))
            return 0;
        $cnt = 0;
        $productModel = Mage::getModel('catalog/product');
        $_product = $productModel->loadByAttribute('sku', $productXml->ProductCode);
        if ($_product) :
            $data = array();
            $_product->getLinkInstance()->useRelatedLinks();
            $attributes = array();
            foreach ($_product->getLinkInstance()->getAttributes() as $_attribute) {
                if (isset($_attribute['code'])) {
                    $attributes[] = $_attribute['code'];
                }
            }
            foreach ($_product->getRelatedLinkCollection() as $_link) {
                $data[$_link->getLinkedProductId()] = $_link->toArray($attributes);
            }
            foreach ($relatedArr as $label) {
                $productXml->$label = trim($productXml->$label);
                //if (isset($productXml->$label) && ! empty($productXml->$label)) {
                if (($productId = $productModel->getIdBySku($productXml->$label))) :
                    if (!isset($data[$productId])) :
                        $data[$productId] = $productModel->toArray($attributes);
                        $cnt++;
                    endif;
                else :
                //die('Error product not found: [' . $productXml->$label . ']');
                endif;
                //}
            }
            if ($cnt) :
                $_product->setRelatedLinkData($data);
                $_product->save();
            endif;
        endif;
        return $cnt;
    }

    /**
     * Download images for this product
     *
     * @param Mage_Catalog_Model_Product $productModel
     * @param SimpleXmlElement $product
     */
    protected function _addImages($productModel, SimpleXMLElement $productXml) {
        if ($this->_addLocalImages($productModel, $productXml)) {
            return true;
        }

        $imgDir = Mage::getBaseDir() .'/'.$this->getImagesLocalPath().'/';

        if (!is_writable($imgDir)) {
            $this->_warnings++;
            return false;
        }

        $productModel->setMediaGallery(array('images' => array(), 'values' => array())); // As it's null for new product

        $img = preg_replace('/[a-zA-Z]$/sumix','',trim($productXml->ProductCode)) . '.jpg'; {
            $written = $tmpFile = $imgDir . $img;
            $agent = "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)";

            $c = curl_init($this->_imageUrl . $img);
            curl_setopt($c, CURLOPT_HEADER, 0); // return headers
            curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($c, CURLOPT_BINARYTRANSFER, 1);
            $content = curl_exec($c);
            if ($content !== FALSE) {
                $written = file_put_contents($tmpFile, $content);
            } else {
                $this->_warnings++;
            }
            curl_close($c);


            if ($written) {
                if (@getimagesize($tmpFile) !== FALSE) {
                    $productModel->addImageToMediaGallery($tmpFile, array('thumbnail', 'small_image', 'image'), FALSE);
                } else {
                    $this->_warnings++;
                }
            } else {
                $this->_warnings++;
            }
        }
    }

    protected function _addLocalImages($productModel, SimpleXMLElement $productXml) {
        $added = false;
//        $imgPath = Mage::getBaseDir() . self::DATA_PATH . '/' . $this->getImagesLocalPath() . '/';
        $imgPath = Mage::getBaseDir() .'/'.$this->getImagesLocalPath().'/';
        
        $imgFilePattern = preg_replace('/[a-zA-Z]$/sumix','',strtolower(trim($productXml->ProductCode))) . '*.jpg';
        $files = glob($imgPath . $imgFilePattern);
        $files[] = $imgPath . preg_replace('/^([0-9]+)/sumix','\1',strtolower(trim($productXml->ProductCode))).'.jpg';
        foreach ( $files as $imgFile) {
            $imgFullFileName = $imgFile;

            if (file_exists($imgFullFileName)) {
                
                if (@getimagesize($imgFullFileName) !== FALSE) {
                    if (!$added) {
                        $productModel->setMediaGallery(array('images' => array(), 'values' => array()));
                        $productModel->addImageToMediaGallery($imgFullFileName, array('thumbnail', 'small_image', 'image'), FALSE);
                    } else {
                        $productModel->addImageToMediaGallery($imgFullFileName, array(), FALSE);
                    }
                    $added = true;
                } else {
                }
            }
            //$this->getStatus()->collect('Not found');
        }
        if($added) $productModel->save();
        return $added;
    }

    static function removeProductImages($product) {
        if ($product->getId()) {
            $mediaApi = Mage::getModel("catalog/product_attribute_media_api");
            $items = $mediaApi->items($product->getId());
            foreach ($items as $item)
                $mediaApi->remove($product->getId(), $item['file']);
        }
    }

    /**
     * Does the product already exists in Magento?
     * @param string $sku
     * @return bool
     */
    protected function _productExists($sku) {
        $product = Mage::getModel('catalog/product');
        $productId = $product->getIdBySku($sku);
        if ($productId) {
            return TRUE;
            //$product->load($productId);
        }
        return FALSE;
    }

//*****************************************************************************
    public function updateMedia() {
        $collection = Mage::getModel('catalog/product')->getCollection();

        foreach ($collection as $product) {
            $this->removeProductImages($product);
            $productXml = simplexml_load_string('<product></product>');
            $productXml->ProductCode = preg_replace('/[a-zA-Z]$/sumix','',$product->getSKU() );
            $this->_addImages($product, $productXml);
        }
    }


//******************************************************************************
//STATUS
//******************************************************************************
    
public function updateIteratorStatus( $current, $_lock = true, $_product = null ){
        $statusData = array(
            self::STATUS_NAME => array(
                'totals'=>Zend_Json::encode(
                    array(
                    'total'     => $this->_total,
                    'processed' => $this->_processed ,
                    'imported'  => $this->_imported,
                    'exists'    => $this->_exists,
                    'warnings'  => $this->_warnings,
                    'errors'    => $this->_errors,
                    'current'   => '['.$current->ProductCode.']: '.$current->ProductName
                                    .(($_product)?(
                                    '<br/><img style="height:135px;" src="'.
                                    Mage::helper('catalog/image')->init($_product, 'small_image')->resize(135)
                                    .'">') :''),
                    'lock'      => $_lock ? 1 : 0,
                    ))
            ));
        return $this->updateStatus( $statusData );
    }
    
    
    public function updateErrorsStatus(  ){
        $statusData = array(
            self::STATUS_NAME =>array(
               'err' => $this->_errstrs,
            ));
        return $this->updateStatus( $statusData );
    }
    
    public function updateStatus( $_statusData ){
        $status = Mage::helper('wiosmiffys/status');
        $status->setStatus( $_statusData );
        return $this;
    }

}