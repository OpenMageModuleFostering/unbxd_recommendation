<?php

/**
 * @category Unbxd
 * @package Unbxd_Recommendation
 * @author Unbxd Software Pvt. Ltd {
 */
class Unbxd_Recommendation_Model_Resource_Sync_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     */
    public function _construct()
    {
        $this->_init('unbxd_recommendation/sync');
    }

    /**
     * add field to Filter
     * @param $websiteId
     * @return void
     */
    public function addWebsiteFilter($websiteIds) {
        $this->addFieldToFilter(Unbxd_Recommendation_Model_Sync::WEBSITE_ID, $websiteIds);
        return $this;
    }

    /**
     * Add Unsync filter
     * @return void
     */
    public function addUnsyncFilter() {
        $this->addFieldToFilter(Unbxd_Recommendation_Model_Sync::SYNCED,
            Unbxd_Recommendation_Model_Sync::SYNCED_FALSE);
        return $this;
    }

    /**
     * Add Synced filter
     * @return void
     */
    public function addSyncFilter() {
        $this->addFieldToFilter(Unbxd_Recommendation_Model_Sync::SYNCED,
            Unbxd_Recommendation_Model_Sync::SYNCED_TRUE);
        return $this;
    }

    public function addOperationFilter($operation = Unbxd_Recommendation_Model_Sync::OPERATION_ADD) {
        $this->addFieldToFilter(Unbxd_Recommendation_Model_Sync::OPERATION, $operation);
        return $this;
    }
}

?>