<?php
class Unbxd_Datafeeder_Helper_Analyticshelper extends Mage_Core_Helper_Abstract{

	/**
	* Returns unbxd script tag
	*;
    * @return String
	**/
	public function getAnalyticsScriptTag() {
		$siteName  = Mage::getResourceSingleton("datafeeder/conf")->getValue(Mage::app()->getWebsite()->getName() . "/siteName");
		if(is_null($siteName) || $siteName == "" ) {
			Mage::throwException("Unbxd site key is not set");
		}
		$scriptDiv = '<script type="text/javascript">';
		$scriptDiv = $scriptDiv.'var UnbxdSiteName="'.$siteName.'";';
		$scriptDiv = $scriptDiv.'var s=document.createElement("script");';
		$scriptDiv = $scriptDiv.'s.src="//unbxd.s3.amazonaws.com/unbxdAnalytics.js";';
		$scriptDiv = $scriptDiv.'s.type="text/javascript";';
		$scriptDiv = $scriptDiv.'s.async=true;';
		$scriptDiv = $scriptDiv.'document.getElementsByTagName("head").item(0).appendChild(s);';
		$scriptDiv = $scriptDiv.'var s=document.createElement("script");';
		$scriptDiv = $scriptDiv."</script>";
		return $scriptDiv;
	}

	/**
	* Returns unbxd  search box attributes 
	*
    * @return String
	**/
	public function getSearchBoxAttribute() {
		return 'unbxdattr="sq"';
	}

	/**
	* Returns unbxd search box button attributes
	*
    * @return String
	**/
	public function getSearchBoxButtonAttribute() {
		return 'unbxdattr="sq_bt"';
	}

	/**
	* Returns unbxd navigation meta tag
	*
    * @return String
	**/
	public function getNavigationTag() {
		return '<meta name="unbxd:type" content="category">';
	}

	/**
	* Returns unbxd product click attributes
	*
    * @return String
	**/
	public function getProductClickAttributes($product, $rank) {
		if (!$product instanceof Mage_Catalog_Model_Product ) {
			Mage::throwException("$product parameter to getProductClickAttributes method should be of type Mage_Catalog_Model_Product");
		}
		if (!$rank || $rank < 1 ) {
			Mage::throwException("$rank parameter to getProductClickAttributes method should be greater than 0");	
		}
		return 'unbxdattr="product" unbxdparam_sku="'.$product->getData('entity_id').'"  unbxdparam_prank="'.$rank.'"';
	}

	/**
	* Returns unbxd add to cart attributes
	*
    * @return String
	**/
	public function getAddToCartAttributes($product) {
		if (!$product instanceof Mage_Catalog_Model_Product ) {
			Mage::throwException("$product parameter to getProductClickAttributes method should be of type Mage_Catalog_Model_Product");
		}
		return 'unbxdattr="AddToCart" unbxdparam_sku="'.$product->getData('entity_id').'"';
	}

	/**
	* Returns unbxd order attributes
	*
    * @return String
	**/
	public function getOrderAttributes($product) {
		if (!$product instanceof Mage_Catalog_Model_Product ) {
			Mage::throwException("$product parameter to getProductClickAttributes method should be of type Mage_Catalog_Model_Product");
		}
		return 'unbxdattr="order" unbxdparam_sku="'.$product->getData('entity_id').'"';
	}

	/**
     * Checks whether request is a navigation 
     *
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @return bool
     */
    public function isNavigation() {
        if(Mage::app()->getRequest()->getControllerName() == "category") {
            return true;
        }
        return false;
    }
}
?>