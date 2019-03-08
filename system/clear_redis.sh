#!/bin/bash

host="2d93210c8d654f3f.m.cnbja.kvstore.aliyuncs.com"
port="6379"
pass="anbs23t4HASH"

while read line
do
        #echo $line
       /usr/local/bin/redis-cli -h $host  -p $port -a $pass del $line
done < c.keys

#host="r-2ze76a74e6f28ed4.redis.rds.aliyuncs.com"
#port="6379"
#pass="anbs23T4mcq"
#key="clear8167"

#filelist=`ls /data1/redis/keys/*.keys`
#for file in $filelist
#do
  #echo $file
  #while read line
  #do
       #echo $line
       #re=$(/usr/local/bin/redis-cli -h $host  -p $port -a $pass rpush $key $line)
  #done < $file
  #mv $file $file.done
#done