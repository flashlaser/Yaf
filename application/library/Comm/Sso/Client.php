<?PHP
/**
 * Sina sso client
 * @package SSOWeiboClient
 * @author   lijunjie <junjie2@staff.sina.com.cn>
 * @author   liuzhiyu <zhiyu@staff.sina.com.cn>
 * @version  $Id: SSOWeiboClient.php 45020 2012-05-29 07:13:46Z juefei $
 */


//@include_once('ssoapc.php');

header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
class Comm_Sso_Client extends Comm_Sso_Base {
	private $_version = 'sinasso_weiboclient_1.2'; // 版本号

	private $_debug = true; // 是否输出调试信息（以响应头的形式）
	//配置信息
	private $_arrConfig = array(
				// 是否开启登录漫游保护
				'use_vf' => false,
				// 登录漫游保护跳转目的页面（为一个中转页面，将用户引导到相应类型的验证页面）
				'vf_validate_url' => 'http://account.weibo.com/sguide/vdun',
				// 登录漫游保护验证回调函数。类的用户可提供该函数以取代默认的跳转行为
				'vf_validate_callback' => null,
				// 如果 session 距过期时间小于这个阈值，那么就发送 cookie 给 session server，尝试续时
				'cookie_renew_threshold' => 3600,
				// 如果连续这个次数以上发生因 session server 故障而导致验 session 失败，那么在一定时间以内总返回验证成功（降解服务）
				'session_server_fail_limit' => 10,
				// apc 中存储连续因 session server 或网络原因发生验 session 失败的次数的变量名称
				'session_server_fail_limit_name' => 'sso_ss_ssfln',
				// apc 中存储连续因 session server 或网络原因发生验 session 失败的时间窗的变量名称
				'session_server_fail_time_name' => 'sso_ss_ssftn',
				// 向 session server 发送请求的超时时间
				'session_server_time_out' => 2,
				// 验证 session 失败后的回调函数
				'session_validate_fail_callback' => null,
				// 是否开启 session 功能
				'use_session' => false,
			);

	private $cookie = '';

	private $loginType = ''; // cookie | st
	private $returnType = 'META';

	private $uid = '';
	private $userInfo = '';
	private $_arrCookie;
	private $_arrLoginQuery = array();

	private $_arrVFQuery = array(); // 发送给“登录漫游保护跳转目的页面”的可配置参数

	private $_cookieCheckLevel = 1; // cookie 验证级别
	private $_arrUserInfoCache = array();
	private $_userInfoCacheExpire = 5;

	private static $_allowReEntrantIsLogined = false; // 默认判断登录状态的函数是不能重入的，第二次调用时直接返回第一次调用的结果

	private $_need_validate_session	= true; // 是否需要验证 session

	private static $_arrStatic = array(
				'usedTicket' => array(),
				'checkResult' => array(
					'checked'=>false,
					'result'=>false
					),

				'instance' => array(
					'cookie' => '',
					'loginType' => '',
					'uid' => '',
					'userInfo' => array(),
					'_arrCookie' => array(),
					'_arrUserInfoCache' => array(),
					'error' => '',
					'errno' => 0,
					),
				);

	private $_serviceId = Comm_Sso_Config::SERVICE; // 应用产品的 ID
	private $_entry = Comm_Sso_Config::ENTRY; // 应用产品的 entry 和 pin , 获取用户详细信息使用，由通行证颁发
	private $_pin = Comm_Sso_Config::PIN;

	const E_SYSTEM = 9999; // 系统错误，用户不需要知道错误的原因

	const LOGIN_URL		= 'http://login.sina.com.cn/sso/login.php'; //登录接口
	const VALIDATE_URL 	= 'http://ilogin.sina.com.cn/sso/validate.php'; //验证接口
	const GETSSO_URL 	= 'http://ilogin.sina.com.cn/api/getsso.php'; //获取用户详细信息接口

	const VF_UNVALIDATED = 1; // 用户需要进行登录漫游保护

	/**
	 * @var array 要使用那（些）种 sso 状态
	 */
	private $sso_status_arr = array();

	const SSO_STATUS_LOGIN = 'login';
	const SSO_STATUS_VISITOR = 'visitor';	// 访客状态

	/**
	 * @var string 当前用户处于哪种 sso 状态
	 */
	private $sso_status;

	/**
	 * 构造函数
	 *
	 * @throws Exception 构造过程中发生异常，则抛出异常
	 */
	public function __construct() {
		$this->_arrCookie = $_COOKIE;
		$this->cookie = new Comm_Sso_Cookie();
		if (defined("Comm_Sso_Config::USERINFO_CACHE_EXPIRE")) {
			$this->_userInfoCacheExpire = intval(Comm_Sso_Config::USERINFO_CACHE_EXPIRE);
		}

		// 如果请求头中指明要 debug 信息，那么打开 debug
		if(isset($_SERVER['HTTP_DEBUG_SSOCLIENT']) && $_SERVER['HTTP_DEBUG_SSOCLIENT'] === 'on') {
			$this->_debug = true;
		}

		// 如果debug打开，那么输出版本号
		if($this->_debug) {
			header('ssoclient_version: ' . $this->_version, false);
		}
	}

	/**
	 * 登出
	 *
	 * @return void
	 */
	public function logout() {
		$this->cookie->delCookie();

		// 下面这两个删除cookie主要是针对非sina.com.cn域写的，对sina.com.cn域没有影响
		setcookie('SSOLoginState', 'deleted', 1, '/', Comm_Sso_Config::COOKIE_DOMAIN);
		setcookie('ALF', 'deleted', 1, '/', Comm_Sso_Config::COOKIE_DOMAIN);

		//清除微博域自动登录cookie
		setcookie(Comm_Sso_Cookie::COOKIE_ALC, 'deleted', 1, '/', Comm_Sso_Config::COOKIE_DOMAIN);

		unset($_COOKIE['SSOLoginState']);
		unset($_COOKIE['ALF']);
		unset($_COOKIE[Comm_Sso_Cookie::COOKIE_ALC]);

		// 清除 SUS Cookie （Cookie 中的 session id）
		if (@$_COOKIE['SUS']) {
			// 向 session server 发请求销毁 session
			$this->_destroySession($_COOKIE['SUS']);
			setcookie(Comm_Sso_Cookie::COOKIE_SUS, 'deleted', 1, '/', Comm_Sso_Config::COOKIE_DOMAIN);
			unset($_COOKIE['SUS']);
		}
	}

	/**
	 * 将程序中保持的 cookie 设成自定义的
	 *
	 * @return bool 是否设置成功
	 */
	public function setCustomCookie($arrCookie) {
		if (!$this->cookie->setCustomCookie($arrCookie)) {
			$this->_setError($this->cookie->getError(), $this->cookie->getErrno());
			return false;
		}
		$this->_arrCookie = array_merge($this->_arrCookie, $arrCookie);
		return true;
	}
	
	 /**
	 * 由于现在cookie都是由sso生成，不再需要这种方式种cookie
	 *
	 * @param $arrUserInfo : [ uid | user | ag | email | nick | name | sex | dob | ps]
	 * @return bool
	 */
	public function setCookie($arrUserInfo) {
	    if (!$this->cookie->setCookie($arrUserInfo)) {
			$this->_setError($this->cookie->getError(), $this->cookie->getErrno());
			return false;
		}
		
		/*
		// 是否使用微博自动登录
		$use_weibo_alc	= defined('Comm_Sso_Config::USE_WEIBO_ALC') ? Comm_Sso_Config::USE_WEIBO_ALC : false;
		if ($use_weibo_alc && !$this->cookie->setALCCookie($arrUserInfo['uniqueid'], $this->_entry, $this->_pin)) {
		    $this->_setError($this->cookie->getError(), $this->cookie->getErrno());
		    return false;
		}
		*/
		return true;
	}
	
	/**
	 * 修改方法访问权限，下次可以删除，需要个产品确认没有使用该方法
	 *
	 * get cookie string for setting cookie
	 * @param $arrUserInfo : [ uid | user | ag | email | nick | name | sex | dob | ps]
	 */
	public function getCookieStr($arrUserInfo) {
	    return $this->cookie->getCookieStr($arrUserInfo);
	}

	/**
	 * 该方法未经会员平台允许，不得擅自外部使用，否则后果自负
	 *
	 * @return void
	 */
	public function resetState($key, $val) {
		self::$_arrStatic[$key] = $val;
	}

	/**
	 * 设置 isLogined 函数是否允许重入，该方法未经会员平台允许，不得擅自外部使用，否则后果自负
	 *
	 * @static
	 *
	 * @param bool 是否允许重入
	 *
	 * @return void
	 */
	public static function allowReEntrantIsLogined($bool) {
		self::$_allowReEntrantIsLogined = $bool;
	}

	/**
	 * 判断当前用户（浏览器的使用者）是否处于登录状态
	 *
	 * @param $noRedirect 是否允许在需要的时候访问 sso，js 判断用户登录状态时，该参数可以设置为 1，
	 *                    然后 js 自己去访问 sso，避免使用 iframe 时浏览器兼容问题
	 *
	 * @return bool 是否处于登录状态
	 */
	public function isLogined($noRedirect = 0) {

		// 防止方法重入, 如果已经验证过了，就直接返回结果
		if (self::$_arrStatic['checkResult']['checked']) {
			$this->_restoreInstance();
			return self::$_arrStatic['checkResult']['result'];
		}

		// 为了避免 rewrite 丢掉 url 的参数，这里从 $_SERVER['REQUEST_URI'] 中分析参数，并入 post 参数数组中
		$arrQuery = $matches = array();
		if (preg_match('/\?(.*)$/', $_SERVER['REQUEST_URI'], $matches)) {
			parse_str($matches[1], $arrQuery);
		}
		$arrQuery = array_merge($arrQuery, $_POST);

		// 验证票据
		if (Comm_Sso_Config::USE_SERVICE_TICKET && isset($arrQuery['ticket']) &&
			!in_array($arrQuery['ticket'], self::$_arrStatic['usedTicket'])) {
			// 不管验证成功与否，都直接返回，不能再请求 SSO Server ，否则就死循环了
			if (!self::$_allowReEntrantIsLogined) self::$_arrStatic['usedTicket'][] = $arrQuery['ticket'];

			$this->loginType = 'st';

			$_info = array();
			if (!$this->isValidateST($arrQuery['ticket'], $_info)) {
				// 对于非 sina.com.cn 域，需要删除 SSOLoginState 标志
				if (isset($this->_arrCookie['SSOLoginState'])) {
					$this->logout();  // 删除可能存在的用户身份
				}
				return $this->_checkResult(false);
			}

			// 如果 query 返回的数据中有 sid，则种下（作为 Cookie）sid（session id）
			if(isset($_info['sid']) && !empty($_info['sid'])) {
				$this->_setSession($_info['sid']);
			}

			// 种本域 cookie（SUE/SUP）
			if (!$_info['cookie']) {
				return $this->_checkResult(false);
			}

			// 从 cookie 字符串中解析出用户信息
			$this->cookie->setCookieFromHeaderCookie($_info['cookie']);
			$userInfo = array();
			parse_str($_COOKIE[Comm_Sso_Cookie::COOKIE_SUP], $userInfo);

			// 种 cookie（header 输出 cookie）
			if (!$this->cookie->headerCookie($_info['cookie'])) {
				$this->_setError($this->cookie->getError(), $this->cookie->getErrno());
				return $this->_checkResult(false);
			}

			$this->cookie->setCookieArr(); // 更新 cookie 数组
			$this->_arrCookie = $_COOKIE; // 更新类属性 cookie 数组
			$this->userInfo = $this->cookie->convertUserInfo($userInfo, false); // 更新用户信息

			if (!@$arrQuery['ssosavestate']) { // delete ALF
				$arrQuery['ssosavestate'] = 1;
			}
			setcookie(Comm_Sso_Cookie::COOKIE_ALF, intval($arrQuery['ssosavestate']), intval($arrQuery['ssosavestate']), '/', Comm_Sso_Config::COOKIE_DOMAIN);

			// 如果此时有 ticket 但无 loginState 则将来需要设置 loginstate,针对非 sina.com.cn 域
			if (!isset($this->_arrCookie[Comm_Sso_Cookie::COOKIE_STATE])) {
				setcookie('SSOLoginState', time(), 0, '/', Comm_Sso_Config::COOKIE_DOMAIN);
			}

			// 若开启了漫游保护，且用户撞上了某种保护（cookie 中 vf = 1），则跳转到特定验证页面
			if($this->_arrConfig['use_vf'] && $this->userInfo['vf'] == self::VF_UNVALIDATED) {
				return $this->_checkResult($this->_validateVF());
			}

			$this->sso_status = self::SSO_STATUS_LOGIN;
			return $this->_checkResult(true);
		}

		// 是否使用微博自动登录
		$use_weibo_alc	= defined('Comm_Sso_Config::USE_WEIBO_ALC') ? Comm_Sso_Config::USE_WEIBO_ALC : false;

		if (isset($this->_arrCookie[Comm_Sso_Cookie::COOKIE_SUE]) && isset($this->_arrCookie[Comm_Sso_Cookie::COOKIE_SUP])) {
			//check by cookie
			$userinfo = array();
			$use_rsa_sign = defined('Comm_Sso_Config::USE_RSA_SIGN') ? Comm_Sso_Config::USE_RSA_SIGN : false;

			$this->cookie->setCookieCheckLevel($this->_cookieCheckLevel);
			if ($this->cookie->getCookie($userinfo, $use_rsa_sign)) {

				// 验证 session
				$use_session = $this->_arrConfig['use_session']; // 类的客户是否开启了 session 机制
				$arr_result = array();
				if ($use_session && !$this->_validateSession($userinfo, $arr_result)) {
				    
					$callback = $this->_arrConfig['session_validate_fail_callback'];
					if (!is_callable($callback) || call_user_func($callback, $userinfo) !== false) {
						// 验证失败退出
						$this->logout();
					}

					return $this->_checkResult(false);
				}
				
				// 如果返回数据中有新 cookie 的串，那么就种下新 cookie(续时)
				if(isset($arr_result['cookie']) && is_string($arr_result['cookie'])) {
					$this->cookie->headerCookie($arr_result['cookie']);
				}

				$this->userInfo = $userinfo;
				$this->uid = $this->userInfo['uniqueid'];
				$this->loginType = 'cookie';

				// 补种 WEIBOALC
				if ($use_weibo_alc && isset($this->_arrCookie[Comm_Sso_Cookie::COOKIE_ALF]) && !isset($this->_arrCookie[Comm_Sso_Cookie::COOKIE_ALC])) {
				    $this->cookie->setALCCookie($userinfo['uniqueid'], $this->_entry, $this->_pin);
				}

				// 若开启了漫游保护，如果验证通过，继续往下走，否则，跳转到特定验证页面
				if($this->_arrConfig['use_vf'] && $this->userInfo['vf'] == self::VF_UNVALIDATED) {
					return $this->_checkResult($this->_validateVF());
				}
				
				$this->sso_status = self::SSO_STATUS_LOGIN;
				return $this->_checkResult(true);
			}

			// 无效的cookie试图删除
			$this->cookie->delCookie();
		}

		//WEIBOALC 验证
		if ($use_weibo_alc && isset($this->_arrCookie[Comm_Sso_Cookie::COOKIE_ALF]) && isset($this->_arrCookie[Comm_Sso_Cookie::COOKIE_ALC])) {
            $userinfo = $this->cookie->autoLogin($this->_entry);
			if ($userinfo) {
				$this->userInfo = $userinfo;
				$this->uid = $userinfo['uniqueid'];
				$this->loginType = 'alc';
				$this->sso_status = self::SSO_STATUS_LOGIN;
				
				//update login info as loginip,logintime
				$update_data = array(
				        'last_loginip' => Helper_Ip::get_client_ip(true),
				        'last_logintime' => time(),
				);
				$upret = ModelUser_Detail::update($userinfo['uid'], $update_data);
				
				return $this->_checkResult(true);
			}
		}

		// 这个判断必须出现在判断 SSOLoginState、ALF 之前，否则就死循环了
		if (@$arrQuery['retcode'] != 0) {
			$this->_setError($arrQuery['reason'], $arrQuery['retcode']);
			$this->logout();  // 对于外域也一定要 logout
			return $this->_checkResult(false);
		}

		// only redirect to sso server when SSOLoginState or ALF is set
		/*if (isset($this->_arrCookie[Comm_Sso_Cookie::COOKIE_STATE]) || isset($this->_arrCookie[Comm_Sso_Cookie::COOKIE_ALF])) {
			if ($noRedirect) {
				// 为了方便 js 判断用户状态
				$this->sso_status = self::SSO_STATUS_LOGIN;
				return $this->_checkResult(true);
			}

			//redirect to sso server ,then user will send a new request with ST
			$returnURL  = $this->_getReturnUrl();
			$loginURL = $this->_getLoginUrl();

			$query = array(
				'url'		=> $returnURL,
				'_rand'		=> microtime(1), // 防IE Cache
				'gateway'	=> 1,
				'service'	=> $this->_serviceId,
				'entry'		=> $this->_entry,
				'useticket'	=> Comm_Sso_Config::USE_SERVICE_TICKET ? 1 : 0,
				'returntype'=> $this->returnType,
			);

			$query = array_merge($query, $this->_arrLoginQuery);
			$url = $loginURL.'?'. http_build_query($query);
			header("Cache-Control: no-cache, no-store");
			header("Pragma: no-cache");
			header("Location: $url");
			exit();
		}*/

		if (Comm_Sso_Config::USE_SERVICE_TICKET && isset($arrQuery['retcode']) && $arrQuery['retcode'] != 0) {
			// 对于外域才参考 retcode
			$this->_setError($arrQuery['reason'], $arrQuery['retcode']);
			$this->logout();  // 对于外域也一定要 logout
			return $this->_checkResult(false);
		}

		if (isset($arrQuery['retcode']) && $arrQuery['retcode'] != 0) {
			// 对于
			$this->_setError($arrQuery['reason'], $arrQuery['retcode']);
			$this->logout();
			return $this->_checkResult(false);
		}

		// 识别各种“亚”登录状态
		foreach($this->sso_status_arr as $sso_status) {
			$class_name = 'SinaSSO_Status_' . ucwords($sso_status);
			$file_name = $class_name . '.php';
			if(file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . $file_name)) {
				include(dirname(__FILE__) . DIRECTORY_SEPARATOR . $file_name);
				if(class_exists($class_name)) {
					/**
					 * @var SinaSSO_Status_Abstract $status
					 */
					$status = new $class_name($this->cookie, $this->_getReturnUrl());

					if($status->hit()) {

						$this->userInfo = $status->get_userinfo();
						$this->sso_status = $sso_status;
						return $this->_checkResult(true);
					}
				}
			}
		}

		return $this->_checkResult(false);
	}

	/**
	 * 获取回跳地址
	 *
	 * @return string 返回地址
	 */
	private function _getReturnUrl() {
		$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
		$host = $_SERVER['HTTP_HOST'];

		//由于 7 层做了内容转发，导致此处取到的 HTTP_HOST 可能与用户访问的地址不同，所以设置了一个修正机制
		if (property_exists('Comm_Sso_Config', 'HOST_MAPPING') && !empty(Comm_Sso_Config::$HOST_MAPPING) && isset(Comm_Sso_Config::$HOST_MAPPING[$host])) {
			$host = Comm_Sso_Config::$HOST_MAPPING[$host];
		}

		return $scheme . '://' . $host . $_SERVER['REQUEST_URI'];
	}

	/**
	 * 由于微博反馈登录慢，所以将自动登录的 https 改为 http
	 *
	 * @return 登录 url
	 */
	private function _getLoginUrl() {
		return self::LOGIN_URL;
	}

	/**
	 * 获取用户详细信息,必须保证用户已登录或指定 $uid 参数
	 *
	 * @param string $uid 用户唯一号
	 * @param bool $cache 是否使用缓存（默认使用）
	 *
	 * @return bool | array 成功，返回用户信息数组
	 * 						失败，返回 false
	 */
	public function getUserInfoByUniqueid($uid, $cache = true) {

		$uid = strval($uid);

		if ($cache && $this->_arrUserInfoCache[$uid] && $this->_arrUserInfoCache[$uid]['etime'] < time()) {
			// 缓存命中
			return $this->_arrUserInfoCache[$uid]['data'];
		}

		$m = md5($uid . 0 . $this->_pin);
		$url = self::GETSSO_URL . '?user=' . $uid . '&ag=0&entry=' . $this->_entry . "&m={$m}";

		$ret = @file_get_contents($url);

		if($ret === false){
			$this->_setError('call ' . $url . ' error', self::E_SYSTEM);
			return false;
		}

		parse_str($ret, $arr);
		if($arr['result'] != 'succ'){
			$this->_setError('call ' . $url . " error \n" . $ret . "\n" . $arr['reason'], self::E_SYSTEM);
			return false;
		}

		if ($cache) {
			$this->_arrUserInfoCache[$uid] = array(
				'data' => $arr,
				'etime' => time() + $this->_userInfoCacheExpire,
			);
		}

		return $arr;
	}

	/**
	 * 获取用户信息
	 *
	 * @return array 用户信息数组
	 */
	public function getUserInfo() {
		return $this->userInfo;
	}

	/**
	 * 获取登录方式
	 *
	 * @return string 登录方式
	 */
	public function getLoginType() {
		return $this->loginType;
	}

	/**
	 * 获取用户唯一号
	 *
	 * @return string 用户唯一号
	 */
	public function getUniqueid() {
		return $this->uid;
	}

	/**
	 * 允许给 login.php 自定义参数
	 *
	 * @param array $arrQuery 参数数组
	 *
	 * @return void
	 */
	public function setLoginQuery($arrQuery) {
		$this->_arrLoginQuery = $arrQuery;
	}

	/**
	 * 允许给“漫游保护跳转目的页面”加自定义参数
	 *
	 * @param array $arrQuery 参数数组
	 *
	 * @return void
	 */
	public function setVFQuery($arrQuery) {
		$this->_arrVFQuery = $arrQuery;
	}

	/**
	 * 设置检查是否登录时的返回值类型
	 *
	 * @param string $returntype 返回值类型
	 *
	 * @return void
	 */
	public function setReturntype($returntype){
		$this->returnType = $returntype;
	}

	/**
	 * 设置用户信息缓存时间
	 *
	 * @param int $userInfoCacheExpire 用户信息缓存时间
	 *
	 * @return void
	 */
	public function setUserInfoCacheExpire($userInfoCacheExpire){
		$this->_userInfoCacheExpire = $userInfoCacheExpire;
	}

	/**
	 * 检查 ST 是否有效,成功则通过 $uid 返回用户唯一号
	 * 现在内网验票如果给出 domain 参数，就会返回 cookie
	 *
	 * @param string $ticket 票据 id
	 * @param array $info 引用传递信息
	 *
	 * @return bool 成功与否
	 */
	private function isValidateST($ticket, &$info) {

		// 登录成功后分发到的 ST 再到 SSO 服务器端确认
		$query = array(
			'service'	=> $this->_serviceId,
			'ticket'	=> $ticket,
			'domain'	=> Comm_Sso_Cookie::COOKIE_DOMAIN,
			'ip'		=> $this->_getIp(),
			'agent'		=> $_SERVER['HTTP_USER_AGENT']
		);

		$url = self::VALIDATE_URL . '?' . http_build_query($query);

		$ret = @file_get_contents($url);

		if($ret === false){ // 请求失败
			$this->_setError('call ' . $url . 'error', self::E_SYSTEM);
			return false;
		}

		$result = json_decode($ret, true);

		if($result['retcode'] != 0) { // 验票失败
			$this->_setError('call ' . $url ." error \n" . $ret, self::E_SYSTEM);
			return false;
		}

		$this->uid = $result['uid'];

		$info = $result;

		return true;
	}

	/**
	 * 该函数为了避免isLogined函数重入
	 *
	 * @param $bool
	 *
	 * @return bool 是否处于登录状态
	 */
	private function _checkResult($bool) {

		if (self::$_allowReEntrantIsLogined) {
			return $bool;
		}

		self::$_arrStatic['checkResult'] = array(
				'checked'	=> true,
				'result'	=> $bool,
			);

		$arr = &self::$_arrStatic['instance'];
		foreach($arr as $key => $val) {
			if ($key == 'error' || $key == 'errno') {
				continue;
			}

			$arr[$key] = $this->$key;
		}

		$arr['error'] = $this->getError();
		$arr['errno'] = $this->getErrno();

		return $bool;
	}

	/**
	 * 复原实例
	 *
	 * @return void
	 */
	private function _restoreInstance() {
		$arr = self::$_arrStatic['instance'];

		foreach($arr as $key => $val) {
			if ($key == 'error' || $key == 'errno'){
				continue;
			}

			$this->$key = $val;
		}

		if ($arr['error']) {
			$this->_setError($arr['error'], $arr['errno']);
		}
	}

	/**
	 * 设置配置信息
	 *
	 * @return bool 是否设置成功
	 */
	public function setConfig($name, $value) {

		if(!array_key_exists($name, $this->_arrConfig)) {
			return false;
		}

		$this->_arrConfig[$name] = $value;

		return true;
	}

	/**
	 * 根据用户是否需要进行某种验证，引导其跳转到目的地址进行验证（漫游保护）
	 *
	 * @return bool 注意，如果跳转走的话本函数不返回
	 */
	private function _validateVF() {

		$callback = $this->_arrConfig['vf_validate_callback'];
		if (is_callable($callback)) {
			// 执行指定的回调函数
			return call_user_func($callback, $this->userInfo);
		}

		// 跳转到漫游保护验证页面
		$arrQuery = array(
			'entry'	=> $this->_entry,
			'act'	=> 'validatevsn',
			'url'	=> $this->_getReturnUrl()
		);

		$arrQuery = array_merge($arrQuery, $this->_arrVFQuery);
		$url = $this->_arrConfig['vf_validate_url'] . '?' . http_build_query($arrQuery);
		header("Cache-Control: no-cache, no-store");
		header("Pragma: no-cache");
		header("Location: $url");
		exit();
	}

	/**
	 * 设置是否要验证 session。若 session 需要续时，则不受此限制。
	 *
	 * @param bool $need
	 *
	 * @return void
	 */
	public function need_validate_session($need) {
		$this->_need_validate_session = $need;
	}

	/**
	 * 种下 sid 的 cookie(SUS)
	 *
	 * @param string $sid session id
	 *
	 * @return void
	 */
	private function _setSession($sid) {
		setcookie('SUS', $sid, 0, Comm_Sso_Cookie::COOKIE_PATH, Comm_Sso_Config::COOKIE_DOMAIN);
	}

	/**
	 * 删除 session
	 *
	 * @param string $sid session id
	 *
	 * @return bool 是否成功
	 */
	private function _destroySession($sid) {
		try {
			$result = Comm_Sso_Sessmanager::destroy_by_sid($this->_entry, $sid, $this->_getIp(), Comm_Sso_Config::PIN);
		} catch(Exception $e) {
			$this->_setError($e->getMessage(), $e->getCode());
			return false;
		}

		return true;
	}

	/**
	 * 验证 session
	 * @param array $arr_cookie_info COOKIE 信息数组
	 * @param array $arr_result session manager 的返回信息数组
	 *
	 * @return bool true 验证通过
	 * 				false 验证不通过
	 */
	private function _validateSession($arr_cookie_info, &$arr_result = array()) {

		// 如果用户没有 session，则直接返回 false
		if($arr_cookie_info['us'] != 1) {
			return true;
		}

		//如果连续发生 n 次（可配置）以上因服务器或网络原因而验证不通过，那么在一个时间窗（可配置）内直接验证通过
		$limit = $this->_arrConfig['session_server_fail_limit'];
		$cur_time = time();
		if($this->_get_vft() > $limit) {
			if($cur_time <= $this->_get_vft_timestamp()) {
				return true;
			} else {
				$this->_clear_vft();
			}
		}

		// 如果距离 cookie 过期的时间小于阈值，那么就将 cookie 发送给 session server 尝试进行续时
		$cookie_renew_threshold = $this->_arrConfig['cookie_renew_threshold'];
		$cookie_is_need_renew = isset($arr_cookie_info['et']) &&
									(($arr_cookie_info['et'] - time()) < $cookie_renew_threshold);

		// 如果 cookie 不需要续时，并且类客户设置不验证 session，则直接返回 true
		if(!$cookie_is_need_renew && !$this->_need_validate_session) {
			return true;
		}

		$cookie_str = null;
		if($cookie_is_need_renew) {
			$cookie_str = 'SUE=' . urlencode($this->_arrCookie[Comm_Sso_Cookie::COOKIE_SUE]) .
				';SUP=' . urlencode($this->_arrCookie[Comm_Sso_Cookie::COOKIE_SUP]);
		}

		try {
			if (!$this->_arrCookie[SSOCookie::COOKIE_SUS]) {
				throw new Comm_Sso_Sessionexception("sid is empty", 30022, 0);
			}
			Comm_Sso_Sessmanager::settimeout($this->_arrConfig['session_server_time_out']);
			$arr_result = Comm_Sso_Sessmanager::validate($this->_entry, 		//entry
							$this->_arrCookie[Comm_Sso_Cookie::COOKIE_SUS],	//sid
							Comm_Sso_Config::COOKIE_DOMAIN,					//domain
							$this->_getIp(),							//ip
							Comm_Sso_Config::PIN,								//signkey
							$cookie_str									//cookie
							);

			// 如果session的uid和SUP的uid不一致，那么验证不通过
			if($arr_result['uid'] != $arr_cookie_info['uniqueid']) {
				throw new Comm_Sso_Sessionexception('uid unmatched: ' . $arr_result['uid'], 30022);
			}

		} catch(Comm_Sso_Sessionexception $e) {
			$errno = $e->getCode();
			if($errno != 30022) {
				$delaytime = $e->getDelayTime();
				$this->_increase_vft();
				$this->_set_vft_timestamp($delaytime);
				// 如果不是确定一定以及肯定 session 不存在，都给算验证通过。
				return true;
			} else {
				$this->_clear_vft();
			}

			$this->_setError($e->getMessage(), $e->getCode());
			return false;
		}

		$this->_clear_vft();
		return true;
	}

	/**
	 * 设置 cookie 校验级别
	 *
	 * @param int $level cookie 校验级别
	 *
	 * @return void
	 */
	public function setCookieCheckLevel($level) {
		$this->_cookieCheckLevel = $level;
		$this->cookie->setCookieCheckLevel($this->_cookieCheckLevel);
	}

	/**
	 * 设置校验 ip（用于 IP 验证）
	 *
	 * @param string $ip ip
	 *
	 * @return void
	 */
	public function setCookieIp($ip) {
		$this->cookie->setCookieIp($ip);
	}

	/**
	 * 清零"验证失败计数器"变量（self::VALIDATE_FAIL_CNT）
	 *
	 * @return void
	 */
	private function _clear_vft() {
		Comm_Sso_Apc::getInstance()->set($this->_arrConfig['session_server_fail_limit_name'], 0);
	}

	/**
	 * 将"验证失败计数器"增 1。（没有这个变量相当于变量为 0）
	 *
	 * @return void
	 */
	private function _increase_vft() {
		$cnt = Comm_Sso_Apc::getInstance()->get($this->_arrConfig['session_server_fail_limit_name']);
		if($cnt === false) { //第一次读取此变量，它还不存在，初始化它
			Comm_Sso_Apc::getInstance()->set($this->_arrConfig['session_server_fail_limit_name'], 1);
		} else {
			Comm_Sso_Apc::getInstance()->set($this->_arrConfig['session_server_fail_limit_name'], $cnt + 1);
		}
	}

	/**
	 * 取得"验证失败计数器"。（没有这个变量相当于变量为0）
	 *
	 * @return int
	 */
	private function _get_vft() {
		$cnt = Comm_Sso_Apc::getInstance()->get($this->_arrConfig['session_server_fail_limit_name']);
		if($cnt === false) { //第一次读取此变量，它还不存在，初始化它
			$cnt = 0;
			Comm_Sso_Apc::getInstance()->set($this->_arrConfig['session_server_fail_limit_name'], $cnt);
		}
		return $cnt;
	}

	/**
	 * 设置第一次连续发生n次（可配置）因为服务器或网络故障而验证失败的时间
	 *
	 * @param int $delaytime 延迟时间
	 *
	 * @return void
	 */
	private function _set_vft_timestamp($delaytime) {
		Comm_Sso_Apc::getInstance()->set($this->_arrConfig['session_server_fail_time_name'], time() + $delaytime);
	}

	/**
	 * 取得第一次连续发生n次（可配置）因为服务器或网络故障而验证失败的时间
	 *
	 * @return int
	 */
	private function _get_vft_timestamp() {
		return Comm_Sso_Apc::getInstance()->get($this->_arrConfig['session_server_fail_time_name']);
	}

	/**
	 * 获取客户端ip
	 *
	 * @return string 客户端 ip
	 */
	private function _getIp() {

		$remoteAddr = getenv("REMOTE_ADDR");

		$xForward = getenv("HTTP_X_FORWARDED_FOR");

		if ($xForward) {
			$arr = explode(',', $xForward);
			$cnt = count($arr);
			$xForward = $cnt == 0 ? '' : trim($arr[$cnt - 1]);
		}

		if ($this->_isPrivateIp($remoteAddr) && $xForward) {
			return $xForward;
		}

		return $remoteAddr;
	}

	/**
	 * 判断是否是内网 ip
	 *
	 * @param $ip
	 *
	 * @return bool 是否是内网 ip
	 */
	private function _isPrivateIp($ip) {

		$i = explode('.', $ip);

		if ($i[0] == 10) {
			return true;
		}

		if ($i[0] == 172 && $i[1] > 15 && $i[1] < 32) {
			return true;
		}

		if ($i[0] == 192 && $i[1] == 168) {
			return true;
		}

		return false;
	}

	/**
	 * 设置要接入的 sso 状态
	 *
	 */
	public function use_sso_status() {
		$status_arr = func_get_args();
		foreach($status_arr as $stat) {
			$this->sso_status_arr[] = trim($stat);
		}
	}

	/**
	 * 返回用户的 sso 状态
	 *
	 * @return string
	 */
	public function get_sso_status() {
		return $this->sso_status;
	}
}
