<?php

/**
 * This class maintains the config of the fields that are needed by unbxd
 *
 * @category Unbxd
 * @package Unbxd_Recommendation
 * @author Unbxd Software Pvt. Ltd
 */
 class Unbxd_Recommendation_Model_Field extends Mage_Core_Model_Abstract {

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
    public static $data_types = array("text", "longText", "link", "decimal", "number", "datetime");

    public static $displayableFeatureFields = array('title', 'price',
        'brand', 'color', 'size', 'imageUrl', 'productUrl');

    public static $featurefields = array();

    /**
     *
     * @return void
     */
    protected function _construct()
	{
		$this->_init('unbxd_recommendation/field');
        Unbxd_Recommendation_Model_Field::$featurefields = $this->getFeaturedFields();

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
            Mage::helper("unbxd_recommendation")->log(Zend_Log::ERR, "Saving fields failed because " . $e->getMessage());
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
        return array( self::status => 1, self::datatype => $dataType,
            self::multivalued => ($multiValued=="true")?1:0,
            self::autosuggest => ($autosuggest=="true")?1:0 );

    }
}

?>
