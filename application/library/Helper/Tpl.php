<?php

/**
 * 专门为模板提供的相关方法
 *
 * @package Helper
 * @author  baojun <baojun4545@sina.com>
 */

abstract class Helper_Tpl{
    
    /**
     * 获取模板对象
     * 
     * @param string $tpl_path 模板路径，默认值为默认module下的views
     * 
     * @return Yaf_View_Simple
     */
    static public function getView($tpl_path = '') {
        if (! $tpl_path) {
            $tpl_path = Yaf_Application::app()->getAppDirectory() . '/views';
        }
        $view = new Yaf_View_Simple($tpl_path);
        return $view;
    }
    
    /**
     * 加载CSS
     * 
     * @param string  $paths  path
     * @param boolean $return return 
     * 
     * @return mixed
     */
    static public function css($paths, $return = false) {
        $ver = self::cssVer();
        $arr_path = is_array($paths) ? $paths : explode(',', $paths);
        $result = '';
        foreach ( $arr_path as $path ) {
            $href = self::getCssBaseUrl() . $path;
            $href .= (strpos($href, '?') === false ? '?' : '&') . "version={$ver}";
            
            $result .= "<link href=\"{$href}\" type=\"text/css\" rel=\"stylesheet\" />\r\n";
        }
        
        if ($return) {
            return $result;
        } else {
            echo $result;
        }
    }
    
    /**
     * 获取图片路径（一般用户加载默认图片）
     *
     * @param string $path 相对路径
     * @param string $type 类型
     *       
     * @return string
     */
    static public function getImgUrl($path, $type = 't4') {
        $ver = self::cssVer();
        $url = self::getCssBaseUrl($type) . $path;
        $url .= (strpos($url, '?') === false ? '?' : '&') . "version={$ver}";
        
        return $url;
    }
    
    /**
     * 加载JS
     * 
     * @param string  $paths  paths 
     * @param boolean $return return 
     * 
     * @return mixed
     */
    static public function js($paths, $return = false) {
        $ver = self::jsVer();
        $arr_path = is_array($paths) ? $paths : explode(',', $paths);
        $result = '';
        foreach ( $arr_path as $path ) {
            $src = self::getJsBaseUrl() . $path;
            $src .= (strpos($src, '?') === false ? '?' : '&') . "version={$ver}";
            $result .= "<script type=\"text/javascript\" src=\"{$src}\"></script>\r\n";
        }
        if ($return) {
            return $result;
        } else {
            echo $result;
        }
    }
    
    /**
     * 获取huati.weibo.cn的js地址
     * 
     * @param string $path      path 
     * @param string $data_main data 
     * 
     * @return string
     */
    static public function jsMobilecn($path, $data_main = '') {
        $ver = self::jsVer('js_mcn_version');
        $src = self::getJsBaseUrl('app.site.js_h5') . $path;
        $src .= (strpos($src, '?') === false ? '?' : '&') . "version={$ver}";
        if (! $data_main) {
            return "<script type=\"text/javascript\" src=\"{$src}\"></script>";
        } else {
            return "<script data-main=\"${data_main}\" type=\"text/javascript\" src=\"{$src}\"></script>";
        }
    }
    
    /**
     * 获取图片占用图片
     * 
     * @param boolean $return is return or not
     * 
     * @return string
     */
    static public function transparent($return = false) {
        $result = self::getCssBaseUrl() . 'style/images/common/transparent.gif';
        $result = '<img alt="" src="' . $result . '" />';
        if ($return) {
            return $result;
        } else {
            echo $result;
        }
    }
    
    /**
     * 获取CSS的基础URL
     * 
     * @param string $type type
     * 
     * @return string
     */
    static public function getCssBaseUrl($type = 't4') {
        static $base_url = '';
        if (! $base_url) {
            if ($type == 't5') {
                $config_key = Helper_Debug::isDebug() ? 'app.site.css_dev_t5' : 'app.site.css_t5';
            } else {
                $config_key = Helper_Debug::isDebug() ? 'app.site.css_dev' : 'app.site.css';
            }
            
            $base_url = Comm_Config::get($config_key);
        }
        return $base_url;
    }
    
    /**
     * 获取JS的基础URL
     * 
     * @param string $config_name base url
     * 
     * @return string
     */
    static public function getJsBaseUrl($config_name = 'app.site.js') {
        static $base_url = array ();
        if (! isset($base_url[$config_name])) {
            $config_key = Helper_Debug::isDebug() ? $config_name . '_dev' : $config_name;
            $base_url[$config_name] = Comm_Config::get($config_key);
        }
        return $base_url[$config_name];
    }
    
    /**
     * 获取IMAGE的基础URL
     * 
     * @param string $config_name config name
     * 
     * @return string
     */
    static public function getImageBaseUrl($config_name = 'app.site.image') {
        static $base_url = array ();
        if (! isset($base_url[$config_name])) {
            $config_key = Helper_Debug::isDebug() ? $config_name . '_dev' : $config_name;
            $base_url[$config_name] = Comm_Config::get($config_key);
        }
        return $base_url[$config_name];
    }
    
    /**
     * 获取当前CSS版本号
     * 
     * @return string
     */
    static public function cssVer() {
        static $ver;
        $ver = time();
        if (! $ver) {
            $ver = ConfModel::get('css_version');
        }
        return $ver;
    }
    
    /**
     * 获取JS版本号
     *  
     * @param string $key key 
     * 
     * @return string
     */
    static public function jsVer($key = 'js_version') {
        static $ver = array ();
        if (! isset($ver[$key])) {
            $ver[$key] = ConfModel::get('js_version');
        }
        return $ver[$key];
    }
    
    /**
     * 加载独立的tpl模块，其模板内的变量与上层模板变量是隔离的
     * 
     * @param string $_tpl 相对views目录的完整路径，开头无“/”
     * @param array $_var 用于注册block内的模板变量，key为变量名，value是对应的值。
     */
    static public function loadBlock($_tpl, array $_var = array()) {
        extract($_var, EXTR_SKIP);
        
        include TPL_PATH . $_tpl;
    }
    
    /**
     * 加载模板模块
     * 
     * @param string $tpl      tpl
     * @param array  $tpl_vars vars
     * 
     * @return void
     */
    static public function loadBlockStatic($tpl, array $tpl_vars = array()) {
        $static_key = 'tpl_eval' . $tpl;
        $tpl_str = Comm_Sdata::get(__CLASS__, $static_key);
        if ($tpl_str === false) {
            $tpl_str = '?>' . file_get_contents(TPL_PATH . $tpl);
            Comm_Sdata::set(__CLASS__, $static_key, $tpl_str);
        }
        $view = new Yaf_View_Simple(TPL_PATH);
        $view->eval($tpl_str, $tpl_vars);
    }
    
    /**
     * 在模板中显示用户昵称
     * 
     * @param array $user 用户信息
     * @param boolean $target 是否在新窗口打开
     * @param int $cut 是否进行内容截取
     * @param string $class class
     * 
     * @return string
     */
    static public function userName(array $user, $target = true, $cut = false, $class = '') {
        $url = ModelUser::url($user);
        $target_param = $target ? 'target="_blank"' : '';
        
        if (is_numeric($cut) && $cut > 0) {
            $screen_name_show = mb_strimwidth($user['screen_name'], 0, $cut);
        } else {
            $screen_name_show = $user['screen_name'];
        }
        
        ! empty($class) && $classtr = " class='$class'";
        // 去掉用户昵称截字和原始昵称的比较，任何时都追加v标
        $html = '<a usercard="id=' . $user['id'] . '" ' . $target_param . $classtr . ' href="' . $url . '">' . $screen_name_show;
        
        // 用户标志
        $html .= ModelUser::ico($user);
        $html .= '</a>';
        
        return $html;
    }
    
    /**
     * 在模板中显示用户昵称
     * 
     * @param array $user 用户信息
     * @param boolean $target 是否在新窗口打开
     * @param int $cut 是否进行内容截取
     * 
     * @return string
     */
    static public function userImgName(array $user, $target = true, $cut = false) {
        $url = ModelUser::url($user);
        $target_param = $target ? 'target="_blank"' : '';
        
        if (is_numeric($cut) && $cut > 0) {
            $screen_name_show = mb_strimwidth($user['screen_name'], 0, $cut);
        } else {
            $screen_name_show = $user['screen_name'];
        }
        
        // 去掉用户昵称截字和原始昵称的比较，任何时都追加v标
        $img = '<img class="avatar" title="' . $user['screen_name'] . '" src="' . $user['profile_image_url'] . '" width="15" height="15">';
        $html = '<a usercard="id=' . $user['id'] . '" ' . $target_param . ' title="' . $user['screen_name'] . '" href="' . $url . '">' . $img . $screen_name_show;
        
        // 用户标志
        $html .= ModelUser::ico($user);
        $html .= '</a>';
        
        return $html;
    }
}
