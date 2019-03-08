<?php
/**
 * 首页
 * 
 * @package Index
 * @author  baojun <zhangbaojun@yixia.com>
 */

class IndexController extends Abstract_Controller_Default{
    protected $allow_no_login = true;
    private $_languageID = 1;
    
    /**
     * 是否获取当前用户信息
     *
     * @var boolean
     */
    protected $fetch_current_user = false;
    
    /**
     * 首页
     *
     * @return void
     */
    public function indexAction() {
        $_var = array (
                'aaa' => 'just test index' 
        );
        $this->getView()->assign($_var);
    }
}