<?php

/**
 * @category Unbxd
 * @package Unbxd_Recommendation
 * @author Unbxd Software Pvt. Ltd {
 */
class Unbxd_Recommendation_AdminController extends Mage_Adminhtml_Controller_Action {

    /**
     * @return void
     */
    public function editAction(){
        //echo json_encode($this->getLayout()->getUpdate()->getHandles());
        $this->loadLayout();
        $this->renderLayout();
    }
}

?>
