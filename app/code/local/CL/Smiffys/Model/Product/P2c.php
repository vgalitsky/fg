<?php

class CL_Smiffys_Model_Product_P2c extends CL_Smiffys_Model_Product {

    const CSV = 'p2c.csv';
    const STATUS_ID = 'p2c';

    public function __construct() {
        parent::__construct();
        $this->setStatusHandler(new CL_Smiffys_Model_Api_V1_Status(self::STATUS_ID /* , array('total','imported','exists','errors', 'warnings') */));
    }

    public function process() {

        $this->getStatusHandler()->add('{START}');

        $fh = fopen(CL_Smiffys_Model_Api_V1_Smiffys::getDataPath() . self::CSV, 'r');

        while ($csv = fgetcsv($fh, 0, ';')) {

            $sku = $csv[0];
            $categoryPath = $csv[1];
            $categorySets = explode('||', $categoryPath);
            $categoryName = array();
            foreach ($categorySets as $categorySetPath) {
                $categoryNames = explode('|', $categorySetPath);
                $categoryName[] = $categoryNames[count($categoryNames) - 1];
            }

            $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
            $this->getStatusHandler()->add("{START}Processing : ($sku)");

            $assignIds = array();

            if ($product) {
                foreach ($categoryName as $cName) {
                    $category = Mage::getModel('catalog/category')->loadByAttribute('name', $cName);
                    if ($category) {
                        $this->getStatusHandler()->add($category->getName() . '(' . $category->getId() . ')');
                        $assignIds[] = $category->getId();
                    }
                }

                if (count($assignIds)) {
                    $product->setCategoryIds($assignIds);
                    $product->save();
                    $this->getStatusHandler()->add('{OK}');
                } else {
                    $this->getStatusHandler()->add('Categories not found');
                }
            } else {
                $this->getStatusHandler()->add('Product not found');
            }
        }

        fclose($fh);
        $this->getStatusHandler()->add('{FINISH}');
        $this->getStatusHandler()->finish();
    }

}