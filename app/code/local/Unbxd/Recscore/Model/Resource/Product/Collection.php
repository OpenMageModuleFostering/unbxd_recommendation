<?php
class Unbxd_Recscore_Model_Resource_Product_Collection extends
    Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection{

	protected function _construct()
    {
    	parent::_construct();
    }

	public function isEnabledFlat()
    {
    	return false;
    }

    /**
     * Join Product Price Table | Ensuring left join happens
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function _productLimitationJoinPrice()
    {
        return $this->_productLimitationPrice(true);
    }

    /**
     * Merge colle
     * @param $collection1
     * @param $collection2
     * @return mixed
     */
    public function mergeCollection($collection) {
        foreach($collection as $product) {
            if(!array_key_exists($product->getEntityId(), $this->_items)) {
                $this->addItem($product);
            }
        }
        return $this;
    }

    /**
     * sets collection is loaded
     * @return $this
     */
    public function virtuallyLoad() {
        $this->_setIsLoaded(true);
        return $this;
    }

    public function addIncrementalUploadFiltersToAdd(Mage_Core_Model_Website $website, $fromDate,
                                                $toDate, $productIds = array()) {
        $this->_addBasicFilterToUpload($website);
        $this->addAttributeToFilter(array(
            array( 'attribute' => 'updated_at',
                    'from' => $fromDate,
                    'to' => $toDate,
                    'date' => true
            ),
            array( 'attribute' =>  'entity_id',
                'in' => $productIds
            )
        ));
        Mage::helper('unbxd_recscore')->log(Zend_Log::DEBUG, (string)$this->getSelect());
        return $this;
    }

    public function addIncrementalUploadFiltersToDelete(Mage_Core_Model_Website $website, $fromDate, $toDate) {
        $this->addAttributeToSelect('entity_id');
        $this->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
        $this->addAttributeToFilter(array(
            array( 'attribute' => 'updated_at',
                'from' => $fromDate,
                'to' => $toDate,
                'date' => true
            )
        ));
        return $this;

    }

    protected function _addBasicFilterToUpload(Mage_Core_Model_Website $website)
    {
        $adapter = Mage::getSingleton("core/resource");
	    $visiblityCondition = array('in' => array(2,3,4));
        $_catalogInventoryTable = method_exists($adapter, 'getTableName')
            ? $adapter->getTableName('cataloginventory_stock_item') : 'catalog_category_product_index';

        $this
            ->addWebsiteFilter($website->getWebsiteId())
            ->joinField("qty", $_catalogInventoryTable, 'qty', 'product_id=entity_id', null, 'left')
            ->addAttributeToSelect('*')
            ->addCategoryIds()
	    ->addAttributeToFilter('visibility',$visiblityCondition)
            ->addPriceData(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID, $website->getWebsiteId());

        if (!Mage::helper('unbxd_recscore')
            ->isConfigTrue($website, Unbxd_Recscore_Helper_Constants::INCLUDE_OUT_OF_STOCK)) {
            Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($this);
        }

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($this);
        #Mage::getSingleton('catalog/product_visibility')->addVisibleInSiteFilterToCollection($this);
        return $this;
    }


    /**
     * method to get the catalog collection
     *
     */
    public function addFullUploadFilters(Mage_Core_Model_Website $website) {
        $this->_addBasicFilterToUpload($website);
        return $this;
    }
}

?>
