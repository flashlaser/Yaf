<?php

/**
 * 服务器环境检测基础类
 *
 * 此类提供了一些基本的检测项目，基本上是不依赖特殊扩展或环境可进行的检测
 * 如要扩展检测项目，请继承该类然后调用 _addCheck() 添加检测项目
 *
 * @package Helper
 * @author  张宝军 <baojun4545@sina.com>
 */

class Helper_Scheck{

    /**
     * 检测成功的数据项是否输出显示
     * @var int
     */
    public $is_show_right = 0;
    protected $is_web = true;
    /**
     * 检查类型配置
     * @var array
     */
    protected $all_check  = array(
        'server' => array(
            'func'  => '_checkServer',
            'desc'  => '$_SERVER预定义变量检查'
        ),
        'class' => array(
            'func'     => '_checkClass',
            'desc'     => '类定义（来自php扩展或自定义）检查'
        ),
        'function' => array(
            'func'  => '_checkFunction',
            'desc'  => '函数定义（来自php扩展或自定义）检查'
        ),
        'mysql' => array(
            'func'      => '_checkMysql',
            'desc'      => '检查MySQL数据库链接'
        ),
        'table' => array(
            'func'      => '_checkTable',
            'desc'      => '数据表是否存在'
        ),
        'memcached' => array(
            'func'   => '_checkMemcached',
            'desc'   => '检查Memcached读写删测试'
        ),
		'aliyunmc' => array(
			'func'   => '_checkAliyunMc',
			'desc'   => '检查AliyunMc服务可用性'
		),
        'redis' => array(
                'func'   => '_checkRedis',
                'desc'   => '检查Redis读写删测试'
        ),
		'aliyunredis' => array(
			'func'   => '_checkAliyunRedis',
			'desc'   => '检查AliyunRedis服务可用性'
		),
        'dir_file' => array(
            'func'          => '_checkDirFile',
            'desc'          => '目录/文件读写检查'
        ),
        'call_function' => array(
            'func'      => '_checkCallFuction',
            'desc'      => '通用检测',
        ),
		'rds' => array(
			'func'      => '_checkRds',
			'desc'      => '检查Rds资源'
		),
    );
    protected $now_check_name = '';
    //配置需检查的项目
    protected $allow_check    = array();//'server'   => 1, 'class'    => 1);
    //检查所需数据
    protected $conf_data = array();

    protected $html = '';

    /**
     * construct
     */
    public function __construct() {

    }

    /**
     * 设置配置数据
     *
     * @param string $check_name 检查名称，必须是$this->all_check里有的名称
     * @param array  $conf       配置数据，格式与$this->check_name对应，见$conf_data
     * 
     * @return void
     */
    public function setConfig($check_name, $conf) {
        if (!isset($this->all_check[$check_name])) {
            exit("setConfig() 第一个参数错误：{$check_name} 不存在。line：" . __LINE__);
        }

        $this->allow_check[$check_name] = 1;
        $this->conf_data[$check_name] = $conf;
    }

    /**
     * 开始运行
     *
     * @param array $arr_get get的数据
     * 
     * @return void
     */
    public function webRun($arr_get) {
        $check_name = empty($arr_get['name']) ? '' : trim($arr_get['name']);
        if (isset($this->all_check[$check_name]) && isset($this->allow_check[$check_name])) {
            error_reporting(0);
            $this->_loopCheck($check_name);
        } else {
            $arr_conf = array_intersect_key($this->all_check, $this->allow_check);
            $this->_pageInit($arr_conf);
        }
    }

    /**
     * cli run
     * 
     * @return string
     */
    public function cliRun() {
        $this->is_web = false;
        $n = 0;
        foreach ($this->allow_check as $check_name => $v) {
            if (!isset($this->all_check[$check_name])) {
                continue;
            }
            ++$n;
            $this->html .= '<h2>'.$n.'.'.$this->all_check[$check_name]['desc'].'</h2>'."\n";
            $this->_loopCheck($check_name);
        }

        return $this->html;
    }

    /**
     * 添加一个检测方法
     *
     * @param string $check_name 检查项目名称
     * @param array  $func       处理方法名称
     * @param string $desc       检查项目描述
     * 
     * @return boolean
     */
    public function addCheck($check_name, $func, $desc) {
        if (isset($this->all_check[$check_name])) {
            exit("{$check_name}已经存在，不能再添加. line:" . __LINE__);
        }
        if (!is_callable($func)) {
            exit("\$func 无法调用，addCheck失败。line：" . __LINE__);
        }

        $this->all_check[$check_name] = array('func' => $func, 'desc' => '[扩展]' . $desc);

        return true;
    }

    /**
     * 循环检查数据
     * 
     * @param string $check_name 检测名称
     * 
     * @return void
     */
    protected function _loopCheck($check_name) {
        $this->now_check_name = $check_name;
        $err_n = 0;
        foreach ($this->conf_data[$check_name] as $v) {
            //兼容简略配置
            if (is_array($v)) {
                $arg  = $v['arg'];
                $desc = $v['desc'];
            } else {
                $arg  = $desc = $v;
            }

            $func  = $this->all_check[$check_name]['func'];
            if (!is_array($func)) {
                $func = array($this, $func);
            }
            $ret = call_user_func($func, $arg);
            if ($ret !== false) { //只有返回true才是通过检测的
                $desc.=' √ ' . $ret;
                $this->_output($desc, 1);
            } else {
                ++$err_n;
                $desc .= ($ret ? " <{$ret}>" : ''); //显示附加的错误信息
                $this->_output($desc, 0);
            }
        }

        $this->_showResult(count($this->conf_data[$check_name]), $err_n);
    }

    /**
     * 检查$_SERVER
     * 
     * @param array $arg 检测用的参数
     * 
     * @return bool
     */
    protected function _checkServer($arg) {
        return isset($_SERVER[$arg]);
    }

    /**
     * 检查类定义，包括php扩展提供和可自动加载的自定义类
     * 
     * @param array $arg 检测用的参数
     * 
     * @return bool
     */
    protected function _checkClass($arg) {
        //捕获自动加载出错可能出现的异常
        try {
            $ret = class_exists($arg, true);
        } catch (Exception $e) {
            $ret = false;
        }

        return $ret;
    }

    /**
     * 检查函数定义，用于检测php扩展提供
     * 
     * @param array $arg 检测用的参数
     * 
     * @return bool
     */
    protected function _checkFunction($arg) {
        return function_exists($arg);
    }

    /**
     * 检查数据库链接
     * 
     * @param array $arg 检测用的参数
     * 
     * @return bool
     */
    protected function _checkMysql($arg) {
        //$link = mysql_connect($arg['host'] . ':' . $arg['port'], $arg['user'], $arg['pass']);
        //return $link && mysql_select_db($arg['db'], $link);
        try {
            $db = Comm_Db::d($arg['srv_key']);
            $pdo = $db->getPdo(Comm_Dbbase::CONN_MASTER);
        } catch (Exception $e) {
            return "[".$e->getCode()."]".$e->getMessage();
        }
        
        return true;
    }
    
    /**
     * 检查Rds资源
     * 
     * @param array $arg 检测用的参数
     * 
     * @return bool
     */
    protected function _checkRds($arg) {
    	try {
    		$rs = array();
    		foreach ($arg['dimensions'] as $metric => $dimensions) {
    			$ret = Apilib_Cms::init(Apilib_Cms::RDS)->setMetric($metric)->setPeriod($arg['period'])->query($dimensions, $arg['start_time'], null, 0, 1);
    			$rs[] = isset($ret['Datapoints']['Datapoint'][0]['Average'])? $metric . ':' .$ret['Datapoints']['Datapoint'][0]['Average']. " " : '';
    		}
    		return implode(" ", $rs);
    	} catch (Exception $e) {
    		return "[".$e->getCode()."]".$e->getMessage();
    	}
    
    	return true;
    }

    /**
     * 检查aliyun redis资源
     * 
     * @param array $arg 检测用的参数
     * 
     * @return bool
     */
    protected function _checkAliyunRedis($arg) {
    	try {
    		$rs = array();
    		$dimensions = array('instanceId' => $arg['instanceId']);
    		foreach ($arg['dimensions'] as $metric) {
    			$ret = Apilib_Cms::init(Apilib_Cms::REDIS)->setMetric($metric)->setPeriod($arg['period'])->query($dimensions, $arg['start_time'], null, 0, 1);
    			$rs[] = isset($ret['Datapoints']['Datapoint'][0]['Average'])? $metric . ':' .$ret['Datapoints']['Datapoint'][0]['Average']. " " : '';
    		}
    		return implode(" ", $rs);
    	} catch (Exception $e) {
    		return "[".$e->getCode()."]".$e->getMessage();
    	}
    
    	return true;
    }
    
    /**
     * 检查aliyun mc资源
     * 
     * @param mixed $arg 检测用的参数
     * 
     * @return bool
     */
    protected function _checkAliyunMc($arg) {
    	try {
    		$rs = array();
    		$dimensions = array('instanceId' => $arg['instanceId']);
    		foreach ($arg['dimensions'] as $metric) {
    			$ret = Apilib_Cms::init(Apilib_Cms::MC)->setMetric($metric)->setPeriod($arg['period'])->query($dimensions, $arg['start_time'], null, 0, 1);
    			$rs[] = isset($ret['Datapoints']['Datapoint'][0]['Average'])? $metric . ':' .$ret['Datapoints']['Datapoint'][0]['Average']. " " : '';
    			if ($metric == 'UsedQps') {
    				$rs[] = "TotalQps:". $arg['total_qps'];
    			}
    		}
    		return implode(" ", $rs);
    	} catch (Exception $e) {
    		return "[".$e->getCode()."]".$e->getMessage();
    	}
    
    	return true;
    }
    
    /**
     * 检查memcacheq的连接
     * 
     * @param string $arg 检测用的参数
     * 
     * @return bool
     */
    protected function _checkMemcacheq($arg) {
        $arr = explode(':', $arg);
        $mcq = new Memcached();
        $mcq->setOption(Memcached::OPT_COMPRESSION, false);
        $ret = $mcq->addServer($arr[0], $arr[1]);

        return $ret;
    }

    /**
     * 检查mc的读写删
     * 
     * @param array $arg arg
     * 
     * @return boolean
     */
    protected function _checkMemcached($arg) {
        try {
            //连接mc
            $mc   = Comm_Mc::init($arg['srv_key']);

            $mc_key = 'scheck_' . time();

            //写
            $ret = $mc->set($mc_key, 1, 30);
            if (!$ret) {
                return false;
            }

            //读
            $ret = $mc->get($mc_key);
            if (!$ret) {
                return false;
            }

            //删
            $ret = $mc->delete($mc_key);
            if (!$ret) {
                return false;
            }

            //再次检查
            if ($mc->get($mc_key)) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            return "[".$e->getCode()."]".$e->getMessage();
        }
    }

    /**
     * 检查redis的读写删
     * 
     * @param array $arg arg
     * 
     * @return boolean
     */
    protected function _checkRedis($arg) {
        try {
            //连接redis
            $redis = Comm_Redis::r($arg['srv_key']);
            
            //ping
            $ret = $redis->ping();
            if ($ret != '+PONG') {
            	return false;
            }
            
            $key = $redis->makeKey('scheck', array($time));

            //写
            $ret = $redis->SETEX($key, 30, 1);
            if (!$ret) {
                return false;
            }
    
            //读
            $ret = $redis->get($key);
            if (!$ret) {
                return false;
            }
    
            //删
            $ret = $redis->delete($key);
            if (!$ret) {
                return false;
            }
    
            //再次检查
            if ($redis->get($key)) {
                return false;
            }
    
            return true;
        } catch (Exception $e) {
            return "[".$e->getCode()."]".$e->getMessage();
        }
    }
    
    /**
     * 检查目录/文件的读写权限
     * 
     * @param type $arg arg
     * 
     * @return string|boolean
     */
    protected function _checkDirFile($arg) {
        if (empty($arg['mod']) || empty($arg['path'])) {
            return 'config error: mod or path not exist';
        }
        $mod = strtolower($arg['mod']);

        if (strpos($mod, 'r') && !is_readable($arg['path'])) {
            return 'not readable';
        }

        if (strpos($mod, 'w') && !is_writable($arg['path'])) {
            return 'not writable';
        }

        return true;
    }

    /**
     * 检测表是否存在
     *
     * $arg = array(
     *      'host' => '',
     *      'port' => '',
     *      'username' => '',
     *      'password' => '',
     *      'database' => '',
     *      'table' => '',
     * );
     *
     * @param array $arg arg
     * 
     * @return boolean |string
     */
    protected function _checkTable($arg) {
        /*$link = mysql_connect($arg['host'] . ':' . $arg['port'],
            $arg['user'], $arg['pass']);
        $ret = $link && mysql_select_db($arg['db']);*/
        
        try {
            $db = Comm_Db::d($arg['db_id']);
            
            $db->execute('SET NAMES "utf8"');
    
            $sql = "show create table `{$arg['table']}`";
            $ret = $db->execute( $sql);
        } catch (Exception $e) {
            return "[".$e->getCode()."]".$e->getMessage();
        }

        return true;
    }

    /**
     * check call func
     * 
     * @param array $arg arg
     * 
     * @return string|unknown
     */
    protected function _checkCallFuction($arg) {
        foreach ($arg as $v) {
            if (isset($ret)) {
                $v['arr_param']['prev_result'] = $ret;
            }
            try{
                $ret = call_user_func_array($v['function'], $v['arr_param']);
            } catch(Exception $e) {
                return "[".$e->getCode()."]".$e->getMessage();
            }
        }

        return $ret;
    }



    /**
     * 输出检测综合结果
     * 
     * @param int $all_n 检测总数
     * @param int $err_n 错误数量
     * 
     * @return void
     */
    protected function _showResult($all_n, $err_n) {
        $this->_output("检测{$all_n}项，共{$err_n}项错误", $err_n ? 0 : 2);
    }

    /**
     * 输出一项检测结果
     * 
     * @param string $str    输出内容
     * @param bool   $type_n 输出类型（1正确，0错误，2正确且强制输出）
     * @param int    $n		 缩进级别（1~5）
     * 
     * @return void
     */
    protected function _output($str, $type_n, $n = 1) {
        if ($type_n == 1 && !$this->is_show_right) {
            return false;
        }

        if (!$this->is_web) {
            $this->html .= '<div style="padding-left:'.(40*$n).'px; color: '.($type_n?'green':'red').'">' . htmlspecialchars($str) . '</div>'."\n";
        } else {
            $html = '<div class="item item' . $n . ' color' . $type_n . '">' . htmlspecialchars($str) . '</div>';
            echo 'add("f', $this->now_check_name, '",', json_encode($html), ');', "\n";
            ob_flush();
            flush();
        }

    }

    /**
     * 初始化页面
     * 
     * @param type $arr_check check array data 
     * 
     * @return void
     */
    protected function _pageInit($arr_check) {
        ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>服务器环境检测工具</title>
        <style>
            .title {
                font-size: 23px;
            }

            .color0 {
                color: red;
            }

            .color1 {
                color: green;
            }

            .color2 {
                color: green;
            }

            .color3 {
                color: blue;
            }

            .color4 {
                color: blueviolet;
            }

            .item1 {
                padding-left: 20px;
            }

            .item2 {
                padding-left: 60px;
            }

            .item3 {
                padding-left: 100px;
            }

            .item4 {
                padding-left: 140px;
            }

            .item5 {
                padding-left: 180px;
            }
        </style>
        <script type="text/javascript"
        src="http://lib.sinaapp.com/js/jquery/1.6/jquery.min.js"></script>
        <script type="text/javascript">
            var obj={};

            function get_obj(id){
                if( typeof obj[id] == 'undefined' ){
                    obj[id]=$('#'+id);
                }
                return obj[id];
            }

            function add(id,html){
                get_obj(id).find('.ret').append(html);
            }

            function check(idx){
                var o = get_obj('f'+idx);
                //o.find('.btn').attr('');
                o.find('.ret').empty();//.html('aaaaaaaaa');
                $.getScript('?name='+idx);


            }

            function hide_right(id){
                $('#f'+id+' .item').not('.color0').hide();
            }
            function show_right(id){
                $('#f'+id+' .item').not('.color0').show();
            }

            function check_all(){
                $('.btn').each(function(i){
                    $(this).click();
                });
            }

        </script>

</head>
<body>

<?php $i = 1;?>
<?php foreach ($arr_check as $k => $v) :?>
<div calss="block" id="f<?php echo $k;?>">
    <div class="title"><span><?php echo $i++, '. ', $v['desc'];?> </span>
        <input type="button" onclick="check('<?php echo $k;?>')" class="btn" value="开始检测" />
    <?php if ($this->is_show_right):?>
        <input type="button" onclick="hide_right('<?php echo $k; ?>')" value="只显示错误" />
        <input type="button" onclick="show_right('<?php echo $k; ?>')" value="显示全部" />
    <?php endif;?>
    </div>
    <div class="ret"></div>
</div>
<?php endforeach ?>
</body>
</html>

<?php
    }


}