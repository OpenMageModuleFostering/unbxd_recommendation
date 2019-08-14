<?php

/**
 * @category Unbxd
 * @package Unbxd_Recscore
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recscore_Model_Observer {

    /**
     * Observer method to track the add to cart
     * @return $this
     */
    public function trackAddToCart(Varien_Event_Observer $observer) {
	if(!Mage::helper('unbxd_recscore')->isExecutable()) {
		return;
	}
        $product = $observer->getEvent()->getProduct();
        if(!$product instanceof Mage_Catalog_Model_Product) {
            Mage::helper('unbxd_recscore')->log(Zend_Log::ERR, 'CART_TRACKER:product is not a valid type');
            return $this;
        }
        $response = Mage::getModel('unbxd_recscore/api_task_trackcart')
            ->setData('data', array('pid' => Mage::helper('unbxd_recscore/feedhelper')->getUniqueId($product),
                'visit_type' => 'repeat'))
            ->setData('ip', isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR'])
            ->setData('agent', $_SERVER['HTTP_USER_AGENT'])
            ->prepare(Mage::app()->getWebsite())
            ->process();
        $errors = $response->getErrors();
        if(sizeof($errors) > 0) {
            Mage::helper('unbxd_recscore')
                ->log(Zend_Log::ERR, 'CART_TRACKER:request failed because ' .json_encode($errors));
        }
		return $this;
	}

    /**
     * Observer method to track orders
     * @return $this
     */
    public function trackOrder(Varien_Event_Observer $observer) {
	if(!Mage::helper('unbxd_recscore')->isExecutable()) {
        	return;
        }
        $payment = $observer->getEvent()->getPayment();
        /* @var Mage_Sales_Model_Order_Payment */

        if(!$payment instanceof Mage_Sales_Model_Order_Payment) {
            Mage::helper('unbxd_recscore')->log(Zend_Log::ERR, 'ORDER_TRACKER:payment is not a valid type');
            return $this;
        }
        $items = $payment->getOrder()->getItemsCollection();

        foreach($items as $item) {
            if($item instanceof Mage_Sales_Model_Order) {
                Mage::helper('unbxd_recscore')
                    ->log(Zend_Log::ERR, 'ORDER_TRACKER:request failed because item is of instancetype ' . get_class($item));
                continue;
            }
            $product =$item->getProduct();
            $response = Mage::getModel('unbxd_recscore/api_task_trackorder')
                ->setData('data',
                    array('visit_type' => 'repeat',
                        'pid' => Mage::helper('unbxd_recscore/feedhelper')->getUniqueId($product),
                        'qty' => $item->getQtyOrdered(),
                        'price' => $item->getPriceInclTax()))
                ->setData('ip', isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'])
                ->setData('agent', $_SERVER['HTTP_USER_AGENT'])
                ->prepare(Mage::app()->getWebsite())
                ->process();

            if ($response->isSuccess() && is_array($response->getErrors()) && sizeof($response->getErrors()) > 0) {
                Mage::helper('unbxd_recscore')
                    ->log(Zend_Log::ERR, 'ORDER_TRACKER:request failed because ' . json_encode($response->getErrors()));
            }
            Mage::getSingleton('unbxd_recscore/sync')->addProduct($product);
        }
        return $this;
	}

    /**
     * Method to sync the product catalog through cron
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function syncFull(Varien_Event_Observer $observer)
    {
	if(!Mage::helper('unbxd_recscore')->isExecutable()) {
                  return;
        }
        $websiteCollection = Mage::getModel('core/website')->getCollection()->load();

        foreach ($websiteCollection as $website) {
            Mage::getResourceModel('unbxd_recscore/config')
                ->setValue($website->getWebsiteId(), Unbxd_Recscore_Helper_Constants::IS_CRON_ENABLED, 1);
            Mage::getSingleton('unbxd_recscore/feed_feedmanager')->process(true, $website);
        }
        return $this;
    }

    /* 
     * Method to sync the product incremental catalog through cron
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function syncIncremental(Varien_Event_Observer $observer)
    {
        if(!Mage::helper('unbxd_recscore')->isExecutable()) {
                  return;
        }
        $websiteCollection = Mage::getModel('core/website')->getCollection()->load();
        foreach ($websiteCollection as $website) {
           Mage::getResourceModel('unbxd_recscore/config')
               ->setValue($website->getWebsiteId(), Unbxd_Recscore_Helper_Constants::IS_CRON_ENABLED, 1);
           Mage::getSingleton('unbxd_recscore/feed_feedmanager')->process(false, $website);
       }
       return $this;
   }

    /**
     * Method to track deleted product
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackDelete(Varien_Event_Observer $observer) {
	if(!Mage::helper('unbxd_recscore')->isExecutable()) {
                   return;
         }
        $product = $observer->getEvent()->getDataObject();
        $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId());
        if(!$parentIds)
            $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
        foreach($parentIds as $parentId) {
            $parentProduct = Mage::getModel('catalog/product')->load($parentId);
            Mage::getSingleton('unbxd_recscore/sync')->addProduct($parentProduct);
        }
        Mage::getSingleton('unbxd_recscore/sync')->deleteProduct($product);
        return $this;
    }

    /**
      * Method to track deleted product
      * @param Varien_Event_Observer $observer
      * @return void
      */

    public function catalogInventorySave(Varien_Event_Observer $observer) {
	if(!Mage::helper('unbxd_recscore')->isExecutable()) {
                   return;
         }
        $_item = $observer->getEvent()->getItem()->getProduct();
	 Mage::helper('unbxd_recscore')->log(Zend_Log::ERR, 'product id is '. $_item->getData("entity_id"));
        Mage::getSingleton('unbxd_recscore/sync')->addProduct($_item);
        return $this;
    }

    public function saleOrderCancel(Varien_Event_Observer $observer) {

    }
}
?>
