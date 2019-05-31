<?php

/**
 * OPENAPI抽象类
 *
 * @package   Apilib
 * @author    baojun <baojun4545@sina.com>
 * @copyright 2012 weibo.com all rights reserved
 */
abstract class Apilib_Wba {
    //认证方式(Cookie)
    const V_COOKIE = 1;

    //认证方式(Tauth)
    const V_TAUTH = 4;
    
    //认证方式(OAuth)
    const V_OAUTH = 5;

    //每次批量获取最多获取多少条数据
    const BATCH_MAX = 20;

    //认证方式
    protected $_verify_type;
    //认证所需的数据
    protected $_verify_data;
    //当前请求是否是机器人
    protected $_rebot = false;
    //APPKEY
    protected $_akey;
    //内部接口地址前缀
    protected $_base = "https://api.weibo.com";
    //当前用户认证信息
    static public $_current_user_auth = array();

    /**
     * 构造方法
     *
     * @param string $akey
     */
    public function __construct($akey = '') {
        !$akey && $akey = Comm_Config::getUseStatic('app.env.app_key');
        $this->_akey = $akey;
    }

    /**
     * 获取当前用户操作对象（单例模式）
     *
     * @param boolean $if_none_use_rebot 如果当前用户未登录是否获取机器人(默认为否)
     *
     * @return Apilib_Wba
     */
    abstract static public function init();
    
    /**
     * 设置当前用户认证数据
     *
     * @param boolean $use_access_token 是否采用默认账号的access_token
     * 
     * @return Apilib_Wba
     */
    public function setCurrentUserAuth($use_access_token = true) {
        if ($this->_verify_type) {
            trigger_error('该对象已设置认证方式', E_USER_ERROR);
        }

        $verify = self::getCurrentUserAuth();
        $this->_verify_type = $verify['verify_type'];
        $this->_verify_data = $verify['verify_data'];
        $this->_rebot = $verify['rebot'];
        
        if ($use_access_token) {
            $access_token = ModelConf::get('access_token');
            $this->_base = 'https://api.weibo.com'; //暂时使用公网接口，调整好权限之后，去掉此项，使用内网接口
            Yaf_Registry::set('access_token', $access_token);
        }
        
        return $this;
    }

    /**
     * 获取当前用户的认证信息
     *
     * @param boolean $if_none_use_rebot 是否允许机器人认证
     *
     * @return array
     */
    static public function getCurrentUserAuth() {
        //如果有静态结果，直接返回静态结果
        if (self::$_current_user_auth) {
            return self::$_current_user_auth;
        }

        $result = array();
        if ($_COOKIE && isset($_COOKIE['SUE']) && isset($_COOKIE['SUP'])) {
            //Cookie认证
            $sue = str_replace(array('%7E', '+'), array('~', ' '), rawurlencode($_COOKIE['SUE']));
            $sup = str_replace(array('%7E', '+'), array('~', ' '), rawurlencode($_COOKIE['SUP']));
            $cookie = "SUE={$sue}; SUP={$sup}";

            $result = array(
                'verify_type' => self::V_COOKIE,
                'verify_data' => $cookie,
                'rebot' => false,
                'real_user' => true,
            );
        } elseif (($access_token = Yaf_Registry::get('access_token'))) {
            //TAuth认证
            $result = array(
                'verify_type' => self::V_OAUTH,
                'verify_data' => $access_token,
                'rebot' => false,
                'real_user' => true,
            );
        } else {
            //无认证
            $result = array(
                'verify_type' => false,
                'verify_data' => false,
                'rebot' => false,
                'real_user' => false,
            );
        }

        //非CLI模式，缓存当前用户认证数据
        if ($result['real_user'] && !Yaf_Dispatcher::getInstance()->getRequest()->isCli()) {
            self::$_current_user_auth = $result;
        }

        return $result;
    }

    /**
     * 清除当前用户信息
     *
     * @return void
     *
     * @author baojun <baojun4545@sina.com>
     */
    static public function clearCurrentUserAuth() {
        self::$_current_user_auth = array();
    }

    /**
     * 通过GET方式获取数据
     *
     * @param string  $url       请求地址
     * @param array   $param     请求参数
     * @return mixed
     */
    public function get($url, $param = null) {
        $url = "{$this->_base}/{$url}.json?source={$this->_akey}";
        if (Yaf_Registry::get('access_token')) {
            $url = $url . "&access_token=".Yaf_Registry::get('access_token');
        }
        if ($param) {
            $url .= '&' . (is_array($param) ? http_build_query($param) : $param);
        }
        return $this->_process($url, null);
    }

    /**
     * 通过POST方式提交数据
     *
     * @param string  $url       请求地址
     * @param array   $param     请求参数
     *
     * @return mixed
     */
    public function post($url, $param = null) {
        $url = "{$this->_base}/{$url}.json?source={$this->_akey}";
        if (Yaf_Registry::get('access_token')) {
            $url = $url . "&access_token=".Yaf_Registry::get('access_token');
        }
        $param = is_array($param) ? http_build_query($param) : $param;
        return $this->_process($url, $param);
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
    abstract public function postMultipart($url, array $param, array $bin_param);

    /**
     * 获取Multipart的header和body数据
     *
     * @param array   $param     请求参数
     * @param array   $bin_param 请求参数（二进制内容）
     *
     * @return array
     */
    protected function _getMultiData(array $param, array $bin_param) {
        $boundary = uniqid('------------------');
        $MPboundary = '--' . $boundary;
        $endMPboundary = $MPboundary . '--';

        $multipartbody = '';

        //遍历普通参数
        foreach ($param as $key => $value) {
            $multipartbody .= $MPboundary . "\r\n";
            $multipartbody .= 'content-disposition: form-data; name="' . $key . "\"\r\n\r\n";
            $multipartbody .= $value . "\r\n";
        }

        //遍历附件参数
        foreach ($bin_param as $key => $value) {
            $multipartbody .= $MPboundary . "\r\n";
            $multipartbody .= 'Content-Disposition: form-data; name="' . $key . '";';
            $multipartbody .= ' filename="huati.jpg"' . "\r\n";
            $multipartbody .= 'Content-Type: application/octet-stream' . "\r\n\r\n";
            $multipartbody .= $value . "\r\n";
        }

        $multipartbody .= $endMPboundary;

        $header = array(
            "Content-Type: multipart/form-data; boundary=$boundary",
//            "Expect: ",
        );

        return array(
            'header' => $header,
            'body' => $multipartbody,
        );
    }

    /**
     * 处理请求
     *
     * @param string  $url        请求接口URL
     * @param mixed   $post_param POST提交参数
     *
     * @return array
     */
    abstract protected function _process($url, $post_param = null);

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
    abstract public function getBatch($url, $batch_key, array $batch_vals, array $param = array(), $callback = null, array $callback_param = array(), $batch_max = self::BATCH_MAX);

    /**
     * 获取OPEN API识别的批量数据
     *
     * @param mixed $data 一维数组或逗号隔开的数据
     *
     * @return string
     */
    protected function batchData($data) {
        if (is_array($data)) {
            $data = implode(',', $data);
        }
        return $data;
    }

    /**
     * UID或昵称的KEY
     *
     * @param mixed $uid_or_name UID或昵称
     *
     * @return string 字段名称
     */
    protected function uidOrNameKey($uid_or_name) {
        return is_numeric($uid_or_name) ? 'uid' : 'screen_name';
    }

    /**
     * Hashmap以及用指定KEY做KEY，批量获取接口时回调
     *
     * @param array  $result   总结果，引用传递
     * @param string $value    结果
     * @param string $data_key 以哪个KEY作返回值的KEY
     * @param string $hashmap  Hashmap的KEY
     *
     * @return void
     */
    static public function cb_hashmapDatakey(array & $result, $value, $data_key, $hashmap) {
        $data_key && $value = $value[$data_key];
        $hashmap && $value = Helper_Array::hashmap($value, $hashmap);
        $result += $value;
    }

    /**
     * 批量获取微博信息回调
     *
     * @param array  $result
     * @param string $value
     *
     * @return void
     */
    static public function cbStatusesBatch(array & $result, $value) {
        //初始化结果
        if (!$result) {
            $result = array('total_number' => 0, 'statuses' => array(), 'states' => array());
        }

        isset($value['total_number']) && $result['total_number'] += $value['total_number'];
        isset($value['statuses']) && $result['statuses'] = array_merge($result['statuses'], $value['statuses']);
        isset($value['states']) && $result['states'] = array_merge($result['states'], $value['states']);
    }

    /**
     * 在提交的数组中追加spr参数
     *
     * @param array $data 提交的数组
     *
     * @return array
     *
     * @author baojun <baojun4545@sina.com>
     */
    static public function appendSpr(array $data) {

        $spr = '';
        isset($_COOKIE['Apache']) && $spr .= "session:{$_COOKIE['Apache']};";
        isset($_COOKIE['SINAGLOBAL']) && $spr .= "global:{$_COOKIE['SINAGLOBAL']};";
        if (isset($_COOKIE['UOR'])) {
            $uor = explode(',', $_COOKIE['UOR']);
            if (isset($uor[2])) {
                $spr_val = explode(':', $uor[2]);
                $spr_val = isset($spr_val[0]) ? $spr_val[0] : '';
            } else {
                $spr_val = '';
            }
            $spr .= "spr:{$spr_val};";
        }

        if ($spr) {
            $data['spr'] = rtrim($spr, ';');
        }
        return $data;
    }

    /**
     * 用户是否注册
     *
     * @param type $uids
     * @param type $cuid
     * @param type $ip
     *
     * @return type
     */
    public function usersIfRegister($uids, $cuid, $ip) {
        $params['uids'] = $uids;
        $params['cuid'] = $cuid;
        $params['ip'] = $ip;
        $signature = Comm_Config::get('app.env.app_key') . Comm_Config::get('app.env.app_secret');
        $params['signature'] = md5($signature);
        return $this->get('2/register/exists_batch', $params);
    }

    /**
     * 获取用户资料
     *
     * @param mixed $uid_or_name 用户ID或昵称
     * @param int   $has_extend  是否获取扩展信息(0:否，1:是，默认:0)
     *
     * @return array 用户资料
     */
    public function usersShow($uid_or_name, $has_extend = '0') {
        if (!$uid_or_name) {
            return array();
        }
        $user_offset_key = $this->uidOrNameKey($uid_or_name);
        return $this->get('2/users/show', array($user_offset_key => $uid_or_name, 'has_extend' => $has_extend));
    }
    
    /**
     * 获取用户资料 根据个性化域名
     *
     * @param string $domain 个性化域名
     * @param int   $has_extend  是否获取扩展信息(0:否，1:是，默认:0)
     *
     * @return array 用户资料
     */
    public function usersShowByDomain($domain, $has_extend = '0') {
        if (!$domain) {
            return array();
        }
        return $this->get('2/users/domain_show', array('domain' => $domain, 'has_extend' => $has_extend));
    }

    /**
     * 批量获取用户信息
     *
     * @param mixed $uids        用户ID集(传多少个都可以，超过20个，底层每批取20个)
     * @param int   $has_extend  是否获取扩展信息(0:否，1:是，默认:0)
     * @param int   $trim_status user中的status信息开关，trim_status为1时，user中的status字段仅返回status_id，trim_status为0时返回完整status信息。默认trim_status为1
     * @param int   $offset_uid  是否使用UID做为KEY
     * @param int   $is_encoded  返回结果是否转义。0：不转义，1：转义。默认1.
     *
     * @return Ds_User
     */
    public function usersShowBatch(array $uids, $has_extend = '0', $trim_status = '1', $offset_uid = true, $is_encoded = '1') {
        $url = '2/users/show_batch';
        $hashmap_key = $offset_uid ? 'idstr' : null;
        return $this->getBatch(
                $url, 'uids', $uids, array(
                'has_extend' => $has_extend,
                'is_encoded' => $is_encoded,
                'trim_status' => $trim_status
                ), array(get_class($this), 'cb_hashmapDatakey'), array('users', $hashmap_key)
        );
    }

    /**
     * 根据用户昵称，批量获取用户信息
     *
     * @param array  $names       用户昵称集(传多少个都可以，超过20个，底层每批取20个)
     * @param int    $has_extend  是否获取扩展信息(0:否，1:是，默认:0)
     * @param int    $trim_status user中的status信息开关，trim_status为1时，user中的status字段仅返回status_id，trim_status为0时返回完整status信息。默认trim_status为1
     * @param int    $offset_name 是否使用UID做为KEY
     * @param int    $is_encoded  返回结果是否转义。0：不转义，1：转义。默认1.
     * @param string $simplify    结果字体过滤
     *
     * @return Ds_User
     */
    public function usersShowBatchByScreenname(array $names, $has_extend = '0', $trim_status = '1', $offset_name = true, $is_encoded = '1', $simplify = '') {
        $url = '2/users/show_batch';
        $hashmap_key = $offset_name ? 'screen_name' : null;
        return $this->getBatch(
                $url, 'screen_name', $names, array(
                'has_extend' => $has_extend,
                'is_encoded' => $is_encoded,
                'trim_status' => $trim_status
                ), array(get_class($this), 'cb_hashmapDatakey'), array('users', $hashmap_key)
        );
    }

    /**
     * 批量获取用户粉丝数量
     *
     * @param string $uids ：用户id，英文半角逗号分隔，最大100.
     *
     * @return mixed
     */
    public function userFollowersCount($uids, $is_encoded = 0) {
        return $this->getBatch(
                '2/users/counts', 'uids', is_array($uids) ? $uids : explode(',', $uids), array('is_encoded' => $is_encoded), null, array(), 100
        );
    }
    
    /**
     * 获取当前登录用户的UID
     * 
     * @param int $uid
     * 
     * @return array
     */
    public function accountGetuid() {
        return $this->get('2/account/get_uid');
    }

    /**
     * 获取用户绑定的手机号
     *
     * @param $uid 不写默认为当前登录用户
     *
     * @return array
     * array
     * 'binding' => string 'true' (length=4)
     * 'code' => string '' (length=0)
     * 'number' => string '187****××××' (length=11)
     */
    public function accountMobile($uid = null) {
        return $this->get('2/account/mobile', array('uid' => $uid), (boolean)$uid);
    }

    /**
     * 获取用户基本信息。
     *
     * @param bigint $uid 用户ID，不写则为当前登录用户
     *
     * @return array
     */
    public function accountProfileBasic($uid = '') {
        $data = array();
        $uid && $data['uid'] = $uid;
        return $this->get('2/account/profile/basic', $data, (boolean)$uid);
    }

    /**
     * 批量获取用户的隐私信息
     *
     * @param array $uids
     *
     * @return array
     */
    public function accountPrivacyBatch($uids) {
        return $this->get('2/account/get_privacy_batch', array('uids' => $this->batchData($uids)));
    }

    /**
     * 搜索微博接口
     * @see    http://wiki.intra.weibo.com/1/search/statuses
     *
     * @param string $q          查询关键字
     * @param int    $need_count 是否需要统计总数
     * @param int    $page       第几页
     * @param int    $count      每页多少项
     * @param array  $filter     其它条件，参考文档
     *
     * @return mixed
     */
    public function searchStatuses($q, $page = '1', $count = '10', array $filter = array()) {
        $data = array('q' => $q, 'page' => $page, 'count' => $count, 'sid' => 't_huati');
        $data = array_merge($data, $filter);
        array_filter($data, 'strlen');
        return $this->get('2/search/statuses', $data);
    }

    /**
     * user_timeline的高级搜索接口，需登录访问 @author baojun<baojun4545@sina.com>
     * @see http://wiki.intra.weibo.com/1/search/statuses/user_timeline
     *
     * @param bigint $uid    被搜索人
     * @param bigint $cuid   用机器人uid
     * @param int    $start  开始
     * @param int    $count  数量
     * @param array  $filter 扩展参数
     *
     * @return array
     */
    public function searchUserTimeline($uid, $cuid, $start = 0, $count = '10', array $filter = array()) {
        $data = array('uid' => $uid, 'cuid' => $cuid, 'start' => $start, 'num' => $count, 'sid' => 't_huati');
        $data = array_merge($data, $filter);
        array_filter($data, 'strlen');
        return $this->get('2/search/statuses/user_timeline', $data);
    }

    /**
     * 获取一条微博数据
     *
     * @param bigint $id OPEN API 微博ID
     *
     * @return Do_Status
     */
    public function statusesShow($id) {
        return $this->get('2/statuses/show', array('id' => $id));
    }

    /**
     * 批量获取微博信息
     *
     * @param mixed  $ids      批量微博ID
     * @param string $simplify 1:无来源,2:无评论数和转发数,3:无转发原文,4:无作者信息;5:无用户关系,6:不加载备注,7:无用户计数(粉丝数等),8:无在线状态
     *
     * @return array
     */
    public function statusesShowBatch($ids, $simplify = '') {
        $param = array();
        $simplify && $param['simplify'] = $simplify;
        return $this->getBatch(
                '2/statuses/show_batch', 'ids', $ids, $param, array(get_class($this), 'cbStatusesBatch'), array(), 10
        );
    }

    /**
     * 批量获取微博信息
     *
     * @param mixed  $ids      批量微博ID
     * @param string $simplify 1:无来源,2:无评论数和转发数,3:无转发原文,4:无作者信息;5:无用户关系,6:不加载备注,7:无用户计数(粉丝数等),8:无在线状态
     *
     * @return array
     */
    public function statusesShowBatch2($ids, $simplify = '') {
        $result = array('total_number' => 0, 'statuses' => array(), 'states' => array());
        $batch_count = count($ids);
        for ($i = 0; $i < $batch_count; $i += 10) {
            $batch_data_current = array_slice($ids, $i, 10);
            try {
                $param_data = array('ids' => $this->batchData($batch_data_current));
                $simplify && $param_data['simplify'] = $simplify;
                $data = $this->get('2/statuses/show_batch', $param_data);
                isset($data['total_number']) && $result['total_number'] += $data['total_number'];
                isset($data['statuses']) && $result['statuses'] = array_merge($result['statuses'], $data['statuses']);
                isset($data['states']) && $result['states'] = array_merge($result['states'], $data['states']);
            } catch (Exception $e) {

            }
        }

        return $result;
    }

    /**
     * 批量通过微博本身的ID查询OPEN APIID
     * @param	array	$mid		微博本身ID
     * @param	int		$type		id的类型：1为微博，2为评论；3为私信
     * @param	int		$is_batch	是否批量(1是，0否)
     * @param	int		$is_base62
     * @param	int		$inbox
     * @return	mixed
     */
    public function statusesQueryid(array $mid, $type = '1', $is_base62 = '0', $inbox = '0') {
        $batch_count = count($mid);
        $result = array();
        for ($i = 0; $i < $batch_count; $i += self::BATCH_MAX) {
            $batch_data_current = array_slice($mid, $i, self::BATCH_MAX);
            try {
                $data = $this->get('2/statuses/queryid', array('mid' => $this->batchData($batch_data_current), 'type' => $type, 'is_batch' => '1', 'isBase62' => $is_base62, 'inbox' => $inbox));
                $result = array_merge($result, $data);
            } catch (Exception $e) {

            }
        }
        return $result;
    }

    /**
     * 批量通过微博本身的ID查询 MID
     *
     * @param array $id       微博本身ID
     * @param int   $type     id的类型：1为微博，2为评论；3为私信
     * @param int   $is_batch 是否批量(1是，0否)
     * @param int   $is_base62
     * @param int   $inbox
     *
     * @return mixed
     */
    public function statusesQuerymid(array $id, $type = '1', $is_base62 = '0', $inbox = '0') {
        $batch_count = count($id);
        $result = array();
        for ($i = 0; $i < $batch_count; $i += self::BATCH_MAX) {
            $batch_data_current = array_slice($id, $i, self::BATCH_MAX);
            try {
                $data = $this->get('2/statuses/querymid', array('id' => $this->batchData($batch_data_current), 'type' => $type, 'is_batch' => '1'));
                $result = array_merge($result, $data);
            } catch (Exception $e) {

            }
        }
        return $result;
    }

    /**
     * 发表一篇纯文本微博
     *
     * @param string $status      微博内容
     * @param array  $annotations 元数据
     * @param   int     $visible        微博的可见性，0：所有人能看，1：仅自己可见，2:密友可见，默认为0。
     *
     * @return array
     */
    public function statusesUpdate($status, array $annotations = array(), $visible = 0, $list_id = 0) {
        $data = array('status' => $status, 'visible' => $visible);
        if ($annotations) {
            !isset($annotations[0]) && $annotations = array($annotations);
            $data['annotations'] = Helper_Json::encode($annotations);
        }
        if ($list_id) {
            $data['list_id'] = $list_id;
        }
        
        return $this->post('2/statuses/update', self::appendSpr($data));
    }

    /**
     * 发表一篇带图片的微博
     *
     * @param string $status      微博内容
     * @param string $pid         图片ID
     * @param array  $annotations 元数据
     *
     * @return array
     */
    public function statusesUploadUrlText($status, $pid, array $annotations = array()) {
        if (!$pid) {
            return $this->statusesUpdate($status, $annotations);
        }

        $data = array('status' => $status, 'pic_id' => $pid);
        if ($annotations) {
            !isset($annotations[0]) && $annotations = array($annotations);
            $data['annotations'] = Helper_Json::encode($annotations);
        }
        return $this->post('2/statuses/upload_url_text', self::appendSpr($data));
    }

    /**
     * 上传图片并发表微博
     *
     * @param string $status
     * @param string $pic
     * @param array  $annotations
     *
     * @return array
     */
    public function statusesUpload($status, $pic, array $annotations = array()) {
        $data = array('status' => $status);
        if ($annotations) {
            !isset($annotations[0]) && $annotations = array($annotations);
            $data['annotations'] = Helper_Json::encode($annotations);
        }
        $data_bin = array('pic' => $pic);
        return $this->postMultipart('2/statuses/upload', self::appendSpr($data), $data_bin);
    }

    /**
     * 上传图片
     *
     * @param string $pic
     *
     * @return mixed
     */
    public function statusesUploadPic($pic) {
        return $this->postMultipart('statuses/upload_pic', array(), array('pic' => $pic), true);
    }

    /**
     * 转发一条微博
     *
     * @param int    $id         微博ID
     * @param string $status     微博内容
     * @param int    $is_comment 是否在转发的同时发表评论。0表示不发表评论，1表示发表评论给当前微博，2表示发表评论给原微博，3是1、2都发表。默认为0。
     * @param int    $is_encoded 返回结果是否转义。0：不转义，1：转义，默认1
     *
     * @return array
     */
    public function statusesRepost($id, $status = '', $is_comment = '0', array $annotations = null, $is_encoded = null) {
        $data = array('id' => $id, 'is_comment' => $is_comment, 'status' => $status);
        if ($annotations) {
            !isset($annotations[0]) && $annotations = array($annotations);
            $data['annotations'] = json_encode($annotations);
        }
        if (is_numeric($is_encoded)) {
            $data['is_encoded'] = $is_encoded;
        }
        return $this->post('2/statuses/repost', self::appendSpr($data));
    }

    /**
     * 删除一条微博数据
     *
     * @param bigint $id 微博ID
     *
     * @return array
     */
    public function statusesDestroy($id) {
        return $this->post('2/statuses/destroy', array('id' => $id));
    }

    /**
     * 获取表情数据
     *
     * @param enum $type     表情类别。"face":普通表情，"ani"：魔法表情，"cartoon"：动漫表情 默认为face
     * @param enum $language 语言类别。"cnname"简体，"twname"繁体"默认为cnname
     *
     * @return array
     */
    public function emotions($type = 'face', $language = 'cnname') {
        $type = Comm_Argchecker::enum($type, 'enum,face,ani,cartoon', 2, 3, 'face');
        $language = Comm_Argchecker::enum($language, 'enum,cnname,twname', 2, 3, 'cnname');
        return $this->get('2/emotions', array('type' => $type, 'language' => $language));
    }

    /**
     * 主键ID
     *
     * @param int $id               微博ID
     * @param int $max_id           若指定此参数，则返回ID小于或等于max_id的微博消息。默认为0
     * @param int $filter_by_author 筛选类型（0：全部，1：我关注的人，2：陌生人，3：认证用户）默认为0
     * @param int $count            返回结果条数数量，默认50
     * @param int $is_asc           是否正序排列(0.倒序，1.正序)
     * @param int $since_id         若指定此参数，则只返回ID比since_id大的微博消息（即比since_id发表时间晚的微博消息）。默认为0
     * @param int $page             指定返回结果的页码。
     *
     * @return array
     */
    public function commentsShow($id, $page = '1', $filter_by_author = '0', $count = '50', $is_asc = '0', $since_id = '0', $max_id = '0') {
        return $this->get('2/comments/show', array('id' => $id, 'filter_by_author' => $filter_by_author, 'count' => $count, 'is_asc' => $is_asc, 'page' => $page, 'since_id' => $since_id, 'max_id' => $max_id));
    }

    /**
     * 发表评论
     *
     * @param int     $id          微博OPEN API ID
     * @param string  $comment     评论内容
     * @param int     $comment_ori 是否评论给原微博。0:不评论给原微博。1：评论给原微博。默认0.
     * @param boolean $no_tips     是否不@提醒
     *
     * @return array
     */
    public function commentsCreate($id, $comment, $comment_ori = '0', $no_tips = '0') {
        return $this->post('2/comments/create', self::appendSpr(array(
                    'id' => $id,
                    'comment' => $comment,
                    'comment_ori' => $comment_ori,
                    'no_tips' => $no_tips,
                ))
        );
    }

    /**
     * 回复评论
     *
     * @param int    $id          微博OPEN API ID
     * @param int    $cid         被回复ID
     * @param string $comment     评论内容
     * @param int    $comment_ori 是否评论给原微博。0:不评论给原微博。1：评论给原微博。默认0.
     * @param int    $no_tips     是否不@提醒
     *
     * @return array
     */
    public function commentsReply($id, $cid, $comment, $comment_ori = '0', $no_tips = '0') {
        return $this->post('2/comments/reply', self::appendSpr(array(
                    'id' => $id,
                    'cid' => $cid,
                    'comment' => $comment,
                    'comment_ori' => $comment_ori,
                    'no_tips' => $no_tips,
                )));
    }

    /**
     * 删除一条评论
     *
     * @param int $cid
     *
     * @return array
     */
    public function commentsDestroy($cid) {
        return $this->post('2/comments/destroy', array('cid' => $cid));
    }

    /**
     * 根据评论ID批量获取评论内容
     *
     * @param array $cids
     *
     * @return array
     */
    public function commentsShowBatch(array $cids) {
        return $this->get('2/comments/show_batch', array('cids' => $this->batchData($cids)));
    }

    /**
     * 获取某条评论对应的对话内容。
     *
     * @param int $cid   评论ID
     * @param int $count 返回结果条数数量，默认6，最大不超过10。
     *
     * @return array
     */
    public function commentsConversation($cid, $count = 6) {
        return $this->get('2/comments/conversation', array('cid' => $cid, 'count' => $count));
    }

    /**
     * 获取用户关注列表
     *
     * @param mixed $uid_or_name UID或昵称
     * @param int   $cursor      游标
     * @param int   $count       每页获取多少项
     * @param int   $trim_status user中的status字段仅返回id，trim_status为0时返回完整status信息。默认trim_status为1。
     * @param int   $order       关注列表的排序。0：按关注时间排序；1：按最近更新排序；2：按昵称首字母排序。默认0.
     *
     * @return array
     */
    public function friendshipsFriends($uid_or_name, $cursor = '-1', $count = '50', $trim_status = '1', $order = '0') {
        $user_offset_key = $this->uidOrNameKey($uid_or_name);
        return $this->get('2/friendships/friends', array($user_offset_key => $uid_or_name, 'cursor' => $cursor, 'count' => $count, 'trim_status' => $trim_status, 'order' => $order));
    }

    /**
     * 获取用户粉丝列表
     *
     * @param mixed $uid_or_name UID或昵称
     * @param int   $cursor      游标
     * @param int   $count       每页获取多少项
     * @param int   $trim_status user中的status字段仅返回id，trim_status为0时返回完整status信息。默认trim_status为1。
     *
     * @return array
     */
    public function friendshipsFollowers($uid_or_name, $cursor = '-1', $count = '50', $trim_status = '1') {
        $user_offset_key = $this->uidOrNameKey($uid_or_name);
        return $this->get('2/friendships/followers', array($user_offset_key => $uid_or_name, 'cursor' => $cursor, 'count' => $count, 'trim_status' => $trim_status));
    }

    /**
     * 获取用户粉丝Ids列表
     *
     * @param mixed $uid_or_name UID或昵称
     * @param int   $cursor      游标
     * @param int   $count       每页获取多少项
     *
     * @return array
     */
    public function friendshipsFollowerIds($uid_or_name, $cursor = '-1', $count = '50') {
        $user_offset_key = $this->uidOrNameKey($uid_or_name);
        return $this->get('2/friendships/followers/ids', array($user_offset_key => $uid_or_name, 'cursor' => $cursor, 'count' => $count));
    }

    /**
     * 返回用户关注的一组用户的ID列表
     *
     * @param mixed $uid_or_name UID或昵称
     * @param int   $cursor      游标
     * @param int   $count       每页获取多少项
     *
     * @return array
     */
    public function friendshipsFriendsIds($uid_or_name, $cursor = '-1', $count = '50') {
        $user_offset_key = $this->uidOrNameKey($uid_or_name);
        return $this->get('2/friendships/friends/ids', array($user_offset_key => $uid_or_name, 'cursor' => $cursor, 'count' => $count));
    }

    /**
     * 获取双向关注列表
     *
     * @param bigint $uid
     * @param int    $count
     * @param int    $page
     *
     * @return array
     */
    public function friendshipsFriendsBilateral($uid, $count = '50', $page = '0') {
        return $this->get('2/friendships/friends/bilateral', array('uid' => $uid, 'count' => $count, 'page' => $page));
    }

    /**
     * 获取当前登录用户的关注人中，关注了指定用户的用户列表。
     *
     * @param bigint $uid
     * @param int    $count
     * @param int    $page
     *
     * @return array
     */
    public function friendshipsFriendsChain($uid, $count = '50', $page = '0') {
        return $this->get('2/friendships/friends_chain/followers', array('uid' => $uid, 'count' => $count, 'page' => $page));
    }

    /**
     * 关注某用户
     *
     * @param mixed $uid_or_name UID或用户昵称
     * @param int   $skip_check  是否跳过安全检测0:否,1:是,默认否
     *
     * @return array
     */
    public function friendshipsCreate($uid_or_name, $skip_check = '0') {
        $user_offset_key = $this->uidOrNameKey($uid_or_name);
        return $this->post('2/friendships/create', array($user_offset_key => $uid_or_name, 'skip_check' => $skip_check));
    }

    /**
     * 批量关注用户
     *
     * @param mixed $uids 用户ID集(最多20个)
     *
     * @return array 成功返回{"result":true}
     */
    public function friendshipsCreateBatch($uids) {
        return $this->post('2/friendships/create_batch', array('uids' => $this->batchData($uids)));
    }

    /**
     * 取消关注
     *
     * @param mixed $uid_or_name 用户ID或昵称
     *
     * @return array
     */
    public function friendshipsDestroy($uid_or_name) {
        $user_offset_key = $this->uidOrNameKey($uid_or_name);
        return $this->post('2/friendships/destroy', array($user_offset_key => $uid_or_name));
    }

    /**
     * 获取两个用户关系的详细情况
     *
     * @param mixed $source_uid_or_name 源用户ID或昵称
     * @param mixed $target_uid_or_name 新用户ID或昵称
     *
     * @return array
     */
    public function friendshipsShow($source_uid_or_name, $target_uid_or_name) {
        $source_offset_key = Helper_Validator::isUid($source_uid_or_name) ? 'source_id' : 'source_screen_name';
        $target_offset_key = Helper_Validator::isUid($target_uid_or_name) ? 'target_id' : 'target_screen_name';
        return $this->get('friendships/show', array($source_offset_key => $source_uid_or_name, $target_offset_key => $target_uid_or_name));
    }

    /**
     * 收藏一条微博
     *
     * @param bigint $id 微博ID
     *
     * @return array 收藏数据及微博数据
     */
    public function favoritesCreate($id) {
        return $this->post('favorites/create', array('id' => $id));
    }

    /**
     * 取消一条收藏
     *
     * @param int $id 微博ID
     *
     * @return array 收藏数据及微博数据
     */
    public function favoritesDestroy($id) {
        return $this->post('favorites/destroy', array('id' => $id));
    }

    /**
     * at联想搜索
     *
     * @param string $q    查询字符串
     * @param int    $type  0代表关注人，1代表最近联系的1000个粉丝，2代表关注人关注的用户，3为互相关注用户。
     * @param int    $count 返回多少条数据（默认10）
     * @param int    $range 0代表只查用户昵称，1代表只搜索当前用户对关注人的备注，2表示都查. 默认为2.
     *
     * @return array
     */
    public function searchSuggestionsAtUsers($q, $type, $count = '10', $range = '2') {
        return $this->get('2/search/suggestions/at_users', array('q' => $q, 'count' => $count, 'type' => $type, 'range' => $range, 'sid' => 't_huati'));
    }

    /**
     * 话题联想搜索
     *
     * @param string $q
     * @param int    $count
     *
     * @return array
     */
    public function searchSuggestionsTopics($q, $count = 10) {
        return $this->get('2/search/suggestions/topics', array('q' => $q, 'count' => $count, 'sid' => 't_huati'));
    }

    /**
     * 音乐联想接口
     *
     * @param string $q
     * @param int    $count
     *
     * @return array
     */
    public function searchSuggestionsMusics($q, $count = 10) {
        return $this->get('2/search/suggestions/musics', array(
                'q' => $q,
                'count' => $count,
                'sid' => 't_huati',
            ));
    }

    /**
     * 获得当前登陆用户的水印设置
     *
     * @return array 水印设置信息
     */
    public function accountWatermark() {
        return $this->get('2/account/watermark', array());
    }

    /**
     * 更新当前用户信息
     *
     * @param array $data
     *
     * @return array
     * @deprecated
     *     screen_name     false     string     用户昵称。
      real_name     false     string     用户真实姓名。
      real_name_visible     false     int     真实姓名可见范围， 0：自己可见、1：我关注人可见、2：所有人可见。
      province     false     int     省份代码。
      city     false     int     城市代码。
      birthday     false     date     用户生日，格式：yyyy-mm-dd。
      birthday_visible     false     int     生日可见范围，0：保密、1：只显示月日、2：只显示星座、3：所有人可见。
      qq     false     string     用户qq号码。
      qq_visible     false     int     用户qq可见范围，0：自己可见、1：我关注人可见、2：所有人可见。
      msn     false     string     用户msn。
      msn_visible     false     int     用户msn可见范围，0：自己可见、1：我关注人可见、2：所有人可见。
      url     false     string     博客地址。
      url_visible     false     int     用户博客地址可见范围，0：自己可见、1：我关注人可见、2：所有人可见。
      gender     false     string     用户性别，m：男、f：女。
      credentials_type     false     int     证件类型，1-身份证、2-学生证、3-军官证、4-护照、5-港澳台身份证。
      credentials_num     false     string     证件号码。
      email     false     string     常用邮箱地址。
      email_visible     false     int     用户Email地址可见范围，0：自己可见、1：我关注人可见、2：所有人可见。
      lang     false     string     语言版本，可选值：zh-cn：简体中文、zh-tw：繁体中文、en：英语。
      description     false     string     一句话描述，最长不超过70个汉字。
     */
    public function accountProfileBasicUpdate(array $data) {
        return $this->post('2/account/profile/basic_update', $data);
    }

    /**
     * 获取今日热门话题
     *
     * @param int $base_app 是否基于当前应用来获取数据。1表示基于当前应用来获取数据。
     *
     * @return array
     */
    public function trendsDaily($base_app = 0) {
        return $this->get('2/trends/daily', array('base_app' => $base_app));
    }

    /**
     * 检查用户是否关注了某一批用户，返回已关注的用户uid列表,若都没关注则返回空数组
     *
     * @param bigint $uid     用户uid
     * @param array  $arr_uid 用户uid列表
     *
     * @return array
     */
    public function friendshipsExistsBatchInternal($uid, $arr_uid) {
        $ret = $this->get('2/friendships/exists_batch_internal', array('uid' => $uid, 'uids' => $this->batchData($arr_uid)));
        return Helper_Array::cols($ret, 'id');
    }

    /**
     * 判断当前登录用户与批量提供的用户的相互关系
     * @author baojun <baojun4545@sina.com>
     *
     * @param array $uids 要查询的用户ids
     *
     * @return array 对于当前登录用户来说，待判断用户是：0：陌生人；1：粉丝；2：关注用户；3：互粉用户；4:黑名单。
     */
    public function friendshipsBatchExists($uids) {
        return $this->getBatch(
                '2/friendships/batch_exists', 'uids', $uids
        );
    }

    /**
     * 将一个或多个短链接转换成长链接
     *
     * @param mixed $url_short 多个封装为数组
     *
     * @return array
     */
    public function shortUrlExpand($url_short, $mark = 0) {
        if (is_array($url_short)) {
            $query = '';
            foreach ($url_long as $value) {
                $query .= 'url_short=' . rawurlencode($value) . '&';
            }
        } else {
            $query = array('url_short' => $url_long);
        }
        $query['mark'] = $mark;
        return $this->get('2/short_url/expand', $query);
    }

    /**
     * 更新短链内容
     *
     * @param string $long_url 长链接
     *
     * @return array 短链信息
     */
    public function sinaurlSecureUpdate($long_url) {
        return $this->get("sinaurl/secure/update", array("url" => $long_url));
    }

    /**
     * 批量获取图床的图片信息
     *
     * @param mixed $pids
     *
     * @return array
     */
    public function imagesShowBatch($pids) {
        return $this->post('images/show_batch', array('pids' => $this->batchData($pids)), true);
    }

    /**
     * 获得用户的相册列表
     *
     * @param int $uid   目标用户ID
     * @param int $page  页面数
     * @param int $count 每页显示条数
     *
     * @return array
     */
    public function photosAlbum($uid = '', $page = 1, $count = 20) {
        $data = array('page' => $page, 'count' => $count);
        if ($uid) {
            $data['uid'] = $uid;
        }
        return $this->get('2/photos/album', $data);
    }

    /**
     * 获得用户相册的图片列有
     *
     * @param int $album_id 相册ID
     * @param int $page     页面数
     * @param int $count    每页显示条数
     *
     * @return array
     */
    public function photosAlbumPhoto($album_id, $page = 1, $count = 20) {
        return $this->get('2/photos/album/photo', array('album_id' => $album_id, 'page' => $page, 'count' => $count));
    }

    /**
     * 获得用户的相册数和图片数
     *
     * @param int $uid 目标用户ID
     *
     * @return array
     */
    public function photosCounts($uid = '') {
        $data = array();
        if ($uid) {
            $data['uid'] = $uid;
        }
        return $this->get('2/photos/counts', $data);
    }

    /**
     * 获得单个相册信息
     *
     * @param string $album_id      相册ID
     * @param int    $skip_question 是否跳过回答问题
     *
     * @return array
     */
    public function albumInfo($album_id, $skip_question = 0) {
        $data = array('album_id' => $album_id);
        if ($skip_question) {
            $data['skip_question'] = $skip_question;
        }
        return $this->get('2/photos/album/show', $data);
    }

    /**
     * 检查照片描述是否有不能发表的关键词
     *
     * @param string $content 描述内容
     *
     * @return array
     */
    public function adminContentCheckKeyword($content) {
        if (!$content) {
            return array('result' => true);
        }
        return $this->post('proxy/admin/content/check_keyword', array('content' => $content), true);
    }

    /**
     * 获取后台更新版本号
     *
     * @param int $type 3：顶导版本号
     *
     * @return array array( "result"=>"9112550ad9730570" )
     */
    public function adminContentVersion($type = 3) {
        return $this->get('proxy/admin/content/version', array('type' => $type));
    }

    /**
     * 根据APPID获取base62的APPKEY
     *
     * @param array $ids
     *
     * @return array
     */
    public function appsAppkey62($ids) {
        return $this->get('2/apps/get_appkey62', array('ids' => $this->batchData($ids)));
    }

    /**
     * 批量获取短链的详细信息
     *
     * @param mixed $url_short
     *
     * @return array
     */
    public function shorUrlInfo($url_short) {
        if (is_array($url_short)) {
            $query = '';
            foreach ($url_short as $value) {
                $query .= 'url_short=' . $value . '&';
            }
        } else {
            $query = array('url_short' => $url_short);
        }
        return $this->get('2/short_url/info', $query);
    }

    /**
     * 将一个或多个长链接转换成短链接
     *
     * @param mixed $url_long 多个封装为数组
     *
     * @return array
     */
    public function shortUrlShorten($url_long) {
        if (is_array($url_long)) {
            $query = '';
            foreach ($url_long as $value) {
                $query .= 'url_long=' . rawurlencode($value) . '&';
            }
        } else {
            $query = array('url_long' => $url_long);
        }

        return $this->post('2/short_url/shorten', $query);
    }

    /**
     * 更新短链通知
     *
     * @param string $url
     *
     * @return array
     */
    public function updateShortUrlNotice($url) {
        $query = array('url' => $url);
        return $this->get('sinaurl/secure/update', $query);
    }

    /**
     * 检测指定用户是否在登录用户的黑名单内。
     * @param	int		$uid	需要检测的用户ID
     * @param	int		$invert	反转判断方向。即判断当前登录用户是否在指定UID的黑名单内。0：不反转，1：反转。默认0.
     * @return	array
     */
    public function blocksExists($uid, $invert = '0') {
        return $this->get('blocks/exists', array('uid' => $uid, 'invert' => $invert));
    }

    /**
     * 接触黑名单
     *
     * @param int $uid
     *
     * @return array
     */
    public function blockDestroy($uid) {
        return $this->post('2/blocks/destroy', array('uid' => $uid));
    }

    /**
     * 首页右侧热门话题(主站专用,带推荐位)
     *
     * @param int $uid
     * @param int $province_id
     * @param int $city_id
     *
     * @return array
     */
    public function trendsHotSp($uid, $province_id, $city_id) {
        return $this->get('2/trends/hot_sp', array('uid' => $uid, 'pid' => $province_id, 'cid' => $city_id), false);
    }

    /**
     * 举报
     *
     * @param string $url
     * @param string $content
     * @param string $ip
     * @param int    $type
     * @param int    $rid
     * @param int    $class_id
     *
     * @return array
     */
    public function reportSpam($url, $content, $ip, $type, $rid, $class_id) {
        return $this->post('2/report/spam', array('url' => $url, 'content' => $content, 'ip' => $ip, 'type' => $type, 'rid' => $rid, 'class_id ' => $class_id));
    }

    /**
     * 举报某条信息，是新版接口，report/spam是老版接口。add by baojun@staff
     *
     * @param int    $rid
     * @param int    $type
     * @param int    $class_id
     * @param string $ip
     * @param string $url
     * @param string $title
     * @param string $content
     *
     * @return array
     */
    public function complaintExposure($rid, $type, $class_id, $ip, $url, $title = '', $content = '') {
        return $this->post('2/proxy/admin/complaint/exposure', array(
                'rid' => $rid,
                'type' => $type,
                'class_id' => $class_id,
                'ip' => $ip,
                'url' => $url,
                'title' => $content,
                'content' => $content,
                )
        );
    }

    /**
     * 检测监控是否关闭微吧
     *
     * @return array
     */
    public function checkCloseWeiba() {
        return $this->get('2/proxy/admin/switch/weiba');
    }

    /**
     * 监控微吧关键词检测接口
     *
     * @param int    $uid
     * @param string $text
     * @param string $ip
     * @param int    $usertype
     * @param int    $userlevel
     * @param int    $type
     *
     * @return  array
     */
    public function checkWeibaKeyword($uid, $text, $ip, $usertype, $userlevel, $type) {
        return $this->post('2/proxy/admin/weiba/check', array(
                'uid' => $uid,
                'text' => $text,
                'ip' => $ip,
                'usertype' => $usertype,
                'userlevel' => $userlevel,
                'type' => $type,
                )
        );
    }

    /**
     * 带图内容入监控后台
     *
     * @param string $content json字符串，数组原型如下：
     * array(
     * 'pid'=>'', //图片id，多个id以逗号分隔
     * 'target_id'=>'',// 业务内容id：帖子或回复ID
     * 'type'=>'', //约定的type值 帖子1，回复2
     * 'appid'=>'406' //应用ID，私钥：87f6f07857
     * 'token' =>'' //加密串，md5($appid."_".私钥."_".$api_time)
     * 'api_time' =>' ' //调用时间 时间戳
     * )
     * @return array
     */
    public function adminSetPidContent($content) {
        return $this->post('2/proxy/admin/content/set_pidcontent', array(
                'content' => $content,
                )
        );
    }

    /**
     * 发勋章，wiki:http://tech.intra.weibo.com/BadgeApi:issue,http://tech.intra.weibo.com/BadgeApi:2/proxy/badges/issue.json
     *
     * @param string $uids       uid1,uid2
     * @param string $badge_code 勋章id
     * @param int    $app_key    app_key
     *
     * @return array
     */
    public function badgeIssue($uids, $badge_code, $app_key) {
        $param = array('source' => $app_key, 'uids' => $uids, 'badge_id' => $badge_code);
        $url = '2/proxy/badges/issue';
        return $this->post($url, $param);
    }

    /**
     * 发勋章，wiki:http://tech.intra.weibo.com/BadgeApi:remove,http://tech.intra.weibo.com/BadgeApi:2/proxy/badges/remove.json
     *
     * @param string $uids       uid1,uid2
     * @param string $badge_code 勋章id
     * @param int    $app_key    app_key
     *
     * @return array
     */
    public function badgeRemove($uids, $badge_code, $app_key) {
        $param = array('source' => $app_key, 'uids' => $uids, 'badge_id' => $badge_code);
        $url = '2/proxy/badges/remove';
        return $this->post($url, $param);
    }

    /**
     * 获取勋章信息：http://wiki.intra.weibo.com/2/proxy/badges/show
     *
     * @param int $uid      uid1,uid2
     * @param int $badge_id 勋章id，不是code，是id
     *
     * @return array
     */
    public function badgeShow($uid, $badge_id) {
        $param = array('uid' => $uid, 'bid' => $badge_id);
        $url = '2/proxy/badges/show';
        return $this->get($url, $param);
    }

    /**
     * 获取勋章信息：http://wiki.intra.weibo.com/2/proxy/badges/show
     *
     * @param string $badge_code 勋章code
     *
     * @return array
     */
    public function badgeCounts($badge_code) {
        $param = array('code' => $badge_code);
        $url = '2/proxy/badges/counts';
        return $this->get($url, $param);
    }

    /**
     * 创建一条新的行为动态
     *
     * @param string $tpl_id    模板ID
     * @param string $object_id 实体唯一ID
     * @param array  $object    操作实体扩展元数据
     *
     * @return array
     */
    public function activitiesAppsUpdate($tpl_id, $object_id, array $object) {
        return $this->post('2/activities/apps/update', array(
                'tpl_id' => $tpl_id,
                'object_id' => $object_id,
                'object' => json_encode($object),
                )
        );
    }

    /**
     * 批量获取指定微博的转发评论数，微博最多100个，英文逗号分隔，by baojun
     *
     * @param string $mids_str 微博ids
     *
     * @return array
     */
    public function statusesCount($mids_str) {
        $param = array("ids" => $mids_str);
        $url = "2/statuses/count";
        return $this->get($url, $param);
    }

    /**
     * 检查文本关键字
     *
     * @param string $content
     *
     * @return  array
     */
    public function checkKeyword($content) {
        return $this->post('2/proxy/admin/content/check_keyword', array(
                'content' => $content,
                )
        );
    }

    /**
     * 获得某一应用下某用户所有的通知
     * @param string $appkey62 appkey的经过base62编码
     * @param int $page 当前页
     * @param int $count 每页显示个数
     * @return array
     */
    public function notificationReceiveListByApp($appkey62, $page = 1, $count = 20) {
        $param = array(
            'appkey62' => $appkey62,
            'page' => $page,
            'count' => $count,
        );
        $url = '2/notification/receive_list_by_app';
        return $this->get($url, $param);
    }

    /**
     * 为当前登录用户某一种消息未读数设置计数
     * @param string $type 消息类型
     * @param int $value 未读消息数
     * @param string $appkey62 appkey的经过base62编码
     * @return boolean
     */
    public function remindSetCount($type, $value = 0, $appkey62 = '') {
        $param = array(
            'type' => $type,
            'appkey' => $appkey62,
            'value' => $value,
        );
        $url = '2/remind/set_count';
        return $this->post($url, $param);
    }

    /**
     * 按分类返回热门微博榜
     *
     * @param category 主站微博主分类 + 子分类，参见：http://wiki.intra.weibo.com/2/proxy/statuses/hot/category
     * @param int $page 页数
     * @param int $count 每页个数
     *
     * @return array
     */
    public function statusesHotCategory($category, $page = 1, $count = 3) {
        $param = array(
            'category' => $category,
            'page' => $page,
            'count' => $count,
        );
        $url = '2/proxy/statuses/hot/category';
        return $this->get($url, $param);
    }

    /**
     * 获取用户等级信息
     *
     * @param  int $uid 用户id
     * @return array
     */
    public function urankShowRank($uid) {
        $param = array(
            'uid' => $uid,
        );
        $url = '2/proxy/urank/show_rank';
        return $this->get($url, $param);
    }

    /**
     * 获取当前用户的隐私设置
     *
     * @return  array
     */
    public function accountPrivacy() {
        return $this->get('2/account/get_privacy', null, false);
    }

    /**
     * 获取某人话题
     *
     * @param	int		$base_app	是否基于当前应用来获取数据。1表示基于当前应用来获取数据。
     *
     * @return	array
     */
    public function trends($uid, $page = 1, $count = 10) {
        $params = array(
            'uid' => $uid,
            'page' => $page,
            'count' => $count
        );
        return $this->get('2/trends', $params);
    }

    /**
     * 某个用户是否喜欢过某个对象
     *
     * @param object        $object_id　对象id
     * @param boolen or int $uid 用户id 不填为当前用户
     *
     * @return mixed
     */
    public function objectLikeExist($object_id, $uid = false) {
        $params['object_id'] = $object_id;
        if ($uid) {
            $params['uid'] = $uid;
        }
        return $this->get('2/likes/exist', $params);
    }

    /**
     * 喜欢某个对象
     * @param unknown_type $data
     * @return Ambigous <mixed, multitype:>
     */
    public function objectLike($data) {
        return $this->post('2/likes/update', $data);
    }

    public function objectimports($data) {
        return $this->posttemp('2/likes/imports', $data);
    }

    /**
     * 取消对某个对象的喜欢
     * @param unknown_type $object_id
     */
    public function objectDisLike($object_id) {
        $params = array(
            'object_id' => $object_id,
        );
        return $this->post('2/likes/destroy', $params);
    }

    /**
     * 根据ID获取单个对象信息
     *
     * @param string $object_id objid
     *
     * @return array
     */
    public function objectShow($object_id) {
        $params = array(
            'object_id' => $object_id,
        );
        return $this->get('2/object/show', $params);
    }

    /**
     * 取对象的喜欢数
     * @param unknown_type $object_id
     * @return Ambigous <mixed, multitype:>
     */
    public function objectCount($object_id) {
        $params = array(
            'object_ids' => $object_id,
        );
        return $this->get('2/likes/counts', $params);
    }

    /**
     * 我发出的喜欢列表 http://wiki.intra.weibo.com/2/likes/by_me
     *
     * @param int    $uid         uid
     * @param string $object_type object_type
     * @param int    $page        page
     * @param int    $count       count
     *
     * @return array
     */
    public function objectLikeByMe($uid, $object_type, $page, $count) {
        $params['uid'] = $uid;
        $params['object_type'] = $object_type;
        $params['page'] = $page;
        $params['count'] = $count;
        return $this->get('2/likes/by_me', $params);
    }

    /**
     * 批量获取指定的一批用户timeline。根据传递进来的用户ids参数，获取指定用户的微博timeline
     *
     * @param unknown_type $uids
     * @param unknown_type $screen_names
     */
    public function statusesTimelineBatch($uids, array $filter = array()) {
        $data = array('uids' => $uids);
        $data = array_merge($data, $filter);
        array_filter($data, 'strlen');
        return $this->get('2/statuses/timeline_batch', $data);
    }
    
    /**
     * 获取当前登录用户发布的微博消息列表
     * wiki:http://wiki.intra.weibo.com/1/statuses/user_timeline
     * 
     * @param unknown_type $uid_or_name
     * @param unknown_type $filter
     */
    public function statusesUserTimeline($uid_or_name, $filter = array()) {
        $user_offset_key = $this->uidOrNameKey($uid_or_name);
        $data = array($user_offset_key => $uid_or_name);
        $data = array_merge($data, $filter);
        array_filter($data, 'strlen');

        return $this->get('2/statuses/user_timeline', $data);
    }

    /**
     * 获取热门微吧
     *
     * @param string $keyword 关键词，为空表示推荐的热门尾巴
     * @param int    $count   推荐微吧条数
     *
     * @return array
     */
    public function getRecommendHotBars($keyword, $count) {
        return $this->get("2/proxy/weiba/recommend/hot_bars", array('keyword' => $keyword, "count" => $count));
    }

    /**
     * 从微吧获取热门帖子
     *
     * @param int    $count
     *
     * @return array
     */
    public function getHotTopicFromWeiba($count) {
        return $this->get("2/proxy/weiba/hot/posts", array("count" => $count, "simplify" => 1));
    }

    /**
     * 通过接口获取热门微博
     *
     * @param int    $count
     *
     * @return array
     */
    public function getHotMblog($count) {
        return $this->get("2/proxy/statuses/hot/category", array("count" => $count, "category" => 9999));
    }

    /**
     * 发表或更新一条表态
     * http://wiki.intra.weibo.com/2/attitudes/create
     *
     * @author baojun@
     * @param int64 $id           微博ID
     * @param string $attitude    表态内容，"smile"：呵呵，"naughty"：挤眼，“surprise”：吃惊，"sad"：悲伤，“heart”：心。
     * @param string $mark        行为扩展信息，例如：1_AB21321XDFJJK，其中1表示置顶微博，AB21321XDFJJK是广告透传信息
     * @param int    $is_encoded  返回结果是否转义，0：不转义，1：转义，默认为0
     * @return Ambigous <mixed, multitype:>
     */
    public function attitudesCreate($id, $attitude = 'heart', $mark = null, $is_encoded = 0) {
        return $this->post('2/attitudes/create', array('id' => $id, 'attitude' => $attitude, 'mark' => $mark, 'is_encoded' => $is_encoded));
    }

    /**
     * 删除一条表态
     * http://wiki.intra.weibo.com/2/attitudes/destroy
     *
     * @author baojun@
     * @param int $id
     * @param int $attid
     * @param number $is_encoded
     * @return Ambigous <mixed, multitype:>
     */
    public function attitudesDestroy($id, $attid = null, $is_encoded = 0) {
        return $this->post('2/attitudes/destroy', array('id' => $id, 'attid' => $attid, 'is_encoded' => $is_encoded));
    }

    /**
     * 批量获取当前用户对微博是否表态过
     * @param	mixed	$ids		批量微博ID
     * @return	array
     */
    public function attitudesExists(array $ids) {
        $result = array();
        $batch_data_current = array_slice($ids, 0, 200);
        try {
            $param_data = array('ids' => $this->batchData($batch_data_current));
            $result = $this->get('2/attitudes/exists', $param_data);
        } catch (Exception $e) {

        }

        return $result;
    }
    
    /**
     * 批量获取微博的表态次数 
     * wiki:http://wiki.intra.weibo.com/2/attitudes/counts
     * @param	mixed	$ids		批量微博ID
     * @param   string  $type       根据传入的type类型，返回对应表态类型的数据。smile：微笑，naughty：顽皮，surprise：惊讶，sad：难过，heart：爱心。默认heart。
     * @return	array
     */
    public function attitudesCounts(array $ids, $type='heart') {
        $result = array();
        $batch_data_current = array_slice($ids, 0, 200);
        try {
            $param_data = array('ids' => $this->batchData($batch_data_current), 'type' => $type);
            $result = $this->get('2/attitudes/counts', $param_data);
        } catch (Exception $e) {
    
        }
    
        return $result;
    }

    /**
     * 获取国家列表
     * @param  enum  $language
     * @return array
     */
    public function getCountry($language = 'zh-cn') {
        $language = Comm_Argchecker::enum($language, 'enum,zh-cn,zh-tw,english');
        return $this->get('2/common/get_country', array('langeuage' => $language));
    }

    /**
     * 获取省份列表
     * @param unknown_type $country
     * @param unknown_type $language
     * @return Ambigous <mixed, multitype:>
     */
    public function getProvince($country, $language = 'zh-cn') {
        $language = Comm_Argchecker::enum($language, 'enum,zh-cn,zh-tw,english');
        return $this->get('2/common/get_province', array(
                'country' => $country,
                'language' => $language,
            ));
    }

    /**
     * 批量获得微吧信息
     * @param array $bids 微吧ID
     * @param int $simplify 是否获取精简信息
     * @param int $extend 是否获取扩展信息
     * @return array
     */
    public function weibaBarShowbatch($bids, $simplify = 0, $extend = 0) {
        return $this->get('2/proxy/weiba/bar/show_batch', array(
                'bids' => $bids,
                'simplify' => $simplify,
                'extend' => $extend
                ), false);
    }
    /**
     * @author lingling5
     * @desc 获取关注用户的微博
     * @param int $page        返回结果的页码，默认为1。
     * @param  int $count      单页返回的记录条数，最大不超过100，默认为20。
     * @param  int64 $since_id 若指定此参数，则返回ID比since_id大的微博（即比since_id时间晚的微博），默认为0。
     * @param  int64 $max_id   若指定此参数，则返回ID小于或等于max_id的微博，默认为0。
     * @param  int $feature    过滤类型ID，0：全部、1：原创、2：图片、3：视频、4：音乐，默认为0。
     */
    public function statusesFriendsTimeline($page=1, $count=10, $since_id=0, $max_id=0,$feature=0) {
        
    	$param['page'] = $page;
    	$param['count'] = $count;
    	$param['since_id'] = $since_id;
    	$param['max_id'] = $max_id;
    	$param['feature'] = $feature;
    	$ret = $this->get('2/statuses/friends_timeline',$param);
    	return $ret;
    }
    
    /**
     * 创建分组
     * http://wiki.intra.weibo.com/1/lists/create
     * 
     * @param string $name name
     * @param string $mode mode
     * @param string $description desc
     * @param number $is_encoded is_encoded
     * 
     * @return Ambigous <mixed, multitype:>
     */
    public function listsCreate($name, $mode='public', $description='', $is_encoded=1) {
        $data = array('name' => $name, 'mode' => $mode, 'description' => $description, 'is_encoded' => $is_encoded);
        
        return $this->post('2/lists/create', self::appendSpr($data));
    }
    
    /**
     * 更新分组
     * http://wiki.intra.weibo.com/1/lists/update
     *
     * @param string $name name
     * @param int    $list_id list_id
     * @param string $mode mode
     * @param string $description desc
     * @param number $is_encoded is_encoded
     *
     * @return Ambigous <mixed, multitype:>
     */
    public function listsUpdate($name, $list_id, $description='', $is_encoded=1) {
        $data = array('name' => $name, 'list_id' => $list_id, 'description' => $description, 'is_encoded' => $is_encoded);
    
        return $this->post('2/lists/update', self::appendSpr($data));
    }
    
    /**
     * 删除分组
     * http://wiki.intra.weibo.com/1/lists/destroy
     *
     * @param int    $list_id list_id
     * @param number $is_encoded is_encoded
     *
     * @return Ambigous <mixed, multitype:>
     */
    public function listsDestroy($list_id, $is_encoded=1) {
        $data = array('list_id' => $list_id, 'is_encoded' => $is_encoded);
    
        return $this->post('2/lists/destroy', self::appendSpr($data));
    }
    
    /**
     * 将多个用户添加到分组中。用户只能将其他用户添加到自己创建的list中。 每个list最多拥有500个用户。私有列表只能添加自己关注的人。
     * http://wiki.intra.weibo.com/1/lists/member/add_users
     *
     * @param int    $list_id list_id
     * @param string $uids uids
     * @param number $is_encoded is_encoded
     *
     * @return Ambigous <mixed, multitype:>
     */
    public function listsMemberAddUsers($list_id, $uids, $is_encoded=1) {
        $data = array('list_id' => $list_id, 'uids' => $uids, 'is_encoded' => $is_encoded);
    
        return $this->post('2/lists/member/add_users', self::appendSpr($data));
    }
    
    /**
     * 将用户添加到list中。用户只能将其他用户添加到自己创建的list中。 每个list最多拥有500个用户。私有列表只能添加自己关注的人。
     * http://wiki.intra.weibo.com/1/lists/member/add
     *
     * @param int    $list_id list_id
     * @param string $uid uid
     * @param number $is_encoded is_encoded
     *
     * @return Ambigous <mixed, multitype:>
     */
    public function listsMemberAdd($list_id, $uid, $is_encoded=1) {
        $data = array('list_id' => $list_id, 'uid' => $uid, 'is_encoded' => $is_encoded);
    
        return $this->post('2/lists/member/add', self::appendSpr($data));
    }
    
    /**
     * 将用户从list中删除。只有登录用户可以从自己创建的list中删除用户。
     * http://wiki.intra.weibo.com/1/lists/member/destory
     *
     * @param int    $list_id list_id
     * @param string $uid uid
     * @param number $is_encoded is_encoded
     *
     * @return Ambigous <mixed, multitype:>
     */
    public function listsMemberDestory($list_id, $uid, $is_encoded=1) {
        $data = array('list_id' => $list_id, 'uid' => $uid, 'is_encoded' => $is_encoded);
    
        return $this->post('2/lists/member/destroy', self::appendSpr($data));
    }
    
    /**
     * 获取指定用户的LIST列表
     * http://wiki.intra.weibo.com/1/lists/user/own_lists
     * 
     * @author baojun
     * @param int $uid
     * @param number $list_type 获取的list的属性。0：公有列表。1：私有列表。默认为0.当要求返回私有列表时，当前用户必须为私有列表的创建者
     * @param int $cursor   将结果分页，每页包含20个list。由-1开始分页，定位一个id地址，通过比较id大小实现next_cursor和previous_cursor向前或向后翻页
     * @param number $is_encoded
     * @return Ambigous <mixed, multitype:>
     */
    public function listsUserOwnlists($uid, $list_type=0, $cursor=-1, $is_encoded=1) {
        $param['uid'] = $uid;
        $param['list_type'] = $list_type;
        $param['cursor'] = $cursor;
        $param['is_encoded'] = $is_encoded;
        $result = $this->get('2/lists/user/own_lists',$param);
        
        return $result;
    }
    
    /**
     * 返回list中所有的成员。对于私有list，当前用户仅能查看自己创建的私有list成员
     * http://wiki.intra.weibo.com/1/lists/members/show
     *
     * @author baojun
     * @param int $uid
     * @param number $list_id
     * @param int $cursor   将结果分页，每页包含20个list。由-1开始分页，定位一个id地址，通过比较id大小实现next_cursor和previous_cursor向前或向后翻页
     * @param number $is_encoded
     * @return Ambigous <mixed, multitype:>
     */
    public function listsMembersShow($uid, $list_id=0, $cursor, $is_encoded=1) {
        $param['uid'] = $uid;
        $param['list_id'] = $list_id;
        $param['cursor'] = $cursor;
        $param['is_encoded'] = $is_encoded;
        $result = $this->get('2/lists/show/members',$param);
    
        return $result;
    }
    
    /**
     * 展示LIST成员的最新微博，私有list的列表只能自己可以访问
     * http://wiki.intra.weibo.com/1/lists/members/timeline
     *
     * @author baojun
     * @param int $uid uid
     * @param number $list_id list_id
     * @param int $page page
     * @param int $count count
     * @param int $since_id 返回带有比指定list ID大的ID（比指定list的ID新的）的结果。被进入API的微博数会被限制。如果当微博的限制达到时，since_id将被强制到最老的可用ID。
     * @param int $max_id   返回带有一个小于（就是比较老的）或等于指定list ID的ID的结果
     * @param number $is_encoded
     * @return Ambigous <mixed, multitype:>
     */
    public function listsMembersTimeline($uid, $list_id=0, $page=1, $count=10, $since_id=0, $max_id=0, $base_app=0, $feature=0, $is_filtered=0, $is_encoded=0) {
        $param['uid'] = $uid;
        $param['list_id'] = $list_id;
        $param['page'] = $page;
        $param['count'] = $count;
        $param['since_id'] = $since_id;
        $param['max_id'] = $max_id;
        $param['base_app'] = $base_app;
        $param['feature'] = $feature;
        $param['is_filtered'] = $is_filtered;
        $param['is_encoded'] = $is_encoded;
        $result = $this->get('2/lists/members/timeline',$param);
    
        return $result;
    }
    
    /**
     * 展示LIST成员的最新微博id，私有list的列表只能自己可以访问
     * http://wiki.intra.weibo.com/1/lists/members/timeline/ids
     *
     * @author baojun
     * @param int $uid uid
     * @param number $list_id list_id
     * @param int $page page
     * @param int $count count
     * @param int $since_id 返回带有比指定list ID大的ID（比指定list的ID新的）的结果。被进入API的微博数会被限制。如果当微博的限制达到时，since_id将被强制到最老的可用ID。
     * @param int $max_id   返回带有一个小于（就是比较老的）或等于指定list ID的ID的结果
     * @param number $is_encoded
     * @return Ambigous <mixed, multitype:>
     */
    public function listsMembersTimelineIds($uid, $list_id=0, $page=1, $count=10, $since_id=null, $max_id=null, $base_app=0, $feature=0, $is_filtered=0, $is_encoded=0) {
        $param['uid'] = $uid;
        $param['list_id'] = $list_id;
        $param['page'] = $page;
        $param['count'] = $count;
        $since_id && $param['since_id'] = $since_id;
        $max_id && $param['max_id'] = $max_id;
        $param['base_app'] = $base_app;
        $param['feature'] = $feature;
        $param['is_filtered'] = $is_filtered;
        $param['is_encoded'] = $is_encoded;
        $result = $this->get('2/lists/members/timeline/ids',$param);
    
        return $result;
    }

}