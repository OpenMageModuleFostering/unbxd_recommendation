<?php

class Unbxd_Recscore_Model_Auth {
	
	public function authorize(Varien_Event_Observer $observer) {
		$controller = $observer->getControllerAction();
		$request = Mage::app()->getRequest()->getParams();
		if(array_key_exists(Unbxd_Recscore_Helper_Constants::AUTH_REQUEST_PARAM, $request)) {
			$requestAuth = $request[Unbxd_Recscore_Helper_Constants::AUTH_REQUEST_PARAM];
			$authToken = $this->getAuthKey();
			if($requestAuth == $authToken) {
				return;
			}
			$websites = Mage::app()->getWebsites();
			foreach ($websites as $website) {
				$secretKey = Mage::getResourceModel('unbxd_recscore/config')->getValue($website->getWebsiteId(), Unbxd_Recscore_Helper_Constants::SECRET_KEY);
				if($requestAuth == $secretKey) {
					return;
				}
			}
		}
		Mage::app()->getFrontController()->getResponse()->setHttpResponseCode(401);
		$json = json_encode(array('success' => false, 'message' => 'Unauthorized')); 
		Mage::app()->getFrontController()->getResponse()->setBody($json);
		Mage::app()->getResponse()->sendResponse();
		exit();
	}


	public function getAuthKey() {
		$authToken = Mage::getResourceModel('unbxd_recscore/config')->getValue(0, Unbxd_Recscore_Helper_Constants::AUTH_TOKEN);
		if($authToken == null) {
			return $this->_generateAuthKey();
		}
		return $authToken;
	}

	protected function _generateAuthKey() {
		$authTokenRawString = Mage::getBaseUrl() . time();
		$authToken = base64_encode($authTokenRawString);
		Mage::getResourceModel('unbxd_recscore/config')->setValue(0, Unbxd_Recscore_Helper_Constants::AUTH_TOKEN, $authToken);
		$authToken = Mage::getResourceModel('unbxd_recscore/config')->getValue(0, Unbxd_Recscore_Helper_Constants::AUTH_TOKEN);
		if(is_null($authToken)) {
			 Mage::helper('unbxd_recscore')->log(Zend_Log::DEBUG, "Couldnt store auth token in DB");
			 Mage::throwException("Couldnt store auth token in DB in Unbxd module");
		} 
		return $authToken;
	}
}
?>
