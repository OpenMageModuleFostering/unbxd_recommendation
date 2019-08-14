<?php

class Unbxd_Recscore_Model_State_Analytics implements Unbxd_Recscore_Model_State {

	/**
         * Method which returns the state of the component
         * @param Mage_Core_Model_Website $website
         * @return Unbxd_Recscore_Model_State_Response
         */
         public function getState(Mage_Core_Model_Website $website) {
		$response = Mage::getModel('unbxd_recscore/state_response');
		$status = true;
		$message = array();
		if(!$this->_checkRecommendorAnalytics($website)) {
			$status = false;
			$message[] = "Analytics impressions not captured";
		}
		if($status && Mage::helper('core')->isModuleEnabled('Unbxd_Searchcore')) {
			if($this->_checkSearchAnalytics($website)) {
				$status = false;
				$message[] = "Analytics impressions not captured";
			}
		}
		$response->setStatus($status);
		$response->setMessage($message);
		return $response;
	 }

	/**
	 * Method to check product click, order, add to cart  tracking information
	 * @param Mage_Core_Model_Website $website
	 * @return bool
	 */
	protected function _checkRecommendorAnalytics(Mage_Core_Model_Website $website) {
		$analyticsImpression = Mage::getModel("unbxd_recscore/api_task_analyticsimpression")
                	->prepare($website)
                	->process();
		if(!$analyticsImpression->isSuccess()) {
			return false;
		}
		$details = $analyticsImpression->getResponse();			
		if(!array_key_exists("IntegrationDetails", $details)) {
			return false;
		}
		if(!is_array($details["IntegrationDetails"]) || sizeof($details["IntegrationDetails"]) == 0) {
			return false;
		}
		$intDetails = $details["IntegrationDetails"][0];
		if(!array_key_exists("ADDTOCART", $intDetails) || !array_key_exists("CLICKRANK", $intDetails) 
				|| !array_key_exists("ORDER", $intDetails)) {
			return false;
		}
		return true;		
	}

        /**
	 * Method to check search integration 
	 * @param Mage_Core_Model_Website $website
	 * @return bool
	 */
	protected function _checkSearchAnalytics(Mage_Core_Model_Website $website) {
		$searchImpressions = Mage::getSingleton('unbxd_recscore/api_task_searchimpression')
					->prepare($website)
					->process();
		if(!$searchImpressions->isSuccess()) {	
			return false;
		}
		$searhitResponseObj = $searchImpressions->getResponse();
		$funnels = $searhitResponseObj["FunnelResponse"]["Funnels"];
		$searchCount = 0;
		foreach($funnels as $funnel) {
			if(array_key_exists("type", $funnel) && $funnel["type"] == "hits" 
				&& array_key_exists("searchCount",$funnel)) {
				$searchCount = $funnel["searchCount"];
			}
		}
		if($searchCount == 0) {
			return false;
		}
		return true;
	}
			
	
}
