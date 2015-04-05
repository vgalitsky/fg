<?php
ini_set('display_errors', 1);
require dirname(__FILE__) . '/../../app/Mage.php';
umask(0);
Mage::setIsDeveloperMode(true);
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);


