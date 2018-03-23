#!/bin/sh
#log_pro="/home/iptables_flow"
#chmod -R 777 $log_pro

log_folder="/home/iptables_flow/log"
if [ ! -d "$log_folder" ]; then
  mkdir "$log_folder"
fi

y=`date "+%Y"`
y_folder=$log_folder"/"$y
if [ ! -d "$y_folder" ]; then
  mkdir "$y_folder"
fi

m=`date "+%m"`
m_folder=$log_folder"/"$y"/"$m
if [ ! -d "$m_folder" ]; then
  mkdir "$m_folder"
fi

d=`date "+%d"`
d_folder=$log_folder"/"$y"/"$m"/"$d
if [ ! -d "$d_folder" ]; then
  mkdir "$d_folder"
fi

h=`date "+%H"`
h_folder=$log_folder"/"$y"/"$m"/"$d"/"$h
if [ ! -d "$h_folder" ]; then
  mkdir "$h_folder"
fi

file_name=`date "+%H%M%S"`
file=$h_folder"/"$file_name.txt
touch $file
chmod -R 777 $file
/sbin/iptables -L -v -n -x>>$file
