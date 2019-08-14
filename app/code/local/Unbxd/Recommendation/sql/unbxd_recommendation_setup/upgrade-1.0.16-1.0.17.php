<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();
$productSyncTable = $installer->getTable('unbxd_product_sync');

$installer->run("
      DROP TABLE IF EXISTS `{$productSyncTable}`;
      CREATE TABLE `{$productSyncTable}` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `product_id` int(10) unsigned NOT NULL,
      `website_id` smallint(5) unsigned NOT NULL,
      `synced` tinyint(1) NOT NULL DEFAULT '0',
      `updated_time` datetime DEFAULT NULL,
      `sync_time` datetime DEFAULT NULL,
      `operation` enum('ADD','DELETE') NOT NULL DEFAULT 'ADD',
      PRIMARY KEY (`id`),
      UNIQUE KEY `product_id` (`product_id`,`website_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");
$installer->endSetup();
