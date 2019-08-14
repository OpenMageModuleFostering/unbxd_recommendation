<?php

class Unbxd_Datafeeder_Model_Feed_Jsonbuilder_Productbuilder extends Unbxd_Datafeeder_Model_Feed_Jsonbuilder_Jsonbuilder {

	const DATA_TYPE = "data_type";
	const MULTIVALUED = "multiValued";
	const NUMBER = "number";
	const DECIMAL = "decimal";
	const DATE = "date";
	const IMAGE_HEIGHT = "image_height";
	const IMAGE_WIDTH = "image_width";
	const GENERATE_IMAGE = "generate_image";

	/*
	* method to get the products in json
	*/
	public function getProducts($collection, $fields) {
		$content='';
		$firstLoop = true;
 		foreach($collection as $product) {
 			if(!$firstLoop) {
				$content = $content . ",";
			}

			$productArray = $this->getProduct($product, $fields);
			$productArray = $this->postProcessProduct($productArray, $fields, false);
 			$content=$content.json_encode($productArray);

 			$firstLoop = false;
 		}
 		
 		return rtrim($content, ",");
	}


	/*
	* method to get the product in json
	*/
	public function getProduct($product, $fields, $childProduct = false) {

		$productArray =array();

		foreach($product->getData('') as $columnHeader=>$columndata){
		
			$unbxdFieldName = $this->getUnbxdFieldName($columnHeader, $childProduct);
			if(isset($unbxdFieldName) && $unbxdFieldName != "" && !array_key_exists($unbxdFieldName, $fields)) {
				continue;
			}
			
			if($columnHeader=="entity_id"){ 				
 				$productArray['uniqueId'] = $columndata;
 			}

 			if($columnHeader=="url_path"){
 				// handling the url
 				$productArray[$unbxdFieldName] = Mage::getUrl('').$columndata;
            } else if (Mage::helper('unbxd_datafeeder/UnbxdIndexingHelper')->isImage($columnHeader)) {
            	// handling tthe images
            	$attributeValue = $this->getImage($columnHeader, $unbxdFieldName, $product, $fields);
            	if(!is_null($attributeValue)) {
            		$productArray[$unbxdFieldName] = $attributeValue;
            	}
            	
            } else if( Mage::helper('unbxd_datafeeder/UnbxdIndexingHelper')->isMultiSelect($columnHeader)){
            	// handling the array/ multiselect attribute
            	$attributeValue = $this->getMultiSelectAttribute($columnHeader, $product);
            	if(!is_null($attributeValue)) {
            		$productArray[$unbxdFieldName] = $attributeValue;
            	}
			} else if (!is_null($columndata) && $columndata != ""){
 				//adding the normal attribute
				$productArray[$unbxdFieldName] = $columndata;
			}
		} 
		if(!$childProduct) {
			// adding the childProduct
			$productArray = $this->addChildrens($product, $fields, $productArray);

			$category = $this->getCategoryAttribute($product);
			// adding the category
			$productArray = $category + $productArray;
		}
		return $productArray;
	}

	/**
 	* method to get category content in xml given the product object
 	*/
 	private function getCategoryAttribute($product){
 		$cats = $product->getCategoryIds();
 		$categoryIds = array();
 		$category = array();
 		$categoryData = array();
 		$index = 0;
		foreach ($cats as $categoryId) {
			$_cat = Mage::helper('unbxd_datafeeder/UnbxdIndexingHelper')->getCategory($categoryId);
			if($_cat == null) {
				continue;	
			}
			$categoryName = $_cat->getName();
			if($categoryName == null || $categoryName == "") {
				continue;
			}
			if($index++ < 4) {
				$categoryData['catlevel' . $index . 'Name'] = $categoryName;
			}
			$categoryIds[] = (string)$categoryId;
			$category[] = $categoryName;
		} 
		$categoryData['categoryIds'] = $categoryIds;
		$categoryData['category'] = $category;
		return $categoryData;
 	}
 
	/*
	* method to returns as an array of values given the fieldName and the product
	*/
	private function getMultiSelectAttribute($fieldName, $product) {
		$data = explode(",", $product->getData($fieldName));
		$valueAsAnArray = array();
		foreach($data as $eachdata){
			$attributeValue = Mage::getResourceSingleton("datafeeder/attribute")
								->getAttributeValue($fieldName, trim($eachdata), $product);
			if(!is_null($attributeValue) && $attributeValue != "" && attributeValue != "Use Config") {
				$valueAsAnArray[] = $attributeValue;
			}
		}
		if(sizeof($valueAsAnArray) > 0) {
			return $valueAsAnArray;
		}
		return null;
	}

	/*
	* generates the image 
	*/
	private function getImage($fieldName, $unbxdFieldName, $product, $fields) {

		if($fields[$unbxdFieldName][self::GENERATE_IMAGE] == "1") {
			Mage::getDesign()->setArea('frontend');
    		try {
    			return (string)Mage::helper('catalog/image')->init($product, $fieldName)
    										->resize($fields[$unbxdFieldName][self::IMAGE_WIDTH],
    											$fields[$unbxdFieldName][self::IMAGE_HEIGHT]);
    		} catch (Exception $e) {
    			error_log("Error while fetching the image" . $e->getMessage());
    		}
    	} 
    	return $product->getData($fieldName);
	}

	/*
	* get unbxd specfic field name for the magento field name
	*/
	private function getUnbxdFieldName($columnHeader, $isChild) {
		if($isChild) {
			$unbxdFieldName = $columnHeader . "Associated";
		} else {
			$unbxdFieldName = $columnHeader;
		}
		return $this->renameConflictedFeatureFields($unbxdFieldName);
	}

	/*
	* Renaming the conflicted unbxd feature fields eg: gender to _gender
	*/
	private function renameConflictedFeatureFields($unbxdFieldName) {
		if (in_array($unbxdFieldName, Mage::getResourceSingleton('datafeeder/field')->getConflictedFeatureFieldLust())) {
			return "_" . $unbxdFieldName;
		}
		return $unbxdFieldName;
	}

	/**
	* gives the children in form of array
	*/
	public function addChildrens($product, $fields, $productArray) {
		$type = $product->getData('type_id');
		if ($type == "configurable" || $type == "grouped" ) {
			$associatedProducts = array();
			if ($product->getTypeId() == 'grouped') {
                $childrens = $product->getTypeInstance(true)->getAssociatedProducts($product);
            } else{
                $conf = Mage::getModel('catalog/product_type_configurable')->setProduct($product);
                $childrens = $conf->getUsedProductCollection()
                                                        ->addAttributeToSelect('*')
                                                        ->addFilterByRequiredOptions();
            }
			$conf = Mage::getModel('catalog/product_type_configurable')->setProduct($product);
	    	$childrens = $conf->getUsedProductCollection()
	    							->addAttributeToSelect('*')
	    							->addFilterByRequiredOptions();
	    	foreach ($childrens as $children)
	    	{
	    		$childProduct = $this->getProduct($children, $fields, true);
	    		if(isset($childProduct) && sizeof($childProduct) > 0 ) {
		    		$childProduct = $this->postProcessProduct($childProduct, $fields, true);
		    		$associatedProducts[] = $childProduct;
		    	}
	    	}
	    	if( sizeof($associatedProducts) > 0) {
	    		$productArray["associatedProducts"] = $associatedProducts;
	    	}
	    	return $productArray;
		} else {
			return $productArray;
		}
	}

	/**
	* process the prodcut
	*/
	public function postProcessProduct($product, $fields, $isChild=false) {
		if($isChild) {
			$product = $this->convertMultivalued($product);
		} else {
			$product = $this->convertMultivalued($product, $fields);
		}
		$product = $this->convertDataType($product, $fields, $isChild);
		return $product;
	}

	/*
	* convert the data type according to the dashboard setup
	*/
	public function convertDataType($product, $fields) {
		foreach($product as $fieldName => $value) {
			if($fieldName != "associatedProducts") {
				$product[$fieldName] = $this->convertDataTypeByValue($fields[$fieldName], $value);
			}
		}
		return $product;
	}

	/*
	* method to get the float values
	*/
	private function getFloatValues($value) {
		if(is_array($value)) {
			$valueAsAnArray  = array();
			foreach ($value as $eachValue) {
				$valueAsAnArray[] = floatval($eachValue);
			}
			return $valueAsAnArray;
		} else {
			return floatval($value);
		}
	}


	/*
	* returns the array as number
	*/
	private function getNumberValues($value) {
		if(is_array($value)) {
			$valueAsAnArray  = array();
			foreach ($value as $eachValue) {
				$valueAsAnArray[] = intval($eachValue);
			}
			return $valueAsAnArray;
		} else {
			return intval($value);
		}
	}

	/*
	* returns the date value
	*/
	private function getDateValues($value) {
		if(is_array($value)) {
			$tempValue = array();
			foreach ($value as $eachValue) {
				$tokens = explode(" ",$eachValue);
				$tempValue[] = $tokens[0].'T'.$tokens[1].'Z';
			}
			return $tempValue;
		}
		$tokens = explode(" ",$value);
		$value = $tokens[0].'T'.$tokens[1].'Z';
		return $value;
	}

	/*
	* returns the data type value
	*/
	private function convertDataTypeByValue($data_type, $value) {
		if($data_type[self::DATA_TYPE] == self::DECIMAL) {
			return $this->getFloatValues($value);
		} else if ($data_type[self::DATA_TYPE] == self::NUMBER) {
			return $this->getNumberValues($value);
		} else if ($data_type[self::DATA_TYPE] == self::DATE) { 
			return $this->getDateValues($value);
		}
		return $value;
	}

	/*
	* returns the product by changing it to multivalued after checking its data type
	*/
	public function convertMultivalued($product, $fields = null) {
		foreach($product as $field=>$value) {
            if(is_null($fields) || 
            	(($field != "associatedProducts") && 
            		(array_key_exists(self::MULTIVALUED,$fields[$field]) && 
            			$fields[$field][self::MULTIVALUED]) && 
            		!is_array($value))) {
				$valueAsAnArray = array();
				$valueAsAnArray[] = $value;
				$product[$field] = $valueAsAnArray;
			}
		}
		return $product;
	}
}

?>