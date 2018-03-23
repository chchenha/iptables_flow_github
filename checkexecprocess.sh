#!/bin/sh
ps -fe|grep config$1.json |grep -v grep
if [ $? -ne 0 ]
then
echo "0"
else
echo "1"
fi