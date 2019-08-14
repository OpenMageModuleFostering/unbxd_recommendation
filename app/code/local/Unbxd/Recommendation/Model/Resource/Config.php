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

    // date format
    const DATE_FORMAT = 'Y-m-d H:i:s';

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

        if(is_array($key)) {
            $keyValuePair = array();
            foreach ($key as $eachKey) {
                $keyValuePair[$key] = $this->getValue($website_id, $eachKey);
            }
            return $keyValuePair;
        }

        $select = $adapter->select()
            ->from($this->_unbxdConfigTable, 'value')
            ->where('`'.Unbxd_Recommendation_Model_Config::WEBSITE_ID.'` = ?', (int)$website_id)
            ->where('`'.Unbxd_Recommendation_Model_Config::KEY.'` = ?', $key);

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
    public function setValue($website_id, $key, $value = null)
    {
        if (!isset($value) || $value == "" || !isset($key) || $key == "") {
            return;
        }

        if(is_array($key)) {
            foreach($key as $eachKey => $eachValue) {
                $this->setValue($website_id, $eachKey, $eachValue);
            }
            return;
        }

        $config = Mage::getModel('unbxd_recommendation/config')->getCollection()
            ->addFieldToFilter('`'.Unbxd_Recommendation_Model_Config::KEY.'`', $key)
            ->addFieldToFilter('`'.Unbxd_Recommendation_Model_Config::WEBSITE_ID.'`', (int)$website_id)
            ->getFirstItem();

        $config->setWebsiteId($website_id)
            ->setKey($key)
            ->setValue($value)
            ->save();
    }

    public function lockSite($website_id) {
        $this->setValue($website_id, array(
            Unbxd_Recommendation_Model_Config::FEED_LOCK => Unbxd_Recommendation_Model_Config::FEED_LOCK_TRUE,
            Unbxd_Recommendation_Model_Config::FEED_LOCK_TIME => date(self::DATE_FORMAT)));

    }


    public function unLockSite($website_id) {
        $this->setValue($website_id, Unbxd_Recommendation_Model_Config::FEED_LOCK,
            Unbxd_Recommendation_Model_Config::FEED_LOCK_FALSE);
    }

    public function isLock($website_id) {
        //fetch the values for feedlock, feed lock time from db
        $feedLockDetails = $this->getValue($website_id,
            array(Unbxd_Recommendation_Model_Config::FEED_LOCK, Unbxd_Recommendation_Model_Config::FEED_LOCK_TIME));
        //fetch feed lock from @var feedLockDetails
        $feedLock = array_key_exists(Unbxd_Recommendation_Model_Config::FEED_LOCK, $feedLockDetails)?
            $feedLockDetails[Unbxd_Recommendation_Model_Config::FEED_LOCK]:null;

        if(is_null($feedLock) || $feedLock == Unbxd_Recommendation_Model_Config::FEED_LOCK_FALSE){
            return false;
        }

        // Ignoring the feed Lock, if the feed has been locked more than $maxFeedLockTime
        if($feedLock == Unbxd_Recommendation_Model_Config::FEED_LOCK_TRUE &&
            array_key_exists(Unbxd_Recommendation_Model_Config::FEED_LOCK_TIME, $feedLockDetails)) {

            $feedLockTime  = $feedLockDetails[Unbxd_Recommendation_Model_Config::FEED_LOCK_TIME];
            $date = strtotime($feedLockTime);
            $currentTime = strtotime(date(self::DATE_FORMAT));
            $diff = abs($date - $currentTime);
            $maxFeedLockTime = Mage::getConfig()->getNode('default/unbxd/general/max_feed_lock_feed');
            if(is_null($maxFeedLockTime)) {
                $maxFeedLockTime = Unbxd_Recommendation_Model_Config::MAX_FEED_LOCK_TIME;
            }

            if(round($diff / ( 60 * 60 )) > $maxFeedLockTime) {
                return false;
            }
        }

        return true;
    }
}

?>