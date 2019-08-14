<?php

/**
 * This class maintains all the configuration w.r.t Unbxd with site basis
 *
 * @category Unbxd
 * @package Unbxd_Recommendation
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recommendation_Model_Config extends Mage_Core_Model_Abstract {

    const key = "key";

    const value = 'value';

    const website_id = 'website_id';

    /**
     *
     * @return void
     */
    protected function _construct()
	{
		$this->_init('unbxd_recommendation/config');
	}
}

?>
