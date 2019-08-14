<?php

/**
 * @category Unbxd
 * @package Unbxd_Recommendation
 * @author Unbxd Software Pvt. Ltd {
 */
class Unbxd_Recommendation_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action {

    /**
     * @return void
     */
    public function indexAction(){
        $this->loadLayout();
        $this->renderLayout();
    }
}
?>