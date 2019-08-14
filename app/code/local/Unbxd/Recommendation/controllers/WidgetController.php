<?php

class Unbxd_Recommendation_WidgetController extends Mage_Core_Controller_Front_Action {

    public function indexAction() {
        $website = Mage::app()->getWebsite();
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'text/javascript');
        $response = Mage::getModel("unbxd_recommendation/api_task_widget")
            ->setData($_GET)
            ->prepare($website)
            ->process();
        if(!$response->isSuccess()) {
            $this->getResponse()->setBody('unbxdRecommendationError = ' .
                json_encode(array('success' => false, 'errors' => $response->getErrors())));
            return;
        }
        $this->getResponse()->setBody($response->getBody());
        return;
    }
}