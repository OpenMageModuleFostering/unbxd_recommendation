<?php

/**
 * controller that provides all the config
 * @category Unbxd
 * @package Unbxd_Recscore
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recscore_ConfigController extends Mage_Core_Controller_Front_Action {

    /**
     * Recscore
     * @return Unbxd_Recscore_Helper_Confighelper
     */
    protected function _helper() {
        return Mage::helper("unbxd_recscore/confighelper");
    }

    protected function _getRawBody($request)
    {
        $requestBody = json_decode($request->getRawBody(), true);
        if (0 != strpos($request->getHeader('Content-Type'), 'application/json') || $requestBody === false) {
            $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(json_encode(array('success' => false,
                'errors' => array('message' => 'Invalid Request'))));
            return null;
        }
        return $requestBody;
    }

    /**
     * @return void
     */
    public function filterAction() {
        $website = $this->_prepare();
        if (is_null($website)) {
            return;
        }

        if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::GET) {
            Mage::getResourceModel('unbxd_recscore/config')->getValues($website->getWebsiteId(),
                Unbxd_Recscore_Helper_Constants::FILTER);
        } else if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::POST) {

        } else {
            $this->_helper()
                ->log(Zend_Log::DEBUG, 'keys api failed because of invalid method');
            $this->getResponse()->setBody(json_encode(array('success' => false,
                'errors' => array('message' => 'Invalid method'))));
        }
    }


    protected function _getFields($request) {
        $requestBody = $this->_getRawBody($request);
        if(is_null($requestBody)) {
            return null;
        }
        if(!array_key_exists("fields", $requestBody)) {
            $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(json_encode(array('success' => false,
                'errors' => array('message' => 'Invalid Request'))));
            return null;
        }
        return $requestBody["fields"];
    }

    /**
     * @param $websiteName
     * @return mixed
     */
    protected function _getWebsiteByName($websiteName)
    {
        return Mage::getResourceModel('core/website_collection')
            ->addFieldToFilter('name', $websiteName)
            ->getFirstItem();
    }

    protected function _prepare() {
        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
        if(array_key_exists("site", $_REQUEST)) {
            $website = $this->_getWebsiteByName($_REQUEST["site"]);
        }

        if(!isset($website) || !$website->hasData("website_id")) {
            $this->_helper()->log(Zend_Log::DEBUG,'api failed because of invalid website');
            $this->getResponse()->setBody(json_encode(array('success' => false, 'errors' => array('Invalid site'))));
            return null;
        }
        return $website;
    }

    public function editAction(){
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * api to return the sites
     * @return void
     */
    public function siteAction()
    {
        $websites = Mage::app()->getWebsites();
        $sites = array();
        foreach ($websites as $website) {
            $sites[] = array('name' => $website->getName(),
                'id' => $website->getId(),
                'numDocs' => $this->_helper()->getNumberOfDocsInUnbxd($website));
        }
        $response = array('sites' => $sites);
        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(json_encode($response));
    }

    /**
     * Method to get the fields
     * @return void
     */
    public function fieldsAction() {
        $request = $this->getRequest();
        if(array_key_exists("site", $_REQUEST)) {
            $website = $this->_getWebsiteByName($_GET["site"]);
        }
        if(!isset($website) || !$website->hasData("website_id")) {
            $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(json_encode(array('success' => false,
                'errors' => array('message' => 'Invalid site'))));
            return;
        }
        if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::GET) {
            $mappedFields = Mage::getResourceModel('unbxd_recscore/field')->getDisplayableFields($website)
                ->__asArray();
            $featureFields = Unbxd_Recscore_Model_Field::$displayableFeatureFields;
            foreach($mappedFields as $fields) {
                if(array_key_exists(Unbxd_Recscore_Model_Field::featured_field, $fields) &&
                    in_array($fields[Unbxd_Recscore_Model_Field::featured_field], $featureFields)) {
                    unset($featureFields[array_search($fields[Unbxd_Recscore_Model_Field::featured_field],
                        $featureFields)]);
                }
            }

            foreach($featureFields as $field) {
                $mappedFields[] = array(Unbxd_Recscore_Model_Field::featured_field => $field,
                    Unbxd_Recscore_Model_Field::datatype => 'text',
                    Unbxd_Recscore_Model_Field::autosuggest => 1);
            }

            $fields = $this->_helper()->getAllAttributes();
            $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(json_encode(array('success' => true,
                'mappedFields' => $mappedFields, 'fields' => $fields,
                'datatype' => Unbxd_Recscore_Model_Field::$data_types)));
        } else if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::POST) {
	    $fields = $this->_getFields($request);
	    if($fields == null ) {
		$this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
            	$this->getResponse()->setBody(json_encode(array('success' => false,
                'errors' => array('message' => 'Invalid Request'))));
		return;
	    }
            //request for adding fields
            $response = $this->_helper()->saveFields($fields, $website);
            if(is_array($response)) {
                $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
                $this->getResponse()->setBody(json_encode(array('success' => false, 'errors' => $response)));
                return;
            }
            $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(json_encode(array('success' => true)));
            return;
        } else if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::PUT) {
	    $fields = $this->_getFields($request);
            if($fields == null ) {
		$this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(json_encode(array('success' => false,
                'errors' => array('message' => 'Invalid Request'))));
		return;
		
            }


            // request for deleting fields
            $response = $this->_helper()->deleteFields($this->_getFields($request), $website);
            if(is_array($response)) {
                $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
                $this->getResponse()->setBody(json_encode(array('success' => false, 'errors' => $response)));
                return;
            }
            $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(json_encode(array('success' => true)));
            return;
        } else {
            $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(json_encode(array('success' => false,
                'errors' => array('message' => 'Invalid method'))));
        }
    }

    /**
     * Api to get the analytics impressions
     *
     * @return void
     */
    public function analyticsimpressionAction() {
        $website = $this->_prepare();
        if(is_null($website)){
            return;
        }
        if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::GET) {

            $analyticsResponse = Mage::getModel("unbxd_recscore/api_task_analyticsimpression")
                ->prepare($website)
                ->process();
            if(!$analyticsResponse->isSuccess()) {
                $this->getResponse()->setBody(json_encode(array('success' => false, 'errors' => $analyticsResponse->getErrors())));
                return;
            }
	    
	    $searchResponse = Mage::getSingleton('unbxd_recscore/api_task_searchimpression')
                ->prepare($website)
                ->process();
            if(!$searchResponse->isSuccess()) {
                $this->getResponse()->setBody(json_encode(array('success' => false, 'errors' => $searchResponse->getErrors())));
                return;
            }
	    $response = $analyticsResponse->getResponse();
	    $searhitResponseObj = $searchResponse->getResponse();
	    $funnels = $searhitResponseObj["FunnelResponse"]["Funnels"];
	    $searchCount = 0;
	    $searchClicks = 0;
	    foreach($funnels as $funnel) {
		if(array_key_exists("type", $funnel) && $funnel["type"] == "hits" && array_key_exists("searchCount",$funnel)) {
			$searchCount = $funnel["searchCount"];
		}
		if(array_key_exists("type", $funnel) && $funnel["type"] == "clicks" && array_key_exists("searchCount",$funnel)) {
                        $searchClicks = $funnel["searchCount"];
                }
	    }
	    $response["searchCount"] = $searchCount;
	    $response["searchClicks"] = $searchClicks;
            $this->getResponse()->setBody(json_encode(array('success' => true) + $response));
        } else {
            $this->getResponse()->setBody(json_encode(array('success' => false, 'errors' => array('Invalid method'))));
        }

    }

    /**
     * Method to get keys
     * @return void
     */
    public function keysAction() {
        $request = $this->getRequest();
        $website = $this->_prepare();
        if(is_null($website)) {
            return;
        }

        if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::GET) {
            $secretKey = Mage::getResourceModel("unbxd_recscore/config")
                ->getValue($website->getWebsiteId(), Unbxd_Recscore_Helper_Constants::SECRET_KEY);
            $siteKey = Mage::getResourceModel("unbxd_recscore/config")
                ->getValue($website->getWebsiteId(), Unbxd_Recscore_Helper_Constants::SITE_KEY);
            $errors = array();
            if(is_null($secretKey)) {
                $errors[Unbxd_Recscore_Helper_Constants::SECRET_KEY] = "secret key not set";
            }
            if(is_null($siteKey)) {
                $errors[Unbxd_Recscore_Helper_Constants::SITE_KEY] = "site key not set";
            }
            if(sizeof($errors) > 0) {
                $this->_helper()
                    ->log(Zend_Log::DEBUG,'save keys api failed because ' . json_encode($errors));
                $this->getResponse()->setBody(json_encode(array('success' => false, 'errors' => $errors)));
                return;
            }
            $this->getResponse()->setBody(json_encode(array('success' => true,
                Unbxd_Recscore_Helper_Constants::SECRET_KEY => $secretKey,
                Unbxd_Recscore_Helper_Constants::SITE_KEY => $siteKey)));
            return;
        } else if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::POST) {
            $requestBody = $request->getRawBody();
            $errors = $this->_helper()->validateAndSaveKeys($website, $requestBody);
            if(sizeof($errors)>0) {
                $this->_helper()
                    ->log(Zend_Log::DEBUG, 'save keys api failed because of ' . json_encode($errors));
                $this->getResponse()->setBody(json_encode(array('success' => false,
                    'errors' => $errors)));
                return;
            }
            $this->getResponse()->setBody(json_encode(array('success' => true)));
        } else {
            $this->_helper()
                ->log(Zend_Log::DEBUG, 'keys api failed because of invalid method');
            $this->getResponse()->setBody(json_encode(array('success' => false,
                'errors' => array('message' => 'Invalid method'))));
            return;
        }
    }

    public function filterablesAction() {
        $website = $this->_prepare();
        if (is_null($website)) {
            return;
        }
        if ($this->getRequest()->getMethod() == Zend_Http_Client::GET) {
            $filterablAttributes = Mage::helper("unbxd_recscore/feedhelper")->getAllFilterableAttributes($website);
            if(is_array($filterablAttributes)) {
                $this->getResponse()->setBody(json_encode(array('success' => false, "fields"=>$filterablAttributes)));
                return;
            }
            $this->getResponse()->setBody(json_encode(array('success' => true)));
            return;
        } else {
            $this->getResponse()->setBody(json_encode(array('success' => false,
                'errors' => array('message' => 'Invalid method'))));

        }

    }



    public function dimensionmapAction()
    {
        $website = $this->_prepare();
        if (is_null($website)) {
            return;
        }
        if ($this->getRequest()->getMethod() == Zend_Http_Client::POST) {
            $response = $this->_helper()->updateFeatureFields($website);
            if(is_array($response)) {
                $this->getResponse()->setBody(json_encode(array('success' => false, $response)));
                return;
            }
            $this->getResponse()->setBody(json_encode(array('success' => true)));
            return;
        } else {
            $this->_helper()
                ->log(Zend_Log::DEBUG, 'keys api failed because of invalid method');
            $this->getResponse()->setBody(json_encode(array('success' => false,
                'errors' => array('message' => 'Invalid method'))));

        }
    }

    public function downloadAction() {
        $filepath = Mage::getBaseDir('log').DS.Unbxd_Recscore_Helper_Data::LOG_FILE;

        if (! is_file ( $filepath ) || ! is_readable ( $filepath )) {
            throw new Exception ( );
        }
        $this->getResponse ()
            ->setHttpResponseCode ( 200 )
            ->setHeader ( 'Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true )
            ->setHeader ( 'Pragma', 'public', true )
            ->setHeader ( 'Content-type', 'application/force-download' )
            ->setHeader ( 'Content-Length', filesize($filepath) )
            ->setHeader ('Content-Disposition', 'attachment' . '; filename=' . basename($filepath) );
        $this->getResponse ()->clearBody ();
        $this->getResponse ()->sendHeaders ();
        readfile ( $filepath );
        return;
    }

    public function feeddetailsAction() {
        $website = $this->_prepare();
        if (is_null($website)) {
            return;
        }

        $this->getResponse()
            ->setBody(json_encode(array('success' => true,
                'numDocs' => $this->_helper()->getNumberOfDocsInUnbxd($website))));
        return;
    }

    public function productsyncAction(){
        $website = $this->_prepare();
        if (is_null($website)) {
            return;
        }
        ignore_user_abort(true);
        set_time_limit(0);
        $isFullUpload = true;
        $feedMgr = Mage::getSingleton('unbxd_recscore/feed_feedmanager');
        if(array_key_exists('incremental', $_REQUEST)) {
            $isFullUpload = false;
        }

        $response = $feedMgr->process($isFullUpload, $website);
        $this->getResponse()->setBody(json_encode($response));
        return;
    }


    public function unlockfeedAction() {
        $website = $this->_prepare();
        if (is_null($website)) {
            return;
        }
        Mage::getResourceModel('unbxd_recscore/config')->unLockSite($website->getWebsiteId());
        $this->getResponse()->setBody(json_encode(array('success' => true)));
    }

    public function cronAction() {
        $website = $this->_prepare();
        if (is_null($website)) {
            return;
        }
        $isCronEnabled = Mage::getResourceSingleton('unbxd_recscore/config')->getValue($website->getWebsiteId(),
            Unbxd_Recscore_Helper_Constants::IS_CRON_ENABLED);
        if(is_null($isCronEnabled)) {
            $this->getResponse()->setBody(json_encode(array('success' => true, 'cron_enabled' => false)));
        }
        $this->getResponse()->setBody(json_encode(array('success' => true, 'cron_enabled' => true)));
    }

    public function supportmailAction() {
        $website = $this->_prepare();
        if (is_null($website)) {
            return;
        }

        if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::POST) {
            $request = $this->getRequest();
            $requestBody = $this->_getRawBody($request);
            if(!array_key_exists(Unbxd_Recscore_Helper_Constants::CC, $requestBody) ||
                !array_key_exists(Unbxd_Recscore_Helper_Constants::SUBJECT, $requestBody) ||
                    !array_key_exists(Unbxd_Recscore_Helper_Constants::CONTENT, $requestBody)) {
                $this->getResponse()->setBody(json_encode(array('success' => false, 'message' => 'Invalid parameters')));
                return;
            }
            Mage::getModel('unbxd_recscore/api_task_supportmail')
                ->setSubject($requestBody[Unbxd_Recscore_Helper_Constants::SUBJECT])
                ->setCc($requestBody[Unbxd_Recscore_Helper_Constants::CC])
                ->setContent($requestBody[Unbxd_Recscore_Helper_Constants::CONTENT])
                ->prepare($website)
                ->process();
            $this->getResponse()->setBody(json_encode(array('success' => true)));
        } else {
            $this->getResponse()->setBody(json_encode(array('success' => false,
                'errors' => array('message' => 'Invalid method'))));
        }
    }

    /**
     * @return void
     */
    public function globalAction() {
        $website = $this->_prepare();
        if (is_null($website)) {
            return;
        }

        if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::GET) {
            $keys = $this->getRequest()->getParam('key');
            if(isset($keys) && $keys != '') {
                $keys = explode(',', $keys);
                $configs = array();
                foreach($keys as $key) {
                    $value = $this->_helper()->getEngineConfigData($key, $website, true);
                    if(is_array($value)) {
                        $configs = $configs + $value;
                    }
                 }
                $this->getResponse()->setBody(json_encode(array('success' => true, 'config' => $configs)));
            } else {
		 $this->getResponse()->setBody(json_encode(array('success' => false,
                 'errors' => array('message' => 'Invalid input'))));
	    }


        } else if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::POST) {
            $request = $this->getRequest();
            $requestBody = $this->_getRawBody($request);
	    if(!is_array($requestBody)) {
		$this->getResponse()->setBody(json_encode(array('success' => false, 'message' => 'Invalid input')));	
		return;
	    }
	    foreach($requestBody as $field=>$value) { 
		if(!is_null($this->_helper()->isConfigSaveAllowed($website, $field, $value))){	
			$this->getResponse()->setBody(json_encode(array('success' => false, $field => $this->_helper()->isConfigSaveAllowed($website, $field, $value))));
			return;
		}
	    }
            $this->_helper()->saveConfig($website, $requestBody);
	    Mage::app()->getCacheInstance()->invalidateType('config');
            $this->getResponse()->setBody(json_encode(array('success' => true)));
        } else {
            $this->getResponse()->setBody(json_encode(array('success' => false,
                'errors' => array('message' => 'Invalid method'))));
        }
    }

    /**
     *
     * @return void
     */
    public function searchimpressionAction() {
        $website = $this->_prepare();
        if (is_null($website)) {
            return;
        }

        if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::GET) {
            $response = Mage::getSingleton('unbxd_recscore/api_task_searchimpression')
                ->prepare($website)
                ->process();
            if(!$response->isSuccess()) {
                $this->getResponse()->setBody(json_encode(array('success' => false, 'errors' => $response->getErrors())));
                return;
            }
            $this->getResponse()->setBody(json_encode(array('success' => true, 'funnel' => $response->getResponse())));
        } else {
            $this->getResponse()->setBody(json_encode(array('success' => false,
                'errors' => array('message' => 'Invalid method'))));
        }
    }
   
    public function feedstatusAction() {
	$website = $this->_prepare();
        if (is_null($website)) {
            return;
        }
	if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::GET) {
	    $response = Mage::getResourceSingleton("unbxd_recscore/config")->getValue($website->getWebsiteId(), 
						array(Unbxd_Recscore_Model_Config::LAST_UPLOAD_TIME,
							Unbxd_Recscore_Model_Config::FEED_STATUS));
	    if(!array_key_exists(Unbxd_Recscore_Model_Config::LAST_UPLOAD_TIME, $response)) {
		$response[Unbxd_Recscore_Model_Config::LAST_UPLOAD_TIME] = null;
		$response[Unbxd_Recscore_Model_Config::FEED_STATUS] = 'NOT UPLOADED';
	    } 
            $this->getResponse()->setBody(json_encode(array('success' => true) + $response));
        } else {
            $this->getResponse()->setBody(json_encode(array('success' => false,
                'errors' => array('message' => 'Invalid method'))));
        }

    }

    public function stateAction() {
	$website = $this->_prepare();
	if (is_null($website)) {
		return;
	}
	if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::GET) {
		$response = Mage::getModel('unbxd_recscore/statemgr')->getAllStates($website);
		$this->getResponse()->setBody(json_encode(array('success' => true) + $response));
	} else {
		$this->getResponse()->setBody(json_encode(array('success' => false,
			'errors' => array('message' => 'Invalid method'))));
	}
    }
}
