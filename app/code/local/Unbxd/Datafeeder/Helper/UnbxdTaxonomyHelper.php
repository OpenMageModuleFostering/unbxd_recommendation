<?php
class Unbxd_Datafeeder_Helper_UnbxdTaxonomyHelper{
	
	var $file='unbxdTaxonomy.xml';
	

	public function __construct(){
		$this->file =  Mage::getBaseDir('tmp').DS.'unbxdTaxonomy.xml';
	}	
	/**
 	 * Function to create a file
 	 */
 	private function createXmlFile(){
 		try{
	 		$f=fopen($this->file,'w'); 		
	 		fclose($f);
	 		return true;
 		} catch (Exception $Ex)
	    {
	    	error_log("UNBXD_MODULE:Error while creating the file");
	    	error_log($Ex->getMessage());
	    	return false;
	    }
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
		
	private function writeXMLHeaderContents(){
		$content="<feed>
			<username>unbxd</username>
			<taxonomyname>tax</taxonomyname>";
		return $this->appendTofile($content);
	}
	
	
	private function writeXMLFooterContents(){
		$content="</feed>";
		return $this->appendTofile($content);
	}
	
	
	
	private function writeXMLProductsContents($site){
		$stores= $this->getStores($site);
		foreach( $stores as $store){
			$categories=$this->getStoreCategories($store);		
			$content='';
			$count=0;
 			$content=$content.'<category>';
            $content=$content.$this->getAttributesInXML("name",$site);
            $content=$content.$this->getAttributesInXML("parents","-1");
            $content=$content.$this->getAttributesInXML("id",1);                   
            $content=$content.$this->getAttributesInXML("url","unbxd");
            $content=$content.'</category>';
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
		return $this->appendTofile($content);
	}

	private function getCategoryContent($category){
		$content='';
		$content=$content.'<category>';
        $content=$content.$this->getAttributesInXML("name",$category->getName());
        $content=$content.$this->getAttributesInXML("parents",$category->getParentId());
        $content=$content.$this->getAttributesInXML("id",$category->getId());
        $content=$content.$this->getAttributesInXML("url","unbxd");
        $content=$content.'</category>';
        $content=$content."\n";
		return $content;
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
	
	
	
	private function getAttributesInXML($columnHeader,$columndata){
 		 
		$columnHeader=$this->_escapeXMLHeader($columnHeader);
		$content=$this->_escapeXMLValue($columndata);
 		return '<'.$columnHeader.'>'.$content.'</'.$columnHeader.'>';
 	}
 	
 	private function _escapeXMLHeader($columnHeader){
 		
 		return str_replace(' ','_',$columnHeader);
 	}
 	
 	private function _escapeXMLValue($columndata){ 
 		 
 		  return strtr($columndata,array(
            "<" => "&lt;",
            ">" => "&gt;",
            '"' => "&quot;",
            "'" => "&apos;",
            "&" => "&amp;",
        			));
 	}
 	
 	
 	public function createFeed($site){ 		
 		$this->log('creating xml file');
 		if($this->createXmlFile()){
 			if(!$this->writeXmlHeaderContents()){
 				return false;
 			}
 			$this->log('writing header');
 			if(!$this->writeXMLProductsContents($site)){
 				return false;
 			}
 			$this->log('writing content');
 			if(!$this->writeXmlFooterContents()){
 				return false;
 			}
 			$this->log('writing footer');
 		} else {
 			return false;
 		}
 		return true;
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

    public function setUnbxdConf($site){

        $this->key = Mage::getResourceSingleton("datafeeder/conf")->getValue("apiKey");
        $this->feedName = Mage::getResourceSingleton("datafeeder/conf")->getValue($site."/feed");
        $this->tax = Mage::getResourceSingleton("datafeeder/conf")->getValue($site."/tax");
    	$this->logFile = Mage::getBaseDir('log').DS.substr($site,0,strrpos($site, ".")-1).'unbxdDataFeeder.log';
        $this->file = Mage::getBaseDir('tmp').DS.substr($site,0,strrpos($site, ".")-1).'unbxdTaxonomyFeed.xml';
        if(!isset($this->key) || $this->key == "" || $this->key == "empty"){
            $this->log("api key not set");
            return false;
        }
        if(!isset($this->feedName) || $this->feedName == "" || $this->feedName == "empty"){
            $this->log("Feed Name not set");
            return false;
        }
        return true;
    }
 	
 	public function indexUnbxdFeed($site){ 		
 	
 		$this->log('unbxd Datafeeder initiated');
        if($this->validateSite($site) == -1){
            $this->log("Invalid site Name".$site);
            return;
        }
        if(! $this->setUnbxdConf($site)){
            return; 
        }
		if(true){
 			$action='taxonomyStatus';
		 	$this->updateAction($action,'1');
	 		$status=$this->createFeed($site);
	 		
	 		if($status){ 		
		 		$status=$this->pushFeed();		 	
	 		}
	 		$action='taxonomyStatus';
		 	$this->updateAction($action,'0');		 	
		 	
 		}
 	}
 	
 	
 	public function pushFeed(){	
 		
 		$fields=array('file'=>'@'.$this->file.";filename=unbxdFeedRenamed.xml");
 		$url="http://feed.unbxdapi.com/taxonomy/upload/".$this->key."/".$this->tax;

 		
 		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$fields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		try{
			$response = curl_exec($ch);
		}catch(Exception $Ex){
			error_log($Ex->getMessage());
			return false;
		}
		$this->log($response);
		return true;
		
 	}
 	
 	public function checkStatus()
	{   	
		$action='taxonomyStatus';
	    $collection=Mage::getModel('datafeeder/conf')->getCollection()
	    	->addFieldToFilter('action',$action);
	    	
	    $count=0;
	    foreach($collection as $coll){
	    	$count++;
	    	$value=$coll->getvalue();
	    }
	    if($count==0){
			$collection=Mage::getModel('datafeeder/conf')
		   		->setAction($action)->setValue("0")->save();
			$value="0";
	    }
	    if($value=='0'){
	    	return true;
	    } else {
	    	return false;
	    }
	}
	 
	public function updateAction($action,$value){
	 	Mage::getResourceSingleton("datafeeder/conf")->updateAction($action, $value);	    	 
	} 	
}
?>
