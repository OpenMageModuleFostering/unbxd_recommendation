<?php 

class Unbxd_Recommendation_Model_Feed_Filemanager {


	var $logFileName;

	public function __construct(){
		$this->logFileName = Mage::getBaseDir('log').DS.'generic.log';
	}

	public function setLog($logFileName) {
		$this->logFileName = Mage::getBaseDir('log').DS.$logFileName;
	}

    /**
     * @param $file
     * @return bool
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
     * @param $fileName
     * @param $content
     * @return bool
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
     * method to log
     * @param $content
     * @return void
     */
    public function log($content){
 		Mage::log(Zend_Log::DEBUG, $content);
 	}


    /**
     * method to delete the file
     * @param $file
     * @return void
     */
    public function deleteFile($file){
 		unlink($file);
 	}
}
?>