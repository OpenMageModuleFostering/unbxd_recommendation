<?php

/**
 * @category Unbxd
 * @package Unbxd_Recscore
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recscore_Model_Api_Task_Searchimpression extends Unbxd_Recscore_Model_Api_Task
{

    const method = Zend_Http_Client::GET;

    public function prepare(Mage_Core_Model_Website $website) {
        $this->preparationSuccessful = true;
        $this->prepareUrl($website);
        $this->prepareHeaders($website);
        return $this;
    }

    protected function prepareUrl(Mage_Core_Model_Website $website) {
        $siteKey = Mage::getResourceModel("unbxd_recscore/config")
            ->getValue($website->getWebsiteId(), Unbxd_Recscore_Helper_Confighelper::SITE_KEY);
        if(is_null($siteKey)) {
            $this->preparationSuccessful = false;
            $this->errors["message"] = "Site key not set";
            return;
        }

        static::$url = static::$RECOMMENDATION_SETTINGS_URL . "dashboard/analytics/hits/" . $siteKey;
    }

    protected function prepareHeaders(Mage_Core_Model_Website $website) {
        $username = Mage::getResourceModel("unbxd_recscore/config")
            ->getValue($website->getWebsiteId(), Unbxd_Recscore_Helper_Confighelper::USERNAME);
        if(is_null($username)) {
            $this->preparationSuccessful = false;
            $this->errors["message"] = "Secret key not set";
            return;
        }

        $this->headers["authorization"] = "Basic " . base64_encode($username . ':$uauth');
    }

    protected function postProcess(Unbxd_Recscore_Model_Api_Response $response) {
	$respObj = $response->getResponse();
	if(array_key_exists("FunnelResponse", $respObj)) {
		if(array_key_exists("Funnels",$respObj["FunnelResponse"])) {
        		return $response;
		}
	}
	$response->setSuccess(false);
	$response->setErrorMessage("Unexpected response from Unbxd server, Contact Support");
	return $response;
    }
}
?>
