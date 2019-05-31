<?php

/**
 * PHPcs
 * 
 * @package   Application
 * @author    baojun <baojun4545@sina.com>
 * @copyright 2016 Yixia all rights reserved
 */
class PhpcsController extends Abstract_Controller_Default{
    protected $allow_no_login = true;
    
    /**
     * 是否获取当前用户信息
     *
     * @var boolean
     */
    protected $fetch_current_user = false;
    
    /**
     * index function
     *
     * @throws Exception_Msg
     * @return boolean
     */
    public function indexAction() {
        // 只允许在开发环境执行
        if (! Helper_Debug::isDebug ()) {
            throw new Exception_Msg ( '303404' );
        }
        
        $code_dir = $_SERVER ['DOCUMENT_ROOT'] . '/';
        $phpcs_path = '/usr/local/weibocs/bin/phpcs';
        $file_types = array (
                'php',
                'phtml' 
        );
        
        $phpcs = new Helper_Phpcs ( $phpcs_path, $code_dir, $file_types );
        $phpcs->run ();
        
        return false;
    }
}

