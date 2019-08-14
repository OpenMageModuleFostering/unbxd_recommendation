<?php 

class Unbxd_Datafeeder_Model_Mysql4_Field extends Mage_Core_Model_Mysql4_Abstract
{

	const STATUS = 'status';
	const DATA_TYPE = 'data_type';
	const SITE = 'site';
	const FIELD_NAME = "name";
	const TABLE_NAME = 'unbxd_field';
	const AUTOSUGGEST = 'autosuggest';
	const MULTIVALUED = 'multiValued';
	const IMAGE_HEIGHT = "image_height";
	const IMAGE_WIDTH = "image_width";
	const GENERATE_IMAGE = "generate_image";

	protected function _construct()
	{
		  $this->_init('datafeeder/field', 'field_id');
	}
	
	/*
	* Method to get Unbxd Fields Configuration as Mapping give the site
	*/
	public function getFieldMapping($site, $enabledFields = false) {
		$results = Mage::getModel('datafeeder/field')->getCollection()->addFieldToFilter(self::SITE, $site);
		$fieldMapping = array();
		$_reader = Mage::getSingleton('core/resource')->getConnection('core_read');
		if(method_exists($_reader, 'getTableName')) {
			$table = $_reader->getTableName(self::TABLE_NAME);
		} else {
			$table = self::TABLE_NAME;
		}
		$select = $_reader->select();
		$select->from($table);
		$filterCond = self::SITE . " = '" . $site . "'";
		if($enabledFields) {
		 	$filterCond = $filterCond . " AND " . self::STATUS ."='1'";
		}

		$select->where($filterCond);
		$results = $_reader->fetchAll($select);

		foreach($results as $eachResult) {
			$fieldMapping[$eachResult[self::FIELD_NAME]] = array(self::STATUS => $eachResult[self::STATUS], 
														self::DATA_TYPE => $eachResult[self::DATA_TYPE],
														self::AUTOSUGGEST => $eachResult[self::AUTOSUGGEST],
														self::IMAGE_HEIGHT =>$eachResult[self::IMAGE_HEIGHT],
														self::IMAGE_WIDTH => $eachResult[self::IMAGE_WIDTH],
														self::GENERATE_IMAGE =>$eachResult[self::GENERATE_IMAGE]
														);
		}
		return $fieldMapping;
	}

	/**
	* Returns the fields as an array of field Name to value
	**/
	public function getFields($site) {
		$fieldMapping = $this->getFieldMapping($site);
		$deltaUpdate = array();
		$attributes = Mage::helper('unbxd_datafeeder/UnbxdIndexingHelper')->getAttributes();
		$escapedFields = $this->getConflictedFeatureFieldLust();
		foreach($attributes as $attribute){
			$fieldName = $attribute->getAttributeCode();

			if (in_array($fieldName, $escapedFields)) {
				$fieldName = "_".$fieldName;
			}
 			if (!array_key_exists($fieldName, $fieldMapping)) {
				$deltaUpdate[$fieldName] = array(self::STATUS => 1, self::DATA_TYPE => 'longText');
				$fieldMapping[$fieldName] = array(self::STATUS => 1, 
					self::DATA_TYPE => 'longText', 
					self::AUTOSUGGEST => 1,
					self::IMAGE_HEIGHT => 0,
					self::IMAGE_WIDTH =>0,
					self::GENERATE_IMAGE =>0);
			}
			if(!array_key_exists($fieldName."Associated", $fieldMapping)) {
					$deltaUpdate[$fieldName."Associated"] = array(self::STATUS => 0, self::DATA_TYPE => 'longText');
					$fieldMapping[$fieldName."Associated"] = array(self::STATUS => 0, 
						self::DATA_TYPE => 'longText', 
						self::AUTOSUGGEST => 1,
						self::IMAGE_HEIGHT => 0,
						self::IMAGE_WIDTH =>0,
						self::GENERATE_IMAGE =>0);	
				}
		}
		if(sizeof($deltaUpdate) > 0) {
			$this->saveField($deltaUpdate, $site);
		}
		return $fieldMapping;
	}

	/*
	* update fields
	*/
	public function updateFields($fieldMapping, $site) {
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
		if(method_exists($write, 'getTableName')) {
			$table = $write->getTableName(self::TABLE_NAME);
		} else {
			$table = self::TABLE_NAME;
		}
		foreach($fieldMapping as $fieldName=>$values) {
			$values = json_decode($values, true);
			if (!isset($values[self::STATUS]) || !isset($values[self::DATA_TYPE]) || 
				!($values[self::STATUS] == 0 || $values[self::STATUS] == 1)) {
				throw new Exception("Invalid data with field " . $fieldName);
			} 
			$updateQuery = 'UPDATE `'. $table .'` set '.
					self::STATUS ." = '".$values[self::STATUS]."' , ".
					self::DATA_TYPE." = '". $values[self::DATA_TYPE]."' ,  ".
					self::AUTOSUGGEST." = '". $values[self::AUTOSUGGEST]."' ,  ".
					self::IMAGE_HEIGHT." = '".  (is_int($values[self::IMAGE_HEIGHT])?$values[self::IMAGE_HEIGHT]:0) ."' ,  ".
					self::IMAGE_WIDTH ." = '". (is_int($values[self::IMAGE_WIDTH])?$values[self::IMAGE_WIDTH]:0)."' ,  ".
					self::GENERATE_IMAGE." = '".$values[self::GENERATE_IMAGE]."' ". 
					' where '.self::SITE . "='".  $site . "' AND " . self::FIELD_NAME . "='".$fieldName."'";
			$write->query($updateQuery);
		}
	}

	/*
	* method to save the fieldMapping information
	*/
	public function saveField($fieldMapping, $site) {
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
		$insertingRequestArray = array();
		foreach($fieldMapping as $field=>$value) { 
			$insertingRequest = array();
			$insertingRequest[self::FIELD_NAME] = $field;
			$insertingRequest[self::STATUS] = $value[self::STATUS];
			$insertingRequest[self::SITE] = $site;
			$insertingRequest[self::DATA_TYPE] = $value[self::DATA_TYPE];
			$insertingRequest[self::AUTOSUGGEST] = $value[self::AUTOSUGGEST];
			$insertingRequest[self::IMAGE_HEIGHT] = $value[self::IMAGE_HEIGHT];
			$insertingRequest[self::IMAGE_WIDTH] = $value[self::IMAGE_WIDTH];
			$insertingRequest[self::GENERATE_IMAGE] = $value[self::GENERATE_IMAGE];
			$insertingRequestArray[] = $insertingRequest;
		}
		if(method_exists($write, 'getTableName')) {
			$table = $write->getTableName(self::TABLE_NAME);
		} else {
			$table = self::TABLE_NAME;
		}

		$write->insertMultiple($table, $insertingRequestArray);
	}

	/*
	* method to get the featured fields 
	*/
	public function getFeaturedFields() {
		$featuredFields = array();
		$featuredFields["uniqueId"]=$this->getField("text", "false", "false");
		$featuredFields["sellingPrice"]=$this->getField("decimal", "false", "false");
		$featuredFields["discount"]=$this->getField("decimal", "false", "false");
		$featuredFields["rating"]=$this->getField("decimal", "false", "false");
		$featuredFields["brandId"]=$this->getField("text", "false", "false");
		$featuredFields["catlevel1Name"]=$this->getField("text", "false", "false");
		$featuredFields["catlevel2Name"]=$this->getField("text", "false", "false");
		$featuredFields["catlevel3Name"]=$this->getField("text", "false", "false");
		$featuredFields["catlevel4Name"]=$this->getField("text", "false", "false");
		$featuredFields["category"]=$this->getField("text", "true", "true");
		$featuredFields["subCategory"]=$this->getField("text", "true", "true");
		$featuredFields["color"]=$this->getField("text", "true", "false");
		$featuredFields["size"]=$this->getField("text", "true", "false");
		$featuredFields["availability"]=$this->getField("bool", "false", "false");
		$featuredFields["description"]=$this->getField("longText", "false", "false");
		$featuredFields["imageUrl"]=$this->getField("link", "true", "false");
		$featuredFields["productUrl"]=$this->getField("link", "false", "false");
		$featuredFields["brand"]=$this->getField("text", "false", "true");
		$featuredFields["price"]=$this->getField("decimal", "false", "false");
		$featuredFields["title"]=$this->getField("text", "false", "true");
		$featuredFields["gender"]=$this->getField("text", "false", "false");
		$featuredFields["unbxdVisibility"]=$this->getField("text", "false", "false");
		return $featuredFields;
	}

	public function getField($dataType, $multiValued, $autosuggest) {
		return array( self::DATA_TYPE => $dataType,
					self::MULTIVALUED => ($multiValued=="true")?1:0,
					self::AUTOSUGGEST => ($autosuggest=="true")?1:0 );

	}


	public function getConflictedFeatureFieldLust() {
		return array('gender');
	}
}
?>