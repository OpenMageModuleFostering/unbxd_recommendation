<?php
/**
 * Created by IntelliJ IDEA.
 * User: anantheshadiga
 * Date: 1/29/15
 * Time: 12:25 PM
 */
class Unbxd_Recommendation_Model_Feed_Tags {
    const CATALOG = 'catalog';

    const ADD = 'add';

    const DELETE = 'delete';

    const OBJ_START = '{';

    const OBJ_END = '}';

    const ARRAY_START = '[';

    const ARRAY_END = ']';

    const COLON = ':';

    const COMMA = ',';

    const DOUBLE_QUOTE = '"';

    public function getKey($key) {
        return self::DOUBLE_QUOTE . $key . self::DOUBLE_QUOTE;
    }
}