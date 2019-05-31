<?php

/**
 * 默认异常处理控制器
 *
 * @package    Internal
 * @author     baojun <baojun4545@sina.com>
 */
class ErrorController extends Yaf_Controller_Abstract {

    /**
     * 默认异常处理控制器
     * @param Exception $exception
     * @return boolean
     */
    public function ErrorAction(Exception $exception) {
        switch (Yaf_Registry::get('internal_api_type')) {
            default :
                //内部接口异常
                $this->_default($exception);
                break;
        }


        return false;
    }

    /**
     * 默认内部接口处理异常方法
     * @param Exception $exception
     */
    protected function _default(Exception $exception) {
var_dump($exception->getMessage());die;

    	Comm_Response::contentType(Comm_Response::TYPE_JSON);
    	$code = $exception->getCode();
    	if($exception instanceof Yaf_Exception_LoadFailed) {
    		//页面未找到
    		$error_info = array(
    				'msg' => 'Request Api not found !',
    				'status' => 100404,
    		);
    	} else {
	        $error_info = array(
	        	'status' => $code,
	            'msg' => $exception->getMessage(),
	        );
    	}
    	
    	//附加调试模式
    	if (Helper_Debug::isDebug()) {
    		$error_info['_debug']['message'] = $exception->getMessage();
    		$error_info['_debug']['code'] = $exception->getCode();
    		$error_info['_debug']['trace'] = $exception->getTrace();
    	
    		if (method_exists($exception, 'getMetadata')) {
    			$error_info['_debug']['metadata'] = $exception->getMetadata();
    		}
    	}
    	
        echo json_encode($error_info);
    }

}
