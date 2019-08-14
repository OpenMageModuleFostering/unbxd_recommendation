<?php

/**
 * @category Unbxd
 * @package Unbxd_Recscore
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recscore_Model_Resource_Sync extends Mage_Core_Model_Mysql4_Abstract {

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('unbxd_recscore/sync', 'id');
    }
}