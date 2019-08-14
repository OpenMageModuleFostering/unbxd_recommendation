<?php 

class Unbxd_Datafeeder_Model_Feed_Filemanager {


	var $logFileName;

	public function __construct(){
		$this->logFileName = Mage::getBaseDir('log').DS.'generic.log';
	}

	public function setLog($logFileName) {
		$this->logFileName = Mage::getBaseDir('log').DS.$logFileName;
	}

	/**
 	 * Function to create a file
 	 */
 	public function createFile($file){
 		try{
	 		$f=fopen($file,'w'); 
	 		
	 		fclose($f);
	 		if(!file_exists($file)) {
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
 	public function appendTofile($fileName, $content){
 		try {
 			if(file_put_contents($fileName, $content, FILE_APPEND)) {
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
 	 * Function to appened the contents to the file
 	 */
 	public function log($content){
 		try{
 			$resp = file_put_contents($this->logFileName, date('Y-m-d H:i:s').$content."\n", FILE_APPEND);
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
}
?>