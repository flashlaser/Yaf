<?php

/**
 * mcq监控程序
 * contab配置：* * * * *  /usr/local/bin/php /data1/www/htdocs/i.miaopai.com/cli.php request_uri='/Cli/Monitor/Mcq' > /dev/null
 * 
 * @package    Action
 * @author baojun <baojun4545@sina.com>
 */
class McqAction extends Yaf_Action_Abstract {

    /**
     * 配置
     * @var array
     */
    protected $config_data;
    protected $die_time = 600;//10分钟，死亡时间，单位：秒。进程无心跳超过此时间则判断为死亡，若还有进程强制杀死

    public function execute() {
        //载入配置列表
        $this->config();

        //检查文件是否改动，若有改动，通知进程停止运行
        $this->checkModify();


        echo "\033[34m.";
        $max = 30;
        for ($i   = 0; $i < $max; $i++) {
            usleep(2 / $max * 1000000);
            echo '.';
        }
        echo "\033[0m";

        echo "\r\n";
        $this->checkRuning();
        echo "\r\n";
    }

    /**
     * 载入配置
     */
    protected function config() {
        $this->config_data = array();
        $tmp = Comm_Config::get('mcq.mcq_list');
        foreach ($tmp as $mcq_name => $mcq_info) {
            if ($mcq_info['action_name']) {
                $this->config_data[$mcq_info['action_name']] = array(
                    'mcq_name'   => $mcq_name,
                    'proc_total' => $mcq_info['proc_total'],
                );
            }
        }
    }

    /**
     * 检查进程是否存在进行
     */
    protected function checkRuning() {
        $controller = $this->getController();

        echo "\033[35mCheck MCQ runing:\r\n\033[0m";
        foreach ($this->config_data as $action_name => $arr) {

            for ($idx = 1; $idx <= $arr['proc_total']; $idx++) {
                $request_uri = "/Cli/Mcq/{$action_name}/proc_num/{$idx}";
                echo "\n{$request_uri}\n";
                //检查进程是否存在
                $shell       = $controller->shell($request_uri);
                $num         = $controller->shell_proc_num($shell);

                if ($num >= 1) { //进程已存在，检查心跳和rysnc停止命令
                    if (!$this->checkBeat($action_name, $idx, $shell)) {//没有心跳了，被判断死亡了，在进行kill了
                        echo "\033[33m [beat KILLING]\033[0m";
                    } elseif ($this->checkRsyncStop($request_uri, $action_name, $idx)) {
                        echo "\033[33m [rsync KILLING]\033[0m";
                    } else {
                        echo "\033[33m [RUNING]\033[0m";
                    }
                } else {  //进程不存在，启动
                    echo "\033[32m [to run]\033[0m";
                    $controller->shell_cmd($request_uri);
                }
            }
        }
    }

    /**
     * 检查文件是否修改
     */
    protected function checkModify() {
        $code_dir = APP_PATH . '/application/modules/Cli/actions/Mcq/';//代码目录
        $bak_dir  = $_SERVER['SRV_PRIVDATA_DIR'] . '/mcq_check_desc/';//文件备份目录。备份最后的代码文件，用于比对是否修改过
        if (!is_dir($bak_dir)) {
            mkdir($bak_dir, 0775, true);
        }

        echo "\033[35mCheck MCQ modify:\r\n\033[0m";

        foreach ($this->config_data as $action_name => $arr) {
            //文件路径
            $code_file = $code_dir . $action_name . '.php';
            $bak_file  = $bak_dir . $action_name . '.bak';

            //文件内容
            $str_code = file_get_contents($code_file);
            if (is_file($bak_file)) {
                $str_bak = file_get_contents($bak_file);
            } else {
                $str_bak = '';
            }

            //比较检查
            echo "{$action_name}:";
            if ($str_code != $str_bak) { //文件有改动，发送停止消息
                for ($idx = 1; $idx <= $arr['proc_total']; $idx++) {
                    $this->getController()->sendStop('mcq', $action_name, $idx);
                }

                file_put_contents($bak_file, $str_code);
                echo "\033[31m [STOP]\033[0m";
            } else {     //文件内容正常
                echo "\033[32m [NORMAL]\033[0m";
                ;
            }
            echo "\r\n";
        }
    }

    /**
     * 检查mcq处理进程（php）的心跳，若无心跳而有进程，kill掉
     *
     * @param string $path 路径，mcq处理进程文件相对于 controller 的路径
     * @return bool
     */
    protected function checkBeat($action_name, $idx, $shell) {
        $file = sprintf(Comm_Config::get('mcq.pub_conf.beat_file_path')
            , $_SERVER['SRV_PRIVDATA_DIR'], $action_name, $idx);

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $last_beat_time = file_exists($file) ? (int)file_get_contents($file) : 0;
        if (NOW - $last_beat_time > $this->die_time) {
            $cmd = 'ps -ef |grep "' . $shell . '"|grep -v "grep" |awk \'{ print  $2}\'';
            $id  = exec($cmd);
            if (is_numeric($id)) {
                $cmd = "kill -9 $id";
                exec($cmd);
            }
            echo "$file :\nnow :", NOW, "\nlast:", $last_beat_time, "\n";

            return false;
        }

        return true;
    }

    /**
     * 检查是否需要手动停止（后台操作停止进程）
     *
     * @staticvar string $arr_uri
     * @param string $uri			mcq进程的request_uri
     * @param string $action_name	mcq 的 action name
     * @param string $idx			进程编号
     * @return bool
     */
    protected function checkRsyncStop($uri, $action_name, $idx) {
        static $arr_uri = null;
        if (is_null($arr_uri)) {
            $arr_uri = $this->getStopUri();
        }

        if (!in_array($uri, $arr_uri)) {
            return false;
        }

        return $this->getController()->setStop('mcq', $action_name, $idx);
    }

    /**
     * 获取监控后台推送的，需要停止的mcq进程的uri列表
     *
     * @return array
     */
    protected function getStopUri() {
        $arr_uri = array();
        //@todo 推送文件路径等申请rsync模块后再修改
        $file = $_SERVER['SRV_PRIVDATA_DIR'] . '/monitor/reboot_weiba_app_daemon.txt';

        if (!file_exists($file)) {
            return $arr_uri;
        }

        $arr_uri = file($file);
        $arr_uri = array_map('trim', $arr_uri);
        @unlink($file);

        return $arr_uri;
    }
}
