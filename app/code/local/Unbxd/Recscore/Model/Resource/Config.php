<?php

/**
 * @category Unbxd
 * @package Unbxd_Recscore
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recscore_Model_Resource_Config extends Mage_Core_Model_Mysql4_Abstract
{

    /**
     * Unbxd Config table Name
     *
     * @var string
     */
    protected $_unbxdConfigTable;

    // date format
    const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('unbxd_recscore/config', 'id');
        $this->_unbxdConfigTable = $this->getTable('unbxd_recscore/config');
    }

    public function getValues($websiteId, $key) {
        if(!isset($key) || is_array($key) ){
            return array();
        }
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
            ->from($this->_unbxdConfigTable, Unbxd_Recscore_Model_Config::VALUE)
            ->where('`'.Unbxd_Recscore_Model_Config::WEBSITE_ID.'` = ?', (int)$websiteId)
            ->where('`'.Unbxd_Recscore_Model_Config::KEY.'` = ?', $key);
        $rows = $adapter->fetchAll($select);
        $values = array();
        foreach($rows as $row) {
            if(array_key_exists(Unbxd_Recscore_Model_Config::VALUE, $row)) {
                $values[] = $row[Unbxd_Recscore_Model_Config::VALUE];
            }
        }
        return $values;
    }

    /**
     * @param $website_id
     * @param $key
     * @return null|string
     */
    public function getValue($website_id, $key)
    {
        $adapter = $this->_getReadAdapter();
        if(is_array($key)) {
            $keyValuePair = array();
            foreach ($key as $eachKey) {
                $keyValuePair[$eachKey] = $this->getValue($website_id, $eachKey);
            }
            return $keyValuePair;
        }
        $select = $adapter->select()
            ->from($this->_unbxdConfigTable, 'value')
            ->where('`'.Unbxd_Recscore_Model_Config::WEBSITE_ID.'` = ?', (int)$website_id)
            ->where('`'.Unbxd_Recscore_Model_Config::KEY.'` = ?', $key);
        $result = $adapter->fetchOne($select);
        if($result === false) {
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
    public function setValue($website_id, $key, $value = null)
    {
        if(is_array($key)) {
            foreach($key as $eachKey => $eachValue) {
                $this->setValue($website_id, $eachKey, $eachValue);
            }
            return;
        }
	if (!isset($value) || $value == "" || !isset($key) || $key == "") {
            return;
        }

        $config = Mage::getModel('unbxd_recscore/config')->getCollection()
            ->addFieldToFilter('`'.Unbxd_Recscore_Model_Config::KEY.'`', $key)
            ->addFieldToFilter('`'.Unbxd_Recscore_Model_Config::WEBSITE_ID.'`', (int)$website_id)
            ->getFirstItem();

        $config->setWebsiteId($website_id)
            ->setKey($key)
            ->setValue($value)
            ->save();
    }

    public function updateValues($websiteId, $key, $values = array()) {
        $this->deleteKey($websiteId, $key);
        $this->beginTransaction();
        foreach($values as $eachValue) {
            Mage::getModel('unbxd_recscore/config')
                ->setWebsiteId($websiteId)
                ->setKey($key)
                ->setValue($eachValue)
                ->save();
        }
        $this->commit();
    }

    public function deleteKey($websiteId, $key) {
        $write = Mage::getSingleton("core/resource")->getConnection("core_write");
        $query = "DELETE FROM `unbxd_recommendation_conf` WHERE `" . Unbxd_Recscore_Model_Config::KEY . "` = :key"
            . " and `" . Unbxd_Recscore_Model_Config::WEBSITE_ID . "` = :website_id";
        $binds = array(
            'key' => $key,
            'website_id' => $websiteId
        );
        $write->query($query, $binds);

    }

    public function lockSite($website_id) {
        $this->setValue($website_id, array(
            Unbxd_Recscore_Model_Config::FEED_LOCK => Unbxd_Recscore_Model_Config::FEED_LOCK_TRUE,
            Unbxd_Recscore_Model_Config::FEED_LOCK_TIME => date(self::DATE_FORMAT)));

    }


    public function unLockSite($website_id) {
        $this->setValue($website_id, Unbxd_Recscore_Model_Config::FEED_LOCK,
            Unbxd_Recscore_Model_Config::FEED_LOCK_FALSE);
    }

    /**
     * Method will check whether there is a feed lock or not
     * @param $website_id
     * @return bool
     */
    public function isLock($website_id) {
        //fetch the values for feedlock, feed lock time from db
        $feedLockDetails = $this->getValue($website_id,
            array(Unbxd_Recscore_Model_Config::FEED_LOCK, Unbxd_Recscore_Model_Config::FEED_LOCK_TIME));
        //fetch feed lock from @var feedLockDetails
        $feedLock = array_key_exists(Unbxd_Recscore_Model_Config::FEED_LOCK, $feedLockDetails)?
            $feedLockDetails[Unbxd_Recscore_Model_Config::FEED_LOCK]:null;
            if(is_null($feedLock) || $feedLock == Unbxd_Recscore_Model_Config::FEED_LOCK_FALSE){
            return false;
        }
        // Ignoring the feed Lock, if the feed has been locked more than $maxFeedLockTime
        if($feedLock == Unbxd_Recscore_Model_Config::FEED_LOCK_TRUE &&
            array_key_exists(Unbxd_Recscore_Model_Config::FEED_LOCK_TIME, $feedLockDetails)) {
            $feedLockTime  = $feedLockDetails[Unbxd_Recscore_Model_Config::FEED_LOCK_TIME];
            $date = strtotime($feedLockTime);
            $currentTime = strtotime(date(self::DATE_FORMAT));
            $diff = abs($date - $currentTime);
            $maxFeedLockTime = Mage::getConfig()->getNode('default/unbxd/general/max_feed_lock_feed');
            if(is_null($maxFeedLockTime)) {
                $maxFeedLockTime = Unbxd_Recscore_Model_Config::MAX_FEED_LOCK_TIME;
            }
            if(round($diff / ( 60 * 60 )) > $maxFeedLockTime) {
                return false;
            }
        }
        return true;
    }

    /**
     * Method to get all the filters
     * @param Mage_Core_Model_Website $website
     * @return array
     */
    public function getFilters(Mage_Core_Model_Website $website) {
        $values = Mage::getResourceModel('unbxd_recscore/config')->getValues($website->getWebsiteId(),
            Unbxd_Recscore_Model_Config::FILTER);
        $filters = array();
        foreach($values as $value) {
            $explodedValues = explode(Unbxd_Recscore_Model_Config::FILTER_DELIMITER, $value);
            if(sizeof($explodedValues) < 2) {
                continue;
            }
            $filters[$explodedValues[0]] = $explodedValues[1];
        }
        return $filters;
    }

    public function saveGlobalConfig(Mage_Core_Model_Website $website, $values = array()) {
	foreach($values as $key => $value ) {
	}
    }

    public function deleteAll($websiteId) {
        $write = Mage::getSingleton("core/resource")->getConnection("core_write");
        $query = "DELETE FROM `unbxd_recommendation_conf` WHERE `" . Unbxd_Recscore_Model_Config::WEBSITE_ID . "` = :website_id";
        $binds = array(
            'website_id' => $websiteId
        );
        $write->query($query, $binds);
    }

    public function getGlobalConfig(Mage_Core_Model_Website $website, $keys =array()) {
    }
}

?>
