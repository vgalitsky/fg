<style>
.catalogsearch-result-index .block-layered-nav{ display:none;}
</style>

<?php
class Vinagento_Vpager_Model_Layer_Filter_Item 
extends Mage_Catalog_Model_Layer_Filter_Item{
    public function getUrl(){
    	return str_replace('?','#%21',parent::getUrl());
    }
    public function getRemoveUrl(){
    	return str_replace('?','#%21',parent::getRemoveUrl());
    }
}