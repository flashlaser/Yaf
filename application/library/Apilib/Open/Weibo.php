<?php

/**
 * open.weibo
 * 
 * @package Apilib
 * @author  dh <lidonghui@yixia.com>
 */
class Apilib_Open_Weibo {

    /**
     * 基本URL
     *
     * @var string
     */
    const BASE_URL = 'https://api.weibo.com/';
    
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
    public function setOpenAccess($app_key, $app_secret, $redirect_uri) {
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
            $meta_data = $e->getMetadata();
            $result = $meta_data['result'];
		}
        return $this->_process($result, $url, $data);
    }

    /**
     * GET请求
     *
     * @param  type $url
     * @param  array $data
     *
     * @return type
     */
    protected function _get($url, array $data=array()) {
        $data && $url .= '?'.http_build_query($data);
		$url = $this->_fetchUrl($url);
		try {
			$result = Helper_Http::get($url, null, null, null, $this->_timeout);
        } catch (Exception_Http $e) {
            $meta_data = $e->getMetadata();
            $result = $meta_data['result'];
		}

        return $this->_process($result, $url);
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
    protected function _process($result, $url, $data=array()) {
        $result = json_decode($result, true);
		if (!empty($result) && !isset($result['error_code'])) {
            return $result;
        }
		$code = isset($result["error_code"]) ? $result["error_code"] : '10001';
		$error = isset($result["error"]) ? $result["error"] : '10001';
        throw new Exception_Api($code, $error, array(
        	'response'  => $result,
			'url'       => $url,
			'data'      => $data,
        ));
	}

	/**
	 * get openid by access_token
	 *
	 * @param string $access_token
	 */
	public function getUid($access_token) {
		$url = "2/account/get_uid.json?access_token={$access_token}";
		return $this->_get($url, array());
	}

	/**
	 * get user info
	 * @param string $access_token
	 * @param string $uid
	 */
	public function usersShow($access_token, $uid) {
		$url = "2/users/show.json?access_token={$access_token}&uid={$uid}";
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
        return $this->_post('oauth2/access_token', $data);
    }
    
    /**
     * request code url
     *
     * @param string $state state
     */
    public function requestCodeUrl($state) {
        $redirect_uri = urlencode($this->redirect_uri);
        return $this->_fetchUrl("oauth2/authorize?client_id={$this->app_key}&response_type=code&state={$state}&redirect_uri={$redirect_uri}");
    }
}
