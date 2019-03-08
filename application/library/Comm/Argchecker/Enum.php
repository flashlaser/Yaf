<?php
/**
 * 参数检查
 *
 * @package comm
 * @author  baojun <baojun4545@sina.com>
 */
class Comm_Argchecker_Enum {

    /**
     * enum 
     * 
     * @param unknown $data       data 
     * @param unknown $enumerates enumerates
     * 
     * @return boolean
     */
    public static function enum($data, $enumerates) {
        $args = func_get_args();
        array_shift($args);

        return in_array(strval($data), $args, true);
    }

}