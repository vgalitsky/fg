<?php
ini_set('display_errors', 1);
$dir = dirname(__FILE__);
$dir = preg_replace('/(.*?app\/).*/','\1',$dir);
//die($dir);
//require $dir.'Mage.php';
require_once '/home/mtr/www/mage/html/app/Mage.php';
umask(0);
Mage::setIsDeveloperMode(true);
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);


