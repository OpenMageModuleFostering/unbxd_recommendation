<?php

/**
 * @category Unbxd
 * @package Unbxd_Recommendation
 * @author Unbxd Software Pvt. Ltd
 */
abstract class Unbxd_Recommendation_Model_Api_Task extends Varien_Object {

    const PLATFORM_API_BASE_URL = "http://accounts.unbxdapi.com/admin/";

    const RECOMMENDATION_SETTINGS_URL = "http://starwreck.unbxdapi.com/";

    const TRACKER_URL = "http://tracker.unbxdapi.com/";

    const RECOMMENDATION_API = "https://apac-recommendations.unbxdapi.com/";

    const TIMEOUT = 30;

    static $url = "";

    const method = Zend_Http_Client::GET;

    protected $preparationSuccessful;

    protected $errors;

    protected $headers = array();

    protected $isRawData = false;

    const jsonResponse = true;

    public function __construct() {
        $this->preparationSuccessful = false;
        $this->errors = array();
    }

    /**
     * Method to check whether parameters to be sent as raw parameter
     *
     * @return bool
     */
    protected function isRawData() {
        return $this->isRawData;
    }

    abstract public function prepare(Mage_Core_Model_Website $website);

    abstract protected function postProcess(Unbxd_Recommendation_Model_Api_Response $response);

    /**
     * Method which will make the api call
     *
     * @return false|Unbxd_Recommendation_Model_Api_Response
     */
    public function process() {
        if(!$this->preparationSuccessful) {
            Mage::helper("unbxd_recommendation")
                ->log(Zend_Log::ERR,
                    sizeof($this->errors == 0)?"prepare method not called":json_encode($this->errors));
            return Mage::getModel("unbxd_recommendation/api_response")
                ->setErrors(sizeof($this->errors) == 0?array("message" => "prepare method not called"):$this->errors);
        }
        $request = Mage::getModel("unbxd_recommendation/api_request")
            ->setUrl(static::$url)
            ->setMethod(static::method)
            ->setHeaders($this->headers)
            ->setTimeout(static::TIMEOUT)
            ->setJsonResponse(static::jsonResponse);
        if($this->isRawData()) {
            $request->setRawData(true);
        }
        if($this->getData()) {
            $request->addData($this->getData());
        }

        return $this->postProcess($request->execute());
    }

}
?>
