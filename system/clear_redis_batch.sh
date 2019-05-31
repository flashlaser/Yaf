#!/bin/bash

host="r-2ze76a74e6f28ed4.redis.rds.aliyuncs.com"
port="6379"
pass="anbs23T4mcq"
key="clear8167"

while true
do
  filelist=`ls /data/tmp/redis/* |grep -v \.keys$ |grep -v \.doing$ |grep -v \.deal$ |grep -v \.sh$`
  a=0
  for file in $filelist
  do
    if [ ! -f "$file" ]; then
      continue
    fi
    #echo $file
    mv $file $file.doing
    while read line
    do
      #echo $line
      re=$(/usr/local/bin/redis-cli -h $host  -p $port -a $pass rpush $key $line)
    done < $file.doing
    mv $file.doing $file.deal
    let a++
  done
  if [ $a -eq 0 ]; then
    exit
  fi
done