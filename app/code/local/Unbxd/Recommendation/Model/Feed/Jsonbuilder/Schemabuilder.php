<?php

class Unbxd_Recommendation_Model_Feed_Jsonbuilder_Schemabuilder extends Unbxd_Recommendation_Model_Feed_Jsonbuilder_Jsonbuilder {


	const FIELD_NAME = "fieldName";
	const DATA_TYPE = "dataType";
	const MULTIVALUED = "multiValued";
	const AUTOSUGGEST = "autoSuggest";
	const TRUE = "true";
	const FALSE = "false";

	public function getSchema($fields) {
		$fieldList = array();
		foreach($fields as $fieldName=>$values ) {
			$fieldList[] = array(self::FIELD_NAME => $fieldName,
            self::DATA_TYPE => $values[Unbxd_Recommendation_Model_Field::datatype],
            self::MULTIVALUED =>
                (array_key_exists(Unbxd_Recommendation_Model_Field::multivalued, $values)
                    && $values[Unbxd_Recommendation_Model_Field::multivalued])?
                    self::TRUE:self::FALSE,
            self::AUTOSUGGEST => ($values[Unbxd_Recommendation_Model_Field::autosuggest] == 1?self::TRUE: self::FALSE));

		}
		return json_encode($fieldList);
	}
}

?>