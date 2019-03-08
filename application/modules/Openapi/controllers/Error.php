<?php

/**
 * 接口报错统一处理
 *
 * @author baojun <baojun4545@sina.com>
 * @package openapi_controller
 */
class ErrorController extends Yaf_Controller_Abstract {

    /**
     * 异常处理方法
     * @param Exception $exception
     * @throws Exception
     * @return void
     */
    public function errorAction(Exception $exception) {
        if($exception instanceof Exception_System) {
            //系统错误
            $error_info = array(
                'msg' => 'oops, ufo comes up, please call for help or contact us',
                'status' => 100001,
            );
        } elseif($exception instanceof Exception_Nologin) {
			$error_info = array(
				'msg' => $exception->getMessage(),
				'status' => $exception->getCode(),
			);
        } elseif($exception instanceof Yaf_Exception_LoadFailed) {
            //页面未找到
            $error_info = array(
                'msg' => 'Request Api not found !',
                'status' => 100005,
            );
        } elseif($exception instanceof Exception_Openapi) {
            //OPEN API错误
            $metadata = $exception->getMetadata();
            $error_info = array(
                'msg' => $metadata['data']['error'],
                'status' => $metadata['data']['error_code'],
            );
        } else {
            //其它错误
            $code = $exception->getCode();
            
			$error_info = array(
				'msg' => $exception->getMessage(),
				'status' => $code,
			);
        }

        //附加当前URL
        $uri = $this->getRequest()->getServer('REQUEST_URI');
        list($request, ) = explode('?', $uri);
        $error_info['request'] = $request . '.json';

        //附加调试模式
        if (Helper_Debug::isDebug()) {
            $error_info['_debug']['message'] = $exception->getMessage();
            $error_info['_debug']['code'] = $exception->getCode();
            $error_info['_debug']['trace'] = $exception->getTrace();

            if (method_exists($exception, 'getMetadata')) {
                $error_info['_debug']['metadata'] = $exception->getMetadata();
            }
        }

        Comm_Response::contentType(Comm_Response::TYPE_JSON);
        echo json_encode($error_info);
        return false;
    }

}
