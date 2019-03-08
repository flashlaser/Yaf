<?php

/**
 * Develop
 *
 * @package Controller
 * @author  baojun <zhangbaojun@yixia.com>
 */

class DevelopController extends Abstract_Controller_Default{
    
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
     * init
     * 
     * @return null
     */
    public function init() {
        parent::init ();
        // 仅允许调试环境下执行此脚本
        if (! Helper_Debug::isDebug ()) {
            throw new Exception_Msg ( '10', 'Can not run this script in no debug mode.' );
        }
    }
    
    /**
     * 服务器配置
     *
     * @return null
     * @throws Exception_Msg
     */
    public function srvconfigAction() {
        $write = $this->getRequest ()->getParam ( 'write' );
        $write === null && $write = $this->getRequest ()->getQuery ( 'write' );
        
        // 获取apache的配置
        $str_conf = '';
        $arr_a = array ();
        foreach ( $_SERVER as $k => $v ) {
            if (substr ( $k, 0, 3 ) != 'SRV') {
                continue;
            }
            $str_conf .= "{$k}\t= \"{$v}\"\n";
            $arr_a [$k] = $v;
        }
        
        // 获取SINASRV_CONFIG的配置
        $arr_s = array ();
        $ini_file = APP_PATH . "/system/SRV_CONFIG";
        if (! is_file ( $ini_file )) {
            throw new Exception_Msg ( '500', 'Can\'t find the SINASRV_CONFIG.' );
        }
        $arr_s = parse_ini_file ( $ini_file );
        
        // 错位类型
        $arr_ret = array (
                '-2' => 'a不存在',
                '-1' => 's不存在',
                '0' => '错误',
                '1' => '正确' 
        );
        
        $out = array ();
        
        // 交集
        $arr_intersect = array_intersect_key ( $arr_s, $arr_a );
        foreach ( $arr_intersect as $k => $v ) {
            if ($arr_a [$k] == $v) {
                $ret = 1; // 正确
            } else {
                $ret = - 1; // 错误
            }
            
            $out [] = array (
                    'k' => $k,
                    'a' => $v . '<br />' . $arr_a [$k] . ' ',
                    'ret' => $ret,
                    'str' => $arr_ret [$ret] 
            );
        }
        
        // 差集 a - s
        $arr_diff = array_diff ( $arr_a, $arr_s );
        foreach ( $arr_diff as $k => $v ) {
            $ret = - 1;
            $out [] = array (
                    'k' => $k,
                    'a' => $arr_a [$k] . '<br />...',
                    'ret' => $ret,
                    'str' => $arr_ret [$ret] 
            );
        }
        
        // 差集 s - a
        $arr_diff = array_diff ( $arr_s, $arr_a );
        foreach ( $arr_diff as $k => $v ) {
            $ret = - 2;
            $out [] = array (
                    'k' => $k,
                    'a' => '...<br />' . $arr_a [$k],
                    'ret' => $ret,
                    'str' => $arr_ret [$ret] 
            );
        }
        
        // print_r($_SERVER);
        echo 'SRV_CONFIG文件：' . $ini_file . '<p />';
        $this->startTable ( array ('key', 'httpd.conf/SINASRV_CONFIG', 'code', '说明' ) );
        $this->putTrs ( $out );
        $this->endTable ();
        
        echo '<p>按照httpd.conf配置生成SINASRV_CONFIG配置数据:</p>';
        if (! empty ( $str_conf )) {
            echo '<textarea rows="50" cols="100" >' . htmlspecialchars ( $str_conf ) . '</textarea>';
            if ($write) {
                file_put_contents ( $ini_file, $str_conf );
            }
        }
        
        return false;
    }
    
    /**
     * 开始表格
     *
     * @param array $arr_th arr th
     * 
     * @return null
     */
    public function startTable($arr_th = array()) {
        echo '<table width="98%" border="1" cellspacing="0" cellpadding="4" > ';
        if ($arr_th)
            $this->put_tr ( $arr_th, 'th' );
    }
    
    /**
     * 结束表格
     * 
     * @return null
     */
    public function endTable() {
        echo '</table>';
    }
    
    /**
     * 输出多行
     *
     * @param array $arr_tr arr tr 
     * 
     * @return mixed   
     */
    public function putTrs($arr_tr) {
        foreach ( $arr_tr as $v ) {
            $this->put_tr ( $v );
        }
    }
    
    /**
     * 输出一行
     *
     * @param array  $arr_td arr td        
     * @param string $td     td  
     * 
     * @return mixed
     */
    public function putTr($arr_td, $td = 'td') {
        echo '<tr>';
        foreach ( $arr_td as $v ) {
            echo "<{$td}>{$v}</{$td}>";
        }
        echo '</tr>';
    }
}
