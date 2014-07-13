<?php

class CL_Smiffys_Model_Api_V1_Smiffys extends Mage_Core_Model_Abstract {

    //default, can be overriden by config
    protected $_imageUrl = 'http://www.temashop.dk/media/catalog/product/'; // + imageName    
    protected $_xml;
    protected $_existingCount;
    protected $_importedCount;
    protected $_processed;
    protected $_errors;
    protected $_warnings;

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

        $this->setStatus(new CL_Smiffys_Model_Api_V1_Status(self::STATUS_ID, array('total', 'imported', 'exists', 'errors', 'warnings')));

        $this->_loadConf();
    }

    protected function _prepareXml($_local = false) {
        $this->getStatus()->add('Getting XML data...');
        if ( false && $_local) {
            die('local');
            $this->_loadLocalXml();
            return true;
        }

        $transport = $this->_getTransport();
        $transport->exec();
        $this->_xml = $transport->getResultData();
        file_put_contents(Mage::getBaseDir() . self::DATA_PATH . $this->getStatus()->getId() .'.'. date('U').'.xml', $transport->getRawData());

//        file_put_contents(Mage::getBaseDir().self::DATA_PATH.'smiffys_data.xml', $transport->getRawData());
        unset($transport);
    }

    protected function _loadLocalXml() {
        $this->_xml = simplexml_load_file(Mage::getBaseDir() . self::DATA_PATH . 'smiffys_data.xml');
    }

    protected function _loadConf() {
        $this->setImportLimit(Mage::getStoreConfig('smiffys_catalog/import/limit'));
        $this->setImportWebsite(Mage::getStoreConfig('smiffys_catalog/import/import_website'));
        $this->setImportStore(Mage::getStoreConfig('smiffys_catalog/import/import_store'));
        $this->setImportCategory(Mage::getStoreConfig('smiffys_catalog/import/import_category'));
        $this->setImportTaxClassId(Mage::getStoreConfig('smiffys_catalog/import/import_tax_class'));
        $this->setImportAttributeSetId(Mage::getStoreConfig('smiffys_catalog/import/import_attribute_set_id'));

        $this->setImagesLocalPath(Mage::getStoreConfig('smiffys_catalog/import/images_local_path'));
        $this->_imageUrl = Mage::getStoreConfig('smiffys_catalog/import/images_url');
    }

    protected function _getTransport() {
        $factory = Mage::getModel('smiffys/product_transportFactory');
        $transport = $factory::getTransport();
        $transport->setCmd(CL_Smiffys_Model_Transport_Abstract::CMD_GET_FULL_DATA_SET);
        $this->setTransport($transport);
        return $transport;
    }

    /**
     * Import the products
     */
    public function import() {
        $this->_prepareXml(false);

        $this->_imported = 0;
        $this->_existing = 0;
        $this->_processed = 0;
        $this->_errors = 0;
        $this->_warnings = 0;

        $this->_getAttributeMapResource()->loadAll();


        $imported_sku = array();
        foreach ($this->_xml->Product as $product) {
            if ($this->getImportLimit() && $this->_processed > $this->getImportLimit()) {
                $this->getStatus()->add("{WARNING}LIMIT REACHED");
                break;
            }


            $this->getStatus()->add('(' . $this->_processed . ') *******************************************');
            $this->getStatus()->add('Importing Product (SKU): ' . trim($product->ProductCode));

            /** Check if it's already there * */
            if ($this->_productExists($product->ProductCode)) {
                $this->_existing++;
                $this->_warnings++;
                $this->getStatus()->add("{WARNING} Product already exists");
                continue;
            }

            if ($this->_importProduct($product)) {
                $imported_sku[] = trim($product->ProductCode);

                $this->getStatus()->add("{OK}: Import Success");
                $this->_imported++;
            } else {
                $this->_errors++;
                $this->getStatus()->add("{ERROR}: Import failed");
            }

            $this->_processed++;

            $status = array(
                'Total items' => $this->_processed,
                'Imported' => $this->_imported,
                'Exists' => $this->_existing,
                'Errors' => $this->_errors,
                'Warnings' => $this->_warnings,
            );
            $this->getStatus()->updateStatus($status);
        }

        foreach ($this->_xml->Product as $product) {
            /** Exclude from import * */
            if (in_array(trim($product->ProductCode), $imported_sku)) {
                $this->getStatus()->add('Importing Related Products (SKU): ' . trim($product->ProductCode));
                $this->_insertRelatedItems($product);
                $this->_insertUpsells($product);
            }
        }
        $this->getStatus()->finish();
        return $status;
    }

    public function updateAllPrice($local = false) {
        $this->setStatus(new CL_Smiffys_Model_Api_V1_Status(self::STATUS_PRICE_ID, array('total', 'imported', 'exists', 'errors', 'warnings')));
        $this->_prepareXml($local);

        $this->_imported = 0;
        $this->_existing = 0;
        $this->_processed = 0;
        $this->_errors = 0;
        $this->_warnings = 0;




        $imported_sku = array();
        foreach ($this->_xml->Product as $product) {

            $this->getStatus()->add('(' . $this->_processed . ') *******************************************');
            $this->getStatus()->add('Updating Price (SKU): ' . trim($product->ProductCode));

            /** Check if it's already there * */
            if ($this->_productExists($product->ProductCode)) {

                if ($this->_updateProductPrice($product)) {

                    $this->getStatus()->add("{OK}: Update Success");
                    $this->_imported++;
                } else {
                    $this->_errors++;
                    $this->getStatus()->add("{ERROR}: Update failed");
                }
            }

            $this->_processed++;

            $status = array(
                'Total items' => $this->_processed,
                'Imported' => $this->_imported,
                'Exists' => $this->_existing,
                'Errors' => $this->_errors,
                'Warnings' => $this->_warnings,
            );
            $this->getStatus()->updateStatus($status);
        }

        $this->getStatus()->add('{FINISH}');
        $this->getStatus()->finish();
        return $status;
    }

    protected $_attributeMapResource = null;

    /**
     * @return Elcommerce_ImportExport_Model_Mysql4_Attribute_Map
     */
    protected function _getAttributeMapResource() {
        if (is_null($this->_attributeMapResource)) {
            $this->_attributeMapResource = new Elcommerce_ImportExport_Model_Mysql4_Attribute_Map();
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

    /**
     * @return Elcommerce_ImportExport_Model_Mysql4_Attribute_Options
     */
    public function _getOptionsResource() {
        if (is_null($this->_optionsResource)) {
            $this->_optionsResource = new Elcommerce_ImportExport_Model_Mysql4_Attribute_Options();
        }
        return $this->_optionsResource;
    }

    /**
     * Import the product into Magento
     * @return bool
     */
    protected function _updateProductPrice(SimpleXMLElement $product) {
        try {
            $productModel = Mage::getModel('catalog/product');
            $productId = $productModel->getIdBySku(trim($product->ProductCode));
            $productModel = $productModel->load($productId);

            $this->getStatus()->add('Original:' . $product->EFPrice);
            $this->getStatus()->add('Price:' . $this->makePrice($product->EFPrice));
            $this->getStatus()->add('Cost:' . $this->makeCost($product->EFPrice));

            $productModel->setPrice($this->makePrice($product->EFPrice));
            $productModel->setCost($this->makeCost($product->EFPrice));

            $productModel->save();
        } catch (Exception $e) {
            $this->getStatus()->add('{ERR}:' . $e->getMessage());
            return FALSE;
        }
        return true;
    }

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
        $productModel->setCategoryIds(array($this->getImportCategory()));
        $productModel->setWebsiteIds(array($this->getImportWebsite()));
        $productModel->setStoreId($this->getImportStore());

        $productModel->setStatus(1); // Disabled - 1 is Enabled
        $productModel->setCost($this->makeCost($product->EFPrice));
        $productModel->setPrice($this->makePrice($product->EFPrice));
        $productModel->setVejlpris($product->EFPrice * 7.45 * 2 * 1.25);

        $productModel->setStockData(array(
            'is_in_stock' => ($product->StockQuantity > 0) ? 1 : 0,
            'qty' => $product->StockQuantity
        ));
        $productModel->setData('is_in_stock', ($product->StockQuantity > 0) ? 1 : 0);

        /*
          $vendor_stock = array();
          $vendor_stock[2] = $product->StockQuantity;
          $vendor_stock[1] = 0;
          $productModel->setUdmultiStock($vendor_stock);

          $insert = array();
          $vendor = array();
          // fjer
          $vendor['vendor_id'] = 2;
          $vendor['vendor_cost'] = $product->Price3 * 7.45 * 1.25;
          $vendor['stock_qty'] = $product->StockQuantity;
          $vendor['vendor_sku'] = trim($product->ProductCode);
          $vendor['priority'] = 9999;
          $insert[] = $vendor;
          $vendor = array();
          // lokal
          $vendor['vendor_id'] = 1;
          $vendor['vendor_cost'] = $product->EFPrice * 7.45 * 1.25;
          $vendor['stock_qty'] = 0;
          $vendor['vendor_sku'] = trim($product->ProductCode);
          $vendor['priority'] = 10;
          $insert[] = $vendor;

          $update = array();
          $delete = array();
          $is_in_stock = ($product->StockQuantity > 0);
          $data = compact('insert', 'update', 'delete', 'is_in_stock', 'vendor_stock');
          $productModel->setUpdateUdmultiVendors($data);
         */
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
            $productModel->save();

            return TRUE;
        } catch (Exception $e) {
            $this->_errors++;
            $this->getStatus()->add('{ERROR}:' . $e->getMessage());
            return FALSE;
        }
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

        $imgDir = Mage::getBaseDir() . self::DATA_PATH . 'images/';
        if (!is_writable($imgDir)) {
            $this->_warnings++;
            $this->getStatus()->add('{WARNING}Images folder not writable. Image not imported ');
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
            $this->getStatus()->add('Getting image:' . $this->_imageUrl . $img);
            if ($content !== FALSE) {
                $written = file_put_contents($tmpFile, $content);
                $this->getStatus()->add("{OK}" . $tmpFile . "(" . $written . " bytes)");
            } else {
                $this->_warnings++;
                $this->getStatus()->add("{WARNING}Image not loaded");
            }
            curl_close($c);


            if ($written) {
                if (@getimagesize($tmpFile) !== FALSE) {
                    $productModel->addImageToMediaGallery($tmpFile, array('thumbnail', 'small_image', 'image'), FALSE);
                } else {
                    $this->_warnings++;
                    $this->getStatus()->add('{WARNING}' . $tmpFile . " is not a valid Image");
                }
            } else {
                $this->_warnings++;
                $this->getStatus()->add('{WARNING}Image not written: ' . $tmpFile);
            }
        }
    }

    protected function _addLocalImages($productModel, SimpleXMLElement $productXml) {
        $added = false;
        $imgPath = Mage::getBaseDir() . self::DATA_PATH . '/' . $this->getImagesLocalPath() . '/';
        $imgFilePattern = preg_replace('/[a-zA-Z]$/sumix','',strtolower(trim($productXml->ProductCode))) . '*.jpg';
        $files = glob($imgPath . $imgFilePattern);
        $files[] = $imgPath . preg_replace('/^([0-9]+)/sumix','\1',strtolower(trim($productXml->ProductCode))).'.jpg';
        foreach ( $files as $imgFile) {
            $imgFullFileName = $imgFile;

            $this->getStatus()->collect('Trying to allocate local image: ' . $imgFullFileName . '...');
            if (file_exists($imgFullFileName)) {
                
                if (@getimagesize($imgFullFileName) !== FALSE) {
                    if (!$added) {
                        $productModel->setMediaGallery(array('images' => array(), 'values' => array()));
                        $productModel->addImageToMediaGallery($imgFullFileName, array('thumbnail', 'small_image', 'image'), FALSE);
                    } else {
                        $productModel->addImageToMediaGallery($imgFullFileName, array(), FALSE);
                    }
                    $this->getStatus()->collect('OK');
                    $this->getStatus()->flushCollected();
                    $added = true;
                } else {
                    $this->getStatus()->collect('Not image file');
                }
            }
            //$this->getStatus()->collect('Not found');
            $this->getStatus()->flushCollected();
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
        $this->getStatus()->setId('media');
        $collection = Mage::getModel('catalog/product')->getCollection();

        foreach ($collection as $product) {
            $this->removeProductImages($product);
            $productXml = simplexml_load_string('<product></product>');
            $productXml->ProductCode = preg_replace('/[a-zA-Z]$/sumix','',$product->getSKU() );
            $this->_addImages($product, $productXml);
        }
        $this->getStatus()->finish();
    }

}

//******************************************************************************
//------------------------------------------------------------------------------
abstract class Elcommerce_ImportExport_Model_Mysql4_Abstract {

    public function getTable($name) {
        return Mage::getSingleton('core/resource')->getTableName($name);
    }

    /**
     * @return Varien_Db_Adapter_Pdo_Mysql
     */
    protected function _getReadAdapter() {
        return Mage::getSingleton('core/resource')->getConnection('core_read');
    }

    /**
     * @return Varien_Db_Adapter_Pdo_Mysql
     */
    protected function _getWriteAdapter() {
        return Mage::getSingleton('core/resource')->getConnection('core_write');
    }

    public function keysDisablePlain($table) {
        $this->_getWriteAdapter()->query("ALTER TABLE {$table} DISABLE KEYS");
        return $this;
    }

    public function keysEnablePlain($table) {
        $this->_getWriteAdapter()->query("ALTER TABLE {$table} ENABLE KEYS");
        return $this;
    }

    public function keysDisable($table) {
        $table = $this->getTable($table);
        $this->_getWriteAdapter()->query("ALTER TABLE {$table} DISABLE KEYS");
        return $this;
    }

    public function keysEnable($table) {
        $table = $this->getTable($table);
        $this->_getWriteAdapter()->query("ALTER TABLE {$table} ENABLE KEYS");
        return $this;
    }

    /**
     * @return Elcommerce_ImportExport_Model_Mysql4_Product
     */
    public function rollbackTransaction() {
        $this->_getWriteAdapter()->query("ROLLBACK");
        return $this;
    }

    /**
     * @return Elcommerce_ImportExport_Model_Mysql4_Product
     */
    public function beginTransaction() {
        $this->_getWriteAdapter()->query("START TRANSACTION");
        return $this;
    }

    /**
     * @return Elcommerce_ImportExport_Model_Mysql4_Product
     */
    public function endTransaction() {
        $this->_getWriteAdapter()->query("COMMIT");
        return $this;
    }

    /**
     * @throws Varien_Db_Exception
     * @param Varien_Db_Select $select
     * @param $table
     * @return string
     */
    public function updateFromSelect(Varien_Db_Select $select, $table) {
        // CE 1.6 only provides updateFromSelect from scratch!

        if (!is_array($table)) {
            $table = array($table => $table);
        }

        // get table name and alias
        $keys = array_keys($table);
        $tableAlias = $keys[0];
        $tableName = $table[$keys[0]];

        $query = sprintf('UPDATE %s', $this->_getWriteAdapter()->quoteTableAs($tableName, $tableAlias));

        // render JOIN conditions (FROM Part)
        $joinConds = array();
        foreach ($select->getPart(Zend_Db_Select::FROM) as $correlationName => $joinProp) {
            if ($joinProp['joinType'] == Zend_Db_Select::FROM) {
                $joinType = strtoupper(Zend_Db_Select::INNER_JOIN);
            } else {
                $joinType = strtoupper($joinProp['joinType']);
            }
            $joinTable = '';
            if ($joinProp['schema'] !== null) {
                $joinTable = sprintf('%s.', $this->_getWriteAdapter()->quoteIdentifier($joinProp['schema']));
            }
            $joinTable .= $this->_getWriteAdapter()->quoteTableAs($joinProp['tableName'], $correlationName);

            $join = sprintf(' %s %s', $joinType, $joinTable);

            if (!empty($joinProp['joinCondition'])) {
                $join = sprintf('%s ON %s', $join, $joinProp['joinCondition']);
            }

            $joinConds[] = $join;
        }

        if ($joinConds) {
            $query = sprintf("%s\n%s", $query, implode("\n", $joinConds));
        }

        // render UPDATE SET
        $columns = array();
        foreach ($select->getPart(Zend_Db_Select::COLUMNS) as $columnEntry) {
            list($correlationName, $column, $alias) = $columnEntry;
            if (empty($alias)) {
                $alias = $column;
            }
            if (!$column instanceof Zend_Db_Expr && !empty($correlationName)) {
                $column = $this->_getWriteAdapter()->quoteIdentifier(array($correlationName, $column));
            }
            $columns[] = sprintf('%s = %s', $this->_getWriteAdapter()->quoteIdentifier(array($tableAlias, $alias)), $column);
        }

        if (!$columns) {
            throw new Varien_Db_Exception('The columns for UPDATE statement are not defined');
        }

        $query = sprintf("%s\nSET %s", $query, implode(', ', $columns));

        // render WHERE
        $wherePart = $select->getPart(Zend_Db_Select::WHERE);
        if ($wherePart) {
            $query = sprintf("%s\nWHERE %s", $query, implode(' ', $wherePart));
        }

        return $query;
    }

    protected function _getReplaceSqlQuery($tableName, array $columns, array $values) {
        $tableName = $this->_getWriteAdapter()->quoteIdentifier($tableName, true);
        $columns = array_map(array($this->_getWriteAdapter(), 'quoteIdentifier'), $columns);
        $columns = implode(',', $columns);
        $values = implode(', ', $values);

        $insertSql = sprintf('REPLACE INTO %s (%s) VALUES %s', $tableName, $columns, $values);

        return $insertSql;
    }

    public function replaceMultiple($table, array $data) {
        $row = reset($data);

        if (!is_array($row)) {
            Mage::throwException("Row should be an array in " . __METHOD__);
        }

        // validate data array
        $cols = array_keys($row);
        $insertArray = array();
        foreach ($data as $row) {
            $line = array();
            if (array_diff($cols, array_keys($row))) {
                throw new Zend_Db_Exception('Invalid data for insert');
            }
            foreach ($cols as $field) {
                $line[] = $row[$field];
            }
            $insertArray[] = $line;
        }
        unset($row);

        return $this->replaceArray($table, $cols, $insertArray);
    }

    /**
     * Insert array to table based on columns definition
     *
     * @param   string $table
     * @param   array $columns
     * @param   array $data
     * @return  int
     * @throws  Zend_Db_Exception
     */
    public function replaceArray($table, array $columns, array $data) {
        $values = array();
        $bind = array();
        $columnsCount = count($columns);
        foreach ($data as $row) {
            if ($columnsCount != count($row)) {
                throw new Zend_Db_Exception('Invalid data for insert');
            }
            $values[] = $this->_prepareInsertData($row, $bind);
        }

        $insertQuery = $this->_getReplaceSqlQuery($table, $columns, $values);

        $stmt = $this->_getWriteAdapter()->query($insertQuery, $bind);
        $result = $stmt->rowCount();
        return $result;
    }

    protected function _prepareInsertData($row, &$bind) {
        if (is_array($row)) {
            $line = array();
            foreach ($row as $value) {
                if ($value instanceof Zend_Db_Expr) {
                    $line[] = $value->__toString();
                } else {
                    $line[] = '?';
                    $bind[] = $value;
                }
            }
            $line = implode(', ', $line);
        } elseif ($row instanceof Zend_Db_Expr) {
            $line = $row->__toString();
        } else {
            $line = '?';
            $bind[] = $row;
        }
        return sprintf('(%s)', $line);
    }

}

class Elcommerce_ImportExport_Model_Mysql4_Attribute extends Elcommerce_ImportExport_Model_Mysql4_Abstract {

    /**
     * @var int
     */
    protected $_entityTypeId = null;
    protected $_defaultAttributeSetId = null;

    /**
     * Constants
     */

    const TABLE_ENTITY_ATTR = "eav/entity_attribute";
    const TABLE_ATTRIBUTE_SET = "eav/attribute_set";

    /**
     * @param $arg
     * @return Elcommerce_ImportExport_Model_Mysql4_Attribute
     */
    public function setDefaultAttributeSetId($arg) {
        $this->_defaultAttributeSetId = (int) $arg;
        return $this;
    }

    /**
     * @return int
     */
    public function getDefaultAttributeSetId() {
        if (is_null($this->_defaultAttributeSetId)) {
            $this->_defaultAttributeSetId = Mage::getModel('catalog/product')->getResource()->getEntityType()->getDefaultAttributeSetId();
        }
        return $this->_defaultAttributeSetId;
    }

    public function getAttributeSetNames($entityTypeId = false) {
        if (false === $entityTypeId) {
            $entityTypeId = $this->getEntityTypeId();
        }
        $table = $this->getTable(self::TABLE_ATTRIBUTE_SET);
        $select = $this->_getReadAdapter()->select();
        $select->from(
                $table, array('attribute_set_id', 'attribute_set_name')
        )->where(
                'entity_type_id = ?', $entityTypeId
        );
        $result = array();
//        var_dump($select->__toString());
        $rowset = $this->_getReadAdapter()->fetchAll($select);
        foreach ($rowset as $row) {
            $result[$row['attribute_set_name']] = $row['attribute_set_id'];
        }
        return $result;
    }

    /**
     * @param bool $forceReload
     * @return int
     */
    public function getEntityTypeId($forceReload = false) {
        if ($forceReload || !$this->_entityTypeId) {
            $this->_entityTypeId = (int) Mage::getModel("eav/entity_type")
                            ->loadByCode("catalog_product")
                            ->getId();
        }
        return $this->_entityTypeId;
    }

    /**
     * @param bool $entityTypeId
     * @return void
     */
    public function getAllAttributeSetIds($entityTypeId = false) {
        if (false === $entityTypeId) {
            $entityTypeId = $this->getEntityTypeId();
        }
        $table = $this->getTable(self::TABLE_ENTITY_ATTR);
        $select = $this->_getReadAdapter()->select();
        $select->from($table, array('attribute_set_id'))
                ->distinct()
                ->where('entity_type_id = ?', $entityTypeId);

        return $this->_getReadAdapter()->fetchCol($select);
    }

    public function getFilterableAttributeCodes() {
        $select = $this->_getReadAdapter()->select();
        $select->from(
                array('main_table' => $this->getTable('catalog/eav_attribute'))
        )->join(
                array('help_table' => $this->getTable('eav/attribute')), "main_table.attribute_id = help_table.attribute_id AND main_table.is_filterable", array('attribute_code')
        );
        return $this->_getReadAdapter()->fetchCol($select);
    }

}

class Elcommerce_ImportExport_Model_Mysql4_Attribute_Options extends Elcommerce_ImportExport_Model_Mysql4_Abstract {

    /**
     * @var array
     */
    protected $_attributeOptions = null;

    /**
     * @param $attributeId
     * @return Elcommerce_ImportExport_Model_Mysql4_Attribute_Options
     */
    protected function _insertAttributeNode($attributeId) {
        $this->_attributeOptions[$attributeId] = array();
        return $this;
    }

    /**
     * @param $attributeId
     * @param $optionId
     * @param $value
     * @return Elcommerce_ImportExport_Model_Mysql4_Attribute_Options
     */
    protected function _insertOption($attributeId, $optionId, $value) {
        $this->_attributeOptions[$attributeId][$value] = $optionId;
        return $this;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->getAttributeOptions(true);
    }

    /**
     * @param $attributeId
     * @param $options
     * @return
     */
    public function insertAttributeOptions($attributeId, $options) {
        if (is_scalar($options)) {
            $options = array($options);
        } elseif (is_object($options)) {
            Mage::throwException("Options arg in " . __METHOD__ . " can't be an object!");
        }
        // Empty array case
        if (!$options) {
            return;
        }
        $this->getAttributeOptions();
        $table = $this->getTable('eav/attribute_option');
        $tableValues = $this->getTable('eav/attribute_option_value');
        foreach ($options as $value) {
            $value = trim(ucwords( strtolower( $value ) ));
            $insert = array("attribute_id" => $attributeId);
            $this->_getWriteAdapter()->insert($table, $insert);
            $optionId = $this->_getWriteAdapter()->fetchOne("SELECT LAST_INSERT_ID()");
            if (!$optionId) {
                Mage::throwException("Can't insert to {$table}");
            }
            $insert = array('option_id' => $optionId, 'store_id' => 0, 'value' => $value);
            $this->_getWriteAdapter()->insert($tableValues, $insert);
            //$valueId = $this->_getWriteAdapter()->fetchOne("SELECT LAST_INSERT_ID()");
            $this->_insertOption($attributeId, $optionId, $value);
        }
    }

    /**
     * @param bool $forceReload
     * @return null
     */
    public function getAttributeOptions($forceReload = false) {
        if ($forceReload || is_null($this->_attributeOptions)) {
            $select = $this->_getReadAdapter()->select();
            $select->from(
                    array('q' => $this->getTable("eav/attribute")), array(
                'attribute_id',
                'attribute_code',
                'frontend_input',
                'backend_type',
                    )
            )->joinLeft(
                    array('qo' => $this->getTable('eav/attribute_option')), 'q.attribute_id = qo.attribute_id', array('option_id')
            )->joinLeft(
                    array('qv' => $this->getTable("eav/attribute_option_value")), 'qo.option_id = qv.option_id', array('value', 'value_id', new Zend_Db_Expr('IFNULL(`qv`.`store_id`, 0) AS `store_id`'))
            )->where(
                    'q.backend_type = "int"'
            )->where(
                    'q.frontend_input = "select" OR q.frontend_input = "multiselect"'
            )->where(
                    'store_id = ?', 0
            );
            $this->_attributeOptions = array();
            foreach ($this->_getReadAdapter()->fetchAll($select) as $row) {
                if (strlen($row['value']) && (int) $row['option_id'] !== 0) {
                    $this->_insertOption((int) $row['attribute_id'], (int) $row['option_id'], (string) $row['value']);
                } else {
                    $this->_insertAttributeNode((int) $row['attribute_id']);
                }
            }
        }
        return $this->_attributeOptions;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function hasOptionValuesByAttrId($id) {
        return isset($this->_attributeOptions[$id]);
    }

    /**
     * @param $id
     * @return array|null
     */
    public function getOptionValuesByAttrId($id) {
        if ($this->hasOptionValuesByAttrId($id)) {
            return $this->_attributeOptions[$id];
        }
        return null;
    }

    /**
     * @param $attrId
     * @param $optionValue
     * @return |null
     */
    public function getAttrOptionId($attrId, $optionValue) {
        if (!$this->hasOptionValuesByAttrId($attrId)) {
            return null;
        }
        return isset($this->_attributeOptions[$attrId][$optionValue]) ? $this->_attributeOptions[$attrId][$optionValue] : null;
    }

}

class Elcommerce_ImportExport_Model_Mysql4_Attribute_Map {

    /**
     * @var array
     */
    protected $_mapById = array();
    protected $_mapByCode = array();
    protected $_linearById = array();
    protected $_linearByCode = array();

    /**
     *
     */
    public function __construct() {
        $this->loadAll();
    }

    /**
     * @return Elcommerce_ImportExport_Model_Mysql4_Attribute_Map
     */
    public function loadAll() {
        foreach ($this->_getResource()->getAllAttributeSetIds() as $setId) {
            $this->loadAttributeSet($setId, true);
        }
        return $this;
    }

    /**
     * @param bool $attrSetId
     * @return bool
     */
    public function hasAttributeSet($attrSetId = false) {
        if (false === $attrSetId) {
            $attrSetId = $this->_getResource()->getDefaultAttributeSetId();
        }
        return array_key_exists($attrSetId, $this->_mapById);
    }

    /**
     * @param bool $attrSetId
     * @return array
     */
    public function getAttributeSet($attrSetId = false) {
        if (false === $attrSetId) {
            $attrSetId = $this->_getResource()->getDefaultAttributeSetId();
        }
        if ($this->hasAttributeSet($attrSetId)) {
            return $this->_mapById[$attrSetId];
        }
        $this->loadAttributeSet($attrSetId);
        if (!$this->hasAttributeSet($attrSetId)) {
            return array();
        }
        return $this->_mapById[$attrSetId];
    }

    /**
     * @param bool $attrSetId
     * @param bool $force
     * @return Elcommerce_ImportExport_Model_Mysql4_Attribute_Map
     */
    public function loadAttributeSet($attrSetId = false, $force = false) {
        if (false === $attrSetId) {
            $attrSetId = $this->_getResource()->getDefaultAttributeSetId();
        }
        if ($force || false === $this->hasAttributeSet($attrSetId)) {
            $collection = Mage::getResourceModel("eav/entity_attribute_collection");
            $collection->setEntityTypeFilter($this->_getResource()->getEntityTypeId());
            $collection->setAttributeSetFilter($attrSetId);
            $collection->load();
            foreach ($collection as $item) {
                $this->_mapById[$attrSetId][$item->getId()] = $item;
                $this->_mapByCode[$attrSetId][$item->getAttributeCode()] = $item;
                $this->_linearByCode[$item->getAttributeCode()] = $item;
                $this->_linearById[$item->getId()] = $item;
            }
        }
        return $this;
    }

    public function getDefaultAttributeSetId() {
        return $this->_getResource()->getDefaultAttributeSetId();
    }

    protected $_resource = null;

    /**
     * @return Elcommerce_ImportExport_Model_Mysql4_Attribute
     */
    protected function _getResource() {
        if (is_null($this->_resource)) {
            $this->_resource = new Elcommerce_ImportExport_Model_Mysql4_Attribute();
        }
        return $this->_resource;
    }

    /**
     * @return Mage_Eav_Model_Entity_Attribute_Collection
     */
    protected function _spawnCollection() {
        return Mage::getResourceModel("eav/entity_attribute_collection");
    }

    /**
     * @param $code
     * @return bool
     */
    public function hasAttributeByCode($code) {
        return isset($this->_linearByCode[$code]);
    }

    /**
     * @param $id
     * @return bool
     */
    public function hasAttributeById($id) {
        return isset($this->_linearById[$id]);
    }

    /**
     * @param $code
     * @return mixed|null
     */
    public function getAttributeByCode($code) {
        if ($this->hasAttributeByCode($code)) {
            return $this->_linearByCode[$code];
        }
        return null;
    }

    /**
     * @param $id
     * @return mixed|null
     */
    public function getAttributeById($id) {
        if ($this->hasAttributeById($id)) {
            return $this->_linearById[$id];
        }
        return null;
    }

    /**
     * @param $attrCode
     * @param bool $attributeSetId
     * @return bool
     */
    public function hasAttributeCodeInSet($attrCode, $attributeSetId = false) {
        if (false === $attributeSetId) {
            $attributeSetId = $this->_getResource()->getDefaultAttributeSetId();
        }
        return isset($this->_mapByCode[$attributeSetId][$attrCode]);
    }

    /**
     * @param $attrId
     * @param bool $attributeSetId
     * @return bool
     */
    public function hasAttributeIdInSet($attrId, $attributeSetId = false) {
        if (false === $attributeSetId) {
            $attributeSetId = $this->_getResource()->getDefaultAttributeSetId();
        }
        return isset($this->_mapById[$attributeSetId][$attrId]);
    }

}
