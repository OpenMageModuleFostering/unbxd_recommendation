<?php
$installer = $this;
/* @var $installer Mage_Recscore_Model_Resource_Setup */

$installer->startSetup();
$fieldTable = $installer->getTable('unbxd_field_conf');
$configTable = $installer->getTable('unbxd_recommendation_conf');
try {
    $installer->run("ALTER TABLE `{$configTable}` CHANGE `key` `unbxd_key` VARCHAR(50)");
} catch (Exception $e) {
    //ignore the exceptions
}

$websiteCollection = Mage::getModel('core/website')->getCollection()->load();
foreach($websiteCollection as $website) {
    $websiteId = $website->getWebsiteId();
    if (is_null($websiteId)) {
        continue;
    }
    $fieldTable = Mage::getResourceModel('unbxd_recscore/field')->getTableName();
    $insertQuery = "
    INSERT INTO `{$fieldTable}` (`website_id`, `field_name`, `datatype`, `autosuggest`, `featured_field`, `multivalued`, `displayed`)
VALUES
    ({$websiteId}, '" . Unbxd_Recscore_Model_Resource_Field::QTY_MANAGE_ASSOCIATED . "',
                   '" .Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_NUMBER . "', 0, NULL, 1, 0),
    ({$websiteId}, '" .Unbxd_Recscore_Model_Resource_Field::QTY_ASSOCIATED . "',
                   '" .Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_NUMBER . "', 0, NULL, 1, 0),
    ({$websiteId}, '" .Unbxd_Recscore_Model_Resource_Field::QTY_CONFIG_USE_MANAGE_STOCK_ASSOCIATED. "',
                   '" .Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_NUMBER . "', 0, NULL, 1, 0),
    ({$websiteId}, 'statusAssociated',
                   '" .Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_NUMBER . "', 0, NULL, 1, 0),
    ({$websiteId}, '" .Unbxd_Recscore_Model_Resource_Field::AVAILABILITY_ASSOCIATED . "',
                   '" .Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_BOOL . "', 0, NULL, 1, 0),
    ({$websiteId}, '" .Unbxd_Recscore_Model_Resource_Field::QTY_MANAGE . "',
                   '" .Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_NUMBER . "', 0, NULL, 0, 0),
    ({$websiteId}, '" .Unbxd_Recscore_Model_Resource_Field::QTY_CONFIG_USE_MANAGE_STOCK . "',
                   '" .Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_NUMBER . "', 0, NULL, 0, 0),
    ({$websiteId}, '" .Unbxd_Recscore_Model_Resource_Field::QTY . "',
                   '" .Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_NUMBER . "', 0, NULL, 0, 0),
    ({$websiteId}, 'type_id',
                   '" .Unbxd_Recscore_Helper_Constants::UNBXD_DATATYPE_LONGTEXT . "', 0, NULL, 0, 1)
    ON DUPLICATE KEY UPDATE `field_name`=`field_name`;";
    $installer->run($insertQuery);
}

?>