<?php 


class Unbxd_Datafeeder_Block_Index extends Mage_Adminhtml_Block_Template
{
	public function getFullindexFormAction()
	{
		
		return Mage::getUrl('*/*/fullindex', array('_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()));
	}
	
	public function getTaxonomyUploadFormAction(){
		return Mage::getUrl('*/*/taxonomyindex', array('_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()));
	}

	public function getSaveApiUrl(){
    	return Mage::getUrl('*/*/saveapi', array('_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()));
    }
	
	public function getSaveFieldUrl()
	{
		return Mage::getUrl('*/field/save', array('_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()));
	}

	public function saveFeedConf(){
		return Mage::getUrl('*/*/savefeedconf', array('_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()));
	}
	
	public function checkApiKeyExists()
	{
		if(Mage::getResourceSingleton("datafeeder/conf")->getValue("apiKey")=="empty"){
			return false;
		}
		else{
			return true;
		}
	}
	
	public function getApiKey(){
		return Mage::getResourceSingleton("datafeeder/conf")->getValue("apiKey");
	}
	
	public function getFields($site)
	{
		
		return Mage::getResourceSingleton('datafeeder/field')->getFields($site);
	}
	
	public function getIncrementalIndexFormAction()
	{
		return Mage::getUrl('*/*/incrementalindex', array('_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()));
	}
	
	public function getProgressUrl()
	{
		
		return Mage::getUrl('datafeeder/config/progress', array('_secure' =>Mage::app()->getFrontController()->getRequest()->isSecure()));
	}

	public function getFeedConf()
    {
    	
        return Mage::getUrl('datafeeder/config/getfeedconf', array('_secure' =>Mage::app()->getFrontController()->getRequest()->isSecure()));
    }

	public function getResetAction(){
	  return Mage::getUrl('datafeeder/config/reset', array('_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()));
	}

	public function getEditUrl()
 	{
 		return Mage::getUrl('*/field/config', array('_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()));
 	}
}

?>
