<?php

/**
 * @category Unbxd
 * @package Unbxd_Recommendation
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recommendation_Model_Resource_Config extends Mage_Core_Model_Mysql4_Abstract
{

    /**
     * Unbxd Config table Name
     *
     * @var string
     */
    protected $_unbxdConfigTable;

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('unbxd_recommendation/config', 'id');
        $this->_unbxdConfigTable = $this->getTable('unbxd_recommendation/config');
    }

    /**
     * @param $website_id
     * @param $key
     * @return null|string
     */
    public function getValue($website_id, $key)
    {
        $adapter = $this->_getReadAdapter();

        $select = $adapter->select()
            ->from($this->_unbxdConfigTable, 'value')
            ->where('`'.Unbxd_Recommendation_Model_Config::website_id.'` = ?', (int)$website_id)
            ->where('`'.Unbxd_Recommendation_Model_Config::key.'` = ?', $key);

        $result = $adapter->fetchOne($select);
        if($result == false) {
            return null;
        }
        return $result;
    }

    /**
     * @param int $website_id
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setValue($website_id, $key, $value)
    {
        if (!isset($value) || $value == "" || !isset($key) || $key == "") {
            return;
        }

        $config = Mage::getModel('unbxd_recommendation/config')->getCollection()
            ->addFieldToFilter('`'.Unbxd_Recommendation_Model_Config::key.'`', $key)
            ->addFieldToFilter('`'.Unbxd_Recommendation_Model_Config::website_id.'`', (int)$website_id)
            ->getFirstItem();

        $config->setWebsiteId($website_id)
            ->setKey($key)
            ->setValue($value)
            ->save();
    }

    public function lockSite($website_id) {
        $this->setValue($website_id, 'feed_lock', '1');
        $this->setValue($website_id, 'feed_lock_time', date('Y-m-d H:i:s'));
    }


    public function unLockSite($website_id) {
        $this->setValue($website_id, 'feed_lock', '0');
    }

    public function isLock($website_id) {
        $feedLock = $this->getValue($website_id, 'feed_lock');
        if(is_null($feedLock) || $feedLock == 0){
            return false;
        }
        return true;
    }
}

?>