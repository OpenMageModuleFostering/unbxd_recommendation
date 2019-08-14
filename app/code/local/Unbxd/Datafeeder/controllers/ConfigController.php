<?php
class Unbxd_Datafeeder_ConfigController extends   Mage_Core_Controller_Front_Action
{

	public function indexAction(){
        $fromdate="1970-01-01 00:00:00";
        $site=$this->getRequest()->getParam("site");
        Mage::getSingleton('unbxd_datafeeder/feed_feedmanager')->process($fromdate,$site);
        echo "Done";
    }

	/**
	* gets all the stores
	*/	
	public function getAllstoreAction(){
		
		$allStores = Mage::app()->getStores();

		foreach ($allStores as $_eachStoreId => $val)
		{
			$_storeCode = Mage::app()->getStore($_eachStoreId)->getCode();
			$_storeName = Mage::app()->getStore($_eachStoreId)->getName();
			$_storeId = Mage::app()->getStore($_eachStoreId)->getId();
			echo $_storeId."<br/>";
			echo $_storeCode."<br/>";
			echo $_storeName."<br/>";
		}
		$allsites=Mage::app()->getWebsites();
		foreach($allsites as $site){
			echo $site->getName();
		}
	}
	
	/**
	* resets the lock if the feed has been locked
	**/
	public function resetAction(){
		$site=$this->getRequest()->getParam("site");
		Mage::getResourceSingleton("datafeeder/conf")->updateAction($site.'/status','0');
      	$this->getResponse()->setBody("success");
		$this->getResponse()->setHttpResponseCode(200);
	}
	
	public function getstoreviewAction(){
		
		echo gettype(Mage::getModel('datafeeder/field'));
		$collection= Mage::getModel("datafeeder/field")->getCollection();
		foreach($collection as $coll){
			echo $coll->getName();
		}
	}
	
    public function progressAction()
    {
		$site = $this->getRequest()->getParam("site");
		$status = Mage::getResourceSingleton("datafeeder/conf")->getValue($site."/status");
		if($status == 'empty'){
			$status = '0';
		}
    	echo '{"status":"'.$status.'"}';
    }

    public function getfeedconfAction(){
		$site = $this->getRequest()->getParam("site");	
		if(isset($site) && $site != ""){
			$response = array();
			$response["feed"] = Mage::getResourceSingleton("datafeeder/conf")->getValue($site."/feed");
			$response["tax"] = Mage::getResourceSingleton("datafeeder/conf")->getValue($site."/tax");
			$response["siteName"] = Mage::getResourceSingleton("datafeeder/conf")->getValue($site."/siteName");
			echo json_encode($response);
		}
    }

    public function upgradedbAction() {
    	Mage::getResourceSingleton("datafeeder/upgrade")->upgrade010To105();
    	echo json_encode( array('success' => 'true' ));	
    }
}
?>

