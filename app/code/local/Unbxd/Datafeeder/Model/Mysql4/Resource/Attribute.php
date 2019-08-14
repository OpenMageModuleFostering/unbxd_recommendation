<?php 

class Unbxd_Datafeeder_Model_Resource_Attribute{

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
		return $this->attributeMap[$label];
	}

}

?>
