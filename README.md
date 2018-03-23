#shelldoc
1.sh脚本，记录iptables里面的tcp和udp流量,生成到文件里
2.把当天的流量数据生成到数据表里
3.生成json帐号，并让来ss-server执行这个帐号

INSTALL:
1.拷贝在/home目录下
ln -s /sbin/iptables /usr/bin/iptables

NOTICE:
1.有时候发现日志写不进去，/home/iptables_flow设为777即可