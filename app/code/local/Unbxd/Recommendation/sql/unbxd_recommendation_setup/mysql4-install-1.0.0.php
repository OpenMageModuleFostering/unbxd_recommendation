<?php


$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$configTable = $installer->getTable('unbxd_recommendation_conf');
$fieldTable = $installer->getTable('unbxd_field_conf');

$installer->run("
DROP TABLE IF EXISTS `{$fieldTable}`;

CREATE TABLE `{$fieldTable}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `website_id` tinyint(5) unsigned NOT NULL,
  `field_name` varchar(100) NOT NULL DEFAULT '',
  `datatype` varchar(20) NOT NULL DEFAULT '',
  `autosuggest` tinyint(1) NOT NULL DEFAULT '0',
  `featured_field` varchar(100) DEFAULT NULL,
  `multivalued` tinyint(1) NOT NULL DEFAULT '0',
  `displayed` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `website_id` (`website_id`,`field_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `{$configTable}`;
CREATE TABLE `{$configTable}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `website_id` smallint(5) unsigned NOT NULL,
  `key` varchar(50) NOT NULL DEFAULT '',
  `value` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
");

$websiteCollection = Mage::getModel('core/website')->getCollection()->load();
foreach($websiteCollection as $website) {
    $websiteId = $website->getWebsiteId();
    if(is_null($websiteId)) {
        continue;
    }
    $fieldTable = Mage::getResourceModel('unbxd_recommendation/field')->getTableName();
    $insertQuery = "
INSERT INTO `{$fieldTable}` (`website_id`, `field_name`, `datatype`, `autosuggest`, `featured_field`, `multivalued`, `displayed`)
VALUES
	({$websiteId}, 'name', 'text', 1, 'title', 0, 1),
	({$websiteId}, 'final_price', 'decimal', 0, 'price', 0, 1),
	({$websiteId}, 'price', 'decimal', 0, NULL, 0, 1),
	({$websiteId}, 'brand', 'text', 0, 'brand', 0, 1),
	({$websiteId}, 'color', 'text', 0, 'color', 1, 1),
	({$websiteId}, 'size', 'text', 0, 'size', 1, 1),
	({$websiteId}, 'image', 'link', 0, 'imageUrl', 1, 1),
	({$websiteId}, 'url_path', 'link', 0, 'productUrl', 0, 1),
	({$websiteId}, 'catLevel1', 'text', 0, NULL, 1, 0),
	({$websiteId}, 'catLevel2', 'text', 0, NULL, 1, 0),
	({$websiteId}, 'catLevel3', 'text', 0, NULL, 1, 0),
	({$websiteId}, 'status', 'number', 0, NULL, 0, 0),
	({$websiteId}, 'visibility', 'number', 0, NULL, 0, 0),
	({$websiteId}, 'qty', 'number', 0, NULL, 0, 0),
	({$websiteId}, 'categoryIds', 'longText', 0, NULL, 1, 0),
	({$websiteId}, 'category', 'text', 0, 'category', 1, 0),
	({$websiteId}, 'uniqueId', 'longText', 0, NULL, 0, 0),
	({$websiteId}, 'entity_id', 'longText', 0, NULL, 0, 0);";
    $installer->run($insertQuery);
}
$installer->endSetup();
?>