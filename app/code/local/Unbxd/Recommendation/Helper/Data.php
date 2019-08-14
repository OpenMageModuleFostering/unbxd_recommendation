<?php

/**
 * @category Unbxd
 * @package Unbxd_Recommendation
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recommendation_Helper_Data extends Mage_Core_Helper_Abstract {

    const LOG_FILE = "unbxd_recommendation.log";

    /**
     * Method to log
     *
     * @param int    $level
     * @param string $message
     */
    public function log($level, $message) {
        Mage::log($message, $level, static::LOG_FILE, true);
    }

}
?>