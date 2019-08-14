<?php

/**
 * @category Unbxd
 * @package Unbxd_Recscore
 * @author Unbxd Software Pvt. Ltd {
 */
class Unbxd_Recscore_Model_Resource_Config_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     */
    public function _construct()
    {
        $this->_init('unbxd_recscore/config');
    }


}

?>
