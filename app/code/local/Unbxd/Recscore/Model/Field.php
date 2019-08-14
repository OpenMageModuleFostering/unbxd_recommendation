<?php

/**
 * This class maintains the config of the fields that are needed by unbxd
 *
 * @category Unbxd
 * @package Unbxd_Recscore
 * @author Unbxd Software Pvt. Ltd
 */
 class Unbxd_Recscore_Model_Field extends Mage_Core_Model_Abstract {

    /**
     * field name column in db
     */
    const field_name = "field_name";

    /**
     * datatype column name in db
     */
    const datatype = "datatype";

    /**
     * autosuggest column name in db
     */
    const autosuggest = "autosuggest";

    /**
     * featured_field column name in db
     */
    const featured_field = "featured_field";

    /**
     * displayable column name in db
     */
    const dislayable = "displayed";

    const multivalued = 'multivalued';

    /**
     * website id column name in db
     */
    const website_id = "website_id";

    const status = 'status';

    /**
     * All possible data type values supported unbxd
     * @var array
     */
    public static $data_types = array(Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_TEXT, Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_LONGTEXT,
					Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_LINK, Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_NUMBER,
					Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_DECIMAL, Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_DATE); 
				

    public static $displayableFeatureFields = array('title', 'price',
        'brand', 'color', 'size', 'imageUrl', 'productUrl');

    public static $featurefields = array();

    /**
     *
     * @return void
     */
    protected function _construct()
	{
		$this->_init('unbxd_recscore/field');
        Unbxd_Recscore_Model_Field::$featurefields = $this->getFeaturedFields();

	}


    /**
     * Save fields
     *
     * @return void
     */
    public function saveFields($collection) {
        $this->_getResource()->beginTransaction();
        try {
            foreach ($collection as $data) {
              if(sizeof($data) > 0) {
                if(array_key_exists("add", $data)) {
                    $data["add"]->save();
                } else if (array_key_exists("delete", $data)) {
                    $data["delete"]->delete();
                }
              }
            }

            $this->_getResource()->commit();
        } catch(Exception $e) {
            $this->_getResource()->rollBack();
            Mage::helper("unbxd_recscore")->log(Zend_Log::ERR, "Saving fields failed because " . $e->getMessage());
            return array('OTHERS' => $e->getMessage());
        }
        return true;

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
        $featuredFields[Unbxd_Recscore_Model_Resource_Field::CAT_LEVEL_1_NAME] =
            $this->getField("text", "false", "false");
        $featuredFields[Unbxd_Recscore_Model_Resource_Field::CAT_LEVEL_2_NAME] =
            $this->getField("text", "false", "false");
        $featuredFields[Unbxd_Recscore_Model_Resource_Field::CAT_LEVEL_3_NAME] =
            $this->getField("text", "false", "false");
        $featuredFields[Unbxd_Recscore_Model_Resource_Field::CAT_LEVEL_4_NAME] =
            $this->getField("text", "false", "false");
        $featuredFields[Unbxd_Recscore_Model_Resource_Field::CAT_LEVEL_1] =
            $this->getField("text", "true", "false");
        $featuredFields[Unbxd_Recscore_Model_Resource_Field::CAT_LEVEL_2] =
            $this->getField("text", "true", "false");
        $featuredFields[Unbxd_Recscore_Model_Resource_Field::CAT_LEVEL_3] =
            $this->getField("text", "true", "false");
        $featuredFields[Unbxd_Recscore_Model_Resource_Field::CAT_LEVEL_4] =
            $this->getField("text", "true", "false");
        $featuredFields["category"] = $this->getField("text", "true", "true");
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
        return array( self::status => 1, self::datatype => $dataType,
            self::multivalued => ($multiValued=="true")?1:0,
            self::autosuggest => ($autosuggest=="true")?1:0 );

    }

     public function getPriceFieldName() {
         $priceFieldConfig =
             Mage::helper('unbxd_recscore')->getEngineConfigData(Unbxd_Recscore_Helper_Constants::FEATURE_FIELD_PRICE);
         return $priceFieldConfig[Unbxd_Recscore_Helper_Constants::FEATURE_FIELD_PRICE];
    }

     public function getImageUrlFieldName() {
             $imageUrlFieldName = $this->_getResource()->getFieldByFeatureField(Mage::app()->getWebsite()->getWebsiteId(),
                 Unbxd_Recscore_Helper_Constants::FEATURE_FIELD_IMAGE_URL);
         return $imageUrlFieldName;
     }

     public function getProductUrlFieldName() {
         $fieldConfig =
             Mage::helper('unbxd_recscore')
                 ->getEngineConfigData(Unbxd_Recscore_Helper_Constants::FEATURE_FIELD_PRODUCT_URL);
         return $fieldConfig[Unbxd_Recscore_Helper_Constants::FEATURE_FIELD_PRODUCT_URL];

     }

     public function getImageFields($website) {
	$conf = Mage::helper('unbxd_recscore')->getEngineConfigData(Unbxd_Recscore_Helper_Constants::FIELD_CONF, $website, true);
        $fieldConf = json_decode($conf[Unbxd_Recscore_Helper_Constants::FIELD_CONF], true);
        if(!is_array($fieldConf)) {
             return array();
        }
	$imageFields = array();
	foreach($fieldConf as $field => $conf ) {
		if(!is_array($conf) || !array_key_exists('image_full', $conf)) {
			continue;
		}
		$imageFields[$field] = Mage::helper('unbxd_recscore')->isConfigTrue($website, 'image_full')?true:false;
	}
	return $imageFields;
     }

     public function getCopyFields($website) {
         $conf = Mage::helper('unbxd_recscore')->getEngineConfigData(Unbxd_Recscore_Helper_Constants::FIELD_CONF, $website, true);

         $fieldConf = json_decode($conf[Unbxd_Recscore_Helper_Constants::FIELD_CONF], true);
	 if(!is_array($fieldConf)) {
		return array();
	 }
         $imageFields = array();
         foreach($fieldConf as $field => $conf ) {
                 if(!is_array($conf) || !array_key_exists('copy_field', $conf)) {
                         continue;
                 }
                 $imageFields[$field] = $conf['copy_field'];
         }
         return $imageFields;
      }

     public function validateDatatype($unbxdDatatype, $magentoDatatype) {
	if($unbxdDatatype == Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_TEXT || $unbxdDatatype == Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_LONGTEXT ||
		$unbxdDatatype == Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_LINK)   {
		return true;
	}
	if($unbxdDatatype == Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_NUMBER && $magentoDatatype == Unbxd_Recscore_Helper_Constants::FIELD_TYPE_NUMBER) {
		return true;
	}
	if($unbxdDatatype == Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_DECIMAL && $magentoDatatype == Unbxd_Recscore_Helper_Constants::FIELD_TYPE_NUMBER) {
		return true;
	}
	if($unbxdDatatype == Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_DATE && $magentoDatatype == Unbxd_Recscore_Helper_Constants::FIELD_TYPE_DATE) {
		return true;
	}
	return false;
     }
}

?>
