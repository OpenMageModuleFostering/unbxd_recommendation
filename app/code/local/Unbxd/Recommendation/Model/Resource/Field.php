<?php

/**
 * @category Unbxd
 * @package Unbxd_Recommendation
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recommendation_Model_Resource_Field extends Mage_Core_Model_Mysql4_Abstract
{

    const CAT_LEVEL_1 = "categoryLevel1";

    const CAT_LEVEL_2 = "categoryLevel2";

    const CAT_LEVEL_3 = "categoryLevel3";

    const CAT_LEVEL_4 = "categoryLevel4";

    const CAT_LEVEL_1_NAME = "catLevel1Name";

    const CAT_LEVEL_2_NAME = "catLevel2Name";

    const CAT_LEVEL_3_NAME = "catLevel3Name";

    const CAT_LEVEL_4_NAME = "catLevel4Name";

    const CATEGORY_IDS_NAME = "categoryIds";

    const CATEGORY_IDS = "category_ids";

    const CATEGORY_NAME = "category";

    const AVAILABILITY = 'availability';

    const FINAL_PRICE = 'final_price';

    const PRICE = 'price';
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
	({$websiteId}, 'gender', 'text', 0, 'gender', 0, 1),
	({$websiteId}, 'description', 'longText', 0, 'description', 0, 1),
	({$websiteId}, 'catLevel1Name', 'text', 0, 'catLevel1Name', 0, 0),
	({$websiteId}, 'catLevel2Name', 'text', 0, 'catLevel2Name', 0, 0),
	({$websiteId}, 'catLevel3Name', 'text', 0, 'catLevel3Name', 0, 0),
	({$websiteId}, 'catLevel4Name', 'text', 0, 'catLevel4Name', 0, 0),
	({$websiteId}, 'categoryLevel1', 'text', 0, 'categoryLevel1', 1, 0),
	({$websiteId}, 'categoryLevel2', 'text', 0, 'categoryLevel2', 1, 0),
	({$websiteId}, 'categoryLevel3', 'text', 0, 'categoryLevel3', 1, 0),
	({$websiteId}, 'categoryLevel4', 'text', 0, 'categoryLevel4', 1, 0),
	({$websiteId}, 'created_at', 'date', 0, NULL, 0, 1)
	({$websiteId}, 'availability', 'bool', 0, 'availability', 0, 0),
	({$websiteId}, 'status', 'number', 0, NULL, 0, 0),
	({$websiteId}, 'visibility', 'number', 0, NULL, 0, 0),
	({$websiteId}, 'qty', 'number', 0, NULL, 0, 0),
	({$websiteId}, 'categoryIds', 'longText', 0, NULL, 1, 0),
	({$websiteId}, 'category', 'text', 0, 'category', 1, 0),
	({$websiteId}, 'uniqueId', 'longText', 0, NULL, 0, 0),
	({$websiteId}, 'entity_id', 'longText', 0, NULL, 0, 0);";
    }
}

?>