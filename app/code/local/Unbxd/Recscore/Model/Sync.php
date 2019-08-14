<?php

/**
 * Model which maintains the product sync
 * @category Unbxd
 * @package Unbxd_Recscore
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recscore_Model_Sync extends Mage_Core_Model_Abstract {

    /** Value to store the synced boolean value */
    const SYNCED_FALSE = 0;

    /** Value to store the synced boolean value */
    const SYNCED_TRUE = 1;

    const WEBSITE_ID = 'website_id';

    const SYNCED = 'synced';

    const SYNCED_TIME = 'sync_time';

    const PRODUCT_ID = 'product_id';

    const OPERATION_ADD = 'ADD';

    const OPERATION_DELETE = 'DELETE';

    const OPERATION = 'operation';

    const UPDATED_TIME = 'updated_time';

    /**
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('unbxd_recscore/sync');

    }

    /**
     * Method to addProduct to sync
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    public function addProduct(Mage_Catalog_Model_Product $product = null) {
        if (is_null($product) || !$product->hasData('entity_id')) {
            Mage::helper('unbxd_recscore')->log(Zend_Log::ERR, 'product argument sent is empty');
            return false;
        }
        $write = Mage::getSingleton("core/resource")->getConnection("core_write");
        foreach($product->getWebsiteIds() as $websiteId) {

            $query = "insert into " . $this->getResource()->getTable('unbxd_recscore/sync')
                . "(".self::PRODUCT_ID.", ".self::WEBSITE_ID.", ".self::SYNCED.",".self::UPDATED_TIME.") values "
                . "(:productId, :websiteId, :synced, :updated_time) ON DUPLICATE KEY UPDATE updated_time=NOW()";

            $binds = array(
                'productId'    => $product->getEntityId(),
                'websiteId'   => $websiteId,
                'synced' => self::SYNCED_FALSE,
                'updated_time' => date('Y-m-d H:i:s')
            );
            $write->query($query, $binds);
        }
    }

    /**
     * Method to addProduct to sync
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    public function deleteProduct(Mage_Catalog_Model_Product $product = null) {
        if (is_null($product) || !$product->hasData('entity_id')) {
            Mage::helper('unbxd_recscore')->log(Zend_Log::ERR, 'product argument sent is empty');
            return false;
        }
        $write = Mage::getSingleton("core/resource")->getConnection("core_write");
        foreach($product->getWebsiteIds() as $websiteId) {

            $query = "insert into " . $this->getResource()->getTable('unbxd_recscore/sync')
                . "(product_id, website_id, synced, operation, updated_time) values "
                . "(:productId, :websiteId, :synced, :operation, :updated_time) ON DUPLICATE KEY UPDATE updated_time=NOW()";

            $binds = array(
                'productId'    => $product->getEntityId(),
                'websiteId'   => $websiteId,
                'synced' => self::SYNCED_FALSE,
                'operation' => 'DELETE',
                'updated_time' => date('Y-m-d H:i:s')
            );
            $write->query($query, $binds);
        }
    }


    public function markItSynced($websiteId, $toTime) {
        $write = Mage::getSingleton("core/resource")->getConnection("core_write");
        $query = "update " . $this->getResource()->getTable('unbxd_recscore/sync')
            . " set " . self::SYNCED . " = 1 where " . self::WEBSITE_ID
            . "= :websiteId and " . self::UPDATED_TIME . '< :toTime';

        $binds = array(
            'websiteId'    => $websiteId,
            'toTime' => $toTime
        );
        $write->query($query, $binds);
    }
 }