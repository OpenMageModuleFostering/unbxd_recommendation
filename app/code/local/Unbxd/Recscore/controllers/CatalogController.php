<?php

/**
 * @category Unbxd
 * @package Unbxd_Recscore
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recscore_CatalogController extends Mage_Core_Controller_Front_Action {

    /**
     * Recscore
     * @return Unbxd_Recscore_Helper_Confighelper
     */
    protected function _helper() {
        return Mage::helper("unbxd_recscore/confighelper");
    }

    protected function _getRawBody($request) {
        $requestBody = json_decode($request->getRawBody(), true);
        if(0 != strpos($request->getHeader('Content-Type'), 'application/json') || $requestBody === false) {
            $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(json_encode(array('success' => false,
                'errors' => array('message' => 'Invalid Request'))));
            return null;
        }
        return $requestBody;
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
            Mage::helper('unbxd_recscore')->log(Zend_Log::DEBUG,'api failed because of invalid website');
            $this->getResponse()->setBody(json_encode(array('success' => false, 'errors' => array('Invalid site'))));
            return null;
        }
        return $website;
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
            $filters = Mage::getResourceModel('unbxd_recscore/config')->getFilters($website);
            $this->getResponse()->setBody(json_encode(array('success' => true,
                'filters' => $filters)));
        } else if($_SERVER['REQUEST_METHOD'] == Zend_Http_Client::POST) {
            $request = $this->getRequest();
            $requestBody = $this->_getRawBody($request);
            $filterValues = array();
            foreach($requestBody as $key=>$value) {
                $filterValues[] = $key . Unbxd_Recscore_Model_Config::FILTER_DELIMITER . $value;
            }
            Mage    ::getResourceModel('unbxd_recscore/config')->updateValues($website->getWebsiteId(),
                Unbxd_Recscore_Model_Config::FILTER, $filterValues);
            $this->getResponse()->setBody(json_encode(array('success' => true)));
        } else {
            Mage::helper('unbxd_recscore')
                ->log(Zend_Log::DEBUG, 'keys api failed because of invalid method');
            $this->getResponse()->setBody(json_encode(array('success' => false,
                'errors' => array('message' => 'Invalid method'))));
        }
    }
}