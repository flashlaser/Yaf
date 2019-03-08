<?php

/**
 * Crontab监控
 * contab配置：* * * * *  /usr/local/bin/php /data1/www/htdocs/i.miaopai.com/cli.php request_uri='/Cli/Monitor/Crontab' > /dev/null
 * 
 * @package    action
 * @author     baojun <baojun4545@sina.com>
 */
class CrontabAction extends Yaf_Action_Abstract {

    //检查计划任务
    public function execute() {
        $is_force = $this->getRequest()->getParam('force');
        $ignore_check = $is_force ? true : false;
        
        $controller = $this->getController();
        $config     = $this->getConfig();
        $code_file_bak_dir = $_SERVER['SRV_PRIVDATA_DIR'] . '/crontab_check_desc/';

        echo "\033[35mCheck Crontab:\r\n\033[0m";
        foreach ($config as $key => $value) {
            if (!$ignore_check && !$this->check_time(time(), $value['time'])) {
                echo "{$key}:[IGNORE]\r\n";
                continue;
            }
            
            //判断文件是否有所改变（仅处理自动重启项）
            $code_changed = false;
            $code_file = $code_current = $code_file_bak = $code_bak = '';
            if(!empty($value['check_file'])) {
                //获取当前代码
                $code_file = APP_PATH . $value['check_file'];
                $code_current = file_get_contents($code_file);

                //获取之前的代码
                $code_file_bak = $code_file_bak_dir . $key . '.bak';
                if(file_exists($code_file_bak)) {
                    $code_bak = file_get_contents($code_file_bak);
                } else {
                    $code_bak = '';
                }
                
                //文件内容改变
                if($code_current !== $code_bak) {
                    $code_changed = true;
                }
            }
            if(!empty($value['limit_time'])) {
            	$seconds = $value['limit_time'] * 60;
            	set_time_limit($seconds);
            }

            for ($i = 1; $i <= $value['proc_total']; ++$i) {
                $request_uri = "{$value['request_uri']}/proc_total/{$value['proc_total']}/proc_num/{$i}";

                //检查进程是否存在
                $shell = $controller->shell($request_uri);
                $num   = $controller->shell_proc_num($shell);


                echo "{$key}_{$i}:";
                if ($num >= 1) { //进程已存在
                    
                    //代码有改变，请求停止
                    if($code_changed) {
                        $this->getController()->sendStop('crontab', $key, $i);
                        echo "\033[31m[STOP]\033[0m";
                    } else {
                        echo "\033[33m[RUNING]\033[0m";
                    }
                } else {  //进程不存在，操作
                    echo "\033[32m[OK]\033[0m";
                    $controller->shell_cmd($request_uri);
                }
                echo "\r\n";
            }
            
            //更新备份文件为当前文件
            if($code_changed) {
                if(!is_dir($code_file_bak_dir)) {
                    mkdir($code_file_bak_dir, 0775, true);
                    chmod($code_file_bak_dir, 0777);
                }
                file_put_contents($code_file_bak, $code_current);
            }
        }
    }

    /**
     * 获取配置
     * @return	array
     */
    public function getConfig() {
        return Comm_Config::get('crontab');
    }

    /**
     * 检查是否该执行crontab了
     * @param	int		$curr_datetime	当前时间
     * @param	string	$timeStr		时间配置
     * @return	boolean
     */
    protected function check_time($curr_datetime, $timeStr) {
        $time = explode(' ', $timeStr);
        if (count($time) != 5) {
            return false;
        }

        $month  = date("n", $curr_datetime); // 没有前导0
        $day    = date("j", $curr_datetime); // 没有前导0
        $hour   = date("G", $curr_datetime);
        $minute = (int)date("i", $curr_datetime);
        $week   = date("w", $curr_datetime); // w 0~6, 0:sunday  6:saturday
        if ($this->is_allow($week, $time[4], 7, 0) &&
            $this->is_allow($month, $time[3], 12) &&
            $this->is_allow($day, $time[2], 31, 1) &&
            $this->is_allow($hour, $time[1], 24) &&
            $this->is_allow($minute, $time[0], 60)
        ) {
            return true;
        }
        return false;
    }

    /**
     * 检查是否允许执行
     * @param	mixed	$needle			数值
     * @param	mixed	$str			要检查的数据
     * @param	int		$TotalCounts	单位内最大数
     * @param	int		$start			单位开始值（默认为0）
     * @return type
     */
    protected function is_allow($needle, $str, $TotalCounts, $start = 0) {
        if (strpos($str, ',') !== false) {
            $weekArray = explode(',', $str);
            if (in_array($needle, $weekArray))
                return true;
            return false;
        }
        $array     = explode('/', $str);
        $end       = $start + $TotalCounts - 1;
        if (isset($array[1])) {
            if ($array[1] > $TotalCounts)
                return false;
            $tmps = explode('-', $array[0]);
            if (isset($tmps[1])) {
                if ($tmps[0] < 0 || $end < $tmps[1])
                    return false;
                $start = $tmps[0];
                $end   = $tmps[1];
            } else {
                if ($tmps[0] != '*')
                    return false;
            }
            if (0 == (($needle - $start) % $array[1]))
                return true;
            return false;
        }
        $tmps = explode('-', $array[0]);
        if (isset($tmps[1])) {
            if ($tmps[0] < 0 || $end < $tmps[1])
                return false;
            if ($needle >= $tmps[0] && $needle <= $tmps[1])
                return true;
            return false;
        } else {
            if ($tmps[0] == '*' || $tmps[0] == $needle)
                return true;
            return false;
        }
    }
}
