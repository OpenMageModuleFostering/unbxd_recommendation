<?php

/**
 * This class maintains all the configuration w.r.t Unbxd with site basis
 *
 * @category Unbxd
 * @package Unbxd_Recscore
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recscore_Model_Config extends Mage_Core_Model_Abstract {

    const KEY = "key";

    const VALUE = 'value';

    const WEBSITE_ID = 'website_id';

    const FEED_LOCK_TIME = 'feed_lock_time';

    const FEED_LOCK = 'feed_lock';

    const FEED_LOCK_TRUE = '1';

    const FEED_LOCK_FALSE = '0';

    const FEED_STATUS = 'feed_status';

    const MAX_FEED_LOCK_TIME = 6;

    const LAST_UPLOAD_TIME = 'lastUpload';

    const FILTER_DELIMITER = "|`";

    const FILTER = 'filter';

    /**
     *
     * @return void
     */
    protected function _construct()
	{
		$this->_init('unbxd_recscore/config');
	}
}

?>
