<?php

interface Unbxd_Recscore_Model_State {

	/**
	 * Method which returns the state of the component
	 * @param Mage_Core_Model_Website $website
	 * @return Unbxd_Recscore_Model_State_Response
	 */
	public function getState(Mage_Core_Model_Website $website);

}
