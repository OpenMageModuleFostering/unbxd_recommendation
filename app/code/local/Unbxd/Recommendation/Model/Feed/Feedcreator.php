<?php

class Unbxd_Recommendation_Model_Feed_Feedcreator {

	var $fileName;

	var $fields;
	var $taxonomyFlag;
	const PAGE_SIZE = 500;

	public function init(Mage_Core_Model_Website $website, $fileName) {
        $this->setFields($website);
        $this->fileName = $fileName;
	}


	/**
 	* method to create the feed
 	**/
 	public function createFeed($fileName, $fromdate, $todate, Mage_Core_Model_Website $website, $operation, $ids){
 		$this->init($website, $fileName);
 		if($this->createFile()){
 			$this->log("started writing header");
 			
 			if(!$this->writeFeedContent($fromdate,$todate,$website,$operation,$ids)){
 				return false;
 			}
 			
 		} else {
 			return false;
 		}
 		return true;
 	}

 	private function writeFeedContent($fromdate,$todate,Mage_Core_Model_Website $website,$operation,$ids) {
 		if(!$this->appendTofile('{"feed":')) {
 			$this->log("Error writing feed tag");
 			return false;
 		}

 		if(!$this->writeCatalogContent($fromdate,$todate,$website,$operation,$ids)) {
 			$this->log("Error writing catalog tag");
 			return false;
 		}

 		if(!$this->appendTofile("}")) {
 			$this->log("Error writing closing feed tag");
 			return false;
 		}

 		return true;
 	}

 	private function writeCatalogContent($fromdate,$todate,Mage_Core_Model_Website $website,$operation,$ids) {
 		if(!$this->appendTofile('{"catalog":{')) {
 			$this->log("Error writing closing catalog tag");
 			return false;
 		}
 		if(!$this->writeSchemaContent()) {
 			return false;
 		}

 		if(!$this->appendTofile(",")) {
 			$this->log("Error while adding comma in catalog");
 			return false;
 		}

 		if(!$this->writeProductsContent($fromdate,$todate,$website,$operation,$ids)) {
 			return false;
 		}


 		if(!$this->appendTofile("}")) {
 			$this->log("Error writing closing catalog tag");
 			return false;
 		}
        /*
 		if(!$this->writeTaxonomyContents($site)) {
 			return false;
 		}*/

 		if(!$this->appendTofile("}")) {
 			$this->log("Error writing closing feed tag");
 			return false;
 		}


 		return true;
 	}

 	private function writeSchemaContent() {
 		return $this->appendTofile('"schema":'.
            Mage::getSingleton('unbxd_recommendation/feed_jsonbuilder_schemabuilder')->getSchema($this->fields));
 	}

 	private function writeProductsContent($fromdate,$todate,Mage_Core_Model_Website $website,$operation,$ids) {
 		
 		$collection=$this->getCatalogCollection($fromdate,$todate,$website,$operation,$ids);
	    // get total size
 		//set the time limit to infinite
 		ignore_user_abort(true);
 		set_time_limit(0);
		$pageNum = 0;	
		$this->log('started writing products');

		if(!$this->appendTofile('"'. $operation . '":{ "items":[')) {
			$this->log("Error while adding items tag");
 			return false;
		}

		$firstLoop = true;

		while(true){	
			$collection->clear();
			$collection->getSelect()->limit(self::PAGE_SIZE, ($pageNum++) * self::PAGE_SIZE);
			$collection->load();
			if(count($collection) == 0){
				if($pageNum == 1){
					$this->log("No products found");
					return false;
				}
				break;
			}

			if(!$firstLoop) {
				if(!$this->appendTofile( ',')) {
					$this->log("Error while addings items separator");
	 				return false;
				}
			}
 			$content = Mage::getSingleton('unbxd_recommendation/feed_jsonbuilder_productbuilder')
                ->getProducts($website, $collection, $this->fields);
			$status=$this->appendTofile($content);
    		if(!$status){
    			$this->log("Error while addings items");
    			return false;
    		}
	    	$this->log('Added '.($pageNum) * self::PAGE_SIZE.' products');
	    	$firstLoop = false;
 		}

 		if(!$this->appendTofile("]}")) {
 			$this->log("Error writing closing items tag");
 			return false;
 		}

		
 		$this->log('Added all products');
 		return true;
 	}

 	private function writeTaxonomyContents($site){

 		$collection=$this->getTaxonomyMappingCollection();
	    // get total size
 		//set the time limit to infinite
 		ignore_user_abort(true);
 		set_time_limit(0);
		$pageNum = 0;	
		$this->log('started writing taxonomy tree');

		if(!$this->appendTofile(',"'. 'taxonomy' . '":{ "tree":[')) {
			$this->log("Error while adding tree tag");
 			return false;
		}

 			$content=Mage::getSingleton('unbxd_recommendation/feed_jsonbuilder_taxonomybuilder')->createTaxonomyFeed($site);
			$status=$this->appendTofile($content);

			if(!$status){
    			$this->log("Error while addings taxonomy");
    			return false;
    		}

    		if(!$this->appendTofile("]")) {
 			$this->log("Error writing closing tree tag");
 			return false;
 		}

 			if(!$this->appendTofile(',"mapping":[')) {
 			$this->log("Error writing opening mapping tag");
 			return false;
 		}

    		$content=Mage::getSingleton('unbxd_recommendation/feed_jsonbuilder_taxonomybuilder')->createMappingFeed($collection);
    		$status=$this->appendTofile($content);

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




 	private function setFields(Mage_Core_Model_Website $website) {
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
 	* method to get the catalog collection
 	* 
 	*/
 	public function getCatalogCollection($fromdate,$todate,Mage_Core_Model_Website $website,$operation,$ids) {
        if ($operation == "add") {
            $_catalogInventoryTable = Mage::getSingleton("core/resource")->getTableName("cataloginventory_stock_item");
            $collection = Mage::getResourceModel('unbxd_recommendation/product_collection')
                ->addWebsiteFilter($website->getWebsiteId())
                ->joinField("qty", $_catalogInventoryTable, 'qty', 'product_id=entity_id', null, 'left')
                ->addAttributeToSelect('*')
                ->addPriceData(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID, $website->getWebsiteId());

            Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
            Mage::getSingleton('catalog/product_visibility')->addVisibleInSiteFilterToCollection($collection);

            if(sizeof($ids) > 0){
                $condition = array('in' => $ids);
                $collection=$collection->addAttributeToFilter('entity_id',$condition);
            }
		} else {
            $collection = Mage::getResourceModel('catalog/product_collection');
            if(sizeof($ids) > 0) {
                $condition = array('in' => $ids);
                $collection= $collection->addAttributeToFilter('entity_id',$condition)->addAttributeToSelect('entity_id');
            }
        }

        $this->log((string)$collection->getSelect());
        return $collection;
 	}

 	/**
 	 * Function to initialize to feed creation process
 	 */
 	protected  function createFile(){
 		return Mage::getSingleton('unbxd_recommendation/feed_filemanager')->createFile($this->fileName);
 	}

    protected function appendTofile($data){
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
