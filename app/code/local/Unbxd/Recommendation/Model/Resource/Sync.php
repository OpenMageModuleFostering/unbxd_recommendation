<?php

/**
 * @category Unbxd
 * @package Unbxd_Recommendation
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recommendation_Model_Resource_Sync extends Mage_Core_Model_Mysql4_Abstract {

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('unbxd_recommendation/sync', 'id');
    }
}