<?php 
	
class Unbxd_Datafeeder_IndexController extends Mage_Adminhtml_Controller_Action {
	
	protected function _getSession()
	{
        return Mage::getSingleton('catalog/session');
	}
			
	public function saveapiAction()
	{
		Mage::getResourceSingleton("datafeeder/conf")->updateAction("apiKey", $this->getRequest()->getparam("apikey"));
		$this->loadLayout();
    	$this->_addContent($this->getLayout()->createBlock('unbxd_datafeeder/index')->setTemplate('datafeeder/conf.phtml'));
    	$this->renderLayout();	
	}

	public function savefeedconfAction(){
        Mage::getResourceSingleton("datafeeder/conf")->updateAction($this->getRequest()->getparam("site")."/feed", $this->getRequest()->getparam("feedName"));
		Mage::getResourceSingleton("datafeeder/conf")->updateAction($this->getRequest()->getparam("site")."/tax", $this->getRequest()->getparam("feedName"));
        Mage::getResourceSingleton("datafeeder/conf")->updateAction($this->getRequest()->getparam("site")."/siteName", $this->getRequest()->getparam("siteName"));

        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('unbxd_datafeeder/index')->setTemplate('datafeeder/conf.phtml'));
        $this->renderLayout();
    }	

    public function indexAction()
    {	
    	$this->loadLayout();
   		$this->_addContent($this->getLayout()->createBlock('unbxd_datafeeder/index')->setTemplate('datafeeder/conf.phtml'));
    	$this->renderLayout();	
    }
      	    
    public function fullindexAction(){
    	$_helper=Mage::helper('unbxd_datafeeder/UnbxdIndexingHelper');
    	$fromdate="1970-01-01 00:00:00";
    	$site=$this->getRequest()->getPost("site");
    	//$_helper->indexUnbxdFeed($fromdate,$site);
        Mage::getSingleton('unbxd_datafeeder/feed_feedmanager')->process($fromdate,$site);
		echo "Done";
    }
    
    public function taxonomyindexAction(){
    	$_helper=Mage::helper('unbxd_datafeeder/UnbxdTaxonomyHelper');
      	$site=$this->getRequest()->getPost("site");	
    	$_helper->indexUnbxdFeed($site);
    }
    
    public function incrementalindexAction()
    {
    	$_helper=Mage::helper('unbxd_datafeeder/UnbxdIndexingHelper');
    	$fromdate=Mage::getResourceSingleton("datafeeder/conf")->getValue("Lastindex");
    	if(is_null($fromdate)){
    		$fromdate="1970-01-01 00:00:00";
    	}
    	$site=$this->getRequest()->getPost("site");
            $_helper->indexUnbxdFeed($fromdate,$site);
            echo "Done";	
    }	  
} ?>