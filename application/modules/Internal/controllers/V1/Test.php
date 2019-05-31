<?php
/**
 * 测试相关接口
 * curl -H "Host:i.api.qiuxin.tech" "http://127.0.0.1/1/test/run.json"
 *
 * @package controller
 * @author  zhangbaojun  <zhangbaojun@qiuxinpay.com>
 */

class V1_TestController extends Abstract_Controller_Internal{
    
    /**
     * 初始化方法
     *
     * {@inheritdoc}
     *
     * @see Abstract_Controller_Cli::init()
     */
    public function init() {
        parent::init();
        
        // 具体的Action映射
        $controller= $this->getRequest()->getControllerName();
        $controller_arr = explode('_', $controller);
        if (isset($controller_arr[1])) {
            $controller_name = $controller_arr[1];
        } else {
            $controller_name = $controller_arr[0];
        }
        $action = $this->getRequest()->getActionName();
        $action_arr = explode('_', $action);
        if (isset($action_arr[1])) {
            $this->actions = array (
                $action => self::ACTION_DIR . $controller_name . '/' . ucfirst($action_arr[0]) . '/' . ucfirst($action_arr[1]) . '.php',
            );
        } else {
            $this->actions = array (
                $action => self::ACTION_DIR . $controller_name . '/' . ucfirst($action) . '.php',
            );
        }
    }
    
    /**
     * test
     *
     * @var string
     */
    public $_test;
    
    /**
     * run
     */
    public function runAction() {
        //$r = $this->getRequest();
        
        $ret = array('test'=> 'hello world', 'max_execution_time' => ini_get('max_execution_time'));
        
        $this->result($ret);
    }
}
