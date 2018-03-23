#!/bin/sh
/sbin/iptables -D INPUT -p tcp --dport $1
/sbin/iptables -D OUTPUT -p tcp --sport $1
/sbin/iptables -D INPUT -p udp --dport $1
/sbin/iptables -D OUTPUT -p udp --sport $1
/etc/rc.d/init.d/iptables save