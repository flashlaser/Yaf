#!/bin/bash
#author     zhangbaojun <zhangbaojun@yixia.com>


set -e
LC_ALL=C
LANG=C
unset TZ
TZBase=$(LC_ALL=C TZ=UTC0 date -R)
UTdate=$(LC_ALL=C TZ=UTC0 date -d "$TZBase")
TZdate=$(unset TZ ; LANG=C date -d "$TZBase")

#要导入的sql文件夹
file_path="xxx"    
#要导入的mysql主机          
host="xxx"          
#mysql的用户名         
username="xxx"   
#mysql的密码                    
password="xxx"      
#mysql的数据库名         
dbname="xxx"    
#计时            
now=$(date "+%s")                       


mysql_source(){
    for file_name in `ls -A $1 | grep .sql$`
    do
        seg_start_time=$(date "+%s")
        if [ -f "$1$file_name" ];then
            mv $1$file_name $1$file_name.$newext
            command="source $1$file_name.$newext"
            mysql -h${host} -u${username} -p${password} ${dbname} -e "$command"
            echo "source:" \"$1$file_name\" "is ok, It takes " `expr $(date "+%s") - ${seg_start_time}` " seconds"
            #sleep 1
        fi
    done


    echo "All sql is done! Total cost: " `expr $(date "+%s") - ${now}` " seconds"
}
echo "Universal Time is now:  $UTdate."
echo "Local time is now:      $TZdate."
mysql_source $file_path