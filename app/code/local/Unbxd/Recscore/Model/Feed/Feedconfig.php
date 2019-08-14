<?php
/**
 * Created by IntelliJ IDEA.
 * User: antz
 * Date: 21/07/15
 * Time: 12:48 AM
 */
class Unbxd_Recscore_Model_Feed_Feedconfig {
    var $page = 0;
    var $pageSize = 500;
    var $limit = -1;
    var $_includeSchema = true;
    var $_includeProduct = true;
    var $_includeTaxonomyNodes = false;
    var $_includeTaxonomyMapping = false;
    var $taxonomyLimit = -1;
    var $taxonomyStart;

    public function __construct(Mage_Core_Model_Website $website) {
        $this->_includeTaxonomyNodes = Mage::helper('unbxd_recscore')
            ->isConfigTrue($website, Unbxd_Recscore_Helper_Constants::INCLUDE_TAXONOMY_NODES);
        $this->_includeTaxonomyMapping = Mage::helper('unbxd_recscore')
            ->isConfigTrue($website, Unbxd_Recscore_Helper_Constants::INCLUDE_TAXONOMY_MAPPING);
    }

    public function setSchemaInclusion($status = true) {
        $this->_includeSchema = $status;
    }
    public function isSchemaToBeIncluded() {
        if (array_key_exists("schema", $_GET)){
            $this->_includeSchema = ($_GET["schema"] == "true")?true:false;
        }
        return $this->_includeSchema;
    }
    public function setProductInclusion($status=true) {
        $this->_includeProduct = $status;
    }
    public function isProductToBeIncluded() {
        if (array_key_exists("product", $_GET)){
            $this->_includeProduct = ($_GET["product"] == "true")?true:false;
        }
        return $this->_includeProduct;
    }
    public function isCatalogIncluded() {
        return $this->isSchemaToBeIncluded() || $this->isProductToBeIncluded();
    }
    public function setTaxonomyNodeInclusion($status=true) {
        $this->_includeTaxonomyNodes = $status;
    }
    public function isTaxonomyNodeToBeIncluded() {
        if (array_key_exists("taxonomy-node", $_GET)){
            $this->_includeTaxonomyNodes = ($_GET["taxonomy-node"] == "true")?true:false;
        }

        return $this->_includeTaxonomyNodes;
    }
    public function setTaxonomyMappingInclusion($status=true) {
        $this->_includeTaxonomyMapping = $status;
    }
    public function isTaxonomyMappingToBeIncluded() {
        if (array_key_exists("taxonomy-mapping", $_GET)){
            $this->_includeTaxonomyMapping= ($_GET["taxonomy-mapping"] == "true")?true:false;
        }

        return $this->_includeTaxonomyMapping;
    }

    public function isTaxonomyIncluded() {
        return $this->isTaxonomyNodeToBeIncluded() || $this->isTaxonomyMappingToBeIncluded();
    }

    public function setPage($page = 0) {
        $this->page = (int)$page;
        return $this;
    }

    public function setLimit($limit = 500) {
        $this->limit = (int)$limit;
        if($limit < $this->pageSize) {
            $this->pageSize = (int)$limit;
        }
        return $this;
    }

    public function setTaxonomyPage($taxPage) {
        $this->taxonomyStart = $taxPage;
        return $this;
    }

    public function getTaxonomyPage() {
        if (array_key_exists("taxonomy-start", $_GET)){
            $this->taxonomyStart=
                (is_numeric($_GET["taxonomy-start"]) && $_GET["taxonomy-start"] > 0)
                    ?$_GET["taxonomy-start"]:$this->taxonomyStart;
        }
        return $this->taxonomyStart;
    }

    public function setTaxonomyLimit($taxLimit) {
        $this->taxonomyLimit = $taxLimit;
        return $this;
    }

    public function getTaxonomyLimit() {
        if (array_key_exists("taxonomy-limit", $_GET)){
            $this->taxonomyLimit=
                (is_numeric($_GET["taxonomy-limit"]) && $_GET["taxonomy-limit"] > 0)
                    ?$_GET["taxonomy-limit"]:$this->taxonomyLimit;
        }
        return $this->taxonomyLimit;
    }
}
