#!/bin/bash

user_name=root
user_pwd=anbs,23t
host_name=10.10.20.144
port=3306
database=follow2fans
db_base=follow2fans_
tb_base=follow_

function sql() {
    dbs=$(mysql -h$host_name -u$user_name -p$user_pwd -A -Bse "show databases like '$db_base%'")
    for db_name in $dbs
    do
        tables=$(mysql -h$host_name -u$user_name -p$user_pwd $db_name -A -Bse "show tables like '$tb_base%'")
        for table_name in $tables
        do
            check_result=$(mysql -h$host_name -u$user_name -p$user_pwd $db_name -A -Bse"DESC $table_name app_id" | awk '{print $1}')
            if [ "$check_result" != "app_id" ]; then
                #echo "alter table $table_name begin,"
                #echo $(mysql -h$host_name -u$user_name -p$user_pwd $db_name -A -Bse"alter table $table_name add column app_id int(10) unsigned not null default 422 after id;ALTER TABLE $table_name DROP INDEX uk_user_fans,ADD UNIQUE uk_user_fans (user, fans, app_id) USING BTREE;ALTER TABLE $table_name DROP INDEX idx_fans, ADD INDEX idx_fans (fans, app_id) USING BTREE;")
                #echo $(mysql -h$host_name -u$user_name -p$user_pwd $db_name -A -Bse"alter table $table_name add column app_id int(10) unsigned not null default 422 after id;ALTER TABLE $table_name DROP INDEX uk_user_fans,ADD UNIQUE uk_user_fans (user, fans, app_id) USING BTREE;ALTER TABLE $table_name DROP INDEX idx_user, ADD INDEX idx_user (user, app_id) USING BTREE;")
                $(mysql -h$host_name -u$user_name -p$user_pwd $db_name -A -Bse"alter table $table_name add column app_id int(10) unsigned not null default 422 after id")
                #echo $(mysql -h$host_name -u$user_name -p$user_pwd $db_name -A -Bse"ALTER TABLE $table_name DROP INDEX uk_user_fans,ADD UNIQUE uk_user_fans (user, fans, app_id)")
                #echo "alter table $table_name end."
                #sleep 1
            fi
            #echo $(mysql -h$host_name -u$user_name -p$user_pwd $db_name -A -Bse"ALTER TABLE $table_name DROP INDEX uk_user_fans,ADD UNIQUE uk_user_fans (user, fans, app_id) USING BTREE;ALTER TABLE $table_name DROP INDEX idx_user, ADD INDEX idx_user (user, app_id) USING BTREE;")
            $(mysql -h$host_name -u$user_name -p$user_pwd $db_name -A -Bse"ALTER TABLE $table_name DROP INDEX uk_user_fans,ADD UNIQUE uk_user_fans (user, fans, app_id) USING BTREE;ALTER TABLE $table_name DROP INDEX idx_fans, ADD INDEX idx_fans (fans, app_id) USING BTREE;")
        done
    done
}
sql;