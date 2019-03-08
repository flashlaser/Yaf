<?php
/**
 * JSON处理
 *
 * @package Helper
 * @author  baojun <baojun4545@sina.com>
 */

abstract class Helper_Json {

    /**
     * encode 
     * 
     * @param array $data data
     * 
     * @return string
     */
    static public function encode($data) {
        $json_data       = new JsonData($data);
        $utf16_diff_data = $json_data->utf16DiffData();

        $result = json_encode($data);
        $result = str_replace(array_keys($utf16_diff_data), $utf16_diff_data, $result);
        return $result;
    }
}

class JsonData{

    protected $data;
    protected $utf16_data_hash;

    /**
     * contruct 
     * 
     * @param array $data data
     * 
     * @return void
     */
    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * utf16 diff data 
     * 
     * @return string
     */
    public function utf16DiffData() {
        array_walk_recursive($this->data, array($this, 'getHashData'));
        return $this->utf16_data_hash;
    }

    /**
     * get hash data 
     * 
     * @param string $item item
     * 
     * @return void
     */
    public function getHashData($item) {
        $json_encode_item = json_encode($item);
        if ($json_encode_item != $item) {
            is_string($item) && $item = '"' . str_replace(array('"', "\n", "\r", "\t"), array('\"', '\n', '\r', '\t'), $item) . '"';
            $this->utf16_data_hash[$json_encode_item] = $item;
        }
    }

}