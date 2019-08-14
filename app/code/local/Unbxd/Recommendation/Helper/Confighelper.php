<?php

/**
 * @category Unbxd
 * @package Unbxd_Recommendation
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recommendation_Helper_Confighelper extends Unbxd_Recommendation_Helper_Data {

    const SITE_KEY = "site_key";

    const API_KEY = "api_key";

    const SECRET_KEY = "secret_key";

    const USERNAME = "username";

    const NEED_FEATURE_FIELD_UPDATION = "need_feature_field_updation";

    const IS_CRON_ENABLED = "cron_enabled";


    /**
     * All possible data type values supported unbxd
     * @var array
     */
    public static $data_types = array("text", "longText", "link", "decimal", "number", "datetime");

    public function validateAndSaveKeys($website, $requestBody){
        $errors = $this->validateKeyParams($requestBody);
        if(sizeof($errors) > 0) {
            return $errors;
        }
        $requestParams = json_decode($requestBody, true);
        if(!$requestParams) {
            $errors['message'] = 'Invalid Request';
            return $errors;
        }
        $response = Mage::getModel("unbxd_recommendation/api_task_validatekeys")
            ->setData(Unbxd_Recommendation_Model_Api_Task_Validatekeys::SECRET_KEY, $requestParams[self::SECRET_KEY])
            ->setData(Unbxd_Recommendation_Model_Api_Task_Validatekeys::SITE_KEY, $requestParams[self::SITE_KEY])
            ->prepare($website)
            ->process();
        if(!$response->isSuccess()) {
            return $response->getErrors();
        }

        Mage::getResourceModel('unbxd_recommendation/config')
            ->setValue($website->getWebsiteId(), self::SECRET_KEY, $requestParams[self::SECRET_KEY]);
        Mage::getResourceModel('unbxd_recommendation/config')
            ->setValue($website->getWebsiteId(), self::SITE_KEY, $requestParams[self::SITE_KEY]);
        $response = $response->getResponse();
        Mage::getResourceModel('unbxd_recommendation/config')
            ->setValue($website->getWebsiteId(),
                self::API_KEY, $response[Unbxd_Recommendation_Model_Api_Task_Validatekeys::API_KEY]);
        Mage::getResourceModel('unbxd_recommendation/config')
            ->setValue($website->getWebsiteId(),
                self::USERNAME, $response[Unbxd_Recommendation_Model_Api_Task_Validatekeys::USERNAME]);
        return $errors;
    }

    public function validateKeyParams($requestBody) {
        $errors = array();
        $requestParams = json_decode($requestBody, true);
        if(!$requestParams) {
            Mage::helper('unbxd_recommendation')->log(Zend_Log::ERR, 'Invalid request witj requestBody' . $requestBody);
            $errors['message'] = 'Invalid Request';
            return $errors;
        }
        if(!array_key_exists(self::SECRET_KEY,$requestParams)){
            $errors[self::SECRET_KEY] = "Has Empty Data";
        }
        if(!array_key_exists(self::SITE_KEY, $requestParams)) {
            $errors[self::SITE_KEY] = "Has Empty Data";
        }
        return $errors;
    }

    public function getFeatureFields() {
        return Unbxd_Recommendation_Model_Field::$feature_fields;
    }

    public function getAllAttributes() {
        $attributes = Mage::getSingleton('eav/config')
            ->getEntityType(Mage_Catalog_Model_Product::ENTITY)->getAttributeCollection();
        $fields = array();
        foreach($attributes as $attribute) {
            $fields[] = $attribute->getName();
        }
        $fields[] = "final_price";
        return $fields;
    }

    private function getFieldMapping($fields) {
        $fieldMapping = array();
        foreach($fields as $field) {
            $fieldMapping[$field->getFieldName()] = $field;
        }
        return $fieldMapping;
    }

    /**
     * @param $fields
     * @return array
     */
    private function validate($fields) {
        $errors = array();
        foreach($fields as $field) {
            if(!array_key_exists(Unbxd_Recommendation_Model_Field::field_name, $field)) {
                $errors[Unbxd_Recommendation_Model_Field::field_name] = "Not Present for all the fields";
            } else if (is_null($field[Unbxd_Recommendation_Model_Field::field_name]) ||
                $field[Unbxd_Recommendation_Model_Field::field_name] =="") {
                $errors[Unbxd_Recommendation_Model_Field::field_name] = "field Name is empty for some fields";
            }
            if (!array_key_exists(Unbxd_Recommendation_Model_Field::datatype, $field)) {
                $errors[Unbxd_Recommendation_Model_Field::datatype] = "Not Present for all the fields";
            } else if (!in_array($field[Unbxd_Recommendation_Model_Field::datatype], Unbxd_Recommendation_Model_Field::$data_types)){
                Mage::helper('unbxd_recommendation')->log(Zend_Log::ERR, 'Invalid feature field '.
                    $field[Unbxd_Recommendation_Model_Field::datatype]);
                $errors[Unbxd_Recommendation_Model_Field::datatype] = "Invalid datatype specified";
            }

            if (array_key_exists(Unbxd_Recommendation_Model_Field::featured_field, $field)) {
                if(!array_key_exists($field[Unbxd_Recommendation_Model_Field::featured_field],
                    Mage::getModel('unbxd_recommendation/field')->getFeaturedFields())) {
                    Mage::helper('unbxd_recommendation')->log(Zend_Log::ERR, 'Invalid feature field '.
                        $field[Unbxd_Recommendation_Model_Field::featured_field]);
                    $errors[Unbxd_Recommendation_Model_Field::featured_field] = "Invalid feature field specified";
                }
            }
        }
        return $errors;
    }

    public function deleteFields($fields, $website) {
        $errors = $this->validate($fields);
        if(sizeof($errors) != 0) {
            return $errors;
        }
        $collection = $this->buildFieldCollection($fields, $website);
        return Mage::getModel("unbxd_recommendation/field")->saveFields($collection);
    }

    /**
     * @param $fields
     * @param $website
     * @return array
     */
    public function saveFields($fields, $website) {
        $errors = $this->validate($fields);
        if(sizeof($errors) != 0) {
            return $errors;
        }
        $collection = $this->buildFieldCollectionToAdd($fields, $website);
        $response = Mage::getModel("unbxd_recommendation/field")->saveFields($collection);
        if(!is_array($response) && $response === true) {
            $this->triggerUpdateFeatureField($website);
        }
    }


    public function triggerUpdateFeatureField(Mage_Core_Model_Website $website) {
        Mage::getResourceModel('unbxd_recommendation/config')
            ->setValue($website->getWebsiteId(), self::NEED_FEATURE_FIELD_UPDATION, 1);
        $this->triggerFeedUpload($website);
    }

    /**
     * Method to trigger feed upload
     * @param Mage_Core_Model_Website $website
     * @return void
     */
    public function triggerFeedUpload(Mage_Core_Model_Website $website) {
        Mage::getModel('unbxd_recommendation/api_task_triggerfeedupload')
            ->prepare($website)
            ->process();
    }

    private function getFeatureFieldToFieldMapping($fields) {
        $featureFieldToFieldMapping = array();
        foreach($fields as $field) {
            if($field instanceof Unbxd_Recommendation_Model_Field &&
                $field->hasData(Unbxd_Recommendation_Model_Field::featured_field) &&
                !is_null($field->getData(Unbxd_Recommendation_Model_Field::featured_field))) {
                $featureFieldToFieldMapping[$field[Unbxd_Recommendation_Model_Field::featured_field]] = $field;
            }
        }
        return $featureFieldToFieldMapping;
    }

    private function buildFieldCollection($fields, $website) {
        $collection = array();
        $fieldMapping = $this->getFieldMapping($this->getFields($fields, $website));
        foreach($fields as $field) {
            if (!array_key_exists(Unbxd_Recommendation_Model_Field::field_name, $field)) {
                continue;
            }
            if(array_key_exists($field[Unbxd_Recommendation_Model_Field::field_name], $fieldMapping)) {
                $collection[]["delete"] = $fieldMapping[$field[Unbxd_Recommendation_Model_Field::field_name]];
            }
        }
        return $collection;
    }

    private function buildFieldCollectionToAdd($fields, $website) {
        $collection = array();
        $fieldMapping = $this->getFieldMapping($this->getFields($fields, $website));
        $featureFieldToFieldMapping = $this->getFeatureFieldToFieldMapping($fieldMapping);

        foreach($fields as $field) {
            if(!array_key_exists(Unbxd_Recommendation_Model_Field::field_name, $field)) {
                continue;
            }
            /*
            All possible test cases
             1) if field name is present and it was a feature field
             1.a) if request feature field is equal to selected feature field, dont do anything
             1.b) if request feature field is not equal to selected feature field, dont do anything
             1.c) if request field is not a feature field,
            remove the field name entry from the feature field row, save as different row.

             2) if field name is present and it was not a feature field
             2.a) if request field is a feature field,
                remove the field name entry as a normal field and save as feature field
             2.b) if request field is not a feature field,
            update the existing field

             3) if field name not present,
            save as a new field
            */

            // case 1
            if(array_key_exists($field[Unbxd_Recommendation_Model_Field::field_name], $fieldMapping ) &&
                $fieldMapping[$field[Unbxd_Recommendation_Model_Field::field_name]]->hasData(Unbxd_Recommendation_Model_Field::featured_field) &&
                !is_null($fieldMapping[$field[Unbxd_Recommendation_Model_Field::field_name]]->getData(Unbxd_Recommendation_Model_Field::featured_field))) {
                //case 1 a)
                if (array_key_exists(Unbxd_Recommendation_Model_Field::featured_field, $field) &&
                    $field[Unbxd_Recommendation_Model_Field::featured_field] ==
                    $fieldMapping[$field[Unbxd_Recommendation_Model_Field::field_name]][Unbxd_Recommendation_Model_Field::featured_field]) {
                    continue;
                }
                // case 1 b)
                else if (array_key_exists(Unbxd_Recommendation_Model_Field::featured_field, $field)) {
                    $collection[]["delete"] = $featureFieldToFieldMapping[$field[Unbxd_Recommendation_Model_Field::featured_field]];
                    $collection[]["delete"] = $fieldMapping[$field[Unbxd_Recommendation_Model_Field::field_name]];
                    $fieldModel = Mage::getModel("unbxd_recommendation/field");
                    $fieldModel->setFeaturedField($field[Unbxd_Recommendation_Model_Field::featured_field]);
                }

                //case 1 c)
                else {
                    $collection[]["delete"] = $fieldMapping[$field[Unbxd_Recommendation_Model_Field::field_name]];
                    $fieldModel = Mage::getModel("unbxd_recommendation/field");

                }
            } else if(array_key_exists($field[Unbxd_Recommendation_Model_Field::field_name], $fieldMapping)) {
                //case 2 a)
                if (array_key_exists(Unbxd_Recommendation_Model_Field::featured_field, $field)) {
                    $collection[]["delete"] = $fieldMapping[$field[Unbxd_Recommendation_Model_Field::field_name]];
                    $fieldModel = Mage::getModel("unbxd_recommendation/field");
                    $fieldModel->setFeaturedField($field[Unbxd_Recommendation_Model_Field::featured_field]);
                }
                // case 2 b)
                else {
                    $fieldModel = $fieldMapping[$field[Unbxd_Recommendation_Model_Field::field_name]];
                }
            } else {
                $fieldModel = Mage::getModel("unbxd_recommendation/field");
                if (array_key_exists(Unbxd_Recommendation_Model_Field::featured_field, $field)) {
                    $fieldModel->setFeaturedField($field[Unbxd_Recommendation_Model_Field::featured_field]);
                }

            }
            $fieldModel->setFieldName($field[Unbxd_Recommendation_Model_Field::field_name]);
            $fieldModel->setDatatype($field[Unbxd_Recommendation_Model_Field::datatype]);
            $fieldModel->setAutosuggest(0);
            $fieldModel->setWebsiteId($website->getWebsiteId());
            $fieldModel->setDisplayed(1);
            $collection[]["add"] = $fieldModel;
        }
        return $collection;
    }

    /**
     * Method to getFields, if
     *
     * @param $fields
     * @return mixed
     */
    private function getFields($fields, $website) {
        $inField = array();
        foreach($fields as $field) {
            $inField[] = "'" .$field[Unbxd_Recommendation_Model_Field::field_name]. "'";
        }
        $collection = Mage::getResourceModel("unbxd_recommendation/field_collection");

        $collection->getSelect()->where('(' . Unbxd_Recommendation_Model_Field::field_name  . ' in ('. implode(",", $inField). ')'. " OR ".
            Unbxd_Recommendation_Model_Field::featured_field. " IS NOT NULL) AND ".
            Unbxd_Recommendation_Model_Field::website_id . " = " . $website->getWebsiteId());
        return $collection->load();
    }


    /**
     * Method to update feature fields to unbxd
     *
     * @return bool| array
     */
    public function updateFeatureFields(Mage_Core_Model_Website $website) {
        $response = Mage::getModel("unbxd_recommendation/api_task_updatefeaturefields")
            ->prepare($website)
            ->process();
        if(! $response->isSuccess()) {
            Mage::log(Zend_Log::ERR,
                "Update feature fields failed because of theses errors ".json_encode($response->getErrors()));
            return $response->getErrors();
        }
        return true;
    }

    public function getNumberOfDocsInUnbxd(Mage_Core_Model_Website $website) {
        $response = Mage::getModel('unbxd_recommendation/api_task_feeddetails')
            ->prepare($website)
            ->process();
        if($response->isSuccess()) {
            $response = $response->getResponse();
            $feedInfo = $response[Unbxd_Recommendation_Model_Api_Task_Feeddetails::FEEDINFO];
            return $feedInfo[Unbxd_Recommendation_Model_Api_Task_Feeddetails::NUMDOCS];
        }
        return 0;
    }
}
?>
