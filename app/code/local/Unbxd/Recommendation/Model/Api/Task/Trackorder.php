<?php

/**
 * @category Unbxd
 * @package Unbxd_Recommendation
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recommendation_Model_Api_Task_Trackorder extends Unbxd_Recommendation_Model_Api_Task {

    const method = Zend_Http_Client::POST;

    const jsonResponse = false;

    const TIMEOUT = 5;

    public function prepare(Mage_Core_Model_Website $website) {
        $this->prepareUrl($website);
        $this->isRawData = true;
        return $this;
    }

    protected function prepareUrl(Mage_Core_Model_Website $website)
    {
        $siteKey = Mage::getResourceModel("unbxd_recommendation/config")
            ->getValue($website->getWebsiteId(), Unbxd_Recommendation_Helper_Confighelper::SITE_KEY);
        if (is_null($siteKey)) {
            $this->errors["message"] = "Site key not set";
            return;
        }

        $uid = array_key_exists('unbxd_userId', $_COOKIE) ? $_COOKIE['unbxd_userId'] : null;
        if (!isset($uid) || is_null($uid)) {
            $this->errors["message"] = "UID Missing";
            return;
        }

        static::$url = static::TRACKER_URL . "v1.0/$siteKey/track/order/$uid";
        $this->preparationSuccessful = true;
    }

    protected function postProcess(Unbxd_Recommendation_Model_Api_Response $response) {
        return $response;
    }
}
?>