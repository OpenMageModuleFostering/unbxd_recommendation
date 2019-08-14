<?php

class Unbxd_Datafeeder_Model_Feed_Jsonbuilder_Taxonomybuilder extends Unbxd_Datafeeder_Model_Feed_Jsonbuilder_Jsonbuilder {
	
	var $file='unbxdTaxonomy.xml';
	
	public function __construct(){
		//$this->file =  Mage::getBaseDir('tmp').DS.'unbxdTaxonomy.xml';
	}	
 	
 	/**
 	 * Function to appened the contents to the file
 	 */
 	private function appendTofile($content){

 		try{
 			
 			file_put_contents($this->file, $content, FILE_APPEND);
 			return true;
 		}catch(Exception $Ex){
 			error_log("UNBXD_MODULE:Error while appending the contents to file");
 			error_log($Ex->getMessage());
 			return false;
 		}
 	}

	private function log($content){
                try{
                        file_put_contents($this->logFile, date('Y-m-d H:i:s').$content."\n", FILE_APPEND);
                        return true;
                }catch(Exception $Ex){
                        error_log("UNBXD_MODULE:Error while appending the contents to file");
                        Mage::throwException($Ex->getMessage());
                        return false;
                }
        }
 	
 	/**
 	 * Function to delete the file
 	 */
 	private function deleteFile(){
 		unlink($this->file); 		
 	}
	
	
	private function writeJSONProductsContents($site){
		$stores= $this->getStores($site);
		foreach( $stores as $store){
			$categories=$this->getStoreCategories($store);		
			$content='';
			$count=0;
			foreach($categories as $category){
				if( $category->getName()== "" ){
					continue;
				}
				$content =$content.$this->getCategoryContent($category);
				$category_obj =  Mage::getModel('catalog/category')->load($category->getId());
				$childrens = $category_obj->getAllChildren(true);
				$childrenCategories = Mage::getModel('catalog/category')->getCollection()->addIdFilter($childrens)->addAttributeToSelect('*')->load();
		
				foreach($childrenCategories as $childCategory){
					$content=$content.$this->getCategoryContent($childCategory);
				}
			}
		}
		return $content;
	}

	private function getCategoryContent($category){
		$content=array();
        $content["nodeName"]= $category->getName();
        $content["parentNodeId"] = array((string)$category->getParentId());
        $content["nodeId"] =(string)$category->getId();
    	return json_encode($content).',';
	}
	

	
	private function getAllCategories($site){
		$collection = Mage::getModel('catalog/category')->getCollection() 
			->setStoreId($this->getStores($site))
			->addAttributeToSelect('name')
                        ->addAttributeToSelect('id');
		
		return $collection->load();
		
	}
	
	private function getStores($site){
  		$sites=Mage::app()->getWebsites();
        foreach( $sites as $eachSite){
            if(strcasecmp ( $eachSite->getName(), $site ) == 0 ){
                return $eachSite->getStores();
            }
        }
	}
	
	
	
	private function getAttributesInJSON($columnHeader,$columndata){
 		 
 		return '"'.$columnHeader.'"'.':'.'"'.$content.'"';
 	}
 	
 	private function _escapeXMLHeader($columnHeader){
 		
 		return str_replace(' ','_',$columnHeader);
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
        $content["unbxdNodeId"] = explode(",", $mapping['category_id']);
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
	  
	  	$tree->addCollectionData(null, $sorted, $parent, $toLoad, false); 
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
