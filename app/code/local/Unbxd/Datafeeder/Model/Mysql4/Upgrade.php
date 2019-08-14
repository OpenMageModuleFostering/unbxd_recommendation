<?php
class Unbxd_Datafeeder_Model_Mysql4_Upgrade {

	public function upgrade010To105() {
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
		$unbxdFieldTable = $write->getTableName('unbxd_field');
		$unbxdConfTable = $write->getTableName('unbxd_datafeeder_conf');
		$write->query("
			DROP TABLE IF EXISTS `{$unbxdFieldTable}`;

			CREATE TABLE `{$unbxdFieldTable}` (
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

			DROP TABLE IF EXISTS `{$unbxdConfTable}`;

			CREATE TABLE `{$unbxdConfTable}` (
			    `uconfig_id` int(10) unsigned NOT NULL auto_increment,
			    `action` varchar(255) NOT NULL default '',
			    `value` varchar(255),
			    PRIMARY KEY  (`uconfig_id`)
			        
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;			
		");
	}

}
?>