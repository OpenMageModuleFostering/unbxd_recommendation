<?php 

class Unbxd_Datafeeder_FieldController extends  Mage_Core_Controller_Front_Action
{
	
	const FIELD_MODEL = 'datafeeder/field';
	
	public function configAction() {
		$site=$this->getRequest()->getParam("site");
		if(!isset($site)){
			echo '{"failed": "no site sent"}';
			return;
		}
		$fields = Mage::getResourceSingleton(self::FIELD_MODEL)->getFields($site);
		foreach ($fields as $field => $value) {
			if (Mage::helper('unbxd_datafeeder/UnbxdIndexingHelper')->isImage($field)) {

				$value["is_image"] = "true";
				$fields[$field] = $value;
			}
		}
		echo json_encode($fields);
	}
	
	public function saveAction()
	{
		$params=$this->getRequest()->getParams();
		if(!isset($params) || count($params) == 0) {
			echo json_encode(array("success"=>"false", "message"=>"No Fields Passed"));
			return;
		}
		$singleProductData = json_decode(reset($params), true);
		if(!isset($singleProductData)) {
			echo json_encode(array("success"=>"false", "message"=>"Improper Data format"));
			return;
		}
		$site = $singleProductData["site_name"];
		if (!isset($site)) {
			echo json_encode(array("success"=>"false", "message"=>"site needed"));
			return;	
		}
		try {
			$fields = Mage::getResourceSingleton(self::FIELD_MODEL)->updateFields($params,$site);
		} catch(Exception $ex) {
			error_log($ex->getMessage());
			echo json_encode(array("success"=>"false", "message" => $ex));
			return;
		}
		echo json_encode(array("success"=>"true"));
	}
}
?>
