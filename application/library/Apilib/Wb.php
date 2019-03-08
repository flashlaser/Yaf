<?php

/**
 * V4接口
 *
 * @package apilib
 * @author  baojun <baojun4545@sina.com>
 */
class Apilib_Wb extends Apilib_Wba {

    //请求信息头
    protected $_header = array();

    /**
     * 获取当前用户操作对象（单例模式）
     *
     * @param boolean $use_access_token 如果当前用户未登录是否采用默认账号的access_token(默认为否)
     *
     * @return Apilib_Wb
     */
    static public function init($use_access_token = false) {
        $obj = new self;
        $obj->setCurrentUserAuth($use_access_token);
        return $obj;
    }

    /**
     * 构造方法
     *
     * @param string $akey
     */
    public function __construct($akey = '') {
        !$akey && $akey = Comm_Config::get('app.env.app_key');
        $this->_akey = $akey;
    }

    /**
     * 以Multipart形式POST提交数据
     *
     * @param string  $url       请求地址
     * @param array   $param     请求参数
     * @param array   $bin_param 请求参数（二进制内容）
     *
     * @return mixed
     */
    public function postMultipart($url, array $param, array $bin_param) {
        $url = "{$this->_base}/{$url}.json?source={$this->_akey}";
        $multi_data = $this->_getMultiData($param, $bin_param);
        $this->_header = array_merge($this->_header, $multi_data['header']);
        return $this->_process($url, $multi_data['body']);
    }

    /**
     * 获取请求的CURL句柄
     *
     * @param string  $url        请求地址
     * @param mixed   $post_param POST提交参数
     *
     * @return resource
     */
    protected function _fetchCurl($url, $post_param = null) {
        $curl = curl_init();
        curl_setopt_array($curl, array(CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3));
        //设置referer,否则接口认证不了
        curl_setopt($curl, CURLOPT_REFERER, Comm_Config::get('app.site.live'));

        $this->_header[] = 'API-RemoteIP:' . Comm_Util::getClientIp();
        //设置认证方式
        switch ($this->_verify_type) {
            case self::V_COOKIE :
                curl_setopt($curl, CURLOPT_COOKIE, $this->_verify_data);
                break;
            case self::V_OAUTH :
                $this->_header[] = 'Authorization:OAuth2 ' . $this->_verify_data;
                break;
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->_header);
        $this->_header = array();

        $post_param !== null && curl_setopt($curl, CURLOPT_POSTFIELDS, $post_param);
        return $curl;
    }

    /**
     * 处理请求
     *
     * @param string  $url        请求接口URL
     * @param mixed   $post_param POST提交参数
     *
     * @return array
     */
    protected function _process($url, $post_param = null) {
        $curl = $this->_fetchCurl($url, $post_param);
        $body = curl_exec($curl);
        //访问接口HTTP异常
        if ($body === false) {
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            throw new Exception_System(200104, "Call open api http error.", array(
                'url' => $url,
                'curl_errno' => curl_errno($curl),
                'curl_error' => curl_error($curl) . "[{$http_code}]",
            ));
        }
        $result = $body;
        $this->_processOne($result);

        //出现错误，抛出异常
        if (isset($result['error'])) {
            $code = $result['error_code'];
            $msg = $result['error'];
            $uid = Yaf_Registry::get('current_uid');
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            throw new Exception_Openapi($code, $msg, array('url' => $url, 'post_param' => $post_param, 'body' => $body, 'data' => $result, 'http_code' => $http_code));
        }

        curl_close($curl);
        return $result;
    }

    /**
     * 非批量模式处理一个结果
     *
     * @param string $body 数据
     *
     * @return void
     */
    protected function _processOne(&$body) {
        $body = json_decode($body, true);
    }

    /**
     * 分批GET获取数据
     *
     * @param string  $url            接口
     * @param string  $batch_key      批量请求时的KEY
     * @param array   $batch_vals     批量请求时的值
     * @param array   $param          可选，其它参数
     * @param closure $callback       回调方法
     * @param array   $callback_param 回调方法参数
     * @param int     $batch_max      每批最大调用量
     *
     * @return array
     */
    public function getBatch($url, $batch_key, array $batch_vals, array $param = array(), $callback = null, array $callback_param = array(), $batch_max = self::BATCH_MAX) {
        $batch_count = count($batch_vals);
        $request_multi = new Helper_Request_Multi();
        for ($i = 0, $j = 0; $i < $batch_count; $i += self::BATCH_MAX) {
            $batch_data_current = array_slice($batch_vals, $i, self::BATCH_MAX);
            $current_param = array_merge($param, array($batch_key => $this->batchData($batch_data_current)));

            $current_url = "{$this->_base}/{$url}.json?source={$this->_akey}&";
            $current_url .= http_build_query($current_param);
            $ch = $this->_fetchCurl($current_url, null);
            $request_multi->addCurl($j++, $ch);
            unset($ch);
        }

        //处理结果
        $result = array();
        foreach ($request_multi->exec() as $key => $value) {
            $value = json_decode($value, true);
            if (isset($value['error'])) {
                continue;
            }
            if ($callback) {
                call_user_func_array($callback, array_merge(
                        array(&$result, $value), $callback_param
                    ));
            } else {
                $result = array_merge($result, $value);
            }
        }
        return $result;
    }

    /**
     * 获取tauth接口
     *
     * @param int    $count
     *
     * @return array
     */
    public static function getTauthToken(){
        $mc_ini = "tauth_token";
        $result = Comm_Mc::init()->getData($mc_ini, array());

        if(!$result) {
            $object = new Apilib_Wb();
            $source = Comm_Config::get('app.env.app_key');
            $app_secret = Comm_Config::get('app.env.app_secret');
            $res = $object->post('auth/tauth_token', array("app_secret" => $app_secret));
            $result = $res['tauth_token'];
            $result && Comm_Mc::init()->setData($mc_ini, array(), $result);
        }
        if($result)
            return $result;
        else
            return false;
    }

    /**
     * 得到用户的spr值（针对主站部门接口）
     */
	function getSpr(){
	    $spr = $spr_val = '';
	    if(isset($_COOKIE['UOR'])) {
	        $uor_ary = explode(',', $_COOKIE['UOR']);
	        if($uor_ary[2]){
	            $spr_ary = explode(':', $uor_ary[2]);
	            $spr_val = $spr_ary[0];
	        }
	    }

	    if(isset($_COOKIE['Apache']) && isset($_COOKIE['SINAGLOBAL'])) {
	        $spr = "session:".$_COOKIE["Apache"].";global:".$_COOKIE["SINAGLOBAL"].";spr:".$spr_val;
	    }

		return $spr;
	}

    /**
     * 话题词精确搜索
     *
     * @param string $q 搜索话题词，urlencode
     * @param int $page 页码
     * @param int $count 返回结果每页的记录数，默认10，最大为50
     *
     * @return	array
     */
    public function searchTopics($q, $page = 1, $count = 10) {
        $params = array(
            'q' => $q,
            'page' => $page,
            'count' => $count
            );
        return $this->get('search/topics', $params);
    }

    /**
     * 关键词搜索
     *
     * @param string $q 搜索话题词，urlencode
     * @param int $page 页码
     * @param int $count 返回结果每页的记录数，默认10，最大为50
     *
     * @return  array
     */
    public function searchStatuses($params) {
        return $this->get('search/topics', $params);
    }
    
}
