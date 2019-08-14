<?php 

class Unbxd_Datafeeder_Model_Mysql4_Attribute {

	protected $attributeMap = array();
	public function getAttributeValue($attributeCode, $value, $product){
		if(!isset($this->attributeMap[$value])){
			if(!($product instanceof Mage_Catalog_Model_Product)){
                        	return null;
                	}
			$options = Mage::getResourceModel('catalog/product')->getAttribute($attributeCode)
                		->getSource()->getAllOptions();
			foreach($options as $option){
				$this->attributeMap[$option["value"]] = $option["label"];
			}
		}
		return $this->attributeMap[$value];
	}

}

?>
