<?php

/**
 * @category Unbxd
 * @package Unbxd_Recscore
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recscore_Model_Api_Task_Updatefeaturefields extends Unbxd_Recscore_Model_Api_Task {

    const method = Zend_Http_Client::POST;

    const STATUS = 'status';

    const MESSAGE = 'message';

    public function prepare(Mage_Core_Model_Website $website) {
        $this->preparationSuccessful = true;
        $this->prepareUrl($website);
        $this->prepareHeaders($website);
        $this->prepareData($website);
        $this->isRawData = true;
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

        static::$url = static::$PLATFORM_API_BASE_URL . $siteKey . "/field-mapping";
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

    protected function prepareData($website) {
        $featureFields = Mage::getResourceModel("unbxd_recscore/field_collection")->getFeatureFields($website);
        $featureFieldMap = array();
        foreach($featureFields as $field) {
	   if($field[Unbxd_Recscore_Model_Field::featured_field] == Unbxd_Recscore_Helper_Constants::FEATURE_FIELD_IMAGE_URL) {
		$featureFieldMap[$field[Unbxd_Recscore_Model_Field::featured_field]] = "unbxd_NA";
		continue;
	   }
            $featureFieldMap[$field[Unbxd_Recscore_Model_Field::featured_field]] =
                $field[Unbxd_Recscore_Model_Field::field_name];
        }
        $this->setData("fieldMapping", $featureFieldMap);
    }

    protected function postProcess(Unbxd_Recscore_Model_Api_Response $response) {
        if(!$response->isSuccess()) {
            return $response;
        }
        $responseObj = $response->getResponse();
        if(!array_key_exists(self::STATUS, $responseObj)) {
            $response->setSuccess(false);
            $response->setMessage('Invalid response from unbxd');
            return $response;
        }
        if($responseObj[self::STATUS] != 200) {
            $response->setSuccess(false);
            $response->setMessage("status code :" .$responseObj[self::STATUS].",".
                (array_key_exists(self::MESSAGE, $responseObj)?$responseObj[self::MESSAGE]:""));
            return $response;
        }
        return $response;
    }
}
