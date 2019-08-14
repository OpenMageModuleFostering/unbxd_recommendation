<?php

/**
 * @category Unbxd
 * @package Unbxd_Recommendation
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recommendation_Model_Api_Task_Analyticsimpression extends Unbxd_Recommendation_Model_Api_Task
{

    const method = Zend_Http_Client::GET;

    public function prepare(Mage_Core_Model_Website $website) {
        $this->preparationSuccessful = true;
        $this->prepareUrl($website);
        $this->prepareHeaders($website);
        return $this;
    }

    protected function prepareUrl($website) {
        $siteKey = Mage::getResourceModel("unbxd_recommendation/config")
            ->getValue($website->getWebsiteId(), Unbxd_Recommendation_Helper_Confighelper::SITE_KEY);
        if(is_null($siteKey)) {
            $this->preparationSuccessful = false;
            $this->errors["message"] = "Site key not set";
            return;
        }

        static::$url = static::RECOMMENDATION_SETTINGS_URL . "dashboard/analytics/integrationDetails/" . $siteKey;
    }

    protected function prepareHeaders($website) {
        $username = Mage::getResourceModel("unbxd_recommendation/config")
            ->getValue($website->getWebsiteId(), Unbxd_Recommendation_Helper_Confighelper::USERNAME);
        if(is_null($username)) {
            $this->preparationSuccessful = false;
            $this->errors["message"] = "Secret key not set";
            return;
        }

        $this->headers["authorization"] = "Basic " . base64_encode($username . ':$uauth');
    }

    protected function postProcess(Unbxd_Recommendation_Model_Api_Response $response) {
        return $response;
    }

}
?>