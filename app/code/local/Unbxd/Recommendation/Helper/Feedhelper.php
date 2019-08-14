<?php

class Unbxd_Recommendation_Helper_Feedhelper extends Unbxd_Recommendation_Helper_Data {

    var $categoryMap;

    var $catlevel1List;

    var $attributeToTypeMap;

    var $_rootCategoryIds = array();

    protected function array_match($needle, $haystack) {
        if(!is_array($needle)) {
            return in_array($needle, $haystack);
        }

        foreach($needle as $eachNeedle) {
            if(in_array($eachNeedle, $haystack)) {
                return true;
            }
        }
        return false;
    }
    /**
     * function to get Category from the category id,
     * This checks it present in the global array 'categoryMap', if it is not there fetches from db
     * So that once it gets one category, it doesn't make db call again for the same category
     *
     * @param string $category_id
     * @return Mage_Catalog_Model_Category
     */
    public function getCategory($category_id = ""){
        if(!isset($this->categoryMap[$category_id])){
            $category = Mage::getModel('catalog/category')->load($category_id);
            $this->categoryMap[$category_id] = $category;
            $parentCategories = $category->getParentCategories();
            foreach($parentCategories as $parentCategory) {
                $parentCategory = Mage::getModel('catalog/category')->load($parentCategory->getId());
                $this->categoryMap[$parentCategory->getId()] = $parentCategory;
            }
            return $this->categoryMap[$category_id];
        }
        return $this->categoryMap[$category_id];
    }

    public function getRootCategoryIds(Mage_Core_Model_Website $website) {
        if(!array_key_exists($website->getWebsiteId(), $this->_rootCategoryIds)) {
            foreach($website->getStores() as $store) {
                $this->_rootCategoryIds[$website->getWebsiteId()][] = $store->getRootCategoryId();
            }
        }
        return $this->_rootCategoryIds[$website->getWebsiteId()];
    }

    public function getCategoryOnLevel($category_ids, $level) {
        if(!is_array($category_ids)) {
            return array();
        }
        $catlevel1 = array();
        foreach($category_ids as $category_id) {
            $category = $this->getCategory($category_id);
            $parentIds = $category->getParentIds();
            if(!is_null($category) && $category->getLevel() == $level) {
                $catlevel1 = $catlevel1 + array($category->getName());
            } else if ($category instanceof Mage_Catalog_Model_Category &&
                is_array($parentIds) &&
                (sizeof($parentIds) >0)) {
                $catlevel1 = $catlevel1 + $this->getCategoryOnLevel($parentIds, $level);
            }
        }
        return $catlevel1;
    }

    public function getCatLevel2(Mage_Core_Model_Website $website, $category_ids, $catlevel1Categories = null) {
        if(is_null($catlevel1Categories)) {
            $catlevel1Categories = $this->getCatLevel1($website, $category_ids);
        }
        $catlevel1Ids = array_keys($catlevel1Categories);
        $catlevel1 = array();
        foreach($category_ids as $category_id) {
            $category = $this->getCategory($category_id);
            $parentIds = $category->getParentIds();

            if(!is_null($category) &&
                $this->array_match($category->getParentId(), $catlevel1Ids)) {
                $catlevel1 = $catlevel1 + array($category->getId() => $category->getName());
            } else if ($category instanceof Mage_Catalog_Model_Category && is_array($parentIds) &&
                (sizeof($parentIds) >0)) {
                $catlevel1 = $catlevel1 + $this->getCatLevel2($website, $parentIds, $catlevel1Categories);
            }
        }
        return $catlevel1;
    }

    public function getCatLevel3(Mage_Core_Model_Website $website, $category_ids, $catlevel2Categories = null) {
        if(is_null($catlevel2Categories)) {
            $catlevel2Categories = $this->getCatLevel1($website, $category_ids);
        }
        $catlevel2Ids = array_keys($catlevel2Categories);
        $catlevel1 = array();
        foreach($category_ids as $category_id) {
            $category = $this->getCategory($category_id);
            $parentIds = $category->getParentIds();

            if(!is_null($category) &&
                $this->array_match($category->getParentId(), $catlevel2Ids)) {
                $catlevel1 = $catlevel1 + array($category->getId() => $category->getName());
            } else if ($category instanceof Mage_Catalog_Model_Category &&
                is_array($parentIds) &&
                (sizeof($parentIds) >0)) {
                $catlevel1 = $catlevel1 + $this->getCatLevel2($website, $parentIds, $catlevel2Categories);
            }
        }
        return $catlevel1;
    }


    /**
     * method to get all the attributes
     **/
    public function getAttributeMapping(){
        if(isset($this->attributeToTypeMap)){
            return $this->attributeToTypeMap;
        } else {
            $attributes = Mage::getSingleton('eav/config')
                ->getEntityType(Mage_Catalog_Model_Product::ENTITY)->getAttributeCollection();
            foreach($attributes as $attribute){
                $this->attributeToTypeMap[$attribute->getAttributeCode()] = $attribute-> getFrontendInput();
            }
            return $this->attributeToTypeMap;
        }
    }

    /**
     * method to get field type of the field
     **/
    protected  function getFieldType($attributeName){
        $fieldMap = $this->getAttributeMapping();
        if(array_key_exists( $attributeName, $fieldMap)){
            return $fieldMap[$attributeName];
        } else {
            return "text";
        }
    }

    /*
    * function to check whether the field is a multiSelect/select or not,
    * This is optimized method, where it doesn't make a database call to get fieldType
    * where it fetches from the local variable, which holds the information of field to fieldType mapping
    */
    public function isMultiSelect($attributeName = ""){
        if($attributeName == "status" || $attributeName == "visibility" ){
            return false;
        }
        if($this->getFieldType($attributeName) == "select" ||
            $this->getFieldType($attributeName) == "multiselect" ||
            $attributeName == Unbxd_Recommendation_Model_Resource_Field::CATEGORY_IDS_NAME ||
            $attributeName == Unbxd_Recommendation_Model_Resource_Field::CAT_LEVEL_1_NAME ||
            $attributeName == Unbxd_Recommendation_Model_Resource_Field::CAT_LEVEL_2_NAME ||
            $attributeName == Unbxd_Recommendation_Model_Resource_Field::CAT_LEVEL_3_NAME){
            return true;
        }
        return false;
    }

    public function isImage($attributeName = "") {
        if($this->getFieldType($attributeName) == "media_image") {
            return true;
        }
        return false;
    }

    /**
     * Get all root categories used by all stores.
     * Note that root categories defined but not used, are not included.
     *
     * @return Mage_Catalog_Model_Category[]
     */
    public function getAllRootCategories()
    {
        $categories = array();

        /** @var $stores Mage_Core_Model_Store[] */
        $stores = Mage::app()->getStores();

        foreach ($stores as $store) {
            $id = $store->getRootCategoryId();
            if (!isset($categories[$id])) {
                $categories[$id] = Mage::getModel('catalog/category')->load($id);
            }
        }

        return $categories;
    }

    public function getUniqueId($product) {
        $type= null;
        if($product->hasData('type_id')) {
            $type = $product->getData('type_id');
        }

        switch($type){
            case 'configurable':
                $productId = $product->getData('entity_id');
                break;
            case 'grouped':
                $productId = $product->getData('entity_id');
                break;
            case 'bundle':
                $productId = $product->getData('entity_id');
                break;
            case 'simple':
                if ($product->getParentItem() != null)	{
                    $productId = $product->getParentItem()->getProductId();
                } else {
                    $productId = $product->getData('entity_id');
                }
                break;
            default:
                $productId = $product->getData('entity_id');
        }
        return $productId;
    }

}