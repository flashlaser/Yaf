<?php
/**
 * URL相关处理
 *
 * @package Helper
 * @author  baojun <baojun4545@sina.com>
 */

abstract class Helper_Url {

    /**
	 * 通过一个字符串获取一串URL
	 * 
	 * @param string  $string 字符串
	 * @param boolean $unique 是否消重
	 * 
	 * @return	array
	 */
    static public function getUrls($string, $unique = true) {
        $preg = '/http:\/\/[-\w.&?=\/%+:;]+/is';
        preg_match_all($preg, $string, $matches);
        $matches_result = isset($matches[0]) ? $matches[0] : array();
        $unique && $matches_result = array_unique($matches[0]);
        return $matches_result;
    }

    /**
     * 为给定的URL加参数
     * 
     * @param string $url       url
     * @param string $param_str param string 
     * 
     * @return string
     */
    static public function addParams($url, $param_str) {
        if (!$param_str) {
        	return $url;
        }
        
        $parse_str = parse_url($url);
        if (isset($parse_str['query'])) {
            parse_str($parse_str['query'], $params);
            if (!isset($params['from'])) {
                $url = $url . '&' . $param_str;
            }
        } else {
            $url = $url . '?' . $param_str;
        }
        return $url;
    }

}
