<?php

/**
 * @category Unbxd
 * @package Unbxd_Recommendation
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recommendation_Model_Resource_Field extends Mage_Core_Model_Mysql4_Abstract
{

    const CAT_LEVEL_1_NAME = "catLevel1";

    const CAT_LEVEL_2_NAME = "catLevel2";

    const CAT_LEVEL_3_NAME = "catLevel3";

    const CATEGORY_IDS_NAME = "categoryIds";

    const CATEGORY_NAME = "category";
    /**
     * Unbxd Field Config table Name
     *
     * @var string
     */
    protected $_unbxdFieldTable;

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('unbxd_recommendation/field', 'id');
        $this->_unbxdFieldTable = $this->getTable('unbxd_recommendation/field');
    }

    public function getTableName() {
        return $this->_unbxdFieldTable;
    }

    public function getFieldByFeatureField($websiteId, $featureField){
        $adapter = $this->_getReadAdapter();

        $select = $adapter->select()
            ->from($this->_unbxdFieldTable, Unbxd_Recommendation_Model_Field::field_name)
            ->where('`'.Unbxd_Recommendation_Model_Field::website_id.'` = ?', (int)$websiteId)
            ->where('`'.Unbxd_Recommendation_Model_Field::featured_field.'` = ?', $featureField);

        $result = $adapter->fetchOne($select);
        if($result == false) {
            return null;
        }
        return $result;
    }

    public function getDisplayableFields(Mage_Core_Model_Website $website) {
        $fields = $this->getDisplayableFieldCollection($website);
        if(count($fields) == 0) {
            return $this->setDefaultFields($website)
                ->getDisplayableFieldCollection($website);
        }
        return $fields;
    }

    protected function getDisplayableFieldCollection(Mage_Core_Model_Website $website) {
        return Mage::getModel("unbxd_recommendation/field")
            ->getCollection()
            ->addFieldsDisplayFilter()
            ->addWebsiteFilter($website)
            ->load();
    }

    public function setDefaultFields(Mage_Core_Model_Website $website) {
        $_writer = $this->_getWriteAdapter();
        $_writer->query($this->getDefaultFieldInsertStatement($website));
        return $this;
    }

    public function getDefaultFieldInsertStatement(Mage_Core_Model_Website $website) {
        $websiteId = $website->getWebsiteId();
        if(is_null($websiteId)) {
            return "";
        }
        $fieldTable = Mage::getResourceModel('unbxd_recommendation/field')->getTableName();
        return "
INSERT INTO `{$fieldTable}` (`website_id`, `field_name`, `datatype`, `autosuggest`, `featured_field`, `multivalued`, `displayed`)
VALUES
	({$websiteId}, 'name', 'text', 1, 'title', 0, 1),
	({$websiteId}, 'final_price', 'decimal', 0, 'price', 0, 1),
	({$websiteId}, 'price', 'decimal', 0, NULL, 0, 1),
	({$websiteId}, 'brand', 'text', 0, 'brand', 0, 1),
	({$websiteId}, 'color', 'text', 0, 'color', 1, 1),
	({$websiteId}, 'size', 'text', 0, 'size', 1, 1),
	({$websiteId}, 'image', 'link', 0, 'imageUrl', 1, 1),
	({$websiteId}, 'url_path', 'link', 0, 'productUrl', 0, 1),
	({$websiteId}, '".self::CAT_LEVEL_1_NAME. "', 'text', 0, NULL, 1, 0),
	({$websiteId}, '".self::CAT_LEVEL_2_NAME. "', 'text', 0, NULL, 1, 0),
	({$websiteId}, '".self::CAT_LEVEL_3_NAME. "', 'text', 0, NULL, 1, 0),
	({$websiteId}, 'status', 'number', 0, NULL, 0, 0),
	({$websiteId}, 'visibility', 'number', 0, NULL, 0, 0),
	({$websiteId}, 'qty', 'number', 0, NULL, 0, 0),
	({$websiteId}, '".self::CATEGORY_IDS_NAME. "', 'longText', 0, NULL, {$websiteId}, 0),
	({$websiteId}, '".self::CATEGORY_NAME. "', 'text', 0, 'category', 1, 0),
	({$websiteId}, 'uniqueId', 'longText', 0, NULL, 0, 0),
	({$websiteId}, 'entity_id', 'longText', 0, NULL, 0, 0);";
    }
}

?>