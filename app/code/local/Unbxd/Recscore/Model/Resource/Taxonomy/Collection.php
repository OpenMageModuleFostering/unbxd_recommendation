<?php

class Unbxd_Recscore_Model_Resource_Taxonomy_Collection  extends Mage_Catalog_Model_Resource_Category_Collection
{
    var $rows = array("entity_id","category_id");

    public function getData($select=null)
    {
        if ($this->_data === null) {
            $this->_renderFilters()
                ->_renderOrders()
                ->_renderLimit();
            if(!is_null($select)){
                $this->_select = $select;
            }
            $this->_data = $this->_fetchAll($this->_select);
            $this->_afterLoadData();
        }
        return $this->_data;
    }
    public function getTaxonomyQuery($start, $limit) {
        return "select catalog_category_product_index.product_id as entity_id,GROUP_CONCAT(catalog_category_product_index.category_id SEPARATOR ',') as category_id FROM catalog_category_product_index
                join catalog_product_entity on catalog_category_product_index.product_id = catalog_product_entity.entity_id
                group by catalog_category_product_index.product_id ". ((!is_null($limit))?(" LIMIT ".$limit):"")
                .((!is_null($start) && $start > 0)?(" OFFSET " .$start):"");
    }
    public function load($start = 0, $limit = null,$select=null, $printQuery = false, $logQuery = false){
        if ($this->isLoaded()) {
            return $this;
        }
        $this->_idFieldName = "entity_id";
        if($select == null) {
            $select = $this->getTaxonomyQuery($start, $limit);
        }
        $this->_beforeLoad();

        /* $this->_renderFilters()
              ->_renderOrders()
              ->_renderLimit();*/

        $this->printLogQuery($printQuery, $logQuery);

        $data = $this->getData($select);
        $this->resetData();

        if (is_array($data)) {
            foreach ($data as $row) {
                $item = $this->getNewEmptyItem();
                $item->setIdFieldName("entity_id");
                $item->addData($row);
                $this->addItem($item);
            }
        }

        $this->_setIsLoaded();
        $this->_afterLoad();
        return $this;
    }
    /**
     * Get SQL for get record count
     *
     * @return Varien_Db_Select
     */
    public function getSelectCountSql()
    {
        return "select count(distinct catalog_category_product_index.product_id) FROM catalog_category_product_index
    		join catalog_product_entity on catalog_category_product_index.product_id = catalog_product_entity.entity_id";
    }
    /**
     * Adding item to item array
     *
     * @param   Varien_Object $item
     * @return  Varien_Data_Collection
     */
    public function addItem(Varien_Object $item)
    {
        $itemId = $this->_getItemId($item);
        if (!is_null($itemId)) {
            if (isset($this->_items[$itemId])) {
                throw new Exception('Item ('.get_class($item).') with the same id "'.$item->getId().'" already exist');
            }
            $this->_items[$itemId] = $item;
        } else {
            $this->_addItem($item);
        }
        return $this;
    }

}