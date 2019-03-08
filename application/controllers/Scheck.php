<?php 
/**
 * 资源检测工具
 *
 * @package Index
 * @author  baojun <zhangbaojun@yixia.com>
 *
 */

class ScheckController extends Abstract_Controller_Default{
    
    /**
     * 是否允许未登录用户访问页面
     * 如果部分Action需要不登录就可以访问，请写一维数组，如 array('hot');
     * 
     * @var boolean
     */
    protected $allow_no_login = true;
    
    /**
     * 是否获取当前用户信息
     * 
     * @var boolean
     */
    protected $fetch_current_user = false;
    
    /**
     * 脚本处理入口
     *
     * @return boolean
     */
    public function indexAction() {
        if (! Helper_Debug::isDebug ()) {
            header ( 'location:/' );
            exit ();
        }
        
        $c = new Helper_Scheck ();
        $c->is_show_right = 1;
        $uid = 0;
        ScheckModel::setConfig ( $c, 0 );
        $c->webRun ( $_GET );
        
        return false;
    }
}
