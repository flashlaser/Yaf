<?php
/**
 * test
 * curl -H "Host:i.miaopai.com" "http://127.0.0.1/test/run.json"
 * 
 * @package controller
 * @author  zhangbaojun  <zhangbaojun@yixia.com>
 */

class TestController extends Abstract_Controller_Internal{

    /**
     * test 
     * 
     * @var string
     */
    public $_test;
    
    /**
     * run
     */
    public function runAction() {
        $r = $this->getRequest();

        Sync_TianxiaModel::creditModify('ovwX21W_yJHUqLzpvGGwmMrodm2k', 13, 2);
        die;


        /*$value = time();
        $ret = Comm_Rdq::write('rq_demo', $value);
        var_dump($ret);exit;*/
        /*$redis = Comm_Redis::r(Comm_Redis::RDQ);
        $ret = $redis->getData('test', array());
        var_dump($ret);exit;
        $ret = $redis->setData('test', array(), 'hi');
        var_dump($ret);exit;*/
        //$rs = CounterModel::gets('feed_user_ym', array(array('205541','205542'), 422, '201702'));
        //$rs = CounterModel::gets('feed_user_ym', array('205541','205542'));
        //var_dump($rs);exit;
        //$ret_cnt = CounterModel::incr('feed_user_ym', array('205542', 422, '201702'));
        //var_dump($ret_cnt);exit;
        //$topic_name = Comm_Argchecker::string($r->getQuery('topic'), 'basic', 2, 2, '');
		//$ret = ConfModel::get('css_version');
		//$ids = array('2097943','573');
		//$rs = Mp_UserModel::infos($ids);
        //$uid   = Helper_Miaopai_User::decodeUid('shX~USWS9JtBa5I~');
        //$to_uid  = Helper_Miaopai_User::decodeUid('9WkIuD7ufdr4Axas');
        //$new_uid = Helper_Miaopai_User::decodeUid('0xVB6TKm8OxfksoO');
        //$new_uid = Helper_Miaopai_User::decodeUid('Z3gmy1vFoReLOiPxjuQcjQ__');
        //var_dump($new_uid);
        //$token = 'Qb2dyIQ9OjBUmBlvjYPkXuCc9IIpbUG9';
        //$s_uid = Helper_Miaopai_User::decodeToken($token);
        //var_dump(Helper_Miaopai_User::encodeUid($s_uid));exit;
        //$ret = Gift_Account_AnchorModel::incr($new_uid, 1);
        //$timestamp = time();
        //$sig = '';
        //Yaf_Registry::set('current_uid', '80510315');
        //$ret = Gift_User_PublicModel::give_gift($token, 'El9GBb~OTIqFT6vz', '6', 1, $timestamp, $sig);
        //var_dump($ret);
        //exit;
        $ret = array('test'=> 'hello world', 'max_execution_time' => ini_get('max_execution_time'));//Gift_Account_AudienceModel::incr($new_uid, 1000000);
        $this->result($ret);
    }

    /**
     * [山子的测试函数]
     * @sonj     Tse
     * @DateTime 2018-07-16T23:23:26+0800
     * @return   [type]                   [description]
     */
    public function sonjAction()
    {
        echo 123;
        $yy = Sync_TianxiaModel::couponInfo('osSTestOpenID2');
        var_dump($yy);
        die;
        echo 123;
        $xx = Sync_TianxiaModel::creditInfo('ovwX21W_yJHUqLzpvGGwmMrodm2k', 0);
        echo 345;
        var_dump($xx);die;
        set_time_limit(0);
        //{"":{"shop_id":25,"openid":"oSsiF1OSfA7opKYOT7sbc2zWpZmA","couponName":"\u963f\u897f","minPrice":"100.00","price":"99.00","startTime":"2018-07-18","endTime":"2018-07-25"}}
        $datas = json_decode('{"":{
    "shop_id": 25,
    "openid": "oSsiF1OSfA7opKYOT7sbc2zWpZmA",
    "couponName": "test券",
    "minPrice": "99.00",
    "price": "50.00",
    "startTime": "2018-07-31",
    "endTime": "2018-08-31",
    "eccode": "1532946430103"
}}', true);
        if (!is_array($datas) || empty($datas)) {
            return false;
        }
        foreach ($datas as $k => $v) {
            $openid = $v['openid'];
            $shop_id = $v['shop_id'];
            $couponName = $v['couponName'];
            $minPrice = $v['minPrice'];
            $price = $v['price'];
            $startTime = $v['startTime'];
            $endTime = $v['endTime'];
            $eccode = $v['eccode'];
            switch ($shop_id) {
                case '25':
                    $ret = Hisense_DhModel::couponCreate($openid,$couponName,$minPrice,$price,$startTime,$endTime, $eccode);
                    var_dump($ret);die;
                    // file_put_contents('/tmp/hisense.log', var_export(['json_encode'=>$v, 'ret'=>$ret])."\t");
                    file_put_contents('/tmp/hisense.log', json_encode(['params'=>$v, 'ret'=>$ret])."\t");
                    break;
                
                default:
                    # code...
                    break;
            }
        }
    }
    
    
    /**
     * run
     */
    public function errAction() {
        ab();
    }
    
    /**
     * mc test
     */
    public function mcAction() {
        $mc = new Memcached();
        $mc->setOption ( Memcached::OPT_BINARY_PROTOCOL, true );
        //$mc->addServer('9139051202d44bdf.m.cnbjalinu16pub001.ocs.aliyuncs.com', 11211);
        //$mc->setSaslAuthData ( '9139051202d44bdf', 'anbs23t4MC' );
        $mc->addServer('0ca859d8b9a847a7.m.cnbjalinu16pub001.ocs.aliyuncs.com', 11211);
        $mc->setSaslAuthData ( '0ca859d8b9a847a7', 'anbs23T4user' );
        $items = array(
                'test_key:1' => 'value1',
                'test_key:2' => 'value2',
                'test_key:3' => 'value3'
        );
        $ret = $mc->setMulti($items, time() + 300);
        //$ret = $mc->set('test_key:4', new stdclass, time() + 300);
        
        $this->result($ret);
    }
    
}
