<?php


$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

 DROP TABLE IF EXISTS `{$installer->getTable('unbxd_field')}`;

CREATE TABLE `{$installer->getTable('unbxd_field')}` (
  `field_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `status` int(1) NOT NULL DEFAULT '1',
  `site` varchar(100) NOT NULL DEFAULT '',
  `data_type` varchar(20) NOT NULL DEFAULT 'longText',
  `autosuggest` int(1) NOT NULL DEFAULT '0',
  `image_height` int(5) NOT NULL DEFAULT '0',
  `image_width` int(5) NOT NULL DEFAULT '0',
  `generate_image` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `{$installer->getTable('unbxd_datafeeder_conf')}`;

CREATE TABLE `{$installer->getTable('unbxd_datafeeder_conf')}` (
    `uconfig_id` int(10) unsigned NOT NULL auto_increment,
    `action` varchar(255) NOT NULL default '',
    `value` varchar(255),
    PRIMARY KEY  (`uconfig_id`)
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		
");
$installer->endSetup();
 

