<?php

/**
 * class to validate the secret key and site key with unbxd
 *
 * @category Unbxd
 * @package Unbxd_Recommendation
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recommendation_Model_Api_Task_Validatekeys extends Unbxd_Recommendation_Model_Api_Task {

    const method = Zend_Http_Client::POST;

    const SECRET_KEY = "secretKey";

    const SITE_KEY = "siteKey";

    const API_KEY = 'apiKey';

    const USERNAME = 'username';

    public function prepare(Mage_Core_Model_Website $website) {
        $this->prepareUrl();
        $this->prepareHeaders($website);
        $this->prepareParams();
        return $this;
    }

    protected function prepareUrl() {
        static::$url = self::RECOMMENDATION_SETTINGS_URL . "dashboard/authenticateMagento";
        return $this;
    }

    protected function prepareHeaders() {
        return $this;
    }

    protected function prepareParams() {
        $params = $this->getData();
        if(!array_key_exists(static::SECRET_KEY, $params)) {
            $this->preparationSuccessful = false;
            $this->errors[Unbxd_Recommendation_Helper_Confighelper::SECRET_KEY] = "secret key expected";
        }
        if(!array_key_exists(static::SITE_KEY, $params)) {
            $this->preparationSuccessful = false;
            $this->errors[Unbxd_Recommendation_Helper_Confighelper::SITE_KEY] = "site key expected";
        }
        if(sizeof($this->getData()) > 2) {
            $this->preparationSuccessful = false;
            $this->errors["message"] = "Extra Parameters Present";
        }
        if(sizeof($this->errors) == 0) {
            $this->preparationSuccessful = true;
        }
        $this->isRawData = true;
        return $this;
    }

    protected function postProcess(Unbxd_Recommendation_Model_Api_Response $response) {
        if(!$response->isSuccess()) {
            return $response;
        }
        $responseObj = $response->getResponse();
        if(!array_key_exists(self::API_KEY, $responseObj) || !array_key_exists(self::USERNAME, $responseObj)) {
            $response->setSuccess(false);
            $response->setMessage('Invalid Combination, Please Refer Docs');
        }
        return $response;
    }
}

?>