<?php 

class Unbxd_Recscore_Model_Resource_Attribute {

	protected $attributeMap = array();
	public function getAttributeValue($attributeCode, $value, $product){
		if(!isset($this->attributeMap[$value])){
			if(!($product instanceof Mage_Catalog_Model_Product) || Mage::getResourceModel('catalog/product')->getAttribute($attributeCode) == null){
                        	return null;
                	}
			$options = Mage::getResourceModel('catalog/product')->getAttribute($attributeCode)
                		->getSource()->getAllOptions();
			foreach($options as $option){
				$this->attributeMap[$option["value"]] = $option["label"];
			}
		}
		return array_key_exists($value, $this->attributeMap)?$this->attributeMap[$value]:null;
	}

}

?>
