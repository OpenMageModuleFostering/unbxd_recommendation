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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `{$configTable}`;
CREATE TABLE `{$configTable}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `website_id` smallint(5) unsigned NOT NULL,
  `key` varchar(50) NOT NULL DEFAULT '',
  `value` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;
");

$websiteCollection = Mage::getModel('core/website')->getCollection()->load();
foreach($websiteCollection as $website) {
    $installer->run(Mage::getResourceSingleton('unbxd_recommendation/field')->getDefaultFieldInsertStatement($website));
}
$installer->endSetup();
?>