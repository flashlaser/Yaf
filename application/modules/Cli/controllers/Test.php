<?php
error_reporting(1);
/**
 * test
 * 
 * 执行命令：
 * /usr/local/bin/php /data1/www/htdocs/i.miaopai.com/cli.php request_uri='/cli/test/run'
 * /usr/local/bin/php cli.php request_uri='/cli/test/run'
 * /usr/local/bin/php cli.php request_uri='/cli/test/oic'
 * 
 * @package test
 * @Autor: baojun <baoju @staff.sina.com.cn>
 * @Date: 2015-01-31 14:57
 */
class TestController extends Abstract_Controller_Cli{

    public function runAction() {
throw new Exception_Soap('11','xx');
    	//$ret = Apilib_Cms::init()->setMetric('CPUUtilization')->query('i-25t9le1dc', '2016-09-29T00:00:00Z', '2016-09-29T12:00:00Z', 0, 10);
    	$dimensions = array('instanceId' => 'rr-2zei1758idojtelmy', 'type' => 'CpuUsage');
    	$start_time = date('Y-m-d H:i:s', time() - 60 * 10);
    	$end_time   = null;//date('Y-m-d\TH:i:s\Z');
    	$ret = Apilib_Cms::init(Apilib_Cms::RDS)->setMetric('CpuUsage')->setPeriod(300)->query($dimensions, $start_time, $end_time, 0, 1);
    	 
    	print_r($ret);
    }
    
    /**
     * mc test
     */
    public function mcAction() {
        $mc = new Memcached();
        $mc->setOption ( Memcached::OPT_BINARY_PROTOCOL, true );
        $mc->addServer('0ca859d8b9a847a7.m.cnbjalinu16pub001.ocs.aliyuncs.com', 11211);
        $mc->setSaslAuthData ( '0ca859d8b9a847a7', 'anbs23T4user' );
        $items = array(
                'test_key:1' => 'value1',
                'test_key:2' => 'value2',
                'test_key:3' => 'value3'
        );
        $ret = $mc->setMulti($items, time() + 300);
    
        var_dump($ret);
    }
    
    /**
     * oic test
     */
    public function oicAction() {
        $code_obj = new Helper_Code();
        $db = null;//Comm_Db::d(Comm_Db::DB_HJHH, true);
        //$sql = "select * from TISUVIPINFO where rownum <= 8";
        //$ret = $db->fetchAll($sql);
        //$ret = $db->insert(Comm_Db::t('kv'), array ('k' => $name, 'v' => $val), true);
        $table = 'TISUVIPINFO';
        $VIPInfoNo = dk_get_next_id();//'7546630111';
        $VIPName = '张宝军';
        $Gender   = '1';
        $Birthday = '1979-09-01';
        $CertType = '01';
        $CertNo   = '452723198501064016';
        $MrgStatus= '2';
        $Height   = 0;
        $Weight   = 0;
        $Mailaddr = '测试';
        $Http     = "http://";
        $Mobile   = '13552399786';
        $OriginType = '0';
        $LrDate = date('Y-m-d');//2018-07-09';
        $LrTime = date('H:i:s');//17:54:03';
        $UserID = 84;
        $UserCode = '009507';
        $UserName = '陈通';
        $OrgCode = '07';
        $HaveChild = '0';
        $IsReceiveMsg = '0';
        $UpderID = 0;
        $RegisterID = 0;
        //$ChkStatus = NULL;
        
        /*$email    = 'zhangbaojun@qiuxinpay.com';
        $mrg_status = '3';
        $is_receive_msg = '0';
        $org_code = '07';*/
        //check vip no
        $sql = "SELECT COUNT(1) as cnt FROM {$table} WHERE Mobile=?";
        $cnt = 0;//$db->fetchOne($sql, array($Mobile));
        if ($cnt <= 0) {
            //insert vip info
            $sql = "INSERT INTO {$table} (";
            $sql.= "VIPInfoNo,VIPName,Gender,Birthday,CertType,CertNo,MrgStatus,Height,Weight,Mailaddr,Http,Mobile,OriginType,LrDate,LrTime,UserID,UserCode,UserName,OrgCode,HaveChild,IsReceiveMsg,UpderID,RegisterID)";
            //$sql.= "VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $sql.= " VALUES(";
            $sql.= "{$VIPInfoNo},'{$VIPName}','{$Gender}','{$Birthday}','{$CertType}','{$CertNo}','{$MrgStatus}',{$Height},{$Weight},'{$Mailaddr}','{$Http}','{$Mobile}','{$OriginType}','{$LrDate}','{$LrTime}',{$UserID},'{$UserCode}','{$UserName}','{$OrgCode}','{$HaveChild}','{$IsReceiveMsg}',{$UpderID},{$RegisterID})";
            print_r(array($sql));
            //$sql = "INSERT INTO {$table}(VIPINFONO,VIPNAME,GENDER,MOBILE,EMAIL,CERTTYPE,CERTNO,MRGSTATUS,ISRECEIVEMSG) ";
            //$sql.= "VALUES({$vip_info_no},'{$vip_name}','{$gender}','{$mobile}','{$email}','{$cert_type}','{$cert_no}','{$mrg_status}','{$is_receive_msg}')";
            $ret = true;//$db->execute($sql, array($VIPInfoNo,$VIPName,$Gender,$Birthday,$CertType,$CertNo,$Native,$MrgStatus,$Height,$Weight,$Mailaddr,$Http,$Mobile,$Photo,$OriginType,$LrDate,$LrTime,$UserID,$UserCode,$UserName,$OrgCode,$HaveChild,$IsReceiveMsg,$UpderID,$RegisterID,$ChkStatus));
            if ($ret === false) {
                throw new Exception_Msg('500001');
            }
            echo "insert {$table} ok.\r\n";
        }

        //create card
        $VIPCARDNO  = dk_get_next_id();//'7546630111';
        $CARDINNO   = $code_obj->encodeID($VIPCARDNO, 80);;//'BE82F7598C64640DD77FC004650EBD69A20D8E7ABCCE030CCF140A5BA4CB2F5BAA6FD6D5C8F762ED';
        $CARDFACENO = $Mobile;//807060952
        $ISMAINCARD = '1';
        $MAINCARDNO = 0;
        $CARDTYPECODE = '8';
        $PASSWORD     = '11FBDA1F22C1D93B';
        $VIPINFONO    = $VIPInfoNo;
        $PARVALUE     = 0;
        $CANTZTOTAL   = 0;
        $CARDSTATUS   = '02';
        $VIPSTATUS    = '1';
        $OLDCARDSTATUS = '00';
        $ENDDATE       = '2199-01-01';
        $ISUORGCODE    = '07';
        $REGDATE       = '2017-12-28';
        $HLBGNDATE     = date('Y-m-d');//2018-07-09';
        $WRITETIMES    = 1;
        $WRITEDATE     = '2018-01-05';
        $USERID        = 29;
        $USERCODE      = '079201';
        $USERNAME      = '刘新红';
        $OPENTYPE      = '0';
        $OPENDATE      = date('Y-m-d');//2018-07-09';
        $KCTYPE        = '0';
        $STAYORGCODE   = '07';
        $STAYDEPCODE   = '70';
        $KEEPERID      = 29;
        $KEEPERCODE    = '079201';
        $KEEPERNAME    = '刘新红';
        $sql = "INSERT INTO tIsuCard (";
        $sql.= "VIPCARDNO,CARDINNO,CARDFACENO,ISMAINCARD,MAINCARDNO,CARDTYPECODE,PASSWORD,VIPINFONO,PARVALUE,CANTZTOTAL,CARDSTATUS,VIPSTATUS,OLDCARDSTATUS,ENDDATE,ISUORGCODE,REGDATE,HLBGNDATE,WRITETIMES,WRITEDATE,USERID,USERCODE,USERNAME,OPENTYPE,OPENDATE,KCTYPE,STAYORGCODE,STAYDEPCODE,KEEPERID,KEEPERCODE,KEEPERNAME)";
        //$sql.= "VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $sql.= " VALUES(";
        $sql.= "{$VIPCARDNO},'{$CARDINNO}','{$CARDFACENO}','{$ISMAINCARD}',{$MAINCARDNO},'{$CARDTYPECODE}','{$PASSWORD}','{$VIPINFONO}',{$PARVALUE},{$CANTZTOTAL},'{$CARDSTATUS}','{$VIPSTATUS}','{$OLDCARDSTATUS}','{$ENDDATE}','{$ISUORGCODE}','{$REGDATE}','{$HLBGNDATE}','{$WRITETIMES}','{$WRITEDATE}',{$USERID},'{$USERCODE}','{$USERNAME}','{$OPENTYPE}','{$OPENDATE}','{$KCTYPE}','{$STAYORGCODE}','{$STAYDEPCODE}',{$KEEPERID},'{$KEEPERCODE}','{$KEEPERNAME}')";
        print_r(array($sql));
        $ret = true;//$db->execute($sql, array($VIPCARDNO,$CARDINNO,$CARDFACENO,$ISMAINCARD,$MAINCARDNO,$CARDTYPECODE,$PASSWORD,$VIPINFONO,$PARVALUE,$CANTZTOTAL,$HKMODE,$CARDSTATUS,$VIPSTATUS,$OLDCARDSTATUS,$VALIDGSDATE,$ENDDATE,$ISUORGCODE,$REGDATE,$SALEDATE,$HLBGNDATE,$WRITETIMES,$WRITEDATE,$USERID,$USERCODE,$USERNAME,$OPENTYPE,$OPENDATE,$KCTYPE,$STAYORGCODE,$STAYDEPCODE,$KEEPERID,$KEEPERCODE,$KEEPERNAME));
        if ($ret === false) {
            throw new Exception_Msg('500002');
        }
        echo "insert tIsuCard ok.\r\n";
        //update vip info
        //$sql = "UPDATE {$table} SET VIPCARDNO=? WHERE VIPInfoNo=?";
        $sql = "UPDATE {$table} SET VIPCARDNO={$VIPCARDNO} WHERE VIPInfoNo={$VIPINFONO}";
        print_r(array($sql));
        $ret = true;//$db->execute($sql, array($VIPCARDNO, $VIPInfoNo));
        if ($ret === false) {
            throw new Exception_Msg('500003');
        }
        echo "update {$table} ok.\r\n";
        
    }

    public function rdqAction()
    {
        $datas = [];
        //{"shop_id":29,"openid":"ovwX21b-0PJMP_zdCaI1TDjsy0NY","num":111,"uniqueId":"153405831158178"}
        //{"shopId":29,"openid":"ovwX21b-0PJMP_zdCaI1TDjsy0NY","total":222,"orderId":null}
        echo json_encode($datas);

        $datas = '[{"shop_id":29,"openid":"ovwX21b-0PJMP_zdCaI1TDjsy0NY","num":111,"uniqueId":"153405831158178"}]';
        $datas = json_decode($datas, true);
        if (!is_array($datas) || empty($datas)) {
            return false;
        }
        foreach ($datas as $k => $v) {
            $openid = $v['openid'];
            $credit = $v['num'];
            $shop_id = $v['shop_id'];
            $uniqueId = $v['uniqueId'];

            switch ($shop_id) {
                case '25':
                    $ret = Hisense_DhModel::creditModify($openid, $credit, $uniqueId);
                    break;
                case '29':
                    Sync_TianxiaModel::creditModify($openid, $credit, $uniqueId);//success
                    break;
                default:
                    # code...
                    break;
            }
        }
    }
} 
