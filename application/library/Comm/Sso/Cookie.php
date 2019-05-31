<?php
/**
 * Sina sso client
 * @package SSOWeiboClient
 * @author   lijunjie <junjie2@staff.sina.com.cn>
 * @author   liuzhiyu <zhiyu@staff.sina.com.cn>
 * @version  $Id: SSOWeiboCookie.php 39532 2011-11-18 07:00:14Z juefei $
 */

class Comm_Sso_Cookie {
	const COOKIE_SUE		= "SUE";			//sina user encrypt info
	const COOKIE_SUP		= "SUP";			//sina user plain info
	const COOKIE_SUS		= "SUS";			//cookie name for session id(sid)
	const COOKIE_ALF		= "ALF";			//auto login flag
	const COOKIE_ALC		= "ALC";		//weibo auto login cookie
	const COOKIE_STATE		= "SSOLoginState";	//login state
	const COOKIE_SUB		= "SUB";			//SUB cookie

	const COOKIE_CHECK_DOMAIN = 0;
	const COOKIE_CHECK_IP     = 2;

	const COOKIE_EXPIRE		= 86400; //cookie 过期时间
	const COOKIE_PATH		= "/";
	const COOKIE_DOMAIN		= ".cria.org.cn";
	const COOKIE_KEY_FILE	= "cookie.conf";
	const COOKIE_VERSION	= 1;


	const GETALC_URL		= 'http://ilogin.sina.com.cn/api/getalc.php';	//获取用户自动登录cookie接口
	const CHKALC_URL		= 'http://ilogin.sina.com.cn/api/chkmini.php';	//验证自动登录cookie接口

	private $_time			= '';
	private $_error;
	private $_errno = 0;
	private $_arrConf; // the infomation in cookie.conf
	private $_arrCookieInfo = array("uid","user","ag",
									"email","nick","name",
									"sex","dob","ps","vf"); // only for set cookie
	private $_arrCookie = array();

	/**
	 * @var int Cookie 验证级别
	 */
	private $_cookieCheckLevel = 0; // 1: domain, 2: ip, 3: domain and ip

	/**
	 * @var string Cookie ip。当 Cookie 验证界级别设置为 3 时，只允许来自这个 ip 的 Cookie 通过验证
	 */
	private $_cookieIp = '';

	/**
	 * cookie conf中定义方式如下
	 *		rv=1
	 *		rv1=xxxxx
	 *		rv2=yyyyyy
	 * rv为当前使用的版本号，rv[n]为该版本号的base64_encode(公钥)
	 */
	const COOKIE_SIGN_VERSION_NAME	= 'rv';
	/**
	 * cookie的SUE中rs[n]即为不同版本的签名
	 */
	const COOKIE_SIGN_VALUE_NAME	= 'rs';
	/**
	 * rsa version
	 * @var int
	 */
	private $_rsa_version			= 0;

	public function __construct($config = self::COOKIE_KEY_FILE) {
		$this->_time = time();
		if(!$this->_parseConfigFile($config)){
			throw new Exception($this->getError());
		}
	}

	public function setCookieArr() {
		$_COOKIE = array_merge($_COOKIE, $this->_arrCookie);
	}

	/**
	 * 设置 Cookie 的 ip（视配置情况有可能作为 Cookie 验证依据）
	 *
	 * @param $ip
	 */
	public function setCookieIp($ip){
		$this->_cookieIp = $ip;
	}

	/**
	 * 设置 Cookie 验证级别
	 *
	 * @param $level
	 */
	public function setCookieCheckLevel($level) {
		$this->_cookieCheckLevel = $level;
	}

	public function getCookie(&$arrUserInfo, $use_rsa_sign=false) {
		$sup = $_COOKIE[self::COOKIE_SUP];
		if(!$sup) {
			$this->_setError("sup not exists");
			return false;
		}

		parse_str($sup,$arrSUP);
		$cv = $arrSUP["cv"];
		switch($cv) {
			case 1:
				return $this->_getCookieV1($arrUserInfo, $use_rsa_sign);
				break;
			default:
				return false;
		}
	}

        /**
	 * 由于现在cookie都是由sso生成，不再需要这种方式种cookie
	 *
	 * @param $arrUserInfo : [ uid | user | ag | email | nick | name | sex | dob | ps]
	 * @return bool
	 */
	/*public function setCookie($arrUserInfo) {
		//return true;
		return $this->headerCookie($this->getCookieStr($arrUserInfo));
	}*/
	/**
	 * 给类内部 Cookie 数组设置值
	 *
	 * @param $arrCookie
	 *
	 * @return bool
	 */
	public function setCustomCookie($arrCookie) {

		if (!is_array($arrCookie)) {
			$this->_setError('custom cookie is not array');
			return false;
		}

		$this->_arrCookie = $arrCookie;

		return true;
	}
	
	/**
	 * 修改方法访问权限，下次可以删除，需要个产品确认没有使用该方法
	 *
	 * get cookie string for setting cookie
	 * @param $arrUserInfo : [ uid | user | ag | email | nick | name | sex | dob | ps]
	 */
	public function getCookieStr($arrUserInfo) {
		$arrUserInfo = $this->convertUserInfo($arrUserInfo);
		if (!isset($arrUserInfo["ps"])) {
			$arrUserInfo["ps"] = 0;
		}

		$arrConf = $this->_arrConf;
		$bt = $arrUserInfo["bt"]?$arrUserInfo["bt"]:$this->_time;
		$et = isset($arrUserInfo["et"])?$arrUserInfo["et"]:($this->_time + self::COOKIE_EXPIRE);

		// for SUP
		$arrSUP = array();
		$arrSUP["cv"] = self::COOKIE_VERSION;
		$arrSUP["bt"] = $bt;
		$arrSUP["et"] = $et;

		// convert encode for setcookie, cookie value should be urlencoded
		foreach ( $this->_arrCookieInfo as $val) {
			$arrSUP[$val] = iconv("GBK","UTF-8",$arrUserInfo[$val]);
		}
		$sup = $this->_raw_http_build_query($arrSUP);

		// for SUE
		$str= $bt. $et. $arrUserInfo["uniqueid"] . $arrUserInfo["userid"] . $arrUserInfo["appgroup"]. $arrConf[$arrConf['v']] ;
		$es = md5($str);
		$es2 = md5($this->_rawurlencode($sup) . $arrConf[$arrConf["v"]]);
		$arrSUE = array("es"=>$es,"es2"=>$es2, "ev"=>$this->_arrConf["v"]);
		$sue = $this->_raw_http_build_query($arrSUE);

		$this->_arrCookie[self::COOKIE_SUE] = $sue;
		$this->_arrCookie[self::COOKIE_SUP] = $sup;

		$sue = $this->_rawurlencode($sue);
		$sup = $this->_rawurlencode($sup);
		$cookieSUE = "Set-Cookie: SUE=$sue;path=".self::COOKIE_PATH.";domain=".self::COOKIE_DOMAIN.";Httponly";
		$cookieSUP = "Set-Cookie: SUP=$sup;path=".self::COOKIE_PATH.";domain=".self::COOKIE_DOMAIN;

		return $cookieSUE."\n".$cookieSUP;
	}

	/**
	 * 由于现在cookie都是由sso生成，不再需要这种方式种cookie
	 *
	 * @param $arrUserInfo : [ uid | user | ag | email | nick | name | sex | dob | ps]
	 */
	public function setCookie($arrUserInfo) {
		//return true;
		return $this->headerCookie($this->getCookieStr($arrUserInfo));
	}

	/**
	 * 通过header()函数输出cookie
	 * @param string $param
	 */
	public function headerCookie($cookie) {
		$cookie = trim($cookie);
		if (!$cookie) {
			return false;
		}

		$header = explode("\n", $cookie);
		foreach($header as $line) {
			header($line, false);
		}
		return true;
	}

	/**
	 * update cookie
	 * @param $arrNewUserInfo : [ uid | user | ag | email | nick | name | sex | dob | ps]
	 * @return bool
	 */
	public function updCookie($arrNewUserInfo) {
		if (!$this->getCookie($arrUserInfo)) {
			return false;
		}
		foreach($arrNewUserInfo as $key=>$val) {
			$arrUserInfo[$key] = $val;
		}
		if (!$this->setCookie($arrUserInfo)) {
			return false;
		}
		return true;
	}

	/**
	 * delete cookie
	 */
	public function delCookie() {
		isset($_COOKIE[self::COOKIE_SUE]) && setcookie(self::COOKIE_SUE,"deleted",1,self::COOKIE_PATH,self::COOKIE_DOMAIN);
		isset($_COOKIE[self::COOKIE_SUP]) && setcookie(self::COOKIE_SUP,"deleted",1,self::COOKIE_PATH,self::COOKIE_DOMAIN);
		unset($_COOKIE[self::COOKIE_SUE]);
		unset($_COOKIE[self::COOKIE_SUP]);
		return true;
	}

	// 该方法不外发
	public function convertUserInfo($arrUserInfo, $toCookie = true) {
		$arrMap = array(
			"uid"=>"uniqueid",
			"user"=>"userid",
			"ag"=>"appgroup",
			"email"=>"sinamail",
			"nick"=>"displayname",
			"name"=>"name",
			"sex"=>"gender",
			"dob"=>"birthday",
			"ps"=>"paysign"
			);
		$arrResult = array();
		foreach($arrMap as $key=>$val) {
			if ($toCookie) {
				$arrResult[$key] = $arrUserInfo[$val];
			} else {
				$arrResult[$val] = $arrUserInfo[$key];
			}
		}
		return array_merge($arrUserInfo,$arrResult);
	}
	public function getError() {
		return $this->_error;
	}

	public function getErrno() {
		return $this->_errno;
	}

	private function _setError($error,$errno=0) {
		$this->_error = $error;
		$this->_errno = $errno;
		return true;
	}

	/**
	 * 验证cookie有效性
	 * @param array $arrUserInfo
	 * @param $use_rsa_sign
	 * @return boolean
	 */
	private function _getCookieV1(&$arrUserInfo, $use_rsa_sign) {
		if (!isset($_COOKIE[self::COOKIE_SUE]) || !isset($_COOKIE[self::COOKIE_SUP])) {
			$this->_setError("not all cookie are exists ");
			return false;
		}

		$arrSUE = $arrSUP = array();
		parse_str($_COOKIE[self::COOKIE_SUE], $arrSUE);
		parse_str($_COOKIE[self::COOKIE_SUP], $arrSUP);
		foreach( $arrSUP as $key=>$val) {
			$arrUserInfo[$key] = iconv("UTF-8","GBK",$val);
		}

		// 判断是否超时
		if ($arrUserInfo["et"] < $this->_time) {
			$this->_setError("cookie timeout {et:".$arrUserInfo["et"].";now:".$this->_time."}");
			return false;
		}

		//	set rsa version
		$this->_setRsaCookieVersion($arrSUE);

		//	解决php5.3版本中rawurlencode不转义~问题
		$rawsup = str_replace('~', '%7E', rawurlencode($_COOKIE[self::COOKIE_SUP]));
		
		//	选择性验证，设置或传递使用RSA方式验证参数
		if ($use_rsa_sign) {	// || SinaSSO_Config::USE_RSA_SIGN
			$rskey		= $this->_getRsaCookieName();
			$crypted	= isset($arrSUE[$rskey]) ? base64_decode($arrSUE[$rskey]) : '';
			$public_key	= $this->_getPublicKey();
			if (empty($crypted)) {
				$this->_setError($rskey . ' cookie not exist');
				return false;
			}
			if (empty($public_key)) {
				$this->_setError('public key not exist');
				return false;
			}
			// 检查rsa sign
			if (!$this->_validate($this->_signSUP($rawsup), $crypted, $public_key)) {
				$this->_setError('rsa sign string error');
				return false;
			}
		} else {
			// 检查加密cookie
			if ($arrSUE['es2'] != md5($rawsup . $this->_arrConf[$arrSUE['ev']])) {
				$this->_setError("encrypt string error");
				return false;
			}
		}

		// 更加严格的检查
		/*$needCheckDomain = ($this->_cookieCheckLevel & self::COOKIE_CHECK_DOMAIN) == self::COOKIE_CHECK_DOMAIN;
		if ($needCheckDomain && !$this->_checkDomain($arrSUP)) {
			$this->_setError('cookie domain no match');
			return false;
		}*/

		$needCheckIp = ($this->_cookieCheckLevel & self::COOKIE_CHECK_IP) == self::COOKIE_CHECK_IP;
		if ($needCheckIp && !$this->_checkIp($arrSUP)) {echo 112;exit;
			$this->_setError('cookie ip no match');
			return false;
		}

		$arrUserInfo = $this->convertUserInfo($arrUserInfo, false);
		return true;
	}

	/**
	 * 微博自动登录
	 *
	 * @return mixed
	 */
	public function autoLogin($entry) {
		$query = array(
			'entry'	=> $entry,
			'alc'	=> $_COOKIE[self::COOKIE_ALC],
			'ip'	=> $this->_getIp(),
			'domain'=> self::COOKIE_DOMAIN
		);
		
		$result = ModelUser_Token::getAlcByToken($_COOKIE[self::COOKIE_ALC]);
		if (!empty($result) && isset($result['cookies']) && $result['user']) {
		    //中SUE/SUP
		    $this->headerCookie($result['cookies']);
		    
		    //获取SUE/SUP值，存入$_COOKIE，以防止同一次请求中多次验证用户登录状态
		    $this->setCookieFromHeaderCookie($result['cookies']);
		    
		    unset($result['cookies']);
		    return $this->convertUserInfo($result['user']);
		}
        /*
		$url = self::CHKALC_URL . '?' . http_build_query($query);
		$ret = file_get_contents($url);

		$result = array();
		parse_str($ret, $result);
        
		if ($result['result'] == 'succ') {
			//TODO 推荐JS做下面的处理。
			//当没有设置SSOLoginState时，代表用户退出浏览器又重新登录。此时需要自动登录一次，保证相关的cookie可以种到sina.com.cn中
//			if (!isset( $_COOKIE['SSOLoginState'])) {
//				self::setSinaLoginCookie($result['uniqueid']);
//				exit;
//			}

			//中SUE/SUP
			$this->headerCookie($result['cookies']);

			//获取SUE/SUP值，存入$_COOKIE，以防止同一次请求中多次验证用户登录状态
			$this->setCookieFromHeaderCookie($result['cookies']);

			unset($result['result'], $result['cookies']);
			return $this->convertUserInfo($result);
		}*/

		setcookie(self::COOKIE_ALC, '', 0, '/', self::COOKIE_DOMAIN,0,true);
		return false;
	}

	/**
	 * 从header可直接输出的set cookie字符串中匹配出SUE和SUP，并设置到$_COOKIE中
	 * @param string $cookie
	 * @return bool
	 */
	public function setCookieFromHeaderCookie($cookie) {
		$sue = $sup = array();
		preg_match('|SUE=(.*);|U', $cookie, $sue);
		preg_match('|SUP=(.*);|U', $cookie, $sup);

		if (!$sue || !$sup) {
			return false;
		}

		$_COOKIE[self::COOKIE_SUE] = rawurldecode($sue[1]);
		$_COOKIE[self::COOKIE_SUP] = rawurldecode($sup[1]);
		return true;
	}


	/**
	 * 设置cookie中的WEIBOALC
	 *
	 * @param string $uid
	 * @param string $entry
	 * @param string $pin
	 * @return bool
	 */
	public function setALCCookie($uid, $entry, $pin) {
	    /*
		$ip = $this->_getIp();
		$query = array(
			'entry'	=> $entry,
			'user'	=> $uid,
			'ip'	=> $ip,
			'm'		=> md5($uid . $ip . $pin)
		);

		$url	= self::GETALC_URL . '?' . http_build_query($query);
		$ret	= file_get_contents($url);
		$result = array();
        parse_str($ret, $result);
        if ($result['result'] === 'succ') {
			$_COOKIE[self::COOKIE_ALC] = $result['alc'];
            setcookie(self::COOKIE_ALC, $result['alc'], $this->_time + 86400*7, '/', self::COOKIE_DOMAIN,0,true);
            return true;
        }*/
	    $user_token = ModelUser_Token::getTokenByUid($uid);var_dump($user_token);
	    if (isset($user_token['token'])) {
	        $_COOKIE[self::COOKIE_ALC] = $user_token['token'];
	        setcookie(self::COOKIE_ALC, $user_token['token'], $this->_time + 86400*7, '/', self::COOKIE_DOMAIN);
	        return true;
	    }
	    
        return false;
	}
	/**
	 * 检查 Cookie 域
	 *
	 * @param array $cookieInfo Cookie 信息
	 *
	 * @return bool
	 */
	private function _checkDomain($cookieInfo) {

		if (isset($cookieInfo['d']) && !$cookieInfo['d']) {
			return true;
		}

		$digest = md5(self::COOKIE_DOMAIN);
		if (strpos($digest, $cookieInfo['d']) === 0) {
			return true;
		}

		return false;
	}

	/**
	 * 检查 Cookie 域
	 *
	 * @param array $cookieInfo Cookie 信息
	 *
	 * @return bool
	 */
	private function _checkIp($cookieInfo) {

		if (!$cookieInfo['i']) {
			return true;
		}

		$ip = $this->_cookieIp?$this->_cookieIp:$this->_getIp();

		$digest = md5($ip);

		if (strpos($digest, $cookieInfo['i']) === 0) {
			return true;
		}

		return false;
	}

	/**
	 * parse cookie config file.
	 * @param $config: cookie config file
	 * @return bool
	 */
	private function _parseConfigFile($config) {
		$arrConf = @parse_ini_file($config);
		if(!$arrConf) {
			$this->_setError("parse file ".$config . " error");
			return false;
		}
		$this->_arrConf = $arrConf;
		return true;
	}
	private function _raw_http_build_query($arrQuery) {
		$arrtmp = array();
		foreach ($arrQuery as $key=>$val) {
			$arrtmp[] = $this->_rawurlencode($key)."=".$this->_rawurlencode($val);
		}
		return implode("&", $arrtmp);
	}
	private function _rawurlencode($str) {
		return str_replace('~','%7E',rawurlencode($str));
	}


	private function _getIp() {
		$xForward	= getenv('HTTP_X_FORWARDED_FOR');
		if ($xForward) {
			$arr = explode(',',$xForward);
			$cnt = count($arr);
			$xForward = $cnt==0 ? '' : trim($arr[$cnt-1]);
		}

		$remoteAddr	= getenv('REMOTE_ADDR');
		if ($this->_isPrivateIp($remoteAddr) && $xForward) {
			return $xForward;
		}
		return $remoteAddr;
	}
	private function _isPrivateIp($ip) {
		$i = explode('.', $ip);
		if ($i[0] == 10 || ($i[0] == 172 && $i[1] > 15 && $i[1] < 32) || ($i[0] == 192 && $i[1] == 168)) {
			return true;
		}
		return false;
	}




	/**
	 * 获取当前rsa算法在cookie中的名字rs[n]（内容为密钥生成的签名）
	 * 同时也为private key文件的名字
	 *
	 * @param bool		$conf	是否从配置文件中读出版本信息，默认从cookie中读取
	 * @return string
	 */
	private function _getRsaCookieName($conf=false) {
		$version = $conf ? $this->_getRsaConfVersion() : $this->_getRsaCookieVersion();
		return self::COOKIE_SIGN_VALUE_NAME . $version;
	}

	/**
	 * 设置当前Cookie中所使用的RSA版本
	 *
	 * @param $sue
	 * @return int
	 */
	private function _setRsaCookieVersion($sue) {
		return $this->_rsa_version = isset($sue[self::COOKIE_SIGN_VERSION_NAME]) ? $sue[self::COOKIE_SIGN_VERSION_NAME] : 0;
	}

	/**
	 * 当前Cookie中所使用的RSA版本
	 *
	 * @return int
	 */
	private function _getRsaCookieVersion() {
		return $this->_rsa_version;
	}

	/**
	 * 获取当前rsa算法所使用的公钥的名字rv[n]
	 * 其内容为base64_encode(公钥)，在配置文件中
	 *
	 * @return string
	 */
	private function _getPublicKeyName() {
		return self::COOKIE_SIGN_VERSION_NAME . $this->_getRsaConfVersion();
	}

	/**
	 * 获取当前配置文件中所使用的RSA版本
	 *
	 * @return int
	 */
	private function _getRsaConfVersion() {
		return $this->_arrConf[self::COOKIE_SIGN_VERSION_NAME];
	}

	/**
	 * 对sup做签名
	 *
	 * @param string $sup
	 * @return string
	 */
	private function _signSUP($sup) {
		return md5($sup);
	}

	/**
	 * 从conf中获取密钥，base64解密
	 * conf中COOKIE_SIGN_VERSION_NAME值为当前正在使用的版本
	 *
	 * @return string
	 */
	private function _getPublicKey() {
		$public_key = $this->_arrConf[$this->_getPublicKeyName()];
		return base64_decode($public_key);
	}

	/**
	 * 验证
	 *
	 * @param string $sign				正确的签名数据
	 * @param string $crypted			签名数据
	 * @param string $public_key		公钥
	 * @throws Exception
	 * @return bool
	 */
	private function _validate($sign, $crypted, $public_key) {
		$decrypted = null;
		if (!openssl_public_decrypt($crypted, $decrypted, openssl_pkey_get_public($public_key))) {
			return false;
			throw new Exception('decrypt error : ' . openssl_error_string());
		}
		return $sign === $decrypted;
	}

	/**
	 * 验证 SUB Cookie
	 *
	 * @return bool false 验证失败
	 *         array 用户信息，验证成功
	 */
	public function validateSUB($sub) {

		$this->_setError('', 0);
		$cookie_info = array();

		// 如果缺少配置，则验证失败，并置错误码
		if(!isset($this->_arrConf['sub_version']) || !isset($this->_arrConf['sub_pub_key']) ||
		   !isset($this->_arrConf['sub_key']) || !isset($this->_arrConf['sub_sign_len'])
		) {
			$this->_setError('sub configuration absent', 100);
			return false;
		}

		$sub_text = base64_decode($sub);

		$sign_len = (int)$this->_arrConf['sub_sign_len']; // 签名长度
		$sign = substr($sub_text, -$sign_len); // 截取数字签名
		$sub_text = substr($sub_text, 0, -$sign_len); // 把签名截掉

		// 取 cookie 版本 （第一个字节）
		$version_byte_1 = $sub_text[0];
		$version = ord($version_byte_1); // cookie 版本
		// 若版本不是当前有效版本，则 cookie 无效，验证失败。
		if($version !== (int)$this->_arrConf['sub_version']) { // 这里写的 1 ，但其实应该有一个可配置的当前版本
			return false;
		}
		$cookie_info['version'] = $version;

		// 校验数字签名
		$pub_key = $this->_arrConf['sub_pub_key']; // 取签名公钥
		$pub_key = pack('H*', $pub_key);
		openssl_public_decrypt($sign, $digest, $pub_key);
		if($digest !== md5($sub_text)) {
			return false; // 校验签名失败，cookie 曾被篡改
		}

		// 解密 cookie 信息
		$encrypt_sub_text = substr($sub_text, 1);
		$key = $this->_arrConf['sub_key']; // 取相应版本的公钥; // 取密钥
		$key = pack('H*', $key);
		$plain_sub_text = mcrypt_decrypt('rijndael-128', $key, $encrypt_sub_text, 'ecb', null);

		// 去掉对齐补位。aes 加密基于块，数据必须对齐到块大小（我们这里是 16 字节）。
		// 为了还原原信息，必须记录下对齐时补了多少字节。我们采取的办法是就用一个 8
		// 位整数作为填充字节，这个整数值就是对齐时补的字节数。于是有下面的逻辑。
		$pad = ord($plain_sub_text[strlen($plain_sub_text) - 1]);
		$plain_sub_text = substr($plain_sub_text, 0, strlen($plain_sub_text) - $pad);

		$offset = 0;

		// 校验 magic byte
		$magic_byte_1 = $plain_sub_text[$offset++];
		if(ord($magic_byte_1) ^ 0xb1) {
			return false; // 魔术字节不对，验证失败
		}

		// 取生成时间。生成时间用一个 32 位整数存 unix 时间戳。按大端方式存储。最左边是高位。于是有以下逻辑。
		$time_byte_4 = substr($plain_sub_text, $offset, 4);
		$time = 0;
		for($i = 0; $i < 4; $i++) {
			$time += ord($time_byte_4[$i]) * pow(256, 3 - $i);
		}
		$cookie_info['create_time'] = date('Y-m-d H:i:s', $time);
		$offset += 4;

		// 取“亚”登录状态。8 位整数标识各种“亚”登录状态，1、2、3、......具体那个数是哪种，后续通知。
		$sub_st_byte_1 = ord($plain_sub_text[$offset++]);
		$cookie_info['status'] = $sub_st_byte_1;

		// 取 cookie 标志位。这里有两字节，16 位。每一位标识一种状态的“开/关”。含义未定，后续通知。
		$flag_byte_2 = substr($plain_sub_text, $offset, 2);
		$flag = ord($flag_byte_2[0]) * 256 + ord($flag_byte_2[1]);
		$cookie_info['flag'] = $flag;
		$offset += 2;

		// 取 uid 长度
		$uid_len = ord($plain_sub_text[$offset++]);

		// 取 uid
		if($uid_len > 0) {
			$uid = substr($plain_sub_text, $offset, $uid_len);
			$cookie_info['uid'] = $uid;
		}
		$offset += $uid_len;

		// 取用户名长度
		$username_len = ord($plain_sub_text[$offset++]);

		// 取用户名
		if($username_len > 0) {
			$uid = substr($plain_sub_text, $offset, $username_len);
			$cookie_info['username'] = $uid;
		}
		$offset += $username_len;

		return $cookie_info;
	}
}
