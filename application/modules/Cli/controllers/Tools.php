<?php

/**
 * SHELL工具
 *
 * @package Controller
 * @author  baojun <baojun4545@sina.com>
 */
class ToolsController extends Abstract_Controller_Cli{

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
        $action_name = $this->getRequest()->getActionName();
        
        $this->actions = array ($action_name => self::ACTION_DIR . 'Tools/' . ucfirst($action_name) . '.php');
    }
}