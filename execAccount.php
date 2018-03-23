<?php
//5 秒跑一次
/*
 * 1.生成json帐号，并让来ss-server执行这个帐号
 * 2.根据时间和流量,清掉进程,删除配置文件
 */
$basedir = dirname(__FILE__);
include_once $basedir.'/config.php';
include_once $basedir.'/class/db.php';

//数据库连接
$db = db::getInstance($config['host'], $config['user'], $config['pwd'], $config['db'], 'utf8');
$_time = date("Y-m-d H:i:s");

//1.生成json帐号，并让来ss-server执行这个帐号
$sql = "select uid,passwd,port from user where is_game=1 and game_end_date>='".$_time."' and game_transfer_enable>=(game_u+game_d)";
$use_res = $db->query($sql);

foreach($use_res as $k => $v){
    $uid        = $v['uid'];
    $passwd     = $v['passwd'];
    $port       = $v['port'];

    /*
    $uid = 999;
    $port = 38626;
    $passwd = '1112ss';
    */

    //config脚本是否存在
    $filename = '/etc/shadowsocks-libev/config'.$uid.'.json';
    if (!file_exists($filename)) {
        $str = '{
        "server":"0.0.0.0",
        "server_port":'.$port.',
        "local_port":1080,
        "password":"'.$passwd.'",
        "timeout":60,
        "method":"rc4-md5",
}';
        file_put_contents($filename, $str);
    }


    //config脚本是否执行
    $doc = 'sh /home/iptables_flow/checkexecprocess.sh '.$uid;
    $check_execprocess_data = exec($doc);
    if($check_execprocess_data == 0){
        echo "create proccess ".$uid."\n";
        $doc = '/user/bin/ss-server -u -c /etc/shadowsocks-libev/config'.$uid.'.json  > /dev/null 2>&1 &';
        exec($doc);
    }
}
unset($use_res);

//2.根据时间,清掉进程,删除配置文件
$sql = "select uid,passwd,port from user where game_end_date<'".$_time."'";
$use_res = $db->query($sql);

foreach($use_res as $k => $v){
    $uid        = $v['uid'];
    $passwd     = $v['passwd'];
    $port       = $v['port'];

    delAccount($uid);
}


//3.根据流量,清掉进程,删除配置文件
$sql = "select uid,passwd,port from user where is_game=1 and game_end_date>='".$_time."' and game_transfer_enable<(game_u+game_d)";
$use_res = $db->query($sql);
foreach($use_res as $k => $v) {
    $uid = $v['uid'];
    $passwd = $v['passwd'];
    $port = $v['port'];

    delAccount($uid);
}

function delAccount($uid){
    //config脚本是否执行
    $doc = 'sh /home/iptables_flow/checkexecprocess.sh '.$uid;
    $check_execprocess_data = exec($doc);
    if($check_execprocess_data == 1){
        echo "del proccess ".$uid."\n";

        $doc1 = 'ps -ef|grep config'.$uid.'.json|grep -v "grep"|xargs kill -s 9  > /dev/null 2>&1 &';
        //echo $doc1."\n";
        exec($doc1);

        $doc2 = 'rm -rf /etc/shadowsocks-libev/config'.$uid.'.json > /dev/null 2>&1 &';
        //echo $doc2."\n";
        exec($doc2);
    }
}
?>