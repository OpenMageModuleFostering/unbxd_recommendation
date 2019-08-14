<?php

class Unbxd_Recommendation_Model_Feed_Feedcreator {

	var $fileName;
	var $fields;
	var $taxonomyFlag;
	const PAGE_SIZE = 500;
    var $_fullupload;


    public function __construct() {
        $this->_fullupload = true;
    }

	public function init(Mage_Core_Model_Website $website, $fileName) {
        $this->_setFields($website);
        $this->fileName = $fileName;
	}

    /**
     * Method to set the full upload
     * @param bool $value
     * @return void
     */
    public function setFullUpload($value = true) {
        if($value === false) {
            $this->_fullupload = false;
        }
        return $this;
    }

    /**
     * Method to check whether is full upload or not
     * @return mixed
     */
    public function isFullUpload() {
        return $this->_fullupload;
    }

	/**
 	* method to create the feed
 	**/
 	public function createFeed($fileName,  Mage_Core_Model_Website $website, $fromDate, $currentDate){
 		$this->init($website, $fileName);
 		if($this->_createFile()){
 			$this->log("started writing header");
 			
 			if(!$this->_writeFeedContent($website, $fromDate, $currentDate)){
 				return false;
 			}
 			
 		} else {
 			return false;
 		}
     	return true;
 	}

    /**
     * Method to trigger write the feed contents
     * @param $fromdate
     * @param $todate
     * @param Mage_Core_Model_Website $website
     * @param $operation
     * @param $ids
     * @return bool
     */
    protected  function _writeFeedContent(Mage_Core_Model_Website $website, $fromDate, $currentDate) {
 		if(!$this->_appendTofile('{"feed":')) {
 			$this->log("Error writing feed tag");
 			return false;
 		}

 		if(!$this->_writeCatalogContent($website, $fromDate, $currentDate)) {
 			$this->log("Error writing catalog tag");
 			return false;
 		}

 		if(!$this->_appendTofile("}")) {
 			$this->log("Error writing closing feed tag");
 			return false;
 		}

 		return true;
 	}

    /**
     * Method to trigger only the catalog content
     * @param Mage_Core_Model_Website $website
     * @param $operation
     * @param $ids
     * @return bool
     */
    protected  function _writeCatalogContent(Mage_Core_Model_Website $website, $fromDate, $currentDate) {
 		if(!$this->_appendTofile('{"catalog":{')) {
 			$this->log("Error writing closing catalog tag");
 			return false;
 		}
 		if(!$this->_writeSchemaContent()) {
 			return false;
 		}

 		if(!$this->_appendTofile(",")) {
 			$this->log("Error while adding comma in catalog");
 			return false;
 		}

        // If both of them are unsuccessful, then tag it as unsuccessful
 		if(!($this->_writeAddProductsContent($website, $fromDate, $currentDate)
            || $this->_writeDeleteProductsContent($website, $fromDate, $currentDate))) {
 			return false;
 		}



 		if(!$this->_appendTofile("}")) {
 			$this->log("Error writing closing catalog tag");
 			return false;
 		}
        /*
 		if(!$this->_writeTaxonomyContents($site)) {
 			return false;
 		}*/

 		if(!$this->_appendTofile("}")) {
 			$this->log("Error writing closing feed tag");
 			return false;
 		}

 		return true;
 	}

    /**
     * Method to trigger to write the schema content
     * @return mixed
     */
    protected  function _writeSchemaContent() {
 		return $this->_appendTofile('"schema":'.
            Mage::getSingleton('unbxd_recommendation/feed_jsonbuilder_schemabuilder')->getSchema($this->fields));
 	}

    /**
     * method to get the collection to add
     * @param Mage_Core_Model_Website $website
     * @param $currentDate
     * @return mixed
     */
    protected  function _getCatalogCollectionToAdd(Mage_Core_Model_Website $website, $fromDate, $currentDate) {
        if($this->isFullUpload()) {
            return Mage::getResourceModel('unbxd_recommendation/product_collection')
                ->addFullUploadFilters($website);
        } else {
            $products = Mage::getModel('unbxd_recommendation/sync')
                ->getCollection()
                ->addWebsiteFilter($website->getWebsiteId())
                ->addUnsyncFilter()
                ->addOperationFilter(Unbxd_Recommendation_Model_Sync::OPERATION_ADD)
                ->load();
            $productIds = array();
            foreach($products as $product) {
                $productIds[] = $product->getProductId();
            }
            return Mage::getResourceModel('unbxd_recommendation/product_collection')
                ->addIncrementalUploadFiltersToAdd($website, $fromDate, $currentDate, $productIds);
        }
    }

    protected function _getCatalogCollectionToDelete(Mage_Core_Model_Website $website) {
        $products = Mage::getModel('unbxd_recommendation/sync')
            ->getCollection()
            ->addWebsiteFilter($website->getWebsiteId())
            ->addUnsyncFilter()
            ->addOperationFilter(Unbxd_Recommendation_Model_Sync::OPERATION_DELETE)
            ->load();
        $collection = Mage::getResourceModel('unbxd_recommendation/product_collection');
        foreach($products as $eachProduct) {
            $product = new Mage_Catalog_Model_Product();
            $product->setEntityId($eachProduct->getProductId());
            $collection->addItem($product);
        }
        return $collection;
    }

    protected function _writeDeleteProductsContent(Mage_Core_Model_Website $website, $fromDate, $currentDate) {
        if($this->isFullUpload()) {
           return true;
        }
        $collection1 = $this->_getCatalogCollectionToDelete($website);
        $collection2 = Mage::getResourceModel('unbxd_recommendation/product_collection')
            ->addIncrementalUploadFiltersToDelete($website, $fromDate, $currentDate)
            ->load();

        $collection = $collection1
            ->mergeCollection($collection2)
            ->virtuallyLoad();
        return $this->_writeProducts($website, $collection, Unbxd_Recommendation_Model_Feed_Tags::DELETE, true);
    }



    /**
     * Method to trigger to write the products
     * @param Mage_Core_Model_Website $website
     * @param $operation
     * @param $ids
     * @return bool
     */
    protected function _writeAddProductsContent(Mage_Core_Model_Website $website, $fromDate, $currentDate) {
        $collection = $this->_getCatalogCollectionToAdd($website, $fromDate, $currentDate);
        return $this->_writeProducts($website, $collection);

 	}

    /**
     * Method to process the collection
     * @param $collection
     * @param $pageNum
     * @param string $operation
     * @param bool $loadAll
     * @return mixed
     */
     protected function _processCollection($collection , $pageNum,
                                       $operation = Unbxd_Recommendation_Model_Feed_Tags::ADD, $loadAll = false) {
        if(!$loadAll) {
            $collection->clear();
            $collection->getSelect()->limit(self::PAGE_SIZE, ($pageNum) * self::PAGE_SIZE);
            $collection->load();
        }
        if($operation == Unbxd_Recommendation_Model_Feed_Tags::ADD) {
            Mage::getModel('cataloginventory/stock_status')->addStockStatusToProducts($collection);
        }
        return $collection;
    }

    protected function _writeProducts(Mage_Core_Model_Website $website, $collection,
                                      $operation = Unbxd_Recommendation_Model_Feed_Tags::ADD, $loadAllAtOnce = false) {
        $pageNum = 0;
        $this->log('started writing products');
        $firstLoop = true;
        while(true){
            $collection = $this->_processCollection($collection, $pageNum++ , $operation, $loadAllAtOnce);

            if(count($collection) == 0){
                if($pageNum == 1){
                    $this->log("No products found");
                    throw new Exception("No Products found");
                }
                break;
            }

            if(!$firstLoop && $loadAllAtOnce) {
                break;
            } else if(!$firstLoop) {
                if(!$this->_appendTofile(Unbxd_Recommendation_Model_Feed_Tags::COMMA)) {
                    $this->log("Error while addings items separator");
                    return false;
                }
            } else {
                // If it is the first loop adding json tag
                if(!$this->_appendTofile(Mage::getSingleton('unbxd_recommendation/feed_tags')->getKey($operation) .
                    Unbxd_Recommendation_Model_Feed_Tags::COLON. Unbxd_Recommendation_Model_Feed_Tags::OBJ_START.
                    Mage::getSingleton('unbxd_recommendation/feed_tags')->getKey($operation) .
                    Unbxd_Recommendation_Model_Feed_Tags::COLON.Unbxd_Recommendation_Model_Feed_Tags::ARRAY_START)) {
                    $this->log("Error while adding items tag");
                    return false;
                }
            }
            $content = Mage::getSingleton('unbxd_recommendation/feed_jsonbuilder_productbuilder')
                ->getProducts($website, $collection, $this->fields);
            if(!$this->_appendTofile($content)){
                $this->log("Error while addings items");
                return false;
            }
            $this->log('Added '.($pageNum) * self::PAGE_SIZE.' products');
            $firstLoop = false;
        }
        if(!$this->_appendTofile(Unbxd_Recommendation_Model_Feed_Tags::ARRAY_END .
            Unbxd_Recommendation_Model_Feed_Tags::OBJ_END)) {
            $this->log("Error writing closing items tag");
            return false;
        }
        $this->log('Added all products');
        return true;
    }

 	protected  function _writeTaxonomyContents($site){

 		$collection=$this->getTaxonomyMappingCollection();
	    // get total size
 		//set the time limit to infinite
 		ignore_user_abort(true);
 		set_time_limit(0);
		$pageNum = 0;	
		$this->log('started writing taxonomy tree');

		if(!$this->_appendTofile(',"'. 'taxonomy' . '":{ "tree":[')) {
			$this->log("Error while adding tree tag");
 			return false;
		}

        $content=Mage::getSingleton('unbxd_recommendation/feed_jsonbuilder_taxonomybuilder')
            ->createTaxonomyFeed($site);
        $status=$this->_appendTofile($content);

        if(!$status){
            $this->log("Error while addings taxonomy");
            return false;
        }

        if(!$this->_appendTofile("]")) {
            $this->log("Error writing closing tree tag");
            return false;
 		}

 		if(!$this->_appendTofile(',"mapping":[')) {
 			$this->log("Error writing opening mapping tag");
 			return false;
 		}

        $content=Mage::getSingleton('unbxd_recommendation/feed_jsonbuilder_taxonomybuilder')->createMappingFeed($collection);
        $status=$this->_appendTofile($content);

        if(!$status){
            $this->log("Error while addings taxonomy");
            return false;
        }

        if(!$this->appendTofile(']}')) {
 			$this->log("Error writing closing mapping tag");
 			return false;
 		}
        $this->log('Added all categories');
 		return true;
 	}


 	protected function _setFields(Mage_Core_Model_Website $website) {
        $fields = Mage::getResourceModel("unbxd_recommendation/field_collection")->getFields($website);
        $featureFields = Mage::getModel('unbxd_recommendation/field')->getFeaturedFields();
        foreach($fields as $eachfield) {
            if(array_key_exists($eachfield->getFieldName(), $featureFields)) {
                $this->fields[$eachfield->getFieldName()] = $featureFields[$eachfield->getFieldName()];
                continue;
            }
            if(!is_null($eachfield->getFeaturedField()) &&
                array_key_exists($eachfield->getFeaturedField(), $featureFields)) {
                $this->fields[$eachfield->getFieldName()] = $featureFields[$eachfield->getFeaturedField()];
                continue;
            }
            $field = array();
            $field[Unbxd_Recommendation_Model_Field::status] = 1;
            $field[Unbxd_Recommendation_Model_Field::datatype] = $eachfield->getDatatype();
            $field[Unbxd_Recommendation_Model_Field::autosuggest] = $eachfield->getAutosuggest();
            $field[Unbxd_Recommendation_Model_Field::multivalued] = Mage::helper('unbxd_recommendation/feedhelper')
                ->isMultiSelect($eachfield->getFieldName());
            $this->fields[$eachfield->getFieldName()] = $field;
        }
		$this->fields["entity_id"] = Mage::getModel('unbxd_recommendation/field')->getField('longText', "true", "true");

	}

 	/**
 	 * Function to initialize to feed creation process
 	 */
 	protected  function _createFile(){
 		return Mage::getSingleton('unbxd_recommendation/feed_filemanager')->createFile($this->fileName);
 	}

    protected function _appendTofile($data){
 		return Mage::getSingleton('unbxd_recommendation/feed_filemanager')->appendTofile($this->fileName, $data);
 	}

    protected function log($message) {
		Mage::helper('unbxd_recommendation')->log(Zend_Log::DEBUG, $message);
	}

	public function getTaxonomyMappingCollection() {
		try{
            $adapter = Mage::getSingleton('core/resource')->getConnection('core_read');
            return $adapter->query("select catalog_category_product_index.product_id as entity_id,GROUP_CONCAT(catalog_category_product_index.category_id SEPARATOR ',') as category_id FROM catalog_category_product_index
                join catalog_product_entity where catalog_category_product_index.product_id = catalog_product_entity.entity_id
                group by catalog_category_product_index.product_id");
        } catch(Exception $e) {
 			$this->log($e->getMessage());
 		}	

        
 	}

}
?>
