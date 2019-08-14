<?php
/*
 * Created on 14-May-2013
 *
 * @author antz(ananthesh@unbxd.com)
 */
 
 class Unbxd_Datafeeder_Helper_UnbxdIndexingHelper {
 	// This is like to act as a temporary cache, which holds the fieldName to fieldType information
 	// so that just to avoid multiple database reads, and make it faster
	var $fieldType = array();
	// This is the name of the logFile which it is writing in to//
	var $logFile;
	// This is also act like a temporary cache, which holds the category id to category information,
	// so that just to avoid multiple database reads, and make it faster
	var $categoryMap = array();
	// size to fetch how much products should it pick in batches
	var $PAGE_SIZE = 500;
	// the file to write to..
	var $file;
	// fields to be selected to push to unbxd
	var $fields;
	// Feed unlock interval
	//LOCK_TIMEOUT = 60 * 30;

	public function __construct(){
		$this->logFile = Mage::getBaseDir('log').DS.'generic.log';
		$this->file =  Mage::getBaseDir('tmp').DS.'unbxdFeed.xml';
		$this-log("calling setfeilds method");
		$this->fields = array();
		$this-> setFieldType();
	}

	public function setFields($site) {
		$this->fields = Mage::getResourceSingleton('datafeeder/field')->getEnabledFields($site);
		$this-log("inside setfeilds method");
		$this->fields[] = 'entity_id';
		$this->log("fields are " .json_encode($this->fields));
	}

 	/**
 	 * Function to create a file
 	 */
 	private function createXmlFile(){
 		try{
	 		$f=fopen($this->file,'w'); 
	 		
	 		fclose($f);
	 		if(!file_exists($this->file)) {
	 			$this->log("UNBXD_MODULE:Couldn't create the file");
	 			return false;
	 		}
	 		$this->log("UNBXD_MODULE: created the file");
	 		return true;
 		} catch (Exception $ex) {
	    	$this->log("UNBXD_MODULE:Error while creating the file");
	    	$this->log($ex->getMessage());
	    	return false;
	    }
 	}
 	
 	/**
 	 * Function to append the contents to the file
 	 */
 	private function appendTofile($content){
 		try{
 			
 			if(file_put_contents($this->file, $content, FILE_APPEND)) {
 				return true;
 			} else {
 				return false;
 			}
 		} catch(Exception $ex) {
 			$this->log("UNBXD_MODULE:Error while appending the contents to feed file");
 			$this->log($ex->getMessage());
 			return false;
 		}
 	}
 	
 	
 	/**
 	 * Function to create a file
 	 */
 	private function createLogFile(){
 		try {
	 		$f=fopen($this->logFile,'w'); 		
	 		fclose($f);
	 		return true;
 		} catch (Exception $ex) {
	    	error_log("UNBXD_MODULE:Error while creating the file");
	    	error_log($ex->getMessage());
	    	return false;
	    }
 	}

 	/**
 	 * Function to appened the contents to the file
 	 */
 	public function log($content){
 		try{
 			$resp = file_put_contents($this->logFile, date('Y-m-d H:i:s').$content."\n", FILE_APPEND);
 			if($resp){
 				return true;
 			} else {
 				error_log("UNBXD_MODULE:Error while appending the contents to log file");
 				return false;
 			}
 			return true;
 		}catch(Exception $ex) {
 			error_log("UNBXD_MODULE:Error while appending the contents to log file");
 			Mage::throwException($ex->getMessage());
 			return false;
 		}
 	}
 	
 	/**
 	 * Function to delete the file
 	 */
 	private function deleteFile(){
 		unlink($this->file); 		
 	}
 	
 	/**
 	 * function to get the file name	
 	 */
 	private function getFile(){
 		return $this->file;
 	} 
 	
 	/**
 	 * Write Header contents to the file
 	 * 
 	 */ 	
 	private function writeXmlHeaderContents($operation){
 		$headerContent="<feed>"."<taxonomyname>".$this->tax.
				"</taxonomyname><username>unbxd</username>".
				"<feedname>feed</feedname>".
				"<entry>"."<data>" .
				"<unbxdActionType>".$operation.
				"</unbxdActionType>"."<products>";
 		
 		return $this->appendTofile($headerContent);
 		  		
 	}
 	
 	/**
 	 * function to append the xml footer contents to the file
 	 * 
 	 */
 	private function writeXmlFooterContents(){
 		$footerContent="</products></data></entry></feed>";
 		return $this->appendTofile($footerContent);
 	}
 	
 	/**
 	 * function to append the product contents to the file
 	 */
 	private function writeXmlProductContents($fromdate,$todate,$site,$operation,$ids){
	    
	   
 		$collection=$this->getCatalogCollection($fromdate,$todate,$site,$operation,$ids);
	    // get total size
 		//set the time limit to infinite
 		set_time_limit(0);
		$pageNum = 0;	
		$this->log('started writing products');

		while(true){	
			$collection->clear();
			$collection->getSelect()->limit($this->PAGE_SIZE, ($pageNum++) * $this->PAGE_SIZE);
			$collection->load();
			echo "<pre>";print_r($collection);echo "</pre>";
			if(count($collection) == 0){
				if($pageNum == 1){
					$this->log("No products found");
					return false;
				}
				break;
			}
 			$content=$this->getCollectionInXML($collection, $operation);
			$status=$this->appendTofile($content);
	    		if(!$status){	    		
	    			return false;
	    		}
	    	$this->log('Added '.($pageNum) * $pageSize.' products');
 		}
		
 		$this->log('Added all products');
 		return true;
 	}
 	
 	/**
 	** This method iterates over the collection and builds the xml for product object
 	**
 	**/
 	private function getCollectionInXML($collection, $operation){
 		
 		$content='';
 		$count=0;
 		foreach($collection as $product){
 			$count++;
 			$content=$content.'<product>';
 			$content=$content.$this->getProductInXML($product, $operation);
 			$content=$content.'</product>';
 		}
 		
 		return $content;
 	}
 	
 	/**
 	** This method builds xml string, given the product object
 	**
 	**/
 	private function getProductInXML($product, $operation){ 		
 		$content='';
		if($operation != "add"){
			return $this->getAttributesInXML('uniqueId',$product->getData('entity_id'));
		}
			
 		foreach($product->getData('') as $columnHeader=>$columndata){
			
			if(!in_array($columnHeader, $this->fields)) {
				continue;
			}
 			if(is_null($columndata)|| !isset($columndata) || $columndata==""){
 				continue;
			}
 			if($columnHeader=="entity_id"){ 				
 				$content=$content. $this->getAttributesInXML('uniqueId',$columndata);
 			}
 			if($columnHeader=="name"){
                $content=$content. $this->getAttributesInXML('productname',$columndata);
            }
			if($columnHeader=="small_image"){
                $content=$content. $this->getAttributesInXML('image_url',$columndata);
            }
			
			if($columnHeader=="entity_id"){
                $content=$content. $this->getAttributesInXML('uniqueId',$columndata);
            }
            if($columnHeader == "visibility") {
            	$content = $content.$this->getAttributesInXML('unbxdVisibility', $columndata);
            }
            if($columnHeader=="url_path"){
                $content=$content. $this->getAttributesInXML('url', Mage::getUrl('').$columndata);
                $content=$content. $this->getAttributesInXML('url_path', Mage::getUrl('').$columndata);
            } else if( $this->isMultiSelect($columnHeader) && $columnHeader != "status"){
				$data = explode(",", $columndata);			
				$attributeModel = Mage::getResourceSingleton("datafeeder/attribute");
				foreach( $data as $eachdata){
					$attributeValue = $attributeModel ->getAttributeValue($columnHeader, trim($eachdata), $product);
 		      		$value = $value .$this->getAttributesInXML($columnHeader,$attributeModel ->getAttributeValue($columnHeader, trim($eachdata), $product));
					if(isset($this->filterable[$columnHeader]) && ($this->filterable[$columnHeader] == 1) && isset($attributeValue) && $attributeValue != ""){
						$value = $value.$this->getAttributesInXML($columnHeader."_id", trim($eachdata));
					}	
				}
			} else if($columnHeader == "created_at" ||$columnHeader == "updated_at") {
				$tokens = explode(" ",$columndata);
				$columndata =$tokens[0].'T'.$tokens[1].'Z';
				$value =$this->getAttributesInXML($columnHeader,$columndata);
			} else if($columnHeader == "category_id"){
				if(!isset($columndata)){
					continue;
				}
				$categoryIds = explode(",",$columndata);
				foreach($categoryIds as $categoryId){
					$value = $value.$this->getAttributesInXML($columnHeader, trim($categoryId));
                                        $value = $value.$this->getAttributesInXML("unbxdTaxonomyId",trim($categoryId));
				        $value = $value.$this->getAttributesInXML("category",$this->getCategoryName(trim($categoryId)));
				}
				
			} else if (is_array($columndata)){
 				$value = $this->getArrayAttributesInXML($columnHeader,$columndata);
 			} else if ($columndata instanceof Varien_Object){ 				
 				$value = $this->getArrayAttributesInXML($columnHeader,$columndata->getData());
 			} else if ( !isset($value) || $value == ""){
				$value =$this->getAttributesInXML($columnHeader, $columndata);
			}
            $content = $content.$value; 
			$value = "";
 		}
 		
 		$content=$content.$this->getCategoryAttribute($product); 		
 		$content=$content.$this->getAttributesInXML('store','default');
 		return $content;
 	}

 	/*
 	* function to check whether the field is a multiSelect/select or not, 
 	* This is optimized method, where it doesn't make a database call to get fieldType 
 	* where it fetches from the local variable, which holds the information of field to fieldType mapping
 	*/
    public function isMultiSelect($attributeName = ""){
		if($this->getFieldType($attributeName) == "select" || $this->getFieldType($attributeName) == "multiselect" || $attributeName == "categoryIds"){
			return true;
		}
		return false;
    }


    public function isImage($attributeName = "") {
    	if($this->getFieldType($attributeName) == "media_image") {
    		return true;
    	}
    	return false;
    }
	
 	/**
 	* function to get Category from the category id, 
 	* This checks it present in the global array 'categoryMap', if it is not there fetches from db
 	* So that once it gets one category, it doesn't make db call again for the same category
 	*/
    public function getCategory($category_id = ""){
		if(!isset($this->categoryMap[$category_id])){
			$category = Mage::getModel('catalog/category')->load($category_id);
			$this->categoryMap[$category_id] = $category;
			return $this->categoryMap[$category_id];
		}
		return $this->categoryMap[$category_id];
    }
 	
 	/**
 	* Method to get stock attribute in xml given the product 
 	*/
 	private function getStockAttribute($product){
		$model = Mage::getModel('catalog/product'); 
		$_product = $model->load($product->getId()); 
		$stocklevel = Mage::getModel('cataloginventory/stock_item')
					->loadByProduct($product)->getQty();
		
		$content=$this->getAttributesInXML("stock",$stocklevel);
		
		if($stocklevel > 0){
			$content=$content.$this->getAttributesInXML("Instock","1");
		}
		else{
			$content=$content.$this->getAttributesInXML("Instock","0");
		}
		return $content;
	}
 	
 	/**
 	* method to get category content in xml given the product object
 	*/
 	private function getCategoryAttribute($product){
 		$cats = $product->getCategoryIds();
 		$categoryId=array();
 		$category=array();
		$content = "";
		foreach ($cats as $category_id) {
			
    			$_cat = $this->getCategory($category_id);
    			$categoryId[]=$category_id;
    			$category[]=$_cat->getName();
		} 
		
		$content=$content.$this->getArrayAttributesInXML("categoryIds",$categoryId);
		$content=$content.$this->getArrayAttributesInXML("unbxdTaxonomyId",$categoryId);
		$content=$content.$this->getArrayAttributesInXML("category",$category);
		return $content;
 	}
 	
 	/**
 	* method to get the xml content given the fieldname and array
 	*/
 	private function getArrayAttributesInXML($columnHeader,$columndata){
 		$content='';
 		
 		foreach($columndata as $element){
 			$content=$content.$this->getAttributesInXML($columnHeader,$element);
 		} 		
 		
 		return $content;
 	}
 	
 	/**
 	* method to get the xml content given the fieldname and value
 	*/
 	private function getAttributesInXML($columnHeader,$columndata){
 		 
		$columnHeader=$this->_escapeXMLHeader($columnHeader);
		$content = $this->_escapeXMLValue($columndata);
		$content = iconv("ISO-8859-1", "UTF-8", $content);
		if($content != '' && $columnHeader != ''){ 		
 			return '<'.$columnHeader.'>'.$content.'</'.$columnHeader.'>';
		}else{
			return '';
		}
 	}
 	
 	/**
 	* method to escape the xml header contents
 	*/ 
 	private function _escapeXMLHeader($columnHeader){
 		if(is_numeric(substr($columnHeader,0,1))){
			$columnHeader ='_'.$columnHeader;
		}
 		return str_replace(' ','_',$columnHeader);
 	}

 	/**
 	* method to escape the xml contents
 	*/
 	private function _escapeXMLValue($str){
 		$encryptedString = "";
		$strlen = strlen( $str );
		for( $i = 0; $i <= $strlen; $i++ ) {
			$char = substr( $str, $i, 1 );
			if(ord($char) == 11){
				continue;
			}
			if((ord($char) < 32) && (ord($char) > 0) && (ord($char) != 9) && (ord($char) != 10) && (ord($char) != 13)){
				$controlCharacter = true;
			} else {
				$controlCharacter = false;
			}
			$unicodeButNotAscii = (ord($char) > 126) ? true: false;
			if ( ($char == "<") || ($char == "&") || ($char == ">") || ($char == '"') || ($char == "'")) {
				$characterWithSpecialMeaningInXML =true;
			} else {
				$characterWithSpecialMeaningInXML =false;
			}
			if ($controlCharacter || $characterWithSpecialMeaningInXML || $unicodeButNotAscii) {
				$encryptedString = $encryptedString . "&#" . (ord($char)) . ";" ;
			} else {
				$encryptedString = $encryptedString.$char;
			}
		} 
		return $encryptedString;
 	}
 	
 	/**
 	* method to get the catalog collection
 	* 
 	*/
 	private function getCatalogCollection($fromdate,$todate,$site,$operation,$ids) {
 		try{
		    if ($operation == "add") {
		    	// select all the attributes
				$website =Mage::getModel("core/website")->setName($site);
				$visiblityCondition = array('in' => array(4));

				$collection = Mage::getResourceModel('catalog/product_collection')
							->addWebsiteFilter($this->validateSite($site))
							->addAttributeToFilter('status',1)
							->joinField("qty", "cataloginventory_stock_item", 'qty', 'product_id=entity_id', null, 'left')
							->addAttributeToSelect('*')
							->addAttributeToFilter('visibility',$visiblityCondition);

				if(sizeof($ids) > 0){
					$condition = array('in' => $ids);
					$collection=$collection->addAttributeToFilter('entity_id',$condition);
				}
		   } else {
				$collection = Mage::getResourceModel('catalog/product_collection');
				if(sizeof($ids) > 0) {
                    $condition = array('in' => $ids);
                    $collection = $collection->addAttributeToFilter('entity_id',$condition)->addAttributeToSelect('entity_id');
                }
            }

			$this->log($collection->getSelect());
			return $collection;
 		} catch(Exception $e) {
 			$this->log($e->getMessage());
 		}					
 	}

 	/**
 	* method to get all the attributes
 	**/
 	public function getAttributes(){
 			return Mage::getSingleton('eav/config')
			 ->getEntityType(Mage_Catalog_Model_Product::ENTITY)->getAttributeCollection();
 	}

 	/**
 	* method to get field type of the field
 	**/
	private function getFieldType($attributeName){
		if(array_key_exists( $attributeName, $this->fieldType)){
			return $this->fieldType[$attributeName]; 
		} else {
			return "text";
		}
	}
	
	/**
 	* method to set field type to the global object
 	**/
	private function setFieldType(){
		$attributes = $this->getAttributes();
		foreach($attributes as $attribute){
			$this->filterable[$attribute->getAttributeCode()] = $attribute->getData('is_filterable');
			$this->fieldType[$attribute->getAttributeCode()] = $attribute-> getFrontendInput();
		}
	}
 	
 	/**
 	* method to get the zend date condition
 	*/
 	private function getDateCondition($fromdate,$todate){
 		return array('from'=>$fromdate,'to'=>$todate,'date'=>true,);
 	}
 	
 	/**
 	* method to create the feed
 	**/
 	public function createFeed($fromdate,$todate,$site,$operation,$ids){		
 		if($this->createXmlFile()){
 			$this->log("started writing header");
 			if(!$this->writeXmlHeaderContents($operation)){
 				return false;
 			}
 			$this->log('Added header contents');
 			try{ 
	 			if(!$this->writeXmlProductContents($fromdate,$todate,$site,$operation,$ids)){
	 				return false;
	 			}
 			}catch(Exception $e){
				$this->log($e->getMessage());
				$this->log($e->getTraceAsString());
				return false; 				
 			}
 			$this->log('Added products'); 	
 					
 			if(!$this->writeXmlFooterContents()){
 				return false;
 			}
 		} else {
 			return false;
 		}
 		return true;
 	}
 	
 	/**
	* method to initiate task after index
	**/
 	private function _afterIndex(){
 		$this->deleteFile();
 	}
	
	/**
	* method to validate whether the site exists or not
	**/
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

	/**
	* method to set the feedName, log, apikey based on site Name
	**/
	public function setUnbxdConf($site)
	{
		$this->key = Mage::getResourceSingleton("datafeeder/conf")->getValue("apiKey");
		$this->feedName = Mage::getResourceSingleton("datafeeder/conf")->getValue($site."/feed");
		$this->tax = Mage::getResourceSingleton("datafeeder/conf")->getValue($site."/tax");
      	$this->logFile = Mage::getBaseDir('log').DS.substr($site,0,strrpos($site, ".")-1).'unbxdDataFeeder.log';
		$this->file = Mage::getBaseDir('tmp').DS.substr($site,0,strrpos($site, ".")-1).'unbxdFeed.xml';
		if (!$this->log("Feed uploading started")) { 
			error_log("No permission to write to " + Mage::getBaseDir('log'));
			return false;
		}
		if(!isset($this->key) || $this->key == "" || $this->key == "empty"){
			$this->log("api key not set");
			return false;
		}
		if(!isset($this->feedName) || $this->feedName == "" || $this->feedName == "empty"){
            $this->log("Feed Name not set");
			return false;
        }
        $this->setFields($site);
		return true;
	}
 	
 	/**
 	* method to initiate feed uploading to the unbxd servers
 	**/
 	public function indexUnbxdFeed($fromdate,$site,$operation = "add", $ids=array()){
	 	
		$this->log('unbxd Datafeeder initiated');
		 // validatest the site 
		if($this->validateSite($site) == -1){
			$this->log("Invalid site Name".$site);
			return;
		}
		// set the basic
		if(! $this->setUnbxdConf($site)){
			return;
		}
 		$todate =date('Y-m-d H:i:s');

 		// check the lock, that if already indexing is happening
 		if($this->checkSiteLock($site)){
 			$this->log('site '. $site.' has been locked');
 			$action=$site.'/status';
 			// lock the feed
		 	Mage::getResourceSingleton("datafeeder/conf")->updateAction($action,'1');
		 	// create the feed

	 		$status=$this->createFeed($fromdate,$todate,$site,$operation,$ids);
            $this->log('unbxd Datafeeder finished creating file');
	
	 		if($status){ 		
				try{
					// if successful push it to unbxd servers
			 		$status=$this->pushFeed($site);
				}catch(Exception $e){
	                $this->log($e->getMessage());

				}
		 		if($status){
			 		Mage::getResourceSingleton("datafeeder/conf")->updateAction('Lastindex',$todate);
		 		}
	 		}
	 		// unlock the feed once everything is completed
		 	Mage::getResourceSingleton("datafeeder/conf")->updateAction($action,'0');	
		 	$this->log('site '. $site.' has been unlocked');
 		} else {
 			$this->log('Feed Uploading failed because site has been locked');
 		}
 	} 
 	
 	
 	/**
 	* method to push the feed to the Unbxd server
 	**/
 	public function pushFeed($site){
 		$fields=array('file'=>'@'.$this->file.';filename=unbxdFeedRenamed.xml');
	 	$header = array('Content-Type: multipart/form-data');
	
 		$url="feed.unbxdapi.com/upload/".$this->key."/".$this->feedName;
 		//clear the feed
 		$clearFeedUrl = "http://feed.unbxdapi.com/clearfeed.do?key=".$this->key."&feedName=".$this->feedName;
 		file_get_contents($clearFeedUrl);
                $this->log('cleared the feed');

 		
 		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POST,true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$fields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		try{
            $this->log('pushing the feed');
            // push the feed to the server
			$response = curl_exec ($ch);
		}catch(Exception $Ex){
	        $this->log($Ex->getMessage());
			return false;
		}
        $this->log($response);
		curl_close($ch);
		return true;		
 	}

 	/**
 	* method to check the status of the uploading
 	**/
 	public function checkSiteLock($site)
	{   	
	    $value = Mage::getResourceSingleton("datafeeder/conf")->getValue($site."/status");
	    if($value == '0' || $value == 'empty'){
	    	$this->log("true");
	    	return true;
	    }else{
	    	$this->log("false". $value);
	    	return false;
	    }
	}
 }
?>	

