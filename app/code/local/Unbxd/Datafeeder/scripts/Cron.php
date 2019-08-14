<?php
require_once 'abstract.php';

class Unbxd_Datafeeder_Scripts_Cron extends Mage_Shell_Abstract
{
	
	public function _getIndexer()
	{
		return Mage::helper('unbxd_datafeeder/UnbxdIndexingHelper');
	}
	
	public function run(){
		$_helper = _getIndexer();
		$fromdate="1970-01-01 00:00:00";
	   	$site='Main Site';
	    	
	  	$_helper->indexUnbxdFeed($fromdate,$site);
	}
	
}


$shell = new Unbxd_Datafeeder_Scripts_Cron();
$shell->run();
?>
