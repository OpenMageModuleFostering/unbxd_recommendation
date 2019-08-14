<?php

class Unbxd_Recscore_Model_Feed_Feedcreator {

    var $fileName;
    var $fields;
    var $taxonomyFlag;
    var $pageSize = 500;
    var $_fullupload;
    var $_copyFields = array();
    var $page = 0;
    var $limit = -1;


    public function __construct() {
        $this->_fullupload = true;
    }

    public function init(Mage_Core_Model_Website $website, $fileName) {
        $this->_setFields($website);
        $this->_setCopyFields($website);
        $this->fileName = $fileName;
    }

    public function setPage($page = 0) {
        $this->page = (int)$page;
        return $this;
    }

    public function setLimit($limit = 500) {
        $this->limit = (int)$limit;
        if($limit < $this->pageSize) {
            $this->pageSize = (int)$limit;
        }
        return $this;
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
    public function createFeed($fileName,  Mage_Core_Model_Website $website, $currentDate){
        $this->init($website, $fileName);
        if($this->_createFile()){
            $this->log("started writing header");

            if(!$this->_writeFeedContent($website, $currentDate)){
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
    protected  function _writeFeedContent(Mage_Core_Model_Website $website, $currentDate) {
        if(!$this->_appendTofile('{"feed":')) {
            $this->log("Error writing feed tag");
            return false;
        }

        if(!$this->_writeCatalogContent($website, $currentDate)) {
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
    protected  function _writeCatalogContent(Mage_Core_Model_Website $website, $currentDate) {
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

        $fromDate = Mage::getResourceSingleton('unbxd_recscore/config')
            ->getValue($website->getWebsiteId(), Unbxd_Recscore_Model_Config::LAST_UPLOAD_TIME);
        if(is_null($fromDate)) {
            $fromDate = "1970-01-01 00:00:00";
        }
        // If both of them are unsuccessful, then tag it as unsuccessful
        if(!($this->_writeAddProductsContent($website, $fromDate, $currentDate)
            || $this->_writeDeleteProductsContent($website, $fromDate, $currentDate))) {
            return false;
        }

        Mage::getModel('unbxd_recscore/sync')->markItSynced($website->getWebsiteId(), $currentDate);


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
            Mage::getSingleton('unbxd_recscore/feed_jsonbuilder_schemabuilder')->getSchema($this->fields));
    }

    public function getSize(Mage_Core_Model_Website $website, $fromDate, $currentDate) {
        $collection = $this->_getCatalogCollectionToAdd($website, $fromDate, $currentDate);
        return $collection->getSize();

    }

    /**
     * method to get the collection to add
     * @param Mage_Core_Model_Website $website
     * @param $currentDate
     * @return mixed
     */
    protected  function _getCatalogCollectionToAdd(Mage_Core_Model_Website $website, $fromDate, $currentDate) {
        if($this->isFullUpload()) {
            return Mage::getResourceModel('unbxd_recscore/product_collection')
                ->addFullUploadFilters($website);
        } else {
            $products = Mage::getModel('unbxd_recscore/sync')
                ->getCollection()
                ->addWebsiteFilter($website->getWebsiteId())
                ->addUnsyncFilter()
                ->addOperationFilter(Unbxd_Recscore_Model_Sync::OPERATION_ADD)
                ->load();
            $productIds = array();
            foreach($products as $product) {
                $productIds[] = $product->getProductId();
            }
            return Mage::getResourceModel('unbxd_recscore/product_collection')
                ->addIncrementalUploadFiltersToAdd($website, $fromDate, $currentDate, $productIds);
        }
    }

    protected function _getCatalogCollectionToDelete(Mage_Core_Model_Website $website) {
        $products = Mage::getModel('unbxd_recscore/sync')
            ->getCollection()
            ->addWebsiteFilter($website->getWebsiteId())
            ->addUnsyncFilter()
            ->addOperationFilter(Unbxd_Recscore_Model_Sync::OPERATION_DELETE)
            ->load();
        $collection = Mage::getResourceModel('unbxd_recscore/product_collection');
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
        $collection2 = Mage::getResourceModel('unbxd_recscore/product_collection')
            ->addIncrementalUploadFiltersToDelete($website, $fromDate, $currentDate)
            ->load();

        $collection = $collection1
            ->mergeCollection($collection2)
            ->virtuallyLoad();
        return $this->_writeProducts($website, $collection, Unbxd_Recscore_Model_Feed_Tags::DELETE, true);
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
                                          $operation = Unbxd_Recscore_Model_Feed_Tags::ADD, $loadAll = false) {
        if(!$loadAll) {
            $collection->clear();
            $collection->getSelect()->limit($this->pageSize, ($pageNum) * $this->pageSize);
            $collection->load();
        }
        return $collection;
    }

    protected function _writeProducts(Mage_Core_Model_Website $website, $collection,
                                      $operation = Unbxd_Recscore_Model_Feed_Tags::ADD, $loadAllAtOnce = false)
    {
        $pageNum = $this->page;
        $this->log('started writing products');
        $firstLoop = true;
        $totalSize = 0;
        while (true) {
            $collection = $this->_processCollection($collection, $pageNum++, $operation, $loadAllAtOnce);

            if (count($collection) == 0) {
                if ($pageNum == 1) {
                    $this->log("No products found");
                    throw new Exception("No Products found");
                }
                break;
            }

            if (!$firstLoop && $loadAllAtOnce) {
                break;
            } else if (!$firstLoop) {
                if (!$this->_appendTofile(Unbxd_Recscore_Model_Feed_Tags::COMMA)) {
                    $this->log("Error while addings items separator");
                    return false;
                }
            } else {
                // If it is the first loop adding json tag
                if (!$this->_appendTofile(
                    Mage::getSingleton('unbxd_recscore/feed_tags')->getKey($operation) .
                    Unbxd_Recscore_Model_Feed_Tags::COLON . Unbxd_Recscore_Model_Feed_Tags::OBJ_START .
                    Unbxd_Recscore_Model_Feed_Tags::DOUBLE_QUOTE .
                    Unbxd_Recscore_Model_Feed_Tags::ITEMS .
                    Unbxd_Recscore_Model_Feed_Tags::DOUBLE_QUOTE .
                    Unbxd_Recscore_Model_Feed_Tags::COLON . Unbxd_Recscore_Model_Feed_Tags::ARRAY_START)
                ) {
                    $this->log("Error while adding items tag");
                    return false;
                }
            }
            $content = Mage::getSingleton('unbxd_recscore/feed_jsonbuilder_productbuilder')
                ->getProducts($website, $collection, $this->fields, $this->_copyFields);
            if (!$this->_appendTofile($content)) {
                $this->log("Error while addings items");
                return false;
            }
            $this->log('Added ' . ($pageNum) * $this->pageSize . ' products');
            $firstLoop = false;
            if ($this->limit != -1) {
                $totalSize += $this->pageSize;
                if ($totalSize >= $this->limit) {
                    break;
                }
            }
        }
        if (!$this->_appendTofile(Unbxd_Recscore_Model_Feed_Tags::ARRAY_END .
            Unbxd_Recscore_Model_Feed_Tags::OBJ_END)
        ) {
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

        $content=Mage::getSingleton('unbxd_recscore/feed_jsonbuilder_taxonomybuilder')
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

        $content=Mage::getSingleton('unbxd_recscore/feed_jsonbuilder_taxonomybuilder')->createMappingFeed($collection);
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

    protected function _setCopyFields(Mage_Core_Model_Website $website) {
        $this->_copyFields = array();
        $copyFields = Mage::getModel('unbxd_recscore/field')->getCopyFields($website);
        foreach($copyFields as $fieldName => $copyField) {
            if(array_key_exists($copyField, $this->fields)) {
                $this->_copyFields[$fieldName] = $copyField;
            }
        }
        $this->_copyFields[Mage::getModel('unbxd_recscore/field')->getImageUrlFieldName()] = "imageUrl";
    }


    protected function _setFields(Mage_Core_Model_Website $website) {
        $fields = Mage::getResourceModel("unbxd_recscore/field_collection")->getFields($website);
        $featureFields = Mage::getModel('unbxd_recscore/field')->getFeaturedFields();
        foreach($fields as $eachfield) {
            if(array_key_exists($eachfield->getFieldName(), $featureFields)) {
                $this->fields[$eachfield->getFieldName()] = $featureFields[$eachfield->getFieldName()];
                continue;
            }
            if(!is_null($eachfield->getFeaturedField()) &&
                array_key_exists($eachfield->getFeaturedField(), $featureFields)) {
                if($eachfield->getFeaturedField() == "imageUrl") {
                    $this->fields["imageUrl"] =  $featureFields[$eachfield->getFeaturedField()];
                    $this->fields[$eachfield->getFieldName()] = Mage::getModel('unbxd_recscore/field')->getField('longText', "false", "false");
                    $this->fields["imageUrl"][Unbxd_Recscore_Model_Feed_Jsonbuilder_Productbuilder::GENERATE_IMAGE] = "1";
                } else {
                    $this->fields[$eachfield->getFieldName()] = $featureFields[$eachfield->getFeaturedField()];
                }
                continue;
            }
            $field = array();
            $field[Unbxd_Recscore_Model_Field::status] = 1;
            $field[Unbxd_Recscore_Model_Field::datatype] = $eachfield->getDatatype();
            $field[Unbxd_Recscore_Model_Field::autosuggest] = $eachfield->getAutosuggest();
            $field[Unbxd_Recscore_Model_Field::multivalued] = Mage::helper('unbxd_recscore/feedhelper')
                ->isMultiSelect($eachfield->getFieldName());
            $this->fields[$eachfield->getFieldName()] = $field;
        }
        $this->_setImageConf($website);

    }

    protected function _setImageConf(Mage_Core_Model_Website $website) {
        $imageFields = Mage::getModel('unbxd_recscore/field')->getImageFields($website);
        foreach($this->fields as $fieldName => $fieldConf) {
            if(array_key_exists($fieldName, $imageFields)) {
                $this->fields[$fieldName][Unbxd_Recscore_Model_Feed_Jsonbuilder_Productbuilder::GENERATE_IMAGE] = '1';
            }
        }
    }

    /**
     * Function to initialize to feed creation process
     */
    protected  function _createFile(){
        return Mage::getSingleton('unbxd_recscore/feed_filemanager')->createFile($this->fileName);
    }

    protected function _appendTofile($data){
        return Mage::getSingleton('unbxd_recscore/feed_filemanager')->appendTofile($this->fileName, $data);
    }

    protected function log($message) {
        Mage::helper('unbxd_recscore')->log(Zend_Log::DEBUG, $message);
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