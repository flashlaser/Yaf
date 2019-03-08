<?php

/**
 * open.weixin
 * 
 * @package Apilib
 * @author  dh <lidonghui@yixia.com>
 */
class Apilib_Open_Wechat {

    /**
     * 基本URL
     *
     * @var string
     */
    const BASE_URL = 'https://api.weixin.qq.com/';

    /**
     * app key
     */
    protected $app_key; 

    /**
     * secret
     */
    protected $app_secret;  
    /**
     * redirect
     */
    protected $redirect_uri;

    /**
     * 接口默认请求超时时间
     *
     * @var int
     */
    protected $timeout = 2;

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
     * @param int $timeout time out
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
     * @param string $url url
     * @param array $data data
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
     * @param string $url url
     * @param array $data data
     *
     * @return type
     */
    protected function _get($url, array $data=array()) {
        $data && $url .= '?'.http_build_query($data);
        $url = $this->_fetchUrl($url);
		try{
			$result = Helper_Http::get($url, null, null, null, $this->_timeout);
		} catch (Exception_Http $e) {
			throw new Exception_Msg('310308');
		}

        return $this->_process($result, $url);
    }

    /**
     * 获取接接好的请求URL
     *
     * @param string $url URL相对路径
     *
     * @return string 完整的请求URL
     */
    protected function _fetchUrl($url) {
        $url = self::BASE_URL . $url;
        return $url;
    }

    /**
     * 处理返回的数据
     *
     * @param  array  $result result
     * @param  string $url url 
     * @param  array  $data data
     *
     * @return mixed
     */
    protected function _process($result, $url, $data=array()) {
		$result = json_decode($result, true);
		if (!empty($result) && !isset($result['errcode'])) {
            return $result;
        }
		$code = isset($result["errcode"]) ? $result["errcode"] : '10001';
		$error = isset($result["errmsg"]) ? $result["errmsg"] : '10001';
        throw new Exception_Api($code, $error, array(
        	'response'  => $result,
			'url'       => $url,
			'data'      => $data,
        ));
	}

	/**
	 * get user info
	 * @param string $access_token token
	 * @param string $openid open uid
	 */
	public function snsUserInfo($access_token, $openid) {
		$url = "sns/userinfo?access_token={$access_token}&openid={$openid}";
		return $this->_get($url, array());
	}

    /**
     * get access token
     *
     * @param string $code code
     */
    public function snsAccessToken($code) {
		$url = "sns/oauth2/access_token?appid={$this->app_key}&secret={$this->app_secret}&code={$code}&grant_type=authorization_code";
		return $this->_get($url, array());
	}
}
