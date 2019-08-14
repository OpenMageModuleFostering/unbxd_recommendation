<?php

class Unbxd_Recscore_Model_Feed_Jsonbuilder_Taxonomybuilder  {

	private function log($message){
		Mage::helper('unbxd_recscore')->log(Zend_Log::DEBUG, $message);
	}

	/**
	 * Function to delete the file
	 */
	private function deleteFile(){
		unlink($this->file);
	}


	private function writeJSONProductsContents($site){
		$stores= $this->getStores($site);
		$content='';
		$categoryIds = array();
		foreach( $stores as $store){
			$categories=$this->getStoreCategories($store);
			foreach($categories as $category){
				$category = Mage::getModel('catalog/category')->load($category->getId());
				$content.= $this->getCategoryContent($category);
				$content.= $this->getTreeCategories($category->getId(), false);
			}
		}
		return $content;
	}

	private function getTreeCategories($parentId, $isChild){
		$allCats = Mage::getModel('catalog/category')->getCollection()
			->addAttributeToSelect('*')
			->addAttributeToFilter('parent_id',array('eq' => $parentId));
		$html ='';
		$subcats = null;
		//$children = Mage::getModel('catalog/category')->getCategories(7);
		foreach ($allCats as $category)
		{
			$html .= $this->getCategoryContent($category);
			$subcats = $category->getChildren();
			if($subcats != ''){
				$html .= $this->getTreeCategories($category->getId(), true);
			}
		}
		return $html;
	}

	private function getCategoryContent($category){
		$content=array();
		$content["nodeName"]= $category->getName();
		$content["parentNodeId"] = array((string)$category->getParentId());
		$content["nodeId"] =(string)$category->getId();
		return json_encode($content).',';
	}

	private function getStores($site){
		$sites=Mage::app()->getWebsites();
		foreach( $sites as $eachSite){
			if(strcasecmp ( $eachSite->getName(), $site ) == 0 ){
				return $eachSite->getStores();
			}
		}
	}

	public function createTaxonomyFeed($site){

		$content=$this->writeJSONProductsContents($site);

		if(!$content){
			return false;
		}
		$this->log('writing content');

		return rtrim($content, ",");;
	}

	public function createMappingFeed($collection){

		$content=$this->writeJSONMappingContents($collection);

		if(!$content){

			return false;
		}

		$this->log('writing content');

		return rtrim($content, ",");

	}

	private function writeJSONMappingContents($collection)
	{
		$content='';
		foreach($collection as $mapping) {

			$content =$content.$this->getMappingContent( $mapping);
		}
		return $content;

	}



	private function getMappingContent($mapping){

		$content=array();
		$content["uniqueId"]= (string)$mapping['entity_id'];
		$content["nodeId"] = explode(",", $mapping['category_id']);
		return json_encode($content).',';
	}

	public function validateSite($site){
		$sites=Mage::app()->getWebsites();
		if( !isset($site) || $site == "") {
			return false;
		}
		foreach( $sites as $eachSite){
			if(strcasecmp ( $eachSite->getName(), $site ) == 0 ){
				return $eachSite->getWebsiteId();
			}
		}
		return -1;
	}

	public function getStoreCategories($store, $sorted=false, $asCollection=false, $toLoad=true)
	{
		$parent     = $store->getRootCategoryId();

		/**
		 * Check if parent node of the store still exists
		 */
		$category = Mage::getModel('catalog/category');
		/* @var $category Mage_Catalog_Model_Category */
		if (!$category->checkId($parent)) {
			if ($asCollection) {
				return new Varien_Data_Collection();
			}
			return array();
		}

		$recursionLevel  = max(0, (int) Mage::app()->getStore()->getConfig('catalog/navigation/max_depth'));
		$tree = Mage::getResourceModel('catalog/category_tree');
		/* @var $tree Mage_Catalog_Model_Resource_Category_Tree */
		$nodes = $tree->loadNode($parent)
			->loadChildren($recursionLevel)
			->getChildren();
		//added to fetch non flat data all the time
		$emptyResourceModel = Mage::getResourceSingleton('catalog/category_collection');
		$tree->addCollectionData($emptyResourceModel, $sorted, $parent, $toLoad, false);
		if ($asCollection) {
			return $tree->getCollection();
		}
		return $nodes;
	}



	public function updateAction($action,$value){
		Mage::getResourceSingleton("datafeeder/conf")->updateAction($action, $value);
	}
}
?>
