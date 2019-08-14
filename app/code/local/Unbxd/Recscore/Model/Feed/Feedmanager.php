<?php

class Unbxd_Recscore_Model_Feed_Feedmanager {
	
	//Delete the file after uploading the feed
	var $deleteIncrementalFeed = true;

    var $fileName = "unbxdFeed.json";

    var $key;

    var $siteName;

	/**
 	* method to push the feed to the Unbxd server
 	**/
    protected function _pushFeed($fullimport=true){
	if(!function_exists('curl_file_create')){
        	$fields=array('file'=>'@'.$this->fileName.';filename='.'unbxdFeed.json');
	} else {
		$file = new CurlFile($this->fileName,'','unbxdFeed.json');
		$fields = array('file'=>$file);
	}
	$feedUrl = Mage::helper('unbxd_recscore/confighelper')->getConfigData('feed_url');
        $url= $feedUrl . "upload/v2/".$this->key."/".$this->siteName. ($fullimport?"?fullimport=true":"");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST,true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        try{
            $this->log('pushing the feed to '.$url);
            // push the feed to the server
            $response = $this->exec($ch);
            $this->log(json_encode($response));
            $responseMessage = json_decode($response['body'], true);
            if($responseMessage["statusCode"] != 200) {
                throw new Exception('Unexpected response from unbxd server');
            }
        }catch(Exception $ex){
            $this->log("Error while uploading the feed because of " . $ex->getMessage());
            return false;
        }
        curl_close($ch);
        return true;
    }

	/**
	  * Method to check the first time feed upload
	  * @param Mage_Core_Model_Website $website
	  * @return bool
	  */
	 protected function _checkFeedUpload(Mage_Core_Model_Website $website) {
		$lastUploadTime = Mage::getResourceModel('unbxd_recscore/config')
				->getValue($website->getWebsiteId(), Unbxd_Recscore_Helper_Constants::LAST_UPLOAD_TIME);
		return !is_null($lastUploadTime);
	 }
	protected function _triggerSearchComplete(Mage_Core_Model_Website $website) {
		Mage::helper('unbxd_recscore')->log(Zend_Log::DEBUG, 'trigger search complete for website ' . $website->getName());
		if(!$this->_checkFeedUpload($website) && Mage::helper('core')->isModuleEnabled('Unbxd_Searchcore')) {
			Mage::getModel('unbxd_searchcore/api_task_searchsetup')
             			 ->prepare($website)
              			 ->process();	
		}
	}

 	protected function exec($ch)
    {
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $result = array( 'header' => '', 
                         'body' => '', 
                         'curl_error' => '', 
                         'http_code' => '',
                         'last_url' => '');
        if ( $error != "" ) {
            $result['curl_error'] = $error;
            return $result;
        }

        $header_size = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
        //$result['header'] = substr($response, 0, $header_size);
        $result['body'] = $response;
        $result['http_code'] = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $result['last_url'] = curl_getinfo($ch,CURLINFO_EFFECTIVE_URL);
        return $result;
    }

 	/**
	* method to set the feedName, log, apikey based on site Name
	**/
	public function init(Mage_Core_Model_Website $website, $isFullUpload)
	{
		$this->fileName = Mage::getBaseDir('tmp').DS.str_replace(' ', '_', $website->getName()).
            "_Feed" . (!$isFullUpload?round(microtime(true) * 1000):'') .  ".json";
        $this->key = Mage::getResourceModel("unbxd_recscore/config")
            ->getValue($website->getWebsiteId(), Unbxd_Recscore_Helper_Confighelper::SECRET_KEY);
        $this->siteName = Mage::getResourceModel("unbxd_recscore/config")
            ->getValue($website->getWebsiteId(), Unbxd_Recscore_Helper_Confighelper::SITE_KEY);
		if(is_null($this->siteName) || is_null($this->key)) {
            $message = 'Authorization failed, keys not set';
            Mage::helper('unbxd_recscore')->log(Zend_Log::ERR, $message);
            return array('success' => false, 'message' => $message);
        }
		return true;
	}

    /**
     * method to update feature field if required
     *
     * @param Mage_Core_Model_Website $website
     * @return void
     */
    private function updateFeatureFields(Mage_Core_Model_Website $website) {
        $needFeatureFieldUpdation = Mage::getResourceModel("unbxd_recscore/config")
            ->getValue($website->getWebsiteId(), Unbxd_Recscore_Helper_Confighelper::NEED_FEATURE_FIELD_UPDATION);
        if(is_null($needFeatureFieldUpdation) || $needFeatureFieldUpdation == "1") {
            $response = Mage::helper('unbxd_recscore/confighelper')->updateFeatureFields($website);
            if($response) {
                Mage::getResourceModel("unbxd_recscore/config")
                    ->setValue($website->getWebsiteId(),
                        Unbxd_Recscore_Helper_Constants::NEED_FEATURE_FIELD_UPDATION,
                        Unbxd_Recscore_Helper_Constants::NEED_FEATURE_FIELD_UPDATION_TRUE);
                //If search module is present and autosuggest is active then trigger autosuggest indexing
		Mage::helper('unbxd_recscore/confighelper')->triggerAutoggestIndexing($website);

            } else {
                $this->log("error while updating the feature fields");
            }
        }
    }

	/**
 	* method to initiate feed uploading to the unbxd servers
 	**/
 	public function process($isFullUpload = true, Mage_Core_Model_Website $website){
	 	
		$this->log('Feed Uploading request recieved');
	        $response = $this->init($website, $isFullUpload);
		if(is_array($response)){
			return $response;
		}
 		$currentDate = date('Y-m-d H:i:s');
		$this->_triggerSearchComplete($website);

 		// check the lock, that if already indexing is happening
 		if(!$isFullUpload ||
            !Mage::getResourceModel("unbxd_recscore/config")->isLock($website->getWebsiteId())) {

        	    $this->log('site '. $website->getName() .' is acquiring feed lock');
        	    if($isFullUpload) {
			Mage::getResourceSingleton("unbxd_recscore/config")
                        	->setValue($website->getWebsiteId(),
                            		Unbxd_Recscore_Model_Config::FEED_STATUS, 
					Unbxd_Recscore_Helper_Constants::FEED_STATUS_UPLOADING);
                	Mage::getResourceModel('unbxd_recscore/config')->lockSite($website->getWebsiteId());
            	    }
	            try {
		    // create the feed
	 	    	$status = Mage::getSingleton('unbxd_recscore/feed_feedcreator')
                        	->setFullUpload($isFullUpload)
	 			->createFeed($this->fileName, $website, $currentDate);
            	    	$this->log('unbxd Datafeeder finished creating file');
		    }catch (Exception $e) {
             		   $this->log('Caught exception: '. $e->getMessage());
			   $status = false;
			  $errorMsg = $e->getMessage(); 
            	      }
	
	 	    if($status){ 		
                	$status=$this->_pushFeed($isFullUpload);
		 	if($status){
			 		Mage::getResourceSingleton("unbxd_recscore/config")
                        ->setValue($website->getWebsiteId(),
                            Unbxd_Recscore_Model_Config::LAST_UPLOAD_TIME, $currentDate);
                    $this->updateFeatureFields($website);
		 		}
	 		}


            if($isFullUpload) {
                // unlock the feed once everything is completed
                Mage::getResourceModel('unbxd_recscore/config')->unLockSite($website->getWebsiteId());
            }  else {
                //In case of incremental feed delete the feed
               // Mage::getSingleton('unbxd_recscore/filemanager')->deleteFile($this->fileName);
            }

		 	$this->log('site ' . $website->getName() .' has been unlocked');
            if($status) {
		Mage::getResourceSingleton("unbxd_recscore/config")
                        ->setValue($website->getWebsiteId(),
                            Unbxd_Recscore_Model_Config::FEED_STATUS,
			    Unbxd_Recscore_Helper_Constants::FEED_STATUS_UPLOADED_SUCCESSFULLY);
              return array('success' => true, 'message' => 'File uploaded successfully');
            }
	    Mage::getResourceSingleton("unbxd_recscore/config")
                        ->setValue($website->getWebsiteId(),
                            Unbxd_Recscore_Model_Config::FEED_STATUS,
			    Unbxd_Recscore_Helper_Constants::FEED_STATUS_UPLOADED_FAILED);
            return array('success' => false, 'message' => isset($errorMsg)?$errorMsg:'Unexpected error, please contact support');
 		} else {
 			$this->log('Feed Uploading failed because site has been locked');
            return array('success' => false, 'message' => 'Feed is already being processed');
 		}
 	}

	public function log($message) {
		Mage::helper('unbxd_recscore')->log(Zend_Log::DEBUG, $message);
	}
}
?>
