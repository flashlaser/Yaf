<?php

/**
 * 异常错误处理类
 *
 * @package Controller
 * @author  baojun <baojun4545@sina.com>
 */

class ErrorController extends Abstract_Controller_Default{

    /**
     * 当前请求是否是AJAX请求
     * @var	boolean
     */
    protected $is_ajax = false;

    /**
     * 修复未登录访问
     * 
     * @return null
     */
    public function init() {
        $this->allow_no_login = true;
        parent::init();
    }

    /**
     * 处理异常
     * 
     * @param Exception $exception exception 
     * 
     * @return boolean
     */
    public function errorAction($exception) {
var_dump($exception);
        Yaf_Registry::set('noAllGuide', true);
        $this->exception = $exception;
        $request = Yaf_Dispatcher::getInstance()->getRequest();
        $this->is_ajax = strpos(strtolower($request->getRequestUri()), '/aj_') === 0;

        if ($exception instanceof ErrorException) {
            $this->debug($exception);
        } elseif ($exception instanceof Exception_Nologin) {
            if ($this->is_ajax) {
                $this->showError($exception, '100002', 'no login');
            } else {
                header('Location:' . Comm_Config::get('app.site.weibo') . 'login.php?url=' . urlencode('http://' . $request->getServer('HTTP_HOST') . $request->getServer('REQUEST_URI')));
            }
        } elseif ($exception instanceof Yaf_Exception_LoadFailed) {
            $code = 303404;
            $msg = Comm_I18n::text('errcode.' . $code);
            $this->showError($exception, $code, $msg, '3');
        } elseif ($exception instanceof Exception_System) {
            $code = $exception->getCode();
            $msg = Comm_I18n::text('errcode.100001');
            $this->showError($exception, $code, $msg, '1');
        } else {
            $this->showError($exception, null, null, '2');
        }

        return false;
    }

    /**
     * 显示错误
     * 
     * @param object $e     exception 
     * @param int    $code  code
     * @param string $msg   msg
     * @param int    $style 样式（1.放屁，2.想象，3.沙漠）
     * 
     * @return mixed
     */
    protected function showError(Exception $e, $code = null, $msg = null, $style = '2') {
        $code === null && $code = $e->getCode();
        $msg === null && $msg = $e->getMessage();
        if (method_exists($e, 'getMetadata')) {
            $metadata = $e->getMetadata();
        } else {
            $metadata = array();
        }

        if ($this->is_ajax) { //AJAX处理
            header("Content-type: application/json");

            $response = array(
                'code' => $code,
                'msg' => $msg,
                'data' => array(),
            );

            //附加调试信息
            if (Helper_Debug::isDebug()) {
                $response['_debug']['code'] = $e->getCode();
                $response['_debug']['message'] = $e->getMessage();
                $response['_debug']['file'] = $e->getFile() . ' (' . $e->getLine() . ')';
                $response['_debug']['trace'] = explode("\n", $e->getTraceAsString());
                if ($e instanceof Exception_Abstract) {
                    $response['_debug']['metadata'] = $e->getMetadata();
                }
                $metadata && $response['_debug']['metadata'] = $metadata;
            }

            echo json_encode($response);
        } else {    //页面处理
            if (Helper_Debug::isDebug()) {
                Helper_Debug::error($e, FirePHP::WARN);
                if ($e instanceof Exception_Abstract) {
                    $debug_data = array(
                        'code' => $code,
                        'msg' => $msg,
                        'metadata' => $metadata,
                    );
                    Helper_Debug::error($debug_data, 'Exception_info', FirePHP::WARN);
                }
            }

            $error_page_time = isset($metadata['error_page_time']) ? $metadata['error_page_time'] : 5;
            $error_data = array(
                //'err_style'			=> $style,
                'msg' => $msg,
                'code' => $code,
                'error_page_time' => $error_page_time,
            );
            $this->display('error', $error_data);
        }
    }

    /**
     * 显示调试信息
     * 
     * @param Exception $exception exception 
     * 
     * @return boolean
     */
    public function debug($exception) {
        try {
            $type = get_class($exception);
            $code = $exception->getCode();
            $message = $exception->getMessage();
            $file = $exception->getFile();
            $line = $exception->getLine();
            $exception_txt = Helper_Error::exceptionText($exception);

            $trace = $exception->getTrace();
            if ($exception instanceof ErrorException) {
                // 替换为human readable
                $code = Helper_Error::showType($code);

                if (version_compare(PHP_VERSION, '5.3', '<')) {
                    // 修复php 5.2下关于getTrace的bug
                    //@TODO bug url
                    for ($i = count($trace) - 1; $i > 0; --$i) {
                        if (isset($trace[$i - 1]['args'])) {
                            $trace[$i]['args'] = $trace[$i - 1]['args'];

                            unset($trace[$i - 1]['args']);
                        }
                    }
                }
            }

            $err_data = array(
                'type' => $type,
                'code' => $code,
                'message' => $message,
                'file' => $file,
                'line' => $line,
                'exception_txt' => $exception_txt,
                'trace' => $trace,
            );
            $this->getView()->assign($err_data);
            $this->display('debug');
        } catch (Exception $exception) {
            var_dump($exception);
            return false;
        }
    }

}
