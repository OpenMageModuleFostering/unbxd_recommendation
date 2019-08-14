<?php

/**
 * @category Unbxd
 * @package Unbxd_Recscore
 * @author Unbxd Software Pvt. Ltd {
 */
class Unbxd_Recscore_Adminhtml_UnbxdController extends Mage_Adminhtml_Controller_Action {

    /**
     * @return void
     */
    public function indexAction(){
        $this->loadLayout();
        $this->renderLayout();
    }
}
?>
