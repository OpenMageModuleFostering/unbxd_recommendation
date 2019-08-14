<?php

class Unbxd_Recommendation_Model_Feed_Feedmanager {
	
	//Delete the file after uploading the feed
	var $deleteIncrementalFeed = true;

    var $fileName = "unbxdFeed.json";

    var $key;

    var $siteName;

    const LAST_UPLOAD_TIME = 'lastUpload';

	/**
 	* method to push the feed to the Unbxd server
 	**/
    public function pushFeed($fullimport=true){
        $fields=array('file'=>'@'.$this->fileName.';filename='.'unbxdFeed.json');
        $header = array('Content-Type: multipart/form-data');

        $url="http://feed.unbxdapi.com/upload/v2/".$this->key."/".$this->siteName. ($fullimport?"?fullimport=true":"");

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
            $this->log($responseMessage);
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

 	public function exec($ch)
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
	public function init(Mage_Core_Model_Website $website)
	{
		$this->fileName = Mage::getBaseDir('tmp').DS.str_replace(' ', '_', $website->getName()). "_Feed.json";
        $this->key = Mage::getResourceModel("unbxd_recommendation/config")
            ->getValue($website->getWebsiteId(), Unbxd_Recommendation_Helper_Confighelper::SECRET_KEY);
        $this->siteName = Mage::getResourceModel("unbxd_recommendation/config")
            ->getValue($website->getWebsiteId(), Unbxd_Recommendation_Helper_Confighelper::SITE_KEY);
		if(is_null($this->siteName) || is_null($this->key)) {
            $message = 'Authorization failed, keys not set';
            Mage::helper('unbxd_recommendation')->log(Zend_Log::ERR, $message);
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
        $needFeatureFieldUpdation = Mage::getResourceModel("unbxd_recommendation/config")
            ->getValue($website->getWebsiteId(), Unbxd_Recommendation_Helper_Confighelper::NEED_FEATURE_FIELD_UPDATION);
        if(is_null($needFeatureFieldUpdation) || $needFeatureFieldUpdation == "1") {
            $response = Mage::helper('unbxd_recommendation/confighelper')->updateFeatureFields($website);
            if(!is_array($response)) {
                Mage::getResourceModel("unbxd_recommendation/config")
                    ->setValue($website->getWebsiteId(),
                        Unbxd_Recommendation_Helper_Confighelper::NEED_FEATURE_FIELD_UPDATION, 0);
            } else {
                $this->log("error while updating the feature fields");
            }
        }
    }

	/**
 	* method to initiate feed uploading to the unbxd servers
 	**/
 	public function process($fromdate, Mage_Core_Model_Website $website, $operation = "add", $ids=array()){
	 	
		$this->log('Feed Uploading request recieved');
        $response = $this->init($website);
		if(is_array($response)){
			return $response;
		}
 		$currentDate = date('Y-m-d H:i:s');

 		// check the lock, that if already indexing is happening
 		if(!Mage::getResourceModel("unbxd_recommendation/config")->isLock($website->getWebsiteId())) {

            $this->log('site '. $website->getName() .' is acquiring feed lock');
            Mage::getResourceModel('unbxd_recommendation/config')->lockSite($website->getWebsiteId());

		 	// create the feed
	 		$status=Mage::getSingleton('unbxd_recommendation/feed_feedcreator')
	 					->createFeed($this->fileName, $fromdate, $currentDate, $website, $operation, $ids);
            $this->log('unbxd Datafeeder finished creating file');
	
	 		if($status){ 		
                $status=$this->pushFeed();
		 		if($status){
			 		Mage::getResourceSingleton("unbxd_recommendation/config")
                        ->setValue($website->getWebsiteId(), self::LAST_UPLOAD_TIME, $currentDate);
		 		}
	 		}

	 		// unlock the feed once everything is completed
            Mage::getResourceModel('unbxd_recommendation/config')->unLockSite($website->getWebsiteId());
            $this->updateFeatureFields($website);
		 	$this->log('site ' . $website->getName() .' has been unlocked');
            if($status) {
              return array('success' => true, 'message' => 'File uploaded successfully');
            }
            return array('success' => false, 'message' => 'Unexpected error, please contact support');
 		} else {
 			$this->log('Feed Uploading failed because site has been locked');
            return array('success' => false, 'message' => 'Feed is already being processed');
 		}
 	}

	public function log($message) {
		Mage::helper('unbxd_recommendation')->log(Zend_Log::DEBUG, $message);
	}
}
?>