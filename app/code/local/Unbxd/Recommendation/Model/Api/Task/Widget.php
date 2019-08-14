<?php
/**
 * Created by IntelliJ IDEA.
 * User: anantheshadiga
 * Date: 10/21/14
 * Time: 1:01 AM
 */

class Unbxd_Recommendation_Model_Api_Task_Widget extends Unbxd_Recommendation_Model_Api_Task {

    const WIDGET_TYPE = 'widgetType';

    const jsonResponse = false;

    public static $WIDGET_TYPES = array('recommend' => 'unbxd_recommended_for_you',
        'recently-viewed' =>'unbxd_recently_viewed',
        'more-like-these' => 'unbxd_more_like_these',
        'also-bought' =>'unbxd_also_bought',
        'also-viewed' => 'unbxd_also_viewed',
        'top-sellers' => 'unbxd_top_sellers',
        'category-top-sellers' => 'unbxd_category_top_sellers',
        'brand-top-sellers' => 'unbxd_brand_top_sellers',
        'pdp-top-sellers' => 'unbxd_pdp_top_sellers',
        'cart-recommend' => 'unbxd_cart_recommendations');

    public function prepare(Mage_Core_Model_Website $website) {
        $this->preparationSuccessful = true;
        $widgetType = $this->getWidgetType();
        if(is_null($widgetType)) {
            return $this;
        }
        $this->prepareUrl($website, $widgetType);
        $this->prepareParams();
        return $this;
    }

    protected function prepareParams() {
        $this->setData('ip',
            isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR'])
            ->setData('format', 'html');
    }

    protected function getWidgetType() {
        $params = $this->getData();
        if(!array_key_exists(static::WIDGET_TYPE, $params) ||
            !array_key_exists($params[static::WIDGET_TYPE], static::$WIDGET_TYPES) ) {
            $this->preparationSuccessful = false;
            $this->errors["message"] = "Invalid widget type";
            return null;
        }
        return $params[static::WIDGET_TYPE];
    }

    protected function prepareUrl(Mage_Core_Model_Website $website, $widget)
    {
        $params = $this->getData();
        $siteKey = Mage::getResourceModel("unbxd_recommendation/config")
            ->getValue($website->getWebsiteId(), Unbxd_Recommendation_Helper_Confighelper::SITE_KEY);
        $apiKey = Mage::getResourceModel("unbxd_recommendation/config")
            ->getValue($website->getWebsiteId(), Unbxd_Recommendation_Helper_Confighelper::API_KEY);
        if (is_null($siteKey)) {
            $this->preparationSuccessful = false;
            $this->errors["message"] = "Site key not set";
            return;
        }

        $pk = $this->getPrimaryToken();
        $pk = rawurlencode($pk);

        if ($pk != '') {
            static::$url = static::RECOMMENDATION_API . "v1.0/$apiKey/$siteKey/$widget/$pk";
            return;
        } else if($params[static::WIDGET_TYPE] == 'top-sellers' || $params[static::WIDGET_TYPE] == 'recommend') {
            static::$url = static::RECOMMENDATION_API . "v1.0/$apiKey/$siteKey/$widget";
            return;
        } else {
            $this->preparationSuccessful = false;
            $this->errors["message"] = "Primary token missing for the specified widget";
            return;
        }
    }

    protected function getPrimaryToken() {
        $params = $this->getData();

        if($params[static::WIDGET_TYPE] == 'recently-viewed' || $params[static::WIDGET_TYPE] == 'recommend' ||
            $params[static::WIDGET_TYPE] == 'cart-recommend') {
            if(array_key_exists('uid', $params)) {
                return $params['uid'];
            } else {
                return '';
            }
        }

        if($params[static::WIDGET_TYPE] == 'also-viewed' || $params[static::WIDGET_TYPE] == 'also-bought' ||
            $params[static::WIDGET_TYPE] == 'more-like-these' || $params[static::WIDGET_TYPE] == 'pdp-top-sellers') {
            if(array_key_exists('uid', $params)) {
                return $params['pid'];
            } else {
                return '';
            }
        }

        if($params[static::WIDGET_TYPE] == 'category-top-sellers') {
            if(array_key_exists('category', $params)) {
                return $params['category'];
            } else {
                return '';
            }
        }

        if($params[static::WIDGET_TYPE] == 'brand-top-sellers') {
            if(array_key_exists('brand', $params)) {
                return $params['brand'];
            } else {
                return '';
            }
        }
    }

    protected function postProcess(Unbxd_Recommendation_Model_Api_Response $response)
    {
        $body = $response->getBody();
        if(is_null($body) || $body == '') {
            return $response;
        }
        $body = $this->trimBody($body);
        $body = $this->parseBody($body);
        $body = $this->prepareResponse($body);
        $response->setData('body', $body);
        return $response;
    }

    protected function prepareResponse($body)
    {
       return $body;
    }

    private function getJavascriptNode($body) {
        $startpos = strpos($body, '<script');
        $endpos = strrpos($body, "</script>");
        if($startpos != false && $endpos != false) {
            return substr($body, $startpos, $endpos + strlen("</script>"));
        }
        return "";
    }

    protected function parseBody($body)
    {
        $prefix = '<html><body>';
        $postfix = '</body></html>';
        $dom = new DOMDocument;

        $strippedJs = $this->getJavascriptNode($body);
        $body = str_replace($strippedJs, "", $body);
        @$dom->loadHTML($prefix.$body. $postfix);

        $xpath = new DOMXPath($dom);
        $classname = 'unbxd-field-image_link';
        $nodes = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
        if(!isset($nodes)) {
            return $body;
        }
        $images = $this->getOrigImages($nodes);
        $images = $this->getFullImages($images);
        $this->setImages($images, $xpath);
        $body = $dom->saveHTML();
        $pos = strrpos($body, $postfix);
        if($pos != false) {
            $body = substr_replace($body, '', $pos, strlen($postfix));
        }
        $pos = strpos($body, $prefix);
        if($pos !== false ) {
            $body = substr_replace($body, '', $pos, strlen($prefix));
        }
        return $body.$strippedJs;
    }

    protected function setImages($images,DOMXPath $xpath) {
        foreach($images as $origImagePath => $modifiedImagePath) {
            $imageNode = $xpath->query('//img[@src="' . $origImagePath . '"]');
            if($imageNode instanceof DOMNodeList && $imageNode->length > 0) {
                for($index = 0;$index < $imageNode->length; $index++) {
                    $imageNode->item($index)->setAttribute('src', $modifiedImagePath);
                }
            }
        }
    }

    protected function getOrigImages(DOMNodeList $nodes) {
        $images = array();
        foreach ($nodes as $node) {
            $img = $node->getElementsByTagName('img');
            if ($img instanceof DOMNodeList && $img->length > 0) {
                if ($img->item(0)->hasAttribute('src')) {
                    $src = $img->item(0)->getAttribute('src');
                    $images[] = $src;
                }
            }
        }
        return $images;
    }

    protected function getFullImages($images) {
        $website = Mage::app()->getWebsite();
        $imageField = Mage::getResourceModel("unbxd_recommendation/field")
            ->getFieldByFeatureField($website->getWebsiteId(), 'imageUrl');
        $fullImages = array();

        foreach($images as $img) {
            $product = Mage::getModel('catalog/product')
                ->setData($imageField, $img);
            $fullImages[$img] = (string)Mage::helper('catalog/image')->init($product, $imageField)
                ->resize(400, 500);
        }
        return $fullImages;
    }

    protected function trimBody($body) {
        $endPart = '").text();$(\'#' . static::$WIDGET_TYPES[$this->getData(static::WIDGET_TYPE)] . '\').html(decoded); })(jQuery);';
        $startPart = '(function($){var decoded = $(\'<div/>\').html("';
        $pos = strrpos($body, $endPart);
        if($pos != false) {
            $body = substr_replace($body, '', $pos, strlen($endPart));
        }
        $pos = strpos($body, $startPart);
        if($pos !== false ) {
            $body = substr_replace($body, '', $pos, strlen($startPart));
        }
        $body = trim(html_entity_decode($body, ENT_QUOTES));
        return $body;
    }
}