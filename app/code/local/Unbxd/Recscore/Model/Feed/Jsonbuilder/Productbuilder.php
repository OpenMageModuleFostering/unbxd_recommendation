<?php

class Unbxd_Recscore_Model_Feed_Jsonbuilder_Productbuilder extends
	Unbxd_Recscore_Model_Feed_Jsonbuilder_Jsonbuilder {

	const NUMBER = "number";
	const DECIMAL = "decimal";
	const DATE = "date";
	const IMAGE_HEIGHT = "image_height";
	const IMAGE_WIDTH = "image_width";
	const GENERATE_IMAGE = "generate_image";
	static $CATEGORY_EXCLUSION_LIST = array();

	/**
	 * @return Unbxd_Recscore_Helper_Feedhelper
	 */
	protected function _getFeedHelper() {
		return Mage::helper('unbxd_recscore/feedhelper');
	}

	/**
	 * Given product collection, gives the products in unbxd formatted json
	 * @param $website
	 * @param $collection
	 * @param $fields
	 * @return string
	 */
	public function getProducts($website, $collection, $fields, $copyFields) {
		$this->_changeTheme(Mage::getStoreConfig('design/package/name', $website->getDefaultStore()->getCode()),
			Mage::getStoreConfig('design/package/theme', $website->getDefaultStore()->getCode()));
		self::$CATEGORY_EXCLUSION_LIST = Mage::helper('unbxd_recscore/confighelper')->getCategoryExclusion($website);
		$content='';
		$firstLoop = true;
		foreach($collection as $product) {
			if($this->skipProduct($website, $product)) {
				continue;
			}
			$productArray = $this->getProduct($website, $product, $fields, $copyFields);
			$productArray = $this->postProcessProduct($productArray, $fields, false);
			if(!$this->whetherOOSproductsToBeIncluded($website, $productArray)) {
				continue;
			}
			if(!$firstLoop) {
				$content = $content . ",";
			}
			$content=$content.json_encode($productArray);
			$firstLoop = false;
		}

		return rtrim($content, ",");
	}

	public function whetherOOSproductsToBeIncluded($website, $productArray) {
		if(!Mage::helper('unbxd_recscore')
			->isConfigTrue($website, Unbxd_Recscore_Helper_Constants::INCLUDE_OUT_OF_STOCK) &&
			array_key_exists(Unbxd_Recscore_Model_Resource_Field::AVAILABILITY, $productArray)) {
				return $productArray[Unbxd_Recscore_Model_Resource_Field::AVAILABILITY] == "true"?true:false;
		}
		return true;
	}

	/**
	 * Method to check whether the product to be skipped or not depending on the filters
	 * @param Mage_Core_Model_Website $website
	 * @param $product
	 * @return bool
	 */
	public function skipProduct(Mage_Core_Model_Website $website, $product) {
		if (!Mage::helper('unbxd_recscore')
			->isConfigTrue($website, Unbxd_Recscore_Helper_Constants::INCLUDE_OUT_OF_STOCK)
			&& !is_null($this->isSalableWithBasicAttr($product))) {
			return !$this->isSalableWithBasicAttr($product);
		}

		$filters = $this->_getFeedHelper()->getFilters($website);
		foreach($filters as $key=>$filter) {
			if($this->_getFeedHelper()->isMultiSelect($key)) {
				$values = $this->_getMultiSelectAttribute($key, $product);
				if(!is_array($values)) {
					return false;
				}
				if(in_array($filter, $values)) {
					return true;
				}
			} else if($this->_getFeedHelper()->getFieldType($key) == 'price') {
				$temp = explode(Unbxd_Recscore_Helper_Constants::FILTER_RANGE_DELIMITER,$filter);
				if(sizeof($temp) >=2) {
					$from = int($temp[0]);
					$to = int($temp[1]);
				} else {
					return false;
				}

				if(int($product->getData($key)) > $from && int($product->getData($key)) < $to) {
					return true;
				}
			} else {
				if($filter == $product->getData($key)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Method to handle Fields
	 */
	protected function _handleFields($columnHeader, $unbxdFieldName, $product, &$productArray, $fields, $childProduct) {
		$columndata = $product->getData($columnHeader);
		if($columnHeader=="entity_id"){
			$uniqueIdField =  'uniqueId'.(($childProduct)?'Associated':'');
			$productArray[$uniqueIdField] = $columndata;
		}
		if($columnHeader=="url_path"){
			// handling the url
			$productArray[$unbxdFieldName] = $product->getProductUrl();
		} else if ($this->_getFeedHelper()->isImage($columnHeader)) {
			// handling tthe images
			$attributeValue = $this->_getImage($columnHeader, $unbxdFieldName, $product, $fields);
			if(!is_null($attributeValue)) {
				$productArray[$unbxdFieldName] = $attributeValue;
			}
		} else if( Mage::helper('unbxd_recscore/feedhelper')->isMultiSelectDatatype($columnHeader)){
			// handling the array/ multiselect attribute
			$attributeValue = $this->_getMultiSelectAttribute($columnHeader, $product);
			if(sizeof($attributeValue) == 1) {
				$attributeValue = $attributeValue[0];
			}
			if(!is_null($attributeValue)) {
				$productArray[$unbxdFieldName] = $attributeValue;
			}
		} else if (!is_null($columndata) && $columndata != ""){
			//adding the normal attribute
			$productArray[$unbxdFieldName] = $columndata;
		}
	}

	/**
	 * method to get the product in json
	 * @param Mage_Core_Model_Website $website
	 * @param $product
	 * @param $fields
	 * @param bool $childProduct
	 * @return array
	 */
	public function getProduct(Mage_Core_Model_Website $website, $product, $fields, $copyFields, $childProduct = false) {
		$productArray =array();

		foreach($product->getData('') as $columnHeader=>$columndata){

			$unbxdFieldName = $this->getUnbxdFieldName($columnHeader, $childProduct);
			if(isset($unbxdFieldName) && $unbxdFieldName != "" && !array_key_exists($unbxdFieldName, $fields)) {
				continue;
			}
			$this->_handleFields($columnHeader, $unbxdFieldName, $product, $productArray, $fields, $childProduct);
			if(array_key_exists($columnHeader, $copyFields)) {
				$this->_handleFields($columnHeader, $copyFields[$columnHeader], $product, $productArray, $fields, $childProduct);
			}
		}
		if(!$childProduct) {

			if($this->_getFeedHelper()
				->isConfigTrue($website, Unbxd_Recscore_Helper_Constants::INCLUDE_CHILD_PRODUCT)) {
				$productArray = $this->addChildrens($website, $product, $fields, $copyFields, $productArray);
			}

			$category = $this->_getCategoryAttribute($product);
			// adding the category
			$productArray = $category + $productArray;

			$productArray[Unbxd_Recscore_Model_Resource_Field::AVAILABILITY] =
				$this->isSalable($product, $productArray)? "true": "false";
			if(array_key_exists('final_price', $fields)) {
				$productArray['final_price'] = $product->getFinalPrice();
			}
			if(array_key_exists('url_path', $fields)) {
				$productArray['url_path'] = $product->getProductUrl();
			}
		} else {
			$productArray[Unbxd_Recscore_Model_Resource_Field::AVAILABILITY_ASSOCIATED] = $this->isSalable($product,array())?"true":"false";
		}
		return $productArray;
	}

	/**
	 * Retrieve Manage Stock data wrapper
	 *
	 * @return int
	 */
	private function isManageStockEnabled($product)
	{
		if ($product->getUseConfigManageStock()) {
			return (int) Mage::getStoreConfigFlag(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_MANAGE_STOCK);
		}
		return $product->getData('manage_stock') == "0"?false:true;
	}

	/**
	 * method to get category content in xml given the product object
	 * @param $product
	 * @return array
	 */
	protected function _getCategoryAttribute($product){
		$cats = $product->getCategoryIds();
		$categoryIds = array();
		$category = array();
		$categoryData = array();
		foreach ($cats as $categoryId) {
			$_cat = $this->_getFeedHelper()->getCategory($categoryId);
			if($_cat == null) {
				continue;
			}
			$categoryName = $_cat->getName();
			if($categoryName == null || $categoryName == "" || !$_cat->getIsActive() ) {
				continue;
			}
			$categoryIds[] = (string)$categoryId;
			$category[] = $categoryName;
		}

		for($level =1; $level <=4 ; $level++) {
			$levelCategories = $this->_getFeedHelper()
				->getCategoryOnLevel($categoryIds, $level+1);

			if (sizeof($levelCategories) > 0) {
				$categoryData['categoryLevel' . $level] = array_values(array_diff($levelCategories, self::$CATEGORY_EXCLUSION_LIST));
				$categoryData['catlevel' . $level . 'Name'] = $levelCategories[0];
				$category = array_merge($category, $levelCategories);
			}
		}

		$categoryData[Unbxd_Recscore_Model_Resource_Field::CATEGORY_IDS_NAME] = $categoryIds;
		$categoryData[Unbxd_Recscore_Model_Resource_Field::CATEGORY_NAME] = array_values(array_unique(array_diff($category, self::$CATEGORY_EXCLUSION_LIST)));

		return $categoryData;
	}

	/**
	 * method to returns as an array of values given the fieldName and the product
	 * @param $fieldName
	 * @param $product
	 * @return array|null
	 */
	protected function _getMultiSelectAttribute($fieldName, $product) {
		$data = explode(",", $product->getData($fieldName));
		$valueAsAnArray = array();
		foreach($data as $eachdata){
			$attributeValue = Mage::getResourceSingleton("unbxd_recscore/attribute")
				->getAttributeValue($fieldName, trim($eachdata), $product);
			if(!is_null($attributeValue) && $attributeValue != "" && $attributeValue != "Use Config") {
				$valueAsAnArray[] = $attributeValue;

			}
		}
		if(sizeof($valueAsAnArray) > 0) {
			return $valueAsAnArray;
		}
		return null;
	}

	protected function _changeTheme($packageName, $themeName) {
		Mage::getDesign()->setArea('frontend')
			->setPackageName($packageName)
			->setTheme($themeName);
	}

	/**
	 * Method given the fieldName and product, returns full image url
	 * @param $fieldName
	 * @param $unbxdFieldName
	 * @param $product
	 * @param $fields
	 * @return string
	 */
	protected  function _getImage($fieldName, $unbxdFieldName, $product, $fields) {
		if(array_key_exists(self::GENERATE_IMAGE, $fields[$unbxdFieldName]) &&
			$fields[$unbxdFieldName][self::GENERATE_IMAGE] == "1") {
			try {
				return (string)Mage::helper('catalog/image')->init($product, $fieldName)
					->resize(155, 155);
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
		return $unbxdFieldName;
		//return $this->renameConflictedFeatureFields($unbxdFieldName);
	}

	/*
	* Renaming the conflicted unbxd feature fields eg: gender to _gender
	*/
	private function renameConflictedFeatureFields($unbxdFieldName) {
		if (in_array($unbxdFieldName, Mage::getResourceSingleton('unbxd_recscore/field')->getConflictedFeatureFieldLust())) {
			return "_" . $unbxdFieldName;
		}
		return $unbxdFieldName;
	}

	private function isSalableWithBasicAttr($product) {
		if($product->getData("status") != 1) {
			return false;
		}
		if($product->getTypeId() == "simple" ||
			$product->getTypeId() == "downloadable" ||
			$product->getTypeId() == "virtual") {
			if (!$this->isManageStockEnabled($product)) {
				return true;
			}
			return $product->getData('is_in_stock')?true:false;
		}
		if(!$product->getData('is_in_stock')) {
			return false;
		}
		return null;
	}

	private function isSalable($product, $productArray) {
		if(is_null($this->isSalableWithBasicAttr($product))) {
			return $this->isSalableChildProducts($productArray);
		}
		return $this->isSalableWithBasicAttr($product);
	}

	private function isSalableChildProducts($productArray) {
		if(array_key_exists("associatedProducts", $productArray)) {
			foreach( $productArray["associatedProducts"] as $childProduct) {
				if($childProduct["availabilityAssociated"][0] == "true"){
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * gives the children in form of array
	 */
	private function addChildrens(Mage_Core_Model_Website $website,  $product, $fields, $copyFields, $productArray) {

		$type = $product->getData('type_id');
		if($type == "configurable") {
			return $this->getConfigurableChildProduct($website, $product, $fields, $copyFields, $productArray);
		} else if( $type == "grouped") {
			return $this->getGroupedChildProduct($website, $product, $fields, $copyFields, $productArray);
		} else if( $type == "bundle") {
			return $this->getBundledChildProduct($website, $product, $fields, $copyFields, $productArray);
		} else {
			return $productArray;
		}
	}

	private function getBundledChildProduct(Mage_Core_Model_Website $website, Mage_Catalog_Model_Product $product,
											$fields, $copyFields, $productArray) {
		$adapter = Mage::getSingleton("core/resource");
		$_catalogInventoryTable = method_exists($adapter, 'getTableName')
			? $adapter->getTableName('cataloginventory_stock_item') : 'cataloginventory_stock_item';
		$stockfields = array("qty" => "qty", "manage_stock" => "manage_stock",
			"use_config_manage_stock" => "use_config_manage_stock", "is_in_stock" => "is_in_stock");
		$associatedProducts = $product->getTypeInstance(true)
			->getSelectionsCollection($product->getTypeInstance(true)->getOptionsIds($product), $product)
			->addAttributeToSelect('*')
			->joinTable($_catalogInventoryTable, 'product_id=entity_id', $stockfields, null, 'left');
		if($this->_getFeedHelper()->isConfigTrue($website, Unbxd_Recscore_Helper_Constants::INCLUDE_ENABLED_CHILD_PRODUCT)) {
			$associatedProducts->addAttributeToFilter('status', 1);
		}
		if(!$this->_getFeedHelper()->isConfigTrue($website, Unbxd_Recscore_Helper_Constants::INCLUDE_OUT_OF_STOCK_CHILD_PRODUCT)) {
			$associatedProducts->addAttributeToFilter('qty',array('gt' => 1));
		}
		return $this->processAssociatedProducts($website, $associatedProducts, $fields, $copyFields, $productArray);
	}

	private function getGroupedChildProduct(Mage_Core_Model_Website $website, Mage_Catalog_Model_Product $product,
											$fields, $copyFields, $productArray) {
		$adapter = Mage::getSingleton("core/resource");
		$_catalogInventoryTable = method_exists($adapter, 'getTableName')
			? $adapter->getTableName('cataloginventory_stock_item') : 'cataloginventory_stock_item';
		$stockfields = array("qty" => "qty", "manage_stock" => "manage_stock",
			"use_config_manage_stock" => "use_config_manage_stock", "is_in_stock" => "is_in_stock");
		$associatedProducts = $product->getTypeInstance(true)
			->getAssociatedProductCollection($product)
			->addAttributeToSelect('*')
			->joinTable($_catalogInventoryTable, 'product_id=entity_id', $stockfields, null, 'left');
		if($this->_getFeedHelper()->isConfigTrue($website, Unbxd_Recscore_Helper_Constants::INCLUDE_ENABLED_CHILD_PRODUCT)) {
			$associatedProducts->addAttributeToFilter('status', 1);
		}
		if(!$this->_getFeedHelper()->isConfigTrue($website, Unbxd_Recscore_Helper_Constants::INCLUDE_OUT_OF_STOCK_CHILD_PRODUCT)) {
			$associatedProducts->addAttributeToFilter('qty',array('gt' => 1));
		}
		return $this->processAssociatedProducts($website, $associatedProducts, $fields, $copyFields, $productArray);
	}

	private function getConfigurableChildProduct(Mage_Core_Model_Website $website,  $product, $fields,
												 $copyFields, $productArray) {
		$conf = Mage::getModel('catalog/product_type_configurable')->setProduct($product);
		$stockfields = array("qty" => "qty", "manage_stock" => "manage_stock",
			"use_config_manage_stock" => "use_config_manage_stock", "is_in_stock" => "is_in_stock");
		$adapter = Mage::getSingleton("core/resource");
		$_catalogInventoryTable = method_exists($adapter, 'getTableName')
			? $adapter->getTableName('cataloginventory_stock_item') : 'catalog_category_product_index';
		$childrens = $conf->getUsedProductCollection()
			->addAttributeToSelect('*')
			->addFilterByRequiredOptions()
			->joinTable($_catalogInventoryTable, 'product_id=entity_id', $stockfields, null, 'left');
		if($this->_getFeedHelper()->isConfigTrue($website, Unbxd_Recscore_Helper_Constants::INCLUDE_ENABLED_CHILD_PRODUCT)) {
			$childrens->addAttributeToFilter('status', 1);
		}
		if(!$this->_getFeedHelper()->isConfigTrue($website, Unbxd_Recscore_Helper_Constants::INCLUDE_OUT_OF_STOCK_CHILD_PRODUCT)) {
			$childrens->addAttributeToFilter('qty', array('gt' => 1));
		}
		return $this->processAssociatedProducts($website, $childrens, $fields, $copyFields, $productArray);
	}

	private function processAssociatedProducts(Mage_Core_Model_Website $website,  $childProducts,
											   $fields, $copyFields, $productArray) {
		$associatedProducts = array();
		foreach ($childProducts as $children) {
			$childProduct = $this->getProduct($website, $children, $fields, $copyFields, true);
			if(isset($childProduct) && sizeof($childProduct) > 0 ) {
				$childProduct = $this->postProcessProduct($childProduct, $fields, true);
				$associatedProducts[] = $childProduct;
			}
		}
		if( sizeof($associatedProducts) > 0) {
			$productArray["associatedProducts"] = $associatedProducts;
		}
		return $productArray;
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
		if($data_type[Unbxd_Recscore_Model_Field::datatype] == self::DECIMAL) {
			return $this->getFloatValues($value);
		} else if ($data_type[Unbxd_Recscore_Model_Field::datatype] == self::NUMBER) {
			return $this->getNumberValues($value);
		} else if ($data_type[Unbxd_Recscore_Model_Field::datatype] == self::DATE) {
			return $this->getDateValues($value);
		}
		return $value;
	}

	/*
	* returns the product by changing it to multivalued after checking its data type
	*/
	public function convertMultivalued($product, $fields = null) {
		foreach($product as $field=>$value) {
			if((is_null($fields) ||
					(($field != "associatedProducts") &&
						array_key_exists(Unbxd_Recscore_Model_Field::multivalued,$fields[$field]) &&
						$fields[$field][Unbxd_Recscore_Model_Field::multivalued])) &&
				!is_array($value)) {

				$valueAsAnArray = array();
				$valueAsAnArray[] = $value;
				$product[$field] = $valueAsAnArray;
			}
		}
		return $product;
	}
}

?>
