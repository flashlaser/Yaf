#!/bin/bash

host="api.miaopai.com"
#url=$1
read -p "input a url :" url

if  [ ! -n "$url" ] ;then
    url="/m/ver.json"
fi

while read line
do
        echo "============================================"
        echo "$line $url"
        re=`curl -o tmp -s -w "%{http_code}\t%{time_connect}\t%{time_total}\n" -H "Host:$host" "http://${line}${url}"`
        echo "$re"
        cat tmp |md5sum
        echo "============================================"

done < /root/baojun/deploylist.txt
