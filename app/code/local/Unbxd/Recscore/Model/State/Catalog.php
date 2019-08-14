<?php

class Unbxd_Recscore_Model_State_Catalog implements Unbxd_Recscore_Model_State {
	

	/**
         * Method which returns the state of the component
         * @param Mage_Core_Model_Website $website
         * @return Unbxd_Recscore_Model_State_Response
         */
         public function getState(Mage_Core_Model_Website $website) {
		$response = Mage::getModel('unbxd_recscore/state_response');
		$status = true;
		$message = array();
		if(!$this->_checkFeedUpload($website)) {
			$status = false;
			$message[] = "Catalog Sync never happened";
		}
		if(!$this->_checkCron($website)) {
			$status = false;
			$message[] = "Cron for catalog sync never ran";
		} 
		$response->setStatus($status);
		$response->setMessage($message);
		return $response;
	 }

	 /**
	  * Method to check the first time feed upload
	  * @param Mage_Core_Model_Website $website
	  * @return bool
	  */
	 protected function _checkFeedUpload(Mage_Core_Model_Website $website) {
		$lastUploadTime = Mage::getResourceModel('unbxd_recscore/config')
				->getValue($website->getWebsiteId(), Unbxd_Recscore_Helper_Constants::LAST_UPLOAD_TIME);
		return !is_null($lastUploadTime);
	 }
	
          /**
           * Method to check the first time feed upload
           * @param Mage_Core_Model_Website $website
           * @return bool
           */
          protected function _checkCron(Mage_Core_Model_Website $website) {
                 $cronFlag = Mage::getResourceModel('unbxd_recscore/config')
                                 ->getValue($website->getWebsiteId(), Unbxd_Recscore_Helper_Constants::IS_CRON_ENABLED);
                 return !is_null($cronFlag);
          }
}
