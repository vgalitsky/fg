<?php
require_once 'mage.php';

$smiffys = Mage::getModel('wiosmiffys/import_product');
$smiffys->import();