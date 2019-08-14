<?php 


class Unbxd_Datafeeder_Block_Edit extends Mage_Core_Block_Template
{
	
	public function getCollection()
	{		
		return Mage::getModel('datafeeder/field')->getCollection();
	}
}
?>
