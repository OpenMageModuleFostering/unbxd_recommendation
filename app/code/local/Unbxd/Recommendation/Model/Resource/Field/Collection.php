<?php

/**
 * @category Unbxd
 * @package Unbxd_Recommendation
 * @author Unbxd Software Pvt. Ltd {
 */
class Unbxd_Recommendation_Model_Resource_Field_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     */
    public function _construct()
    {
        $this->_init('unbxd_recommendation/field');
    }

    /**
     * Method to add fields to display filter
     *
     * @return void
     */
    public function addFieldsDisplayFilter() {
        $this->addFieldToFilter(Unbxd_Recommendation_Model_Field::dislayable,1);
        return $this;
    }

    public function addWebsiteFilter(Mage_Core_Model_Website $website) {
        $this->addFieldToFilter(Unbxd_Recommendation_Model_Field::website_id, $website->getWebsiteId());
        return $this;
    }

    /**
     * Method to get field collection as array
     *
     * @return array
     */
    public function __asArray() {
        $fields = array();
        foreach($this->_items as $item) {
            $field = array();
            $field[Unbxd_Recommendation_Model_Field::field_name] = $item->getFieldName();
            $field[Unbxd_Recommendation_Model_Field::datatype] = $item->getDatatype();
            $field[Unbxd_Recommendation_Model_Field::autosuggest] = $item->getAutosuggest();
            $featureField = $item->getFeaturedField();
            if(isset($featureField)) {
                $field[Unbxd_Recommendation_Model_Field::featured_field] = $featureField;
            }
            $fields[] = $field;
        }
        return $fields;
    }

    public function getFeatureFields(Mage_Core_Model_Website $website) {
        $this->getSelect()->where(Unbxd_Recommendation_Model_Field::featured_field. " IS NOT NULL AND ".
            Unbxd_Recommendation_Model_Field::website_id . " = " . $website->getWebsiteId());
        return $this->load();
    }

    public function getFields(Mage_Core_Model_Website $website) {
        $this->getSelect()->where(Unbxd_Recommendation_Model_Field::website_id . " = " . $website->getWebsiteId());
        return $this->load();
    }
}

?>
