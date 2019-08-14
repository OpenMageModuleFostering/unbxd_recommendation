<?php

/**
 * @category Unbxd
 * @package Unbxd_Recommendation
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recommendation_Model_Observer {

    /**
     * Observer method to track the add to cart
     * @return $this
     */
    public function trackAddToCart(Varien_Event_Observer $observer) {
        $product = $observer->getEvent()->getProduct();
        if(!$product instanceof Mage_Catalog_Model_Product) {
            Mage::helper('unbxd_recommendation')->log(Zend_Log::ERR, 'CART_TRACKER:product is not a valid type');
            return $this;
        }
        $response = Mage::getModel('unbxd_recommendation/api_task_trackcart')
            ->setData('data', array('pid' => Mage::helper('unbxd_recommendation/feedhelper')->getUniqueId($product),'visit_type' => 'repeat'))
            ->setData('ip', isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR'])
            ->setData('agent', $_SERVER['HTTP_USER_AGENT'])
            ->prepare(Mage::app()->getWebsite())
            ->process();
        $errors = $response->getErrors();
        if(sizeof($errors) > 0) {
            Mage::helper('unbxd_recommendation')
                ->log(Zend_Log::ERR, 'CART_TRACKER:request failed because ' .json_encode($errors));
        }
		return $this;
	}

    /**
     * Observer method to track orders
     * @return $this
     */
    public function trackOrder(Varien_Event_Observer $observer) {
        $payment = $observer->getEvent()->getPayment();
        if(!$payment instanceof Mage_Sales_Model_Order_Payment) {
            Mage::helper('unbxd_recommendation')->log(Zend_Log::ERR, 'ORDER_TRACKER:payment is not a valid type');
            return $this;
        }
        $items = $payment->getOrder()->getItemsCollection();

        foreach($items as $item) {
            if($item instanceof Mage_Sales_Model_Order) {
                Mage::helper('unbxd_recommendation')
                    ->log(Zend_Log::ERR, 'ORDER_TRACKER:request failed because item is of instancetype ' . get_class($item));
                continue;
            }
            $type = $item->getProductType();

            switch($type){
                case 'configurable':
                    if ($item->getHasChildren()) {
                        $productId = $item->getProductId();
                    }elseif($item->getParentItem() != null)	{
                        $productId = $item->getParentItem()->getProductId();
                    }
                    break;
                case 'grouped':
                    $values=$item->getProductOptionByCode('info_buyRequest');
                    $parentId = $values['super_product_config']['product_id'];
                    $productId = $parentId;
                    break;
                case 'bundle':
                    $productId = $item->getProductId();
                    break;
                case 'simple':
                    if ($item->getParentItem() != null)	{
                        $productId = $item->getParentItem()->getProductId();
                    } else {
                        $productId = $item->getProductId();
                    }
                    break;
                default:
                    $productId = $item->getProductId();
            }
            $response = Mage::getModel('unbxd_recommendation/api_task_trackorder')
                ->setData('data',
                    array('visit_type' => 'repeat', 'pid' => $productId,
                        'qty' => $item->getQtyOrdered(), 'price' => $item->getPriceInclTax()))
                ->setData('ip', isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'])
                ->setData('agent', $_SERVER['HTTP_USER_AGENT'])
                ->prepare(Mage::app()->getWebsite())
                ->process();
            if ($response->isSuccess() && is_array($response->getErrors()) && sizeof($response->getErrors()) > 0) {
                Mage::helper('unbxd_recommendation')
                    ->log(Zend_Log::ERR, 'ORDER_TRACKER:request failed because ' . json_encode($response->getErrors()));
            }
        }
        return $this;
	}


    public function syncProduct()
    {
        $websiteCollection = Mage::getModel('core/website')->getCollection()->load();

        foreach ($websiteCollection as $website) {
            Mage::getResourceModel('unbxd_recommendation/config')
                ->setValue($website->getWebsiteId(), Unbxd_Recommendation_Helper_Confighelper::IS_CRON_ENABLED, 1);
            $fromdate = "1970-01-01 00:00:01";
            Mage::getSingleton('unbxd_recommendation/feed_feedmanager')->process($fromdate, $website);
        }
        return $this;
    }
}
?>