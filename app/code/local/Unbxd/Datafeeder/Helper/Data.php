<?php

class Unbxd_Datafeeder_Helper_Data extends Mage_Core_Helper_Abstract
{
	
	public function getsetaddressparams()
	{
		$params=$this->_getRequest()->getParams();
		return $params;
	}	
	
}
