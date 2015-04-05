<?php
require_once('mage.php');
$cats = array();
$cc = Mage::getModel('catalog/category')->getCollection()->addAttributeToSelect('smiffys_code');
foreach($cc as $c){
 $sc = $c->getData('smiffys_code');
 if( $sc ){
    $cats[$sc] = $c->getId();
 }
}

$pc = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('catalogue_code');
foreach($pc as $p){
$cc = $p->getData('catalogue_code');
  echo "\n".$p->getId().'=>'.$cc."($cc)...";
  
 if(!isset($cats[$cc])){
  echo "Not exists";
    continue;
 }
  $p->setCategoryIds($cats[$cc]);
  $p->save();
  echo "{$cats[$cc]}:OK";
}