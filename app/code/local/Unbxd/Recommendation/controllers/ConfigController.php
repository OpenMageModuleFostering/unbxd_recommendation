<?php

/**
 * controller that provides all the config
 * @category Unbxd
 * @package Unbxd_Recommendation
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recommendation_ConfigController extends Mage_Core_Controller_Front_Action {

    /**
     * @return Unbxd_Recommendation_Helper_Confighelper
     */
    protected function helper() {
        return Mage::helper("unbxd_recommendation/confighelper");
    }

    protected function getRawBody($request) {
        $requestBody = json_decode($request->getRawBody(), true);
        if(0 != strpos($request->getHeader('Content-Type'), 'application/json') || $requestBody === false) {
            $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(json_encode(array('success' => false,
                'errors' => array('message' => 'Invalid Request'))));
            return null;
        }
        return $requestBody;
    }


    protected function getFields($request) {
        $requestBody = $this->getRawBody($request);
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
    protected function getWebsiteByName($websiteName)
    {
        return Mage::getResourceModel('core/website_collection')
            ->addFieldToFilter('name', $websiteName)
            ->getFirstItem();
    }

    protected function prepare() {
        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
        if(array_key_exists("site", $_REQUEST)) {
            $website = $this->getWebsiteByName($_REQUEST["site"]);
        }

        if(!isset($website) || !$website->hasData("website_id")) {
            Mage::helper('unbxd_recommendation')->log(Zend_Log::DEBUG,'api failed because of invalid website');
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
                'numDocs' => $this->helper()->getNumberOfDocsInUnbxd($website));
        }
        $response = array('sites' => $sites);
        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(json_encode($response));
    }

    public function fieldsAction() {
        $request = $this->getRequest();
        if(array_key_exists("site", $_REQUEST)) {
            $website = $this->getWebsiteByName($_GET["site"]);
        }
        if(!isset($website) || !$website->hasData("website_id")) {
            $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(json_encode(array('success' => false,
                'errors' => array('message' => 'Invalid site'))));
            return;
        }
        if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::GET) {
            $mappedFields = Mage::getResourceModel('unbxd_recommendation/field')->getDisplayableFields($website)
                ->__asArray();
            $featureFields = Unbxd_Recommendation_Model_Field::$displayableFeatureFields;
            foreach($mappedFields as $fields) {
                if(array_key_exists(Unbxd_Recommendation_Model_Field::featured_field, $fields) &&
                    in_array($fields[Unbxd_Recommendation_Model_Field::featured_field], $featureFields)) {
                    unset($featureFields[array_search($fields[Unbxd_Recommendation_Model_Field::featured_field],
                        $featureFields)]);
                }
            }

            foreach($featureFields as $field) {
                $mappedFields[] = array(Unbxd_Recommendation_Model_Field::featured_field => $field,
                    Unbxd_Recommendation_Model_Field::datatype => 'text',
                    Unbxd_Recommendation_Model_Field::autosuggest => 1);
            }

            $fields = Mage::helper("unbxd_recommendation/confighelper")->getAllAttributes();
            $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(json_encode(array('success' => true,
                'mappedFields' => $mappedFields, 'fields' => $fields,
                'datatype' => Unbxd_Recommendation_Model_Field::$data_types)));
        } else if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::POST) {

            //request for adding fields
            $response = $this->helper()->saveFields($this->getFields($request), $website);
            if(is_array($response)) {
                $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
                $this->getResponse()->setBody(json_encode(array('success' => false, 'errors' => $response)));
                return;
            }
            $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(json_encode(array('success' => true)));
            return;
        } else if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::PUT) {

            // request for deleting fields
            $response = $this->helper()->deleteFields($this->getFields($request), $website);
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
     * Api to update dimension mapping
     *
     * @return void
     */
    public function analyticsimpressionAction() {
        $website = $this->prepare();
        if(is_null($website)){
            return;
        }
        if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::GET) {

            $response = Mage::getModel("unbxd_recommendation/api_task_analyticsimpression")
                ->prepare($website)
                ->process();
            if(!$response->isSuccess()) {
                $this->getResponse()->setBody(json_encode(array('success' => false, 'errors' => $response->getErrors())));
                return;
            }
            $this->getResponse()->setBody(json_encode(array('success' => true) + $response->getResponse()));
        } else {
            $this->getResponse()->setBody(json_encode(array('success' => false, 'errors' => array('Invalid method'))));
        }

    }

    public function keysAction() {
        $request = $this->getRequest();
        $website = $this->prepare();
        if(is_null($website)) {
            return;
        }

        if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::GET) {
            $secretKey =Mage::getResourceModel("unbxd_recommendation/config")
                ->getValue($website->getWebsiteId(), Unbxd_Recommendation_Helper_Confighelper::SECRET_KEY);
            $siteKey =Mage::getResourceModel("unbxd_recommendation/config")
                ->getValue($website->getWebsiteId(), Unbxd_Recommendation_Helper_Confighelper::SITE_KEY);
            $errors = array();
            if(is_null($secretKey)) {
                $errors[Unbxd_Recommendation_Helper_Confighelper::SECRET_KEY] = "secret key not set";
            }
            if(is_null($siteKey)) {
                $errors[Unbxd_Recommendation_Helper_Confighelper::SITE_KEY] = "site key not set";
            }
            if(sizeof($errors) > 0) {
                Mage::helper('unbxd_recommendation')
                    ->log(Zend_Log::DEBUG,'save keys api failed because ' . json_encode($errors));
                $this->getResponse()->setBody(json_encode(array('success' => false, 'errors' => $errors)));
                return;
            }
            $this->getResponse()->setBody(json_encode(array('success' => true,
                Unbxd_Recommendation_Helper_Confighelper::SECRET_KEY => $secretKey,
                Unbxd_Recommendation_Helper_Confighelper::SITE_KEY => $siteKey)));
            return;
        } else if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::POST) {
            $requestBody = $request->getRawBody();
            $errors = $this->helper()->validateAndSaveKeys($website, $requestBody);
            if(sizeof($errors)>0) {
                Mage::helper('unbxd_recommendation')
                    ->log(Zend_Log::DEBUG, 'save keys api failed because of ' . json_encode($errors));
                $this->getResponse()->setBody(json_encode(array('success' => false,
                    'errors' => $errors)));
                return;
            }
            $this->getResponse()->setBody(json_encode(array('success' => true)));
        } else {
            Mage::helper('unbxd_recommendation')
                ->log(Zend_Log::DEBUG, 'keys api failed because of invalid method');
            $this->getResponse()->setBody(json_encode(array('success' => false,
                'errors' => array('message' => 'Invalid method'))));
            return;
        }
    }


    public function dimensionmapAction()
    {
        $website = $this->prepare();
        if (is_null($website)) {
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::POST) {
            $response = $this->helper()->updateFeatureFields($website);
            if(is_array($response)) {
                $this->getResponse()->setBody(json_encode(array('success' => false, $response)));
                return;
            }
            $this->getResponse()->setBody(json_encode(array('success' => true)));
            return;
        } else {
            Mage::helper('unbxd_recommendation')
                ->log(Zend_Log::DEBUG, 'keys api failed because of invalid method');
            $this->getResponse()->setBody(json_encode(array('success' => false,
                'errors' => array('message' => 'Invalid method'))));

        }
    }

    public function downloadAction() {
        $filepath = Mage::getBaseDir('log').DS.Unbxd_Recommendation_Helper_Data::LOG_FILE;

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
        $website = $this->prepare();
        if (is_null($website)) {
            return;
        }

        $this->getResponse()
            ->setBody(json_encode(array('success' => true,
                'numDocs' => $this->helper()->getNumberOfDocsInUnbxd($website))));
        return;
    }

    public function productsyncAction(){
        $website = $this->prepare();
        if (is_null($website)) {
            return;
        }
        ignore_user_abort(true);
        set_time_limit(0);
        $isFullUpload = true;
        $feedMgr = Mage::getSingleton('unbxd_recommendation/feed_feedmanager');
        if(array_key_exists('incremental', $_REQUEST)) {
            $isFullUpload = false;
        }

        $response = $feedMgr->process($isFullUpload, $website);
        $this->getResponse()->setBody(json_encode($response));
        return;
    }


    public function unlockfeedAction() {
        $website = $this->prepare();
        if (is_null($website)) {
            return;
        }
        Mage::getResourceModel('unbxd_recommendation/config')->unLockSite($website->getWebsiteId());
        $this->getResponse()->setBody(json_encode(array('success' => true)));
    }

    public function cronAction() {
        $website = $this->prepare();
        if (is_null($website)) {
            return;
        }
        $isCronEnabled = Mage::getResourceSingleton('unbxd_recommendation/config')->getValue($website->getWebsiteId(),
            Unbxd_Recommendation_Helper_Confighelper::IS_CRON_ENABLED);
        if(is_null($isCronEnabled)) {
            $this->getResponse()->setBody(json_encode(array('success' => true, 'cron_enabled' => false)));
        }
        $this->getResponse()->setBody(json_encode(array('success' => true, 'cron_enabled' => true)));
    }
}
