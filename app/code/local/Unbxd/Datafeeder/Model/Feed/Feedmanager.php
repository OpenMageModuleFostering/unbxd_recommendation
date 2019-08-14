<?php

class Unbxd_Datafeeder_Model_Feed_Feedmanager {


	/**
 	* method to push the feed to the Unbxd server
 	**/
 	public function pushFeed($site){
 		$fields=array('file'=>'@'.$this->fileName.';filename=unbxdFeedRenamed.json');
	 	$header = array('Content-Type: multipart/form-data');
	
 		$url="http://feed.unbxdapi.com/upload/v2/".$this->key."/".$this->siteName."?fullimport=true";
 		
 		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POST,true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$fields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		try{
            $this->log('pushing the feed to '.$url);
            // push the feed to the server
			$response = $this->exec($ch);
		}catch(Exception $Ex){
	        $this->log($Ex->getMessage());
			return false;
		}
        $this->log(json_encode($response));
		curl_close($ch);
		return true;		
 	}

 	public function exec($ch)
    {
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $result = array( 'header' => '', 
                         'body' => '', 
                         'curl_error' => '', 
                         'http_code' => '',
                         'last_url' => '');
        if ( $error != "" )
        {
            $result['curl_error'] = $error;
            return $result;
        }
        
        $header_size = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
        $result['header'] = substr($response, 0, $header_size);
        $result['body'] = substr( $response, $header_size );
        $result['http_code'] = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $result['last_url'] = curl_getinfo($ch,CURLINFO_EFFECTIVE_URL);
        return $result;
    }

 	/**
	* method to set the feedName, log, apikey based on site Name
	**/
	public function init($site)
	{
		$this->fileName = Mage::getBaseDir('tmp').DS.str_replace(' ', '_',$site). "_Feed.json";
		$this->key = Mage::getResourceSingleton("datafeeder/conf")->getValue("apiKey");
		$this->siteName = Mage::getResourceSingleton("datafeeder/conf")->getValue($site."/siteName");
		if(!isset($this->key) || $this->key == "" || $this->key == "empty"){
			$this->log("api key not set");
			return false;
		}
		if(!isset($this->siteName) || $this->siteName == "" || $this->siteName == "empty"){
            $this->log("site Name not set");
			return false;
        }
		return true;
	}

	/**
	* method to validate whether the site exists or not
	**/
	public function validateSite($site){
		$sites=Mage::app()->getWebsites();
		if( !isset($site) || $site == "") {
			return false;
		}
		foreach( $sites as $eachSite){
			if(strcasecmp ( $eachSite->getName(), $site ) == 0 ){
				return $eachSite->getWebsiteId();	
			}
		}
		return -1;
	}

	/**
 	* method to initiate feed uploading to the unbxd servers
 	**/
 	public function process($fromdate,$site,$operation = "add", $ids=array()){
	 	
		$this->log('unbxd Datafeeder initiated');
		 // validatest the site 
		if($this->validateSite($site) == -1){
			$this->log("Invalid site Name".$site);
			return;
		}
		// set the basic
		if(! $this->init($site)){
			return;
		}
 		$todate =date('Y-m-d H:i:s');

 		// check the lock, that if already indexing is happening
 		if($this->checkSiteLock($site)){
 			$this->log('site '. $site.' has been locked');
 			$action=$site.'/status';
 			// lock the feed
		 	Mage::getResourceSingleton("datafeeder/conf")->updateAction($action,'1');
		 	// create the feed

	 		$status=Mage::getSingleton('unbxd_datafeeder/feed_feedcreator')
	 					->createFeed($this->fileName, $fromdate,$todate,$site,$operation,$ids);
            $this->log('unbxd Datafeeder finished creating file');
	
	 		if($status){ 		
				try{
					// if successful push it to unbxd servers
			 		$status=$this->pushFeed($site);
				}catch(Exception $e){
	                $this->log($e->getMessage());

				}
		 		if($status){
			 		Mage::getResourceSingleton("datafeeder/conf")->updateAction('Lastindex',$todate);
		 		}
	 		}
	 		// unlock the feed once everything is completed
		 	Mage::getResourceSingleton("datafeeder/conf")->updateAction($action,'0');	
		 	$this->log('site '. $site.' has been unlocked');
 		} else {
 			$this->log('Feed Uploading failed because site has been locked');
 		}
 	}

 	/**
 	* method to check the status of the uploading
 	**/
 	public function checkSiteLock($site)
	{   	
	    $value = Mage::getResourceSingleton("datafeeder/conf")->getValue($site."/status");
	    if($value == '0' || $value == 'empty'){
	    	$this->log("true");
	    	return true;
	    }else{
	    	$this->log("false". $value);
	    	return false;
	    }
	}

	public function log($message) {
		Mage::getSingleton('unbxd_datafeeder/feed_filemanager')->log($message);
	}
}
?>