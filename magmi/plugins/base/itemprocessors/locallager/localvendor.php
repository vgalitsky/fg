<?php
class LocalVendorItemProcessor extends Magmi_ItemProcessor
{

	protected $_dset=array();
	protected $_dcols=array();
	
    public function getPluginInfo()
    {
        return array(
            "name" => "Add local vendor",
            "author" => "mtr",
            "version" => "0.0.1",
        	//"url" => $this->pluginDocUrl("Udropship_Default_Values_setter")
        );
    }
	
	public function processItemAfterId(&$item,$params=null)
	{
        $ssql = 'SELECT vendor_product_id as vpid from `'.$this->tablename("udropship_vendor_product").'` WHERE product_id=?';
        $pvendor = $this->selectOne($ssql,array($params['product_id']),'vpid');

        if( !$pvendor ){
            $isql = 'INSERT INTO '.$this->tablename("udropship_vendor_product")."(vendor_id,product_id,vendor_sku,vendor_cost,stock_qty)
            VALUES(1,'{$params['product_id']}','{$item['sku']}','{$item['cost']}',0)";
            $this->insert($isql,array());

        }
        return true;
	}
	

static public function getCategory()
	{
		return "Temashop";
	}
}