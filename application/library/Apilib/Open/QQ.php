<?php

/**
 * open.qq
 * 
 * @package Apilib
 * @author  dh <lidonghui@yixia.com>
 */
class Apilib_Open_QQ {
    /**
     * 基本URL
     *
     * @var string
     */
    const BASE_URL = 'https://graph.qq.com/';
    /**
     * 接口默认请求超时时间
     *
     * @var int
     */
    protected $timeout = 2;

	/**
	 * open key
	 */
	protected $app_key;
	/**
	 * open secret
	 */
	protected $app_secret;
    /**
     * redirect
     */
    protected $redirect_uri;
    
    /**
     * set app info
     *
     * @param string $app_key appkey
     * @param string $app_secret app_secret
     * @param string $redirect_uri uri
     */
    public function setOpenAccess($app_key, $app_secret, $redirect_uri = '') {
        $this->app_key = $app_key;
        $this->app_secret = $app_secret;
        $this->redirect_uri = $redirect_uri;
        return $this;
    }

    /**
     * 设置超时
     *
     * @param int           $timeout
     *
     * @return Apilib_Mp
     */
    public function setTimeout($timeout) {
    	$this->_timeout = $timeout;
    	return $this;
    }
    
    /**
     * POST请求
     *
     * @param  type $url
     * @param  array $data
     *
     * @return type
     */
    protected function _post($url, array $data=array()) {
		$url = $this->_fetchUrl($url);
		try {
			$result = Helper_Http::post($url, $data, null, null, null, $this->_timeout);
		} catch (Exception_Http $e) {
			throw new Exception_Msg('310308');
		}
        return $this->_process($result, $url, $data);
    }

    /**
     * GET请求
     *
     * @param type $url url
     * @param array $data data
     *
     * @return type
     */
    protected function _get($url, array $data=array()) {
        $data && $url .= '?'.http_build_query($data);
		$url = $this->_fetchUrl($url);
		try {
			$result = Helper_Http::get($url, null, null, null, $this->_timeout);
		} catch (Exception_Http $e) {
			throw new Exception_Msg('310308');
		}

        return $this->_process($result, $url);
    }
    /**
     * GET请求
     *
     * @param type $url url 
     * @param array $data data
     *
     * @return type
     */
    protected function _getRaw($url, array $data=array()) {
        $data && $url .= '?'.http_build_query($data);
        $url = $this->_fetchUrl($url);
		try {
			$result = Helper_Http::get($url, null, null, null, $this->_timeout);
		} catch (Exception_Http $e) {
			throw new Exception_Msg('310308');
		}

        return $this->_processRaw($result, $url);
    }

	/**
     * response include callback();
     *
     * @param string $url url
     * @param array $data params
	 */
    protected function _getCallback($url, array $data=array()) {
        $data && $url .= '?'.http_build_query($data);
		$url = $this->_fetchUrl($url);
		try {
			$result = Helper_Http::get($url, null, null, null, $this->_timeout);
		} catch (Exception_Http $e) {
			throw new Exception_Msg('310308');
		}

        return $this->_processCallback($result, $url);
    }
    /**
     * 获取接接好的请求URL
     *
     * @param  string $url URL相对路径
     *
     * @return string      完整的请求URL
     */
    protected function _fetchUrl($url) {
        $url = self::BASE_URL . $url;
        return $url;
    }

    /**
     * 处理返回的数据
     *
     * @param  array  $result
     * @param  string $url
     * @param  array  $data
     *
     * @return mixed
     *
     * @throws Exception_Api
     */
    protected function _processCallback($result, $url, $data=array()) {
		$result_filt = str_replace('callback(', '', $result);
		$result_filt = trim(str_replace(');', '', $result_filt));
        $result_filt = json_decode($result_filt, true);
		if (!empty($result_filt) && !isset($result_filt['error'])) {
            return $result_filt;
        }
        $code = isset($result_filt["error"]) ? $result_filt["error"] : '10001';
        $msg = isset($result_filt["error_description"]) ? $result_filt["error_description"] : '10001';
        throw new Exception_Api($code, $msg, array(
        	'response'  => $result,
			'url'       => $url,
			'data'      => $data,
        ));
	}
    /**
     * 处理返回的数据
     *
     * @param array  $result result
     * @param string $url    url
     * @param array  $data   data
     *
     * @return mixed
     */
    protected function _processRaw($result, $url, $data=array()) {
        if (strpos($result, 'callback') !== false) {
            $this->_processCallback($result, $url, $data);
        }
        foreach (explode('&', $result) as $row) {
            list($key, $value) = explode('=', $row);
            $result_filt[$key] = $value;
        }
		if (!empty($result_filt) && !isset($result_filt['error'])) {
            return $result_filt;
        }
        $code = isset($result_filt["error"]) ? $result_filt["error"] : '10001';
        $msg = isset($result_filt["error_description"]) ? $result_filt["error_description"] : '10001';
        throw new Exception_Api($code, $msg, array(
        	'response'  => $result,
			'url'       => $url,
			'data'      => $data,
        ));
	}

	/**
	 * 解析json
	 */
	protected function _process($result, $url, $data=array()) {
		$result_filt = json_decode($result, true);
		if (!empty($result_filt) && (!isset($result_filt['ret']) || $result_filt['ret'] == 0)) {
            return $result_filt;
        }
        $code = isset($result_filt["ret"]) ? $result_filt["ret"] : '10001';
        $msg = isset($result_filt["msg"]) ? $result_filt["msg"] : '10001';
        throw new Exception_Api($code, $msg, array(
        	'response'  => $result,
			'url'       => $url,
			'data'      => $data,
        ));
	}
	
	/**
	 * get uid
	 * @param string $access_token token
	 */
	public function oauthMe($access_token) {
		$url = "oauth2.0/me?access_token={$access_token}";
		return $this->_getCallback($url, array());
	}
	/**
	 * get user info
	 * @param string $access_token token
	 * @param string $uid uid
	 */
	public function userInfo($access_token, $uid) {
		$url = "user/get_user_info?access_token={$access_token}&oauth_consumer_key={$this->app_key}&openid={$uid}";
		return $this->_get($url, array());
    }
    /** 
     * get access token
     *
     * @param string $code code
     */
    public function accessToken($code) {
        $data = array(
            'client_id' => $this->app_key, 
            'client_secret' => $this->app_secret, 
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirect_uri,
        );
        return $this->_getRaw('oauth2.0/token', $data);
    }

    /**
     * request code url
     *
     * @param string $state state
     */
    public function requestCodeUrl($state) {
        $redirect_uri = urlencode($this->redirect_uri);
        return $this->_fetchUrl("oauth2.0/authorize?response_type=code&client_id={$this->app_key}&redirect_uri={$redirect_uri}&state={$state}");
    }

}
