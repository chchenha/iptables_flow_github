#!/bin/sh
/sbin/iptables -A INPUT -p tcp --dport $1
/sbin/iptables -A OUTPUT -p tcp --sport $1
/sbin/iptables -A INPUT -p udp --dport $1
/sbin/iptables -A OUTPUT -p udp --sport $1
/etc/rc.d/init.d/iptables save