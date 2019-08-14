<?php

/**
 * @category Unbxd
 * @package Unbxd_Recscore
 * @author Unbxd Software Pvt. Ltd
 */
class Unbxd_Recscore_Helper_Confighelper extends Unbxd_Recscore_Helper_Data
{

    const SITE_KEY = "site_key";

    const API_KEY = "api_key";

    const SECRET_KEY = "secret_key";

    const USERNAME = "username";

    const NEED_FEATURE_FIELD_UPDATION = "need_feature_field_updation";

    const IS_CRON_ENABLED = "cron_enabled";

    const SUBJECT = 'subject';

    const CONTENT = 'content';

    const CC = 'cc';


    /**
     * All possible data type values supported unbxd
     * @var array
     */
    public static $data_types = array("text", "longText", "link", "decimal", "number", "datetime");

    public function validateAndSaveKeys($website, $requestBody)
    {
        $errors = $this->validateKeyParams($requestBody);
        if (sizeof($errors) > 0) {
            return $errors;
        }
        $requestParams = json_decode($requestBody, true);
        if (!$requestParams) {
            $errors['message'] = 'Invalid Request';
            return $errors;
        }
        $response = Mage::getModel("unbxd_recscore/api_task_validatekeys")
            ->setData(Unbxd_Recscore_Model_Api_Task_Validatekeys::SECRET_KEY, $requestParams[self::SECRET_KEY])
            ->setData(Unbxd_Recscore_Model_Api_Task_Validatekeys::SITE_KEY, $requestParams[self::SITE_KEY])
            ->prepare($website)
            ->process();
        if (!$response->isSuccess()) {
            return $response->getErrors();
        }

        $existingSecretKey = Mage::getResourceModel('unbxd_recscore/config')
            ->getValue($website->getWebsiteId(), Unbxd_Recscore_Helper_Constants::SECRET_KEY);
        $keyAlreadyExists = !is_null($existingSecretKey);
        if ($keyAlreadyExists) {
            $this->flushConfigs($website);
        }

        Mage::getResourceModel('unbxd_recscore/config')
            ->setValue($website->getWebsiteId(), Unbxd_Recscore_Helper_Constants::SECRET_KEY,
                $requestParams[Unbxd_Recscore_Helper_Constants::SECRET_KEY]);
        Mage::getResourceModel('unbxd_recscore/config')
            ->setValue($website->getWebsiteId(), Unbxd_Recscore_Helper_Constants::SITE_KEY,
                $requestParams[Unbxd_Recscore_Helper_Constants::SITE_KEY]);
        $response = $response->getResponse();
        Mage::getResourceModel('unbxd_recscore/config')
            ->setValue($website->getWebsiteId(),
                Unbxd_Recscore_Helper_Constants::API_KEY,
                $response[Unbxd_Recscore_Model_Api_Task_Validatekeys::API_KEY]);
        Mage::getResourceModel('unbxd_recscore/config')
            ->setValue($website->getWebsiteId(),
                Unbxd_Recscore_Helper_Constants::USERNAME,
                $response[Unbxd_Recscore_Model_Api_Task_Validatekeys::USERNAME]);
        $this->saveConfig(Mage::app()->getWebsite(),
            array(Unbxd_Recscore_Helper_Constants::API_KEY => $response[Unbxd_Recscore_Model_Api_Task_Validatekeys::API_KEY],
                Unbxd_Recscore_Helper_Constants::SITE_KEY => $requestParams[Unbxd_Recscore_Helper_Constants::SITE_KEY]));
        return $errors;
    }

    public function flushConfigs($website)
    {
        Mage::helper('unbxd_recscore')->log(Zend_Log::DEBUG, 'Flushing all the configs');
        $configs = $this->getEngineConfigData('', $website, true);
        foreach ($configs as $config => $value) {
            Mage::getConfig()->deleteConfig(Unbxd_Recscore_Helper_Constants::UNBXD_CONFIG_PREFIX .
                Unbxd_Recscore_Helper_Constants::CONFIG_SEPARATOR .
                $config,
                'websites',
                (int)$website->getWebsiteId());
        }
        Mage::getResourceModel('unbxd_recscore/config')->deleteAll($website->getWebsiteId());

    }

    public function validateKeyParams($requestBody)
    {
        $errors = array();
        $requestParams = json_decode($requestBody, true);
        if (!$requestParams) {
            Mage::helper('unbxd_recscore')->log(Zend_Log::ERR, 'Invalid request with requestBody' . $requestBody);
            $errors['message'] = 'Invalid Request';
            return $errors;
        }
        if (!array_key_exists(Unbxd_Recscore_Helper_Constants::SECRET_KEY, $requestParams)) {
            $errors[Unbxd_Recscore_Helper_Constants::SECRET_KEY] = "Has Empty Data";
        }
        if (!array_key_exists(Unbxd_Recscore_Helper_Constants::SITE_KEY, $requestParams)) {
            $errors[Unbxd_Recscore_Helper_Constants::SITE_KEY] = "Has Empty Data";
        }
        return $errors;
    }

    public function getFeatureFields()
    {
        return Unbxd_Recscore_Model_Field::$feature_fields;
    }

    public function getAllAttributes($fieldNameAsKey = false)
    {
        $attributes = Mage::getSingleton('eav/config')
            ->getEntityType(Mage_Catalog_Model_Product::ENTITY)->getAttributeCollection();
        $fields = array();
        foreach ($attributes as $attribute) {
            $attributeType = $attribute->getFrontendInput();
            $fieldType = $attributeType == 'media_image' ? Unbxd_Recscore_Helper_Constants::FIELD_TYPE_IMAGE :
                ($attributeType == 'price' ? Unbxd_Recscore_Helper_Constants::FIELD_TYPE_NUMBER :
                    ($attributeType == 'date' ? Unbxd_Recscore_Helper_Constants::FIELD_TYPE_DATE :
                        Unbxd_Recscore_Helper_Constants::FIELD_TYPE_STRING));
            $fieldType = ($attribute->getName() == "created_at") ? Unbxd_Recscore_Helper_Constants::FIELD_TYPE_DATE : $fieldType;
            $fieldType = ($attribute->getName() == "updated_at") ? Unbxd_Recscore_Helper_Constants::FIELD_TYPE_DATE : $fieldType;
            if ($fieldNameAsKey) {
                $fields[$attribute->getName()] = array(Unbxd_Recscore_Helper_Constants::FIELD_NAME => $attribute->getName(),
                    Unbxd_Recscore_Helper_Constants::FIELD_TYPE => $fieldType);
            } else {
                $fields[] = array(Unbxd_Recscore_Helper_Constants::FIELD_NAME => $attribute->getName(),
                    Unbxd_Recscore_Helper_Constants::FIELD_TYPE => $fieldType);
            }
        }
        if ($fieldNameAsKey) {
            $fields['final_price'] = array(Unbxd_Recscore_Helper_Constants::FIELD_NAME => "final_price",
                Unbxd_Recscore_Helper_Constants::FIELD_TYPE => Unbxd_Recscore_Helper_Constants::FIELD_TYPE_NUMBER);
            $fields['type_id'] = array(Unbxd_Recscore_Helper_Constants::FIELD_NAME => "type_id",
                Unbxd_Recscore_Helper_Constants::FIELD_TYPE => Unbxd_Recscore_Helper_Constants::FIELD_TYPE_STRING);
        } else {
            $fields[] = array(Unbxd_Recscore_Helper_Constants::FIELD_NAME => "final_price",
                Unbxd_Recscore_Helper_Constants::FIELD_TYPE => Unbxd_Recscore_Helper_Constants::FIELD_TYPE_NUMBER);
            $fields[] = array(Unbxd_Recscore_Helper_Constants::FIELD_NAME => "type_id",
                Unbxd_Recscore_Helper_Constants::FIELD_TYPE => Unbxd_Recscore_Helper_Constants::FIELD_TYPE_STRING);
        }
        return $fields;
    }

    private function getFieldMapping($fields)
    {
        $fieldMapping = array();
        foreach ($fields as $field) {
            $fieldMapping[$field->getFieldName()] = $field;
        }
        return $fieldMapping;
    }

    /**
     * @param $fields
     * @return array
     */
    private function validate($fields)
    {
        $errors = array();
        if (!is_array($fields)) {
            $errors["message"] = "Expecting theInput data should be an array, Given " . gettype($fields);
            return $errors;
        }
        $existingAttributes = $this->getAllAttributes(true);
        $featureFields = Mage::getModel('unbxd_recscore/field')->getFeaturedFields();
        foreach ($fields as $field) {
            if (!array_key_exists(Unbxd_Recscore_Model_Field::field_name, $field)) {
                $errors["extra"] = "Not Present for all the fields";
                continue;
            } else if (is_null($field[Unbxd_Recscore_Model_Field::field_name]) ||
                $field[Unbxd_Recscore_Model_Field::field_name] == ""
            ) {
                $errors["extra"] = "field Name is empty for some fields";
                continue;
            }
            if (!array_key_exists(Unbxd_Recscore_Model_Field::datatype, $field)) {
                $errors[$field[Unbxd_Recscore_Model_Field::field_name]] = "Not Present for all the fields";
            } else if (!in_array($field[Unbxd_Recscore_Model_Field::datatype], Unbxd_Recscore_Model_Field::$data_types)) {
                Mage::helper('unbxd_recscore')->log(Zend_Log::ERR, 'Invalid feature field ' .
                    $field[Unbxd_Recscore_Model_Field::datatype]);
                $errors[$field[Unbxd_Recscore_Model_Field::field_name]] = "Invalid datatype specified";
            }

            if (array_key_exists($field[Unbxd_Recscore_Model_Field::field_name], $existingAttributes)) {
                if (!Mage::getSingleton('unbxd_recscore/field')->validateDatatype($field[Unbxd_Recscore_Model_Field::datatype], $existingAttributes[$field[Unbxd_Recscore_Model_Field::field_name]][Unbxd_Recscore_Helper_Constants::FIELD_TYPE])) {
                    $errors[$field[Unbxd_Recscore_Model_Field::field_name]] = "Field cannot be mapped to " . $field[Unbxd_Recscore_Model_Field::datatype];
                }
            }

            if (array_key_exists(Unbxd_Recscore_Model_Field::featured_field, $field)) {
                if (!array_key_exists($field[Unbxd_Recscore_Model_Field::featured_field], $featureFields)) {
                    Mage::helper('unbxd_recscore')->log(Zend_Log::ERR, 'Invalid feature field ' .
                        $field[Unbxd_Recscore_Model_Field::featured_field]);
                    $errors[$field[Unbxd_Recscore_Model_Field::field_name]] = "Invalid feature field specified";
                } else if (!Mage::getSingleton('unbxd_recscore/field')->validateDatatype($featureFields[$field[Unbxd_Recscore_Model_Field::featured_field]]["datatype"], $existingAttributes[$field[Unbxd_Recscore_Model_Field::field_name]][Unbxd_Recscore_Helper_Constants::FIELD_TYPE])) {
                    $errors[$field[Unbxd_Recscore_Model_Field::field_name]] = "Field cannot be mapped to " . $field[Unbxd_Recscore_Model_Field::datatype];
                }
            }
        }
        return $errors;
    }

    public function deleteFields($fields, $website)
    {
        $errors = $this->validate($fields);
        if (sizeof($errors) != 0) {
            return $errors;
        }
        $collection = $this->buildFieldCollection($fields, $website);
        return Mage::getModel("unbxd_recscore/field")->saveFields($collection);
    }

    /**
     * @param $fields
     * @param $website
     * @return array
     */
    public function saveFields($fields, $website)
    {
        $errors = $this->validate($fields);
        if (sizeof($errors) != 0) {
            return $errors;
        }
        $collection = $this->buildFieldCollectionToAdd($fields, $website);
        $response = Mage::getModel("unbxd_recscore/field")->saveFields($collection);
        if (!is_array($response) && $response === true) {
            Mage::getSingleton('unbxd_recscore/field')->rebuildConfigCache($website);
            $this->triggerUpdateFeatureField($website);
        }
    }


    public function triggerUpdateFeatureField(Mage_Core_Model_Website $website)
    {
        Mage::getResourceModel('unbxd_recscore/config')
            ->setValue($website->getWebsiteId(),
                Unbxd_Recscore_Helper_Constants::NEED_FEATURE_FIELD_UPDATION,
                Unbxd_Recscore_Helper_Constants::NEED_FEATURE_FIELD_UPDATION_TRUE);
        $this->triggerFeedUpload($website);
    }

    /**
     * Method to trigger feed upload
     * @param Mage_Core_Model_Website $website
     * @return void
     */
    public function triggerFeedUpload(Mage_Core_Model_Website $website)
    {
        Mage::getModel('unbxd_recscore/api_task_triggerfeedupload')
            ->prepare($website)
            ->process();
    }


    private function getFeatureFieldToFieldMapping($fields)
    {
        $featureFieldToFieldMapping = array();
        foreach ($fields as $field) {
            if ($field instanceof Unbxd_Recscore_Model_Field &&
                $field->hasData(Unbxd_Recscore_Model_Field::featured_field) &&
                !is_null($field->getData(Unbxd_Recscore_Model_Field::featured_field))
            ) {
                $featureFieldToFieldMapping[$field[Unbxd_Recscore_Model_Field::featured_field]] = $field;
            }
        }
        return $featureFieldToFieldMapping;
    }

    private function buildFieldCollection($fields, $website)
    {
        $collection = array();
        $fieldMapping = $this->getFieldMapping($this->getFields($fields, $website));
        foreach ($fields as $field) {
            if (!array_key_exists(Unbxd_Recscore_Model_Field::field_name, $field)) {
                continue;
            }
            if (array_key_exists($field[Unbxd_Recscore_Model_Field::field_name], $fieldMapping)) {
                $collection[]["delete"] = $fieldMapping[$field[Unbxd_Recscore_Model_Field::field_name]];
            }
        }
        return $collection;
    }

    private function buildFieldCollectionToAdd($fields, $website)
    {
        $collection = array();
        $fieldMapping = $this->getFieldMapping($this->getFields($fields, $website));
        $featureFieldToFieldMapping = $this->getFeatureFieldToFieldMapping($fieldMapping);

        foreach ($fields as $field) {
            if (!array_key_exists(Unbxd_Recscore_Model_Field::field_name, $field)) {
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
	     3.a) if it has feature field, delete it from db and insert the new field
             3.b) save as a new field
            */

            // case 1
            if (array_key_exists($field[Unbxd_Recscore_Model_Field::field_name], $fieldMapping) &&
                $fieldMapping[$field[Unbxd_Recscore_Model_Field::field_name]]->hasData(Unbxd_Recscore_Model_Field::featured_field) &&
                !is_null($fieldMapping[$field[Unbxd_Recscore_Model_Field::field_name]]->getData(Unbxd_Recscore_Model_Field::featured_field))
            ) {
                //case 1 a)
                if (array_key_exists(Unbxd_Recscore_Model_Field::featured_field, $field) &&
                    $field[Unbxd_Recscore_Model_Field::featured_field] ==
                    $fieldMapping[$field[Unbxd_Recscore_Model_Field::field_name]][Unbxd_Recscore_Model_Field::featured_field]
                ) {
                    continue;
                } // case 1 b)
                else if (array_key_exists(Unbxd_Recscore_Model_Field::featured_field, $field)) {
                    $collection[]["delete"] = $featureFieldToFieldMapping[$field[Unbxd_Recscore_Model_Field::featured_field]];
                    $collection[]["delete"] = $fieldMapping[$field[Unbxd_Recscore_Model_Field::field_name]];
                    $fieldModel = Mage::getModel("unbxd_recscore/field");
                    $fieldModel->setFeaturedField($field[Unbxd_Recscore_Model_Field::featured_field]);
                } //case 1 c)
                else {
                    $collection[]["delete"] = $fieldMapping[$field[Unbxd_Recscore_Model_Field::field_name]];
                    $fieldModel = Mage::getModel("unbxd_recscore/field");

                }
            } else if (array_key_exists($field[Unbxd_Recscore_Model_Field::field_name], $fieldMapping)) {
                //case 2 a)
                if (array_key_exists(Unbxd_Recscore_Model_Field::featured_field, $field)) {
                    $collection[]["delete"] = $fieldMapping[$field[Unbxd_Recscore_Model_Field::field_name]];
                    $fieldModel = Mage::getModel("unbxd_recscore/field");
                    $fieldModel->setFeaturedField($field[Unbxd_Recscore_Model_Field::featured_field]);
                } // case 2 b)
                else {
                    $fieldModel = $fieldMapping[$field[Unbxd_Recscore_Model_Field::field_name]];
                }
            } else {
                $fieldModel = Mage::getModel("unbxd_recscore/field");
                if (array_key_exists(Unbxd_Recscore_Model_Field::featured_field, $field)) {
                    $fieldModel->setFeaturedField($field[Unbxd_Recscore_Model_Field::featured_field]);
                    // case 3 a)
                    if (array_key_exists($field[Unbxd_Recscore_Model_Field::featured_field], $featureFieldToFieldMapping)) {
                        $collection[]["delete"] = $featureFieldToFieldMapping[$field[Unbxd_Recscore_Model_Field::featured_field]];
                    }
                }

            }
            $fieldModel->setFieldName($field[Unbxd_Recscore_Model_Field::field_name]);
            $fieldModel->setDatatype($field[Unbxd_Recscore_Model_Field::datatype]);
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
    private function getFields($fields, $website)
    {
        $inField = array();
        foreach ($fields as $field) {
            if ($field[Unbxd_Recscore_Model_Field::field_name] == "") {
                continue;
            }
            $inField[] = "'" . $field[Unbxd_Recscore_Model_Field::field_name] . "'";
        }
        $collection = Mage::getResourceModel("unbxd_recscore/field_collection");

        $collection->getSelect()
            ->where('(' . Unbxd_Recscore_Model_Field::field_name . ' in (' . implode(",", $inField) . ')' . " OR " .
                Unbxd_Recscore_Model_Field::featured_field . " IS NOT NULL) AND " .
                Unbxd_Recscore_Model_Field::website_id . " = " . $website->getWebsiteId());
        return $collection->load();
    }


    /**
     * Method to update feature fields to unbxd
     *
     * @return bool| array
     */
    public function updateFeatureFields(Mage_Core_Model_Website $website)
    {
        $response = Mage::getModel("unbxd_recscore/api_task_updatefeaturefields")
            ->prepare($website)
            ->process();
        if (!$response->isSuccess()) {
            Mage::log(Zend_Log::ERR,
                "Update feature fields failed because of theses errors " . json_encode($response->getErrors()));
            return $response->getErrors();
        }
        return true;
    }

    public function getNumberOfDocsInUnbxd(Mage_Core_Model_Website $website)
    {
        $response = Mage::getModel('unbxd_recscore/api_task_feeddetails')
            ->prepare($website)
            ->process();
        if ($response->isSuccess()) {
            $response = $response->getResponse();
            $feedInfo = $response[Unbxd_Recscore_Model_Api_Task_Feeddetails::FEEDINFO];
            return $feedInfo[Unbxd_Recscore_Model_Api_Task_Feeddetails::NUMDOCS];
        }
        return 0;
    }

    /**
     * @param Mage_Core_Model_Website $website
     * @return void
     */
    public function triggerAutoggestIndexing(Mage_Core_Model_Website $website)
    {
        if (Mage::helper('core')->isModuleEnabled('Unbxd_Searchcore') &&
            $this->isConfigTrue($website, Unbxd_Recscore_Helper_Constants::AUTOSUGGEST_STATUS)
        ) {
            //trigger Autosuggest
            $response = Mage::getModel('unbxd_recscore/api_task_autosuggestindex')
                ->prepare($website)
                ->process();
        }
    }

    public function getCategoryExclusion(Mage_Core_Model_Website $website)
    {
        $conf = Mage::helper('unbxd_recscore')->getEngineConfigData(Unbxd_Recscore_Helper_Constants::EXCLUDE_CATEGORY, $website, true);
        $categoryExclusionConf = json_decode($conf[Unbxd_Recscore_Helper_Constants::EXCLUDE_CATEGORY], true);
        if (!is_array($categoryExclusionConf)) {
            return array();
        }
        $categoryToBeExcluded = array();
        foreach ($categoryExclusionConf as $eachExclusion) {
            $categoryToBeExcluded[] = (string)$eachExclusion;
        }
        return $categoryToBeExcluded;
    }

    public function getConfigData($name)
    {
        return (string)Mage::getConfig()->getNode("default/" . Unbxd_Recscore_Helper_Constants::UNBXD_CONFIG_PREFIX . "/" . $name);
    }
}

?>
