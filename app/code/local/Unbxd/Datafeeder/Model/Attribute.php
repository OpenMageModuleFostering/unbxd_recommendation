<?php 

class Unbxd_Datafeeder_Model_Attribute{

	$attributeMap = array();
	public function getAttributeValue($attributeCode, $label, $product){
		if(!isset($this->attributeMap[$label])){
			if(!($product instanceof Mage_Catalog_Model_Product)){
                        	return null;
                	}
			$options = $product->getAttribute($attributeCode)
                		->getSource()->getAllOptions();
			foreach($options as $option){
				$this->attributeMap[$option["label"]] = $option["value"];
			}
		}
        return array_key_exists($value, $this->attributeMap)?$this->attributeMap[$value]:NULL;
	}

}

?>
