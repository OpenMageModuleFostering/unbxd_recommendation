<?php

/**
 * @category Unbxd
 * @package Unbxd_Recscore
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recscore_Model_Api_Task_Autosuggestindex extends Unbxd_Recscore_Model_Api_Task {

    const method = Zend_Http_Client::POST;

    const STATUS = 'status';

    const MESSAGE = 'message';

    public function prepare(Mage_Core_Model_Website $website) {
        $this->preparationSuccessful = true;
        $this->prepareUrl($website);
        $this->prepareHeaders($website);
        return $this;
    }

    protected  function prepareUrl(Mage_Core_Model_Website $website) {
        $siteKey = Mage::getResourceModel("unbxd_recscore/config")
            ->getValue($website->getWebsiteId(), Unbxd_Recscore_Helper_Confighelper::SITE_KEY);
        if(is_null($siteKey)) {
            $this->preparationSuccessful = false;
            $this->errors["message"] = "Site key not set";
            return;
        }

        static::$url = static::$PLATFORM_API_BASE_URL . $siteKey . "/build-autosuggest";
    }

    protected function prepareHeaders(Mage_Core_Model_Website $website) {
        $apiKey = Mage::getResourceModel("unbxd_recscore/config")
            ->getValue($website->getWebsiteId(), Unbxd_Recscore_Helper_Confighelper::API_KEY);
        $secretKey = Mage::getResourceModel("unbxd_recscore/config")
            ->getValue($website->getWebsiteId(), Unbxd_Recscore_Helper_Confighelper::SECRET_KEY);
        if(is_null($secretKey) || is_null($apiKey)) {
            $this->preparationSuccessful = false;
            $this->errors["message"] = "Site key not set";
            return;
        }
        $this->headers["Authorization"] = base64_encode($apiKey.":" .$secretKey);
    }

    protected function postProcess(Unbxd_Recscore_Model_Api_Response $response) {
        return $response;
    }
}
