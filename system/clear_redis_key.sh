#!/bin/bash

#while read line
max=100000
host="xxx"
port="xxx"
key=""

#for((i=1;i<=10000;i++));
while true
do
        #echo $line
        left=$(/usr/local/bin/redis-cli -h $host  -p $port llen $key)
        if [ $left -ge $max ];then 
                /usr/local/bin/redis-cli -h $host  -p $port lpop $key
        else
                break
        fi
#done < aaakey
done
/usr/local/bin/redis-cli -h $host  -p $port llen $key