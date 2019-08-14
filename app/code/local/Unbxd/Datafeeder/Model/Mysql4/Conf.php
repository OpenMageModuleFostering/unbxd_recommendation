<?php 

class Unbxd_Datafeeder_Model_Mysql4_Conf extends Mage_Core_Model_Mysql4_Abstract
{
	protected function _construct()
	{
		  $this->_init('datafeeder/conf', 'uconfig_id');
	}	

	public function getValue($action)
    {
        $collection=Mage::getModel('datafeeder/conf')->getCollection()
        ->addFieldToFilter('action',$action);

        $count=0;
        foreach($collection as $coll){
            $count++;
            $value=$coll->getvalue();
        }
        if($count==0){
            $collection=Mage::getModel('datafeeder/conf')
            ->setAction($action)->setValue("empty")->save();
            $value="empty";
        }
        return $value;
    }

    public function updateAction($action,$value)
    {
        if (!isset($value) || $value == "") {
            return;
        }
    	$collection=Mage::getModel('datafeeder/conf')->getCollection()
    	->addFieldToFilter('action',$action);
    	
    	$count=0;
    	foreach($collection as $coll){
    		$count++;
    		Mage::getModel('datafeeder/conf')->load($coll->getId())->setValue($value)->save();
    	}
    	if($count==0){
	   		$collection=Mage::getModel('datafeeder/conf')
	   		->setAction($action)->setValue($value)->save();
    	}	    	 
    }
}
?>
