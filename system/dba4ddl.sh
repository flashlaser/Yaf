#!/bin/bash

user_name=yixiadba
user_pwd=anbs23T4yixiadba
host_name=rm-2zea9o1z9w08gqycm.mysql.rds.aliyuncs.com
port=3306
db_name=mp_user
tb_base=user_

function sql() {
        tables=$(mysql -h$host_name -u$user_name -p$user_pwd $db_name -A -Bse "show tables like '$tb_base%'")
        for table in $tables
        do
            check_result=$(mysql -h$host_name -u$user_name -p$user_pwd $db_name -A -Bse"DESC $table birthday" | awk '{print $1}')
            if [ "$check_result" != "birthday" ]; then
                #cmd=" ALTER TABLE $table algorithm=inplace, lock=none, CHANGE lastModifyTime lastModifyTime TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
                echo $(mysql -h$host_name -u$user_name -p$user_pwd $db_name -A -Bse" ALTER TABLE $table algorithm=inplace, lock=none, ADD COLUMN birthday date NOT NULL DEFAULT '19000101' COMMENT '用户生日'")
                sleep 1
           fi
        done
}
sql;