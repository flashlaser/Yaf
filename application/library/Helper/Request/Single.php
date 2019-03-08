<?php

/**
 * HTTP Request请求，支持批量处理
 *
 * @copyright 2012 weibo.com all rights reserved
 * @author    baojun <baojun4545@sina.com>
 * @package   Helper
 */
class Helper_Request_Single {

    /**
     * CURL资源
     *
     * @var resource
     */
    protected $_ch;

    /**
     * 构造方法
     *
     * @param   string  $url        URL
     * @param   array   $post_data  提交的数据内容
     *
     * @return  void
     */
    public function __construct($url=null, array $post_data=null) {
        $this->_ch = curl_init();
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        $url !== null && $this->setUrl($url);
    }

    /**
     * 设置请求的URL
     *
     * @param string $url URL
     *
     * @return Helper_Request_Single
     */
    public function setUrl($url) {
        curl_setopt($this->_ch, CURLOPT_URL, $url);
        return $this;
    }

    /**
     * 设置提交参数
     * @param   mixed                   $post_param     提交参数，字符串或数组
     * @param   boolean                 $build_query    是否自动执行http_build_query
     *
     * @return  Helper_Request_Single
     */
    public function setPostData($post_param, $build_query=true) {
        $build_query && is_array($post_param) && $post_param = http_build_query($post_param);
        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $post_param);
        return $this;
    }

    /**
     * 请求头
     *
     * @param string $header HTTP头信息
     *
     * @return Helper_Request_Single
     */
    public function setHeader($header) {
        curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $header);
        return $this;
    }

    /**
     * 获取url
     * @return	url
     */
    public function getUrl($url, $param = null) {
        $akey = Comm_Config::get('app.env.app_key');
        $url = "http://i2.api.weibo.com/{$url}.json?source={$akey}";

        if ($param) {
            $url .= '&' . (is_array($param) ? http_build_query($param) : $param);
        }
        return $url;
    }

    /**
     * 设置Cookie
     *
     * @param string $cookie 设置COOKIE
     *
     * @return Helper_Request_Single
     */
    public function setCookie($cookie) {
        if ($_COOKIE && isset($_COOKIE['SUE']) && isset($_COOKIE['SUP'])) {
            $object = new self();
            $sue    = str_replace('+', ' ', str_replace('%7E', '~', rawurlencode($_COOKIE['SUE'])));
            $sup    = str_replace('+', ' ', str_replace('%7E', '~', rawurlencode($_COOKIE['SUP'])));
            $cookie = "SUE={$sue}; SUP={$sup}";
        }
        curl_setopt($this->_ch, CURLOPT_COOKIE, $cookie);
        return $this;
    }

    /**
     * 设置超时时间
     *
     * @param int $timeout 设置超时时间
     *
     * @return Helper_Request_Single
     */
    public function setTimeout($timeout) {
        curl_setopt($this->_ch, CURLOPT_TIMEOUT, $timeout);
        return $this;
    }

    /**
     * 获取CURL资源
     *
     * @return resource
     */
    public function fetchCurl() {
        return $this->_ch;
    }

    /**
     * 执行CURL请求
     *
     * @return string
     */
    public function exec() {
        $result = curl_exec($this->_ch);
        curl_close($this->_ch);
        return $result;
    }

}
