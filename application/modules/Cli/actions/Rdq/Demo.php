<?php

/**
 * Demo
 * 
 * /usr/local/bin/php cli.php request_uri='/Cli/Rdq/Demo/proc_num/1';
 * 
 * @author baojun
 *
 */
class DemoAction extends Abstract_Action_Rdq {

    public function process(array $datas) {
    	if (!is_array($datas) || empty($datas)) {
    		return false;
    	}
    	
    	foreach ($datas as $k => $v) {
    	    print_r($v);
/*
    		$param=array();
			//$param['frontAdd']='http://wscdn.miaopai.com/stream/gIX1pWMt2bkEhRDgm2rkrA__.mp4';
			$param['frontAdd']='http://graph2.yixia.com/assets/testip4shu.mp4';
			$req=array(
				//'src'=>'http://wscdn.miaopai.com/stream/J~fuZMZgVdl09wMiH47ueA__.mp4',
				'src'=>'http://graph2.yixia.com/assets/testsrc.mp4',
				'dist'=>'stream/UkeM1TgFP4ZW45uy-jWd~Q__.mp4',
				'disttype'=>'s3',
				'callback'=>'http://www.yixia.com/v4_remind.json?test=1',
				'logo'=>'miaopai',
				'type'=>'combine',
				'format'=>'mp4_800',
			);
			$req['params']=urlencode(json_encode($param));
    		$tid = Transcode_MainModel::addJob($req['src'], $req['dist'], $req['disttype'], $req['callback'], $req['type']
    			, $req['format'], $req['logo'], json_decode(urldecode($req['params']),true));
    		echo('Add job tid:'.$tid);
*/
    	}
    }

}
