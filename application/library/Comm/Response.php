<?php
/**
 * 输出结构
 *
 * @package Comm
 * @author  baojun <baojun4545@sina.com>
 */

abstract class Comm_Response{
    //响应体类型（JSON）

    const TYPE_JSON = 'json';
    
    //响应体类型（JS）
    const TYPE_JS = 'js';

    /**
     * 输出响应类型
     * 
     * @param type $type Type
     * 
     * @return bool
     */
    static public function contentType($type) {
        if (headers_sent()) {
            return false;
        }
        switch ($type) {
            case 'json' :
                header('Content-type: application/json');
                break;
            case 'html' :
                header('Content-type: text/html; charset=utf-8');
                break;
            case 'js' :
                header('text/javascript; charset=utf-8');
                break;
            case 'jpg' :
                header('Content-Type: image/jpeg');
                break;
        }
        return true;
    }

    /**
     * 输出一段JSON
     * 
     * @param type $code   code
     * @param type $msg    msg
     * @param type $data   data 
     * @param type $return return 
     * 
     * @return boolean
     */
    static public function json($code, $msg, $data = null, $return = true) {
        $result = json_encode(array('code' => $code, 'msg' => $msg, 'data' => $data));
        if ($return) {
            return $result;
        } else {
            echo $result;
            return true;
        }
    }
    
    /**
     * 返回一段JSONP数据
     * 
	 * @param int    $code code 
	 * @param string $msg  msg
	 * @param mixed  $data data 
	 * 
	 * @return    string
     */
    static public function jsonp($code, $msg, $data = null) {
        $result = array(
            'code'   => $code,
            'msg'    => $msg,
            'data'   => $data,
        );
        
        $r = Yaf_Dispatcher::getInstance()->getRequest();
        $result['key'] = Comm_Argchecker::string($r->getQuery('_ck'), 'alnumu', 2, 3, null);
        $callback = Comm_Argchecker::string($r->getQuery('_cb'), 'alnumu', 2, 3, 'callback');
        
        $response = "window.{$callback} && {$callback}(".json_encode($result).");";
        return $response;
    }

}
