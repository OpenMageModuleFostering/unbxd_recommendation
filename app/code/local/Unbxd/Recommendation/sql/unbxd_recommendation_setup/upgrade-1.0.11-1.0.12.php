<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();
$fieldTable = $installer->getTable('unbxd_field_conf');

$websiteCollection = Mage::getModel('core/website')->getCollection()->load();
foreach($websiteCollection as $website) {
    $websiteId = $website->getWebsiteId();
    if(is_null($websiteId)) {
        continue;
    }
    $insertQuery = "
INSERT INTO `{$fieldTable}` (`website_id`, `field_name`, `datatype`, `autosuggest`, `featured_field`, `multivalued`, `displayed`)
VALUES
    ({$websiteId}, 'gender', 'text', 0, 'gender', 0, 1),
	({$websiteId}, 'description', 'longText', 0, 'description', 0, 1),
	({$websiteId}, 'catLevel1Name', 'text', 0, 'catlevel1Name', 0, 0),
	({$websiteId}, 'catLevel2Name', 'text', 0, 'catlevel2Name', 0, 0),
	({$websiteId}, 'catLevel3Name', 'text', 0, 'catlevel3Name', 0, 0),
	({$websiteId}, 'catLevel4Name', 'text', 0, 'catlevel4Name', 0, 0),
	({$websiteId}, 'categoryLevel1', 'text', 0, NULL, 1, 0),
	({$websiteId}, 'categoryLevel2', 'text', 0, NULL, 1, 0),
	({$websiteId}, 'categoryLevel3', 'text', 0, NULL, 1, 0),
	({$websiteId}, 'categoryLevel4', 'text', 0, NULL, 1, 0),
	({$websiteId}, 'availability', 'bool', 0, 'availability', 0, 0),
	({$websiteId}, 'created_at', 'date', 0, NULL, 0, 1);";
    $installer->run($insertQuery);
}
$installer->endSetup();