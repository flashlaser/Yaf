<?php

/**
 * 服务器环境检测的配置
 *
 * @package model
 * @author  baojun <zhangbaojun@yixia.com>
 */
class ScheckModel extends Abstract_M{
    protected static $uid = 0;

    /**
     * 进行检查参数的配置
     *
     * @param Helper_Scheck $c   c
     * @param bigint        $uid uid 
     *
     * @return void
     */
    static public function setConfig(Helper_Scheck $c, $uid) {
        self::$uid = $uid;
        
        self::_confServer($c);
        self::_confClass($c);
        self::_confFunction($c);
        self::_confDirFile($c);
        self::_confMysql($c);
        self::_confRds($c);
        self::_confMemcached($c);
        self::_confRedis($c);
        self::_confAliyunRedis($c);
        self::_confAliyunMc($c);
        self::_confTable($c);
        // self::_confCallFunction($c);
    }

    /**
     * 检查后把结果写入指定的日志文件中，适合cli方式下调用
     *
     * @return string
     */
    static public function cliCheck() {
        $c = new Helper_Scheck();
        $c->is_show_right = 0;
        self::setConfig($c, 1863014355);
        $str_log = $c->cliRun();
        $d = date('[Y-m-d H:i:s]');
        
        return "\n====== {$d} ======\n{$str_log}\n{$d} end >>>>>>\n\n";
    }

    /**
     * 配置$_SERVER 环境变量的检查
     *
     * @param Helper_Scheck $c c
     *
     * @return void
     */
    static protected function _confServer(Helper_Scheck $c) {
        $c->setConfig('server', array (
                // 目录
                'SRV_APPLOGS_DIR', 'SRV_PRIVDATA_DIR', 
                // 环境识别
                'SRV_DEVELOP_LEVEL', 
                // MC
                'SRV_MC_EVENT_SERVERS', // 基本信息
'SRV_MC_EVENT_SERVERS_USER', // 索引信息
'SRV_MC_EVENT_SERVERS_PASS', // 计数器
'SRV_MC_YIXIA_SERVERS', // 内容
'SRV_MC_YIXIA_SERVERS_USER', // 内容
'SRV_MC_YIXIA_SERVERS_PASS', // 内容
                                             // MCQ
                                             // 'SRV_MEMCACHEQ_ADMIN_6509_SERVERS',
                                             // 数据库
                'SRV_DB3694_HOST', 'SRV_DB3694_HOST_R', 'SRV_DB3694_PORT', 'SRV_DB3694_NAME', 'SRV_DB3694_USER', 'SRV_DB3694_PASS', 'SRV_DB3694_PORT_R', 'SRV_DB3694_NAME_R', 'SRV_DB3694_USER_R', 'SRV_DB3694_PASS_R', 

                'SRV_DB3695_HOST', 'SRV_DB3695_HOST_R', 'SRV_DB3695_PORT', 'SRV_DB3695_NAME', 'SRV_DB3695_USER', 'SRV_DB3695_PASS', 'SRV_DB3695_PORT_R', 'SRV_DB3695_NAME_R', 'SRV_DB3695_USER_R', 'SRV_DB3695_PASS_R', 

                'SRV_DB_PUB_HOST', 'SRV_DB_PUB_HOST_R', 'SRV_DB_PUB_PORT', 'SRV_DB_PUB_NAME', 'SRV_DB_PUB_USER', 'SRV_DB_PUB_PASS', 'SRV_DB_PUB_PORT_R', 'SRV_DB_PUB_NAME_R', 'SRV_DB_PUB_USER_R', 'SRV_DB_PUB_PASS_R', 

                'SRV_DB_EVENT_HOST', 'SRV_DB_EVENT_HOST_R', 'SRV_DB_EVENT_PORT', 'SRV_DB_EVENT_NAME', 'SRV_DB_EVENT_USER', 'SRV_DB_EVENT_PASS', 'SRV_DB_EVENT_PORT_R', 'SRV_DB_EVENT_NAME_R', 'SRV_DB_EVENT_USER_R', 'SRV_DB_EVENT_PASS_R', 
                
                // redis
                'SRV_REDIS1_HOST', 'SRV_REDIS1_PORT', 'SRV_REDIS1_PASS', 'SRV_REDIS1_HOST_R', 'SRV_REDIS1_PORT_R', 'SRV_REDIS1_PASS_R', 

                'SRV_REDIS2_HOST', 'SRV_REDIS2_PORT', 'SRV_REDIS2_PASS', 'SRV_REDIS2_HOST_R', 'SRV_REDIS2_PORT_R', 'SRV_REDIS2_PASS_R', 

                'SRV_REDIS3_HOST', 'SRV_REDIS3_PORT', 'SRV_REDIS3_PASS', 'SRV_REDIS3_HOST_R', 'SRV_REDIS3_PORT_R', 'SRV_REDIS3_PASS_R'));
    }

    /**
     * 配置类
     *
     * @param Helper_Scheck $c c
     */
    static protected function _confClass(Helper_Scheck $c) {
        $c->setConfig('class', array(
            /*array(
                'arg'  => 'SinaCombService',
                'desc' => 'mc中间件： SinaCombService'
            ),*/
            'memcached', 
                // arg和desc相同时，可以如此简略配置，
                'Yaf_Application', // yaf
'DOMDocument', // DOM
'PDO', // PDO
'redis', 'memcache'));
    }

    /**
     * 配置函数
     *
     * @param Helper_Scheck $c c
     */
    static protected function _confFunction(Helper_Scheck $c) {
        $c->setConfig('function', array (array ('arg' => 'gd_info', 'desc' => 'GD库'), array ('arg' => 'mb_strlen', 'desc' => 'Mbstring库'), array ('arg' => 'curl_init', 'desc' => 'curl库')));
    }

    /**
     * 配置目录文件
     *
     * @param Helper_Scheck $c c
     */
    static protected function _confDirFile(Helper_Scheck $c) {
        // 配置目录/文件读写权限检查
        $c->setConfig('dir_file', array (array ('desc' => 'APPLOGS目录', 'arg' => array ('path' => $_SERVER['SRV_APPLOGS_DIR'], 
                // 读写权限配置，r：读，w：写
                'mod' => 'w')), array ('desc' => 'PRIVDATA目录', 'arg' => array ('path' => $_SERVER['SRV_PRIVDATA_DIR'], 
                // 读写权限配置，r：读，w：写
                'mod' => 'wr')), array ('desc' => 'SRV_CONFIG文件读权限', 'arg' => array ('path' => APP_PATH . '/system/SRV_CONFIG', 
                // 读写权限配置，r：读，w：写
                'mod' => 'r'))));
    }

    /**
     * 配置Mysql
     *
     * @param Helper_Scheck $c c
     */
    static protected function _confMysql(Helper_Scheck $c) {
        // mysql连接检查
        $c->setConfig('mysql', array (array ('desc' => 'Comm_Db::DB_3694', 'arg' => array ('srv_key' => Comm_Db::DB_3694)), // $_SERVER 的 kye

        array ('desc' => 'Comm_Db::DB_3695', 'arg' => array ('srv_key' => Comm_Db::DB_3695)), // $_SERVER 的 kye

        array ('desc' => 'Comm_Db::DB_PUB', 'arg' => array ('srv_key' => Comm_Db::DB_PUB)), // $_SERVER 的 kye

        array ('desc' => 'Comm_Db::DB_EVENT', 'arg' => array ('srv_key' => Comm_Db::DB_EVENT)), // $_SERVER 的 kye

        array ('desc' => 'Comm_Db::DB_MARK_COMMENT', 'arg' => array ('srv_key' => Comm_Db::DB_MARK_COMMENT)))) // $_SERVER 的 kye

        ;
    }

    /**
     * 配置Rds
     *
     * @param Helper_Scheck $c c
     */
    static protected function _confRds(Helper_Scheck $c) {
        // mysql连接检查
        $c->setConfig('rds', array (array ('desc' => 'event_r', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - 60 * 10), 'period' => 300, 'dimensions' => array ('CpuUsage' => array ('instanceId' => 'rr-2zeu17nq5bd091514', 'type' => 'CpuUsage'), // $_SERVER 的 kye
'DiskUsage' => array ('instanceId' => 'rr-2zeu17nq5bd091514', 'type' => 'DiskUsage'), // $_SERVER 的 kye
'IOPSUsage' => array ('instanceId' => 'rr-2zeu17nq5bd091514', 'type' => 'IOPSUsage'), // $_SERVER 的 kye
'ConnectionUsage' => array ('instanceId' => 'rr-2zeu17nq5bd091514', 'type' => 'ConnectionUsage'), // $_SERVER 的 kye
'MemoryUsage' => array ('instanceId' => 'rr-2zeu17nq5bd091514', 'type' => 'MemoryUsage')))), // $_SERVER 的 kye

        array ('desc' => 'event', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - 60 * 10), 'period' => 300, 'dimensions' => array ('CpuUsage' => array ('instanceId' => 'rm-2zem2npot8w6bib84', 'type' => 'CpuUsage'), 'DiskUsage' => array ('instanceId' => 'rm-2zem2npot8w6bib84', 'type' => 'DiskUsage'), 'IOPSUsage' => array ('instanceId' => 'rm-2zem2npot8w6bib84', 'type' => 'IOPSUsage'), 'ConnectionUsage' => array ('instanceId' => 'rm-2zem2npot8w6bib84', 'type' => 'ConnectionUsage'), 'MemoryUsage' => array ('instanceId' => 'rm-2zem2npot8w6bib84', 'type' => 'MemoryUsage')))), 

        array ('desc' => 'dbhash4mark_1', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - 60 * 10), 'period' => 300, 'dimensions' => array ('CpuUsage' => array ('instanceId' => 'rm-2ze6i27k2kq9h1z5w', 'type' => 'CpuUsage'), 'DiskUsage' => array ('instanceId' => 'rm-2ze6i27k2kq9h1z5w', 'type' => 'DiskUsage'), 'IOPSUsage' => array ('instanceId' => 'rm-2ze6i27k2kq9h1z5w', 'type' => 'IOPSUsage'), 'ConnectionUsage' => array ('instanceId' => 'rm-2ze6i27k2kq9h1z5w', 'type' => 'ConnectionUsage'), 'MemoryUsage' => array ('instanceId' => 'rm-2ze6i27k2kq9h1z5w', 'type' => 'MemoryUsage')))), 

        array ('desc' => 'dbhash4mark_2', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - 60 * 10), 'period' => 300, 'dimensions' => array ('CpuUsage' => array ('instanceId' => 'rm-2zehy392192b00069', 'type' => 'CpuUsage'), 'DiskUsage' => array ('instanceId' => 'rm-2zehy392192b00069', 'type' => 'DiskUsage'), 'IOPSUsage' => array ('instanceId' => 'rm-2zehy392192b00069', 'type' => 'IOPSUsage'), 'ConnectionUsage' => array ('instanceId' => 'rm-2zehy392192b00069', 'type' => 'ConnectionUsage'), 'MemoryUsage' => array ('instanceId' => 'rm-2zehy392192b00069', 'type' => 'MemoryUsage')))), 

        array ('desc' => 'dbhash4mark_3', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - 60 * 10), 'period' => 300, 'dimensions' => array ('CpuUsage' => array ('instanceId' => 'rm-2ze05i1g64sewb51i', 'type' => 'CpuUsage'), 'DiskUsage' => array ('instanceId' => 'rm-2ze05i1g64sewb51i', 'type' => 'DiskUsage'), 'IOPSUsage' => array ('instanceId' => 'rm-2ze05i1g64sewb51i', 'type' => 'IOPSUsage'), 'ConnectionUsage' => array ('instanceId' => 'rm-2ze05i1g64sewb51i', 'type' => 'ConnectionUsage'), 'MemoryUsage' => array ('instanceId' => 'rm-2ze05i1g64sewb51i', 'type' => 'MemoryUsage')))), 

        array ('desc' => 'mark_comment', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - 60 * 10), 'period' => 300, 'dimensions' => array ('CpuUsage' => array ('instanceId' => 'rm-2zeo77p1rq136x76s', 'type' => 'CpuUsage'), 'DiskUsage' => array ('instanceId' => 'rm-2zeo77p1rq136x76s', 'type' => 'DiskUsage'), 'IOPSUsage' => array ('instanceId' => 'rm-2zeo77p1rq136x76s', 'type' => 'IOPSUsage'), 'ConnectionUsage' => array ('instanceId' => 'rm-2zeo77p1rq136x76s', 'type' => 'ConnectionUsage'), 'MemoryUsage' => array ('instanceId' => 'rm-2zeo77p1rq136x76s', 'type' => 'MemoryUsage')))), 

        array ('desc' => 'mark_comment_r', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - 60 * 10), 'period' => 300, 'dimensions' => array ('CpuUsage' => array ('instanceId' => 'rr-2ze3wn6m557154mnl', 'type' => 'CpuUsage'), 'DiskUsage' => array ('instanceId' => 'rr-2ze3wn6m557154mnl', 'type' => 'DiskUsage'), 'IOPSUsage' => array ('instanceId' => 'rr-2ze3wn6m557154mnl', 'type' => 'IOPSUsage'), 'ConnectionUsage' => array ('instanceId' => 'rr-2ze3wn6m557154mnl', 'type' => 'ConnectionUsage'), 'MemoryUsage' => array ('instanceId' => 'rr-2ze3wn6m557154mnl', 'type' => 'MemoryUsage')))), 

        array ('desc' => 'sina2ali3695', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - 60 * 10), 'period' => 300, 'dimensions' => array ('CpuUsage' => array ('instanceId' => 'rm-2zea9o1z9w08gqycm', 'type' => 'CpuUsage'), 'DiskUsage' => array ('instanceId' => 'rm-2zea9o1z9w08gqycm', 'type' => 'DiskUsage'), 'IOPSUsage' => array ('instanceId' => 'rm-2zea9o1z9w08gqycm', 'type' => 'IOPSUsage'), 'ConnectionUsage' => array ('instanceId' => 'rm-2zea9o1z9w08gqycm', 'type' => 'ConnectionUsage'), 'MemoryUsage' => array ('instanceId' => 'rm-2zea9o1z9w08gqycm', 'type' => 'MemoryUsage')))), 

        array ('desc' => 'sina2ali3695_r', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - 60 * 10), 'period' => 300, 'dimensions' => array ('CpuUsage' => array ('instanceId' => 'rr-2zei1758idojtelmy', 'type' => 'CpuUsage'), 'DiskUsage' => array ('instanceId' => 'rr-2zei1758idojtelmy', 'type' => 'DiskUsage'), 'IOPSUsage' => array ('instanceId' => 'rr-2zei1758idojtelmy', 'type' => 'IOPSUsage'), 'ConnectionUsage' => array ('instanceId' => 'rr-2zei1758idojtelmy', 'type' => 'ConnectionUsage'), 'MemoryUsage' => array ('instanceId' => 'rr-2zei1758idojtelmy', 'type' => 'MemoryUsage')))), 

        array ('desc' => 'sina2ali3694_r', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - 60 * 10), 'period' => 300, 'dimensions' => array ('CpuUsage' => array ('instanceId' => 'rr-2ze1m5x7qlfgo2sx5', 'type' => 'CpuUsage'), 'DiskUsage' => array ('instanceId' => 'rr-2ze1m5x7qlfgo2sx5', 'type' => 'DiskUsage'), 'IOPSUsage' => array ('instanceId' => 'rr-2ze1m5x7qlfgo2sx5', 'type' => 'IOPSUsage'), 'ConnectionUsage' => array ('instanceId' => 'rr-2ze1m5x7qlfgo2sx5', 'type' => 'ConnectionUsage'), 'MemoryUsage' => array ('instanceId' => 'rr-2ze1m5x7qlfgo2sx5', 'type' => 'MemoryUsage')))), 

        array ('desc' => 'sina2ali3694', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - 60 * 10), 'period' => 300, 'dimensions' => array ('CpuUsage' => array ('instanceId' => 'rm-2ze538k1gv61870er', 'type' => 'CpuUsage'), 'DiskUsage' => array ('instanceId' => 'rm-2ze538k1gv61870er', 'type' => 'DiskUsage'), 'IOPSUsage' => array ('instanceId' => 'rm-2ze538k1gv61870er', 'type' => 'IOPSUsage'), 'ConnectionUsage' => array ('instanceId' => 'rm-2ze538k1gv61870er', 'type' => 'ConnectionUsage'), 'MemoryUsage' => array ('instanceId' => 'rm-2ze538k1gv61870er', 'type' => 'MemoryUsage')))), 

        array ('desc' => 'follow', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - 60 * 10), 'period' => 300, 'dimensions' => array ('CpuUsage' => array ('instanceId' => 'rds3nf68mri08xz29ih8', 'type' => 'CpuUsage'), 'DiskUsage' => array ('instanceId' => 'rds3nf68mri08xz29ih8', 'type' => 'DiskUsage'), 'IOPSUsage' => array ('instanceId' => 'rds3nf68mri08xz29ih8', 'type' => 'IOPSUsage'), 'ConnectionUsage' => array ('instanceId' => 'rds3nf68mri08xz29ih8', 'type' => 'ConnectionUsage'), 'MemoryUsage' => array ('instanceId' => 'rds3nf68mri08xz29ih8', 'type' => 'MemoryUsage')))), 

        array ('desc' => 'mp2.0', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - 60 * 10), 'period' => 300, 'dimensions' => array ('CpuUsage' => array ('instanceId' => 'rdswd0xe0hr3x5c3b639', 'type' => 'CpuUsage'), 'DiskUsage' => array ('instanceId' => 'rdswd0xe0hr3x5c3b639', 'type' => 'DiskUsage'), 'IOPSUsage' => array ('instanceId' => 'rdswd0xe0hr3x5c3b639', 'type' => 'IOPSUsage'), 'ConnectionUsage' => array ('instanceId' => 'rdswd0xe0hr3x5c3b639', 'type' => 'ConnectionUsage'), 'MemoryUsage' => array ('instanceId' => 'rdswd0xe0hr3x5c3b639', 'type' => 'MemoryUsage')))))
        );
    }

    /**
     * 配置Aliyun Redis
     *
     * @param Helper_Scheck $c c
     */
    static protected function _confAliyunRedis(Helper_Scheck $c) {
        $pos = 60 * 2;
        // mysql连接检查
        $c->setConfig('aliyunredis', array (array ('desc' => 'mark_cmt_top', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - $pos), 'period' => 60, 'instanceId' => 'f10cabebc69444ef', 'dimensions' => array ('MemoryUsage', 'QPSUsage', 'ConnectionUsage', 'FailedCount'))), array ('desc' => 'sina2ali_8167', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - $pos), 'period' => 60, 'instanceId' => '7b0f56d9817b4c7e', 'dimensions' => array ('MemoryUsage', 'QPSUsage', 'ConnectionUsage', 'FailedCount'))), array ('desc' => 'sina2ali_hash8573-8576', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - $pos), 'period' => 60, 'instanceId' => '2d93210c8d654f3f', 'dimensions' => array ('MemoryUsage', 'QPSUsage', 'ConnectionUsage', 'FailedCount'))), array ('desc' => 'sina2ali_counter8577-8580', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - $pos), 'period' => 60, 'instanceId' => '1a0d187388f64dc4', 'dimensions' => array ('MemoryUsage', 'QPSUsage', 'ConnectionUsage', 'FailedCount'))), array ('desc' => 'gslb-db', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - $pos), 'period' => 60, 'instanceId' => '20dba7b919144244', 'dimensions' => array ('MemoryUsage', 'QPSUsage', 'ConnectionUsage', 'FailedCount')))));
    }

    /**
     * 配置Aliyun Mc
     *
     * @param Helper_Scheck $c c
     */
    static protected function _confAliyunMc(Helper_Scheck $c) {
        $pos = 60 * 2;
        $c->setConfig('aliyunmc', array (array ('desc' => 'mark_comment', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - $pos), 'period' => 60, 'instanceId' => '797a6087a3594df7', 'total_qps' => 9000, 'dimensions' => array ('Evict', 'HitRate', 'ItemCount', 'UsedMemCache', 'UsedQps'))), array ('desc' => 'search', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - $pos), 'period' => 60, 'instanceId' => 'd36fe1c3c5f34e6c', 'total_qps' => 36000, 'dimensions' => array ('Evict', 'HitRate', 'ItemCount', 'UsedMemCache', 'UsedQps'))), array ('desc' => 'event', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - $pos), 'period' => 60, 'instanceId' => '3592cf10e6944ef8', 'total_qps' => 9000, 'dimensions' => array ('Evict', 'HitRate', 'ItemCount', 'UsedMemCache', 'UsedQps'))), array ('desc' => 'sina2aliyun', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - $pos), 'period' => 60, 'instanceId' => '9139051202d44bdf', 'total_qps' => 720000, 'dimensions' => array ('Evict', 'HitRate', 'ItemCount', 'UsedMemCache', 'UsedQps'))), array ('desc' => 'miaopai_rec', 'arg' => array ('start_time' => date('Y-m-d H:i:s', time() - $pos), 'period' => 60, 'instanceId' => '5dda045e38df42bc', 'total_qps' => 144000, 'dimensions' => array ('Evict', 'HitRate', 'ItemCount', 'UsedMemCache', 'UsedQps')))));
    }

    /**
     * 配置Memcacheq
     *
     * @param Helper_Scheck $c c
     */
    static protected function _confMemcacheq(Helper_Scheck $c) {
        // memcached 连接检查，可用于检查mc和mcq以及所有使用memcache协议的服务
        $c->setConfig('memcacheq', array (array ('arg' => $_SERVER['SRV_MC_YIXIA_SERVERS'], 
                // 格式为 host:prot
                'desc' => 'MC:SRV_MC_YIXIA_SERVERS', 'user' => $_SERVER['SRV_MC_YIXIA_SERVERS_USER'], 'pass' => $_SERVER['SRV_MC_YIXIA_SERVERS_PASS']), array ('arg' => $_SERVER['SRV_MC_EVENT_SERVERS'], // 格式为 host:prot
'desc' => 'MC:SRV_MC_EVENT_SERVERS', 'user' => $_SERVER['SRV_MC_EVENT_SERVERS_USER'], 'pass' => $_SERVER['SRV_MC_EVENT_SERVERS_PASS'])));
    }

    /**
     * 配置Mc
     *
     * @param Helper_Scheck $c c
     */
    static protected function _confMemcached(Helper_Scheck $c) {
        // 配置mc中间件读写检查
        $c->setConfig('memcached', array (array ('desc' => 'Comm_Mc::YIXIA', 'arg' => array ('srv_key' => Comm_Mc::YIXIA)), // $_SERVER 的 kye

        array ('desc' => 'Comm_Mc::EVENT', 'arg' => array ('srv_key' => Comm_Mc::EVENT)))) // $_SERVER 的 kye

        ;
    }

    /**
     * 配置Redis
     *
     * @param Helper_Scheck $c c
     */
    static protected function _confRedis(Helper_Scheck $c) {
        // 配置mc中间件读写检查
        $c->setConfig('redis', array (array ('desc' => 'Comm_Redis::RHASH', 'arg' => array ('srv_key' => Comm_Redis::RHASH)), // $_SERVER 的 kye

        array ('desc' => 'Comm_Redis::COUNTER', 'arg' => array ('srv_key' => Comm_Redis::COUNTER)), // $_SERVER 的 kye

        array ('desc' => 'Comm_Redis::R8167', 'arg' => array ('srv_key' => Comm_Redis::R8167)))) // $_SERVER 的 kye

        ;
    }

    /**
     * 配置表
     *
     * @param Helper_Scheck $c c 
     */
    static protected function _confTable(Helper_Scheck $c) {
        // 配置数据表检查
        $arr = array (Comm_Db::DB_3694 => array ( // 不分表
't' => array ('activate             ', 'activity             ', 'ad                   ', 'admin_record         ', 'alldevice            ', 'app_auth             ', 'appver               ', 'atme                 ', 'audit_log            ', 'blacklist_1          ', 'blacklist_2          ', 'blacklist_3          ', 'blacklist_4          ', 'blacklist_5          ', 'blacklist_6          ', 'blacklist_7          ', 'blacklist_8          ', 'bonus_info           ', 'broadcastmsg         ', 'category             ', 'category-channel     ', 'category-channel-sina', 'channel-forward      ', 'channel-recommend    ', 'channel-report       ', 'channel-share-with   ', 'channel-stat         ', 'channel_audit        ', 'channel_copy         ', 'channel_extra        ', 'channel_identify     ', 'channel_md5          ', 'channel_robot        ', 'city_channel         ', 'comment              ', 'comment-normal       ', 'comment_warehouse    ', 'commentmap_sina      ', 'consume              ', 'contacts             ', 'cron2queue           ', 'dosth                ', 'dts_increment_trx    ', 'error-report         ', 'event-push           ', 'feedback             ', 'follow               ', 'gold                 ', 'income               ', 'interest_collect     ', 'ios_devid            ', 'keyword              ', 'mark                 ', 'mission              ', 'motive               ', 'motive_category      ', 'mp_plaza             ', 'msg                  ', 'msg-session          ', 'msg_ban              ', 'netsafe_manage_control', 'news                 ', 'normalstat-apicost   ', 'normalstat-topicvcnt ', 'normalstat-ver       ', 'normalstat-vvcnt     ', 'object_channel       ', 'op_stat              ', 'opweibo              ', 'people               ', 'people-oem           ', 'people-reg           ', 'place                ', 'plaza                ', 'pom_attitude         ', 'pom_bullet_sub       ', 'prohibitword         ', 'promote              ', 'reward               ', 'reward_topic         ', 'rewardname           ', 'rewardorder          ', 'robotdata            ', 'sequence_opt         ', 'series_theme         ', 'sinauserinfo         ', 'sku_list             ', 'sku_order            ', 'slideplaza           ', 'sysmsg               ', 'talent               ', 'talent_info          ', 'theme                ', 'topic                ', 'topic_relation       ', 'topic_report         ', 'topicfollow          ', 'topicname            ', 'upload-server        ', 'upload_status        ', 'urlmap               ', 'urls                 ', 'user-cntv            ', 'user-disney          ', 'user-guest           ', 'user-oem             ', 'user-otherinfo       ', 'user-qweibo          ', 'user-recommend       ', 'user-renren          ', 'user-settings        ', 'user-sinapaike       ', 'user-sinaweibo       ', 'user_extra           ', 'user_group           ', 'user_qq              ', 'user_qweibo_mp       ', 'user_rr_mp           ', 'user_third           ', 'user_wechat          ', 'video-meta           ', 'video-sign           ', 'video_thread         ', 'vqrawdata            ', 'vvdetail             ', 'web_news             ', 'weibojob             ', 'with                 ', 'withdrawal_applicant '), // 按uid分表
'tSubHash' => array (), // 按月分表
'tSubDate' => array ()), Comm_Db::DB_3695 => array ( // 不分表
't' => array ('activate            ', 'activity            ', 'ad                  ', 'alldevice           ', 'app_auth            ', 'appver              ', 'atme                ', 'audit_log           ', 'auth                ', 'auth_user           ', 'blacklist_1         ', 'blacklist_2         ', 'blacklist_3         ', 'blacklist_4         ', 'blacklist_5         ', 'blacklist_6         ', 'blacklist_7         ', 'blacklist_8         ', 'bonus_info          ', 'broadcastmsg        ', 'category            ', 'category-channel    ', 'category-channel-sina', 'channel             ', 'channel-forward     ', 'channel-recommend   ', 'channel-report      ', 'channel-share-with  ', 'channel-stat        ', 'channel_audit       ', 'channel_copy        ', 'channel_extra       ', 'channel_robot       ', 'comment-normal      ', 'comment_relation    ', 'comment_warehouse   ', 'commentmap_sina     ', 'consume             ', 'contacts            ', 'dts_increment_trx   ', 'error-report        ', 'event               ', 'event-push          ', 'event_merge         ', 'mission             ', 'mp_plaza            ', 'msg                 ', 'msg-session         ', 'msg_ban             ', 'news                ', 'normalstat-apicost  ', 'normalstat-topicvcnt', 'normalstat-ver      ', 'normalstat-vvcnt    ', 'object_channel      ', 'op_log              ', 'op_stat             ', 'opweibo             ', 'people              ', 'people-oem          ', 'people-reg          ', 'place               ', 'plaza               ', 'pom_attitude        ', 'pom_bullet_sub      ', 'prohibitword        ', 'promote             ', 'robotdata           ', 'series_theme        ', 'sinauserinfo        ', 'sku_list            ', 'sku_order           ', 'slideplaza          ', 'sysmsg              ', 'talent              ', 'theme               ', 'topic               ', 'topic_relation      ', 'topic_report        ', 'topicfollow         ', 'topicname           ', 'upload-server       ', 'upload_status       ', 'urlmap              ', 'urls                ', 'user                ', 'user-cntv           ', 'user-disney         ', 'user-guest          ', 'user-oem            ', 'user-otherinfo      ', 'user-qweibo         ', 'user-recommend      ', 'user-renren         ', 'user-settings       ', 'user-sinapaike      ', 'user-sinaweibo      ', 'user_account        ', 'user_ban            ', 'user_extra          ', 'user_group          ', 'user_party          ', 'user_qq             ', 'user_qweibo_mp      ', 'user_rr_mp          ', 'user_third          ', 'user_wechat         ', 'video-meta          ', 'video-sign          ', 'video_thread        ', 'vqrawdata           ', 'vvdetail            ', 'web_news            ', 'weibojob            ', 'with                ', 'withdrawal_applicant'), 
                // 按uid分表
                'tSubHash' => array (), 
                // 按月分表
                'tSubDate' => array ()), Comm_Db::DB_PUB => array ( // 不分表
't' => array ('channel_audit_log', 'channel_recmd', 'data_chart', 'push'), 
                // 按uid分表
                'tSubHash' => array (), 
                // 按月分表
                'tSubDate' => array ()), Comm_Db::DB_EVENT => array ( // 不分表
't' => array ('aoyun_category', 'aoyun_recommend'), 
                // 按uid分表
                'tSubHash' => array (), 
                // 按月分表
                'tSubDate' => array ()));
        $conf = array ();
        foreach ( $arr as $db_id => $v ) {
            // $db_link = self::_getDbLinkArg($db_id);
            foreach ( $v as $hash => $val ) {
                $tables = self::_getTables($hash, $val);
                foreach ( $tables as $tb ) {
                    $conf[] = array ('desc' => $db_id . '.' . trim($tb), 'arg' => array ('db_id' => $db_id, 'table' => trim($tb)));
                }
            }
        }
        $c->setConfig('table', $conf);
    }

    /**
     * 配置回调函数
     *
     * @param Helper_Scheck $c c 
     */
    static protected function _confCallFunction(Helper_Scheck $c) {
        // 杂项检测
        // 配置发通知检查
        $tmp = date('Y-m-d H:i:s');
        $tpl_data = array ('objects1' => $tmp, 'objects2' => $tmp, 'objects3' => $tmp, 'objects4' => $tmp, 'action_url' => $tmp);
        $arr = Comm_Config::get('notice');
        $conf = array ();
        foreach ( $arr as $notice_type => $v ) {
            $conf[] = array ('desc' => $notice_type, 'arg' => array (array ('arr_param' => array ($notice_type, self::$uid, $tpl_data), 'function' => array ('ModelNotice', 'send'))));
        }
        $conf[] = array ('desc' => '获取用户信息', 'arg' => array (array ('arr_param' => array (self::$uid), 'function' => array (Apilib_Wb::init(), 'usersShow')), array ('arr_param' => array ('prev_result' => null, 'boolean'), 'function' => 'settype')));
        $c->setConfig('call_function', $conf);
    }

    /**
     * 获取完整表名
     *
     * @param string $hash   分表方法名
     * @param array  $tables 基础表名列表
     *       
     * @return array
     */
    static protected function _getTables($hash, $tables) {
        $out = array ();
        switch ($hash) {
            case 't' : // 无分表，只加前缀
                foreach ( $tables as $tb ) {
                    $out[] = Comm_Db::t($tb);
                }
                break;
            case 'tSubHash' : // 普通哈希分256个表
                foreach ( $tables as $tb ) {
                    for ($i = 1; $i <= 256; $i ++) {
                        $out[] = Comm_Db::tSubHash($tb, $i);
                    }
                }
                break;
            case 'tSubDate' : // 按月分表
                foreach ( $tables as $tb ) {
                    $now = date('Ym31');
                    $date = new DateTime('2012-6-1');
                    do {
                        $out[] = Comm_Db::tSubDate($tb, $date);
                        $date->modify('+1 month');
                    } while ( $date->format('Ymd') < $now );
                }
                
                break;
            default :
                exit("confing error: hash_func({$hash})不存在");
        }
        
        return $out;
    }

    /**
     * 获取链接参数
     *
     * @param int $db_id db id
     *
     * @return array
     */
    static protected function _getDbLinkArg($db_id) {
        switch ($db_id) {
            case Comm_Db::DB_3694 :
                $db_link = array ('host' => $_SERVER['SRV_DB3694_HOST'], 'port' => $_SERVER['SRV_DB3694_PORT'], 'user' => $_SERVER['SRV_DB3694_USER'], 'pass' => $_SERVER['SRV_DB3694_PASS'], 'db' => $_SERVER['SRV_DB3694_NAME']);
                break;
            case Comm_Db::DB_3695 :
                $db_link = array ('host' => $_SERVER['SRV_DB3695_HOST'], 'port' => $_SERVER['SRV_DB3695_PORT'], 'user' => $_SERVER['SRV_DB3695_USER'], 'pass' => $_SERVER['SRV_DB3695_PASS'], 'db' => $_SERVER['SRV_DB3595_NAME']);
                break;
            case Comm_Db::DB_PUB :
                $db_link = array ('host' => $_SERVER['SRV_DB_PUB_HOST'], 'port' => $_SERVER['SRV_DB_PUB_PORT'], 'user' => $_SERVER['SRV_DB_PUB_USER'], 'pass' => $_SERVER['SRV_DB_PUB_PASS'], 'db' => $_SERVER['SRV_DB_PUB_NAME']);
                break;
            case Comm_Db::DB_EVENT :
                $db_link = array ('host' => $_SERVER['SRV_DB_EVENT_HOST'], 'port' => $_SERVER['SRV_DB_EVENT_PORT'], 'user' => $_SERVER['SRV_DB_EVENT_USER'], 'pass' => $_SERVER['SRV_DB_EVENT_PASS'], 'db' => $_SERVER['SRV_DB_EVNET_NAME']);
                break;
            default :
                exit($db_id . ' 不存在');
        }
        
        return $db_link;
    }
}
