<?php
//1分钟跑一次,来统计下流量数据
$basedir = dirname(__FILE__);
include_once $basedir.'/config.php';
include_once $basedir.'/class/db.php';

//数据库连接
$db = db::getInstance($config['host'], $config['user'], $config['pwd'], $config['db'], 'utf8');

$day = date('d');
$floder_path = $basedir.'/log/'.date('Y').'/'.date('m').'/'.$day.'/'.date('H');
//$floder_path = $basedir.'/log/'.date('Y').'/'.date('m').'/'.$day.'/10';
$file_arr = listDir($floder_path);
$table = 'ss_iptables_log_'.date('Y').date('m');
sort($file_arr);


$fileNameArr = getLastFileNameAndSecondLastFileName($file_arr); //文件里面有所有开放端口和流量
$runPorts = array();

//需要两个都存在
if(!empty($fileNameArr['lastfilename']) && !empty($fileNameArr['secondLastfilename'])){
    $lastFilenameFlowInfo = getFlowInfoByFile($fileNameArr['lastfilename']);
    $secondLastFilenameFlowInfo = getFlowInfoByFile($fileNameArr['secondLastfilename']);

    //比对下这两个文件的port是否一样,如果不一样则写日志,短信提醒
    $comparePortRes = comparePort($lastFilenameFlowInfo,$secondLastFilenameFlowInfo);
    if($comparePortRes == false)    return false;

    if(count($lastFilenameFlowInfo['port']))    $runPorts = $lastFilenameFlowInfo['port'];

    //重新生成流量规则    iptables--根据文件生成
    runIptablesRule($runPorts,$db);

    //写入增量数据
    $increaseData = getIncreaseData($lastFilenameFlowInfo,$secondLastFilenameFlowInfo,$runPorts);
    $_add_time = date('Y-m-d H:i:s');
    if(count($increaseData['flow'])){
        foreach($increaseData['flow'] as $port => $v){
            $info['file_name'] = $increaseData['file_name'];
            $info['port'] = $port;
            $info['d'] = $day;
            $info['output_tcp_bytes'] = $v['output_tcp_bytes'];
            $info['output_udp_bytes'] = $v['output_udp_bytes'];
            $info['input_tcp_bytes'] = $v['input_tcp_bytes'];
            $info['input_udp_bytes'] = $v['input_udp_bytes'];
            $info['total_out_bytes'] = $info['output_tcp_bytes']+$info['output_udp_bytes'];
            $info['total_in_bytes'] = $info['input_tcp_bytes']+$info['input_udp_bytes'];
            $info['total_bytes'] = $info['total_out_bytes']+$info['total_in_bytes'];
            $info['server_ip'] = $config['server_ip'];
            $info['add_time'] = $_add_time;
            $db->insert($table,$info);
        }
    }
}



echo "ok";
$db->destruct();


//重新生成流量规则    iptables
/*
 * 1.如果端口没有，则生成规则
 * 2.如果端口过期，则关闭端口
 * 3.如端口流量超标，则关闭端口
 */
function runIptablesRule($runPorts,$db){
    $_time = date("Y-m-d H:i:s");

    //1.如果端口没有，则生成规则
    $sql = "select port from user where is_game=1 and game_end_date>='".$_time."'";
    $use_res = $db->query($sql);
    foreach ($use_res as $k => $v){
        $port = $v['port'];
        if(!in_array($port,$runPorts)){
            $doc = 'sh /home/iptables_flow/createiptables.sh '.$port;
            exec($doc);
            echo "create iptables port:".$port."\n";
        }
    }
    unset($sql);
    unset($use_res);

    //2.如果端口过期，则关闭端口
    $sql = "select port from user where is_game=1 and game_end_date<'".$_time."'";
    $use_res = $db->query($sql);
    foreach ($use_res as $k => $v){
        $port = $v['port'];
        if(in_array($port,$runPorts)){
            $doc = 'sh /home/iptables_flow/deliptables.sh '.$port;
            exec($doc);
            echo "del iptables port:".$port."\n";
        }
    }


    //3.如端口流量超标，则关闭端口

    unset($db);
}

/*
 * 比对下两个文件的port是否一样
 */
function comparePort($lastFilenameFlowInfo,$secondLastFilenameFlowInfo){
    $lastFilePortCount          = count($lastFilenameFlowInfo['port']);
    $secondLastFilenPortCount   = count($secondLastFilenameFlowInfo['port']);

    if($lastFilePortCount != $secondLastFilenPortCount){
        return flase;
    }

    for($i=0;$i<=$lastFilePortCount-1;$i++){
        if($lastFilenameFlowInfo['port'][$i] != $secondLastFilenameFlowInfo['port'][$i]){
            return false;
        }
    }

    return true;
}

/*
 * 获取增量流量
 */
function getIncreaseData($lastFilenameFlowInfo,$secondLastFilenameFlowInfo,$portArray){
    if(!is_array($portArray) || !count($portArray))   return false;
    $info = array();
    foreach($portArray as $k => $port){
        $lastFilenameFlowOutputTcpBytes = $lastFilenameFlowInfo['flow'][$port]['output_tcp_bytes'];
        $lastFilenameFlowOutputUdpBytes = $lastFilenameFlowInfo['flow'][$port]['output_udp_bytes'];
        $lastFilenameFlowInputTcpBytes = $lastFilenameFlowInfo['flow'][$port]['input_tcp_bytes'];
        $lastFilenameFlowInputUdpBytes = $lastFilenameFlowInfo['flow'][$port]['input_udp_bytes'];
        $lastFilenameFlowTotalBytes = $lastFilenameFlowInfo['flow'][$port]['total_bytes'];

        $secondLastFilenameFlowOutputTcpBytes = $secondLastFilenameFlowInfo['flow'][$port]['output_tcp_bytes'];
        $secondLastFilenameFlowOutputUdpBytes = $secondLastFilenameFlowInfo['flow'][$port]['output_udp_bytes'];
        $secondLastFilenameFlowInputTcpBytes = $secondLastFilenameFlowInfo['flow'][$port]['input_tcp_bytes'];
        $secondLastFilenameFlowInputUdpBytes = $secondLastFilenameFlowInfo['flow'][$port]['input_udp_bytes'];
        $secondLastFilenameFlowTotalBytes = $secondLastFilenameFlowInfo['flow'][$port]['total_bytes'];

        //增量数据
        $output_tcp_bytes = $lastFilenameFlowOutputTcpBytes-$secondLastFilenameFlowOutputTcpBytes;
        if($output_tcp_bytes<=0){$output_tcp_bytes = 0;}
        $output_udp_bytes = $lastFilenameFlowOutputUdpBytes-$secondLastFilenameFlowOutputUdpBytes;
        if($output_udp_bytes<=0){$output_udp_bytes = 0;}
        $input_tcp_bytes = $lastFilenameFlowInputTcpBytes-$secondLastFilenameFlowInputTcpBytes;
        if($input_tcp_bytes<=0){$input_tcp_bytes = 0;}
        $input_udp_bytes = $lastFilenameFlowInputUdpBytes-$secondLastFilenameFlowInputUdpBytes;
        if($input_udp_bytes<=0){$input_tcp_bytes = 0;}
        $total_bytes = $lastFilenameFlowTotalBytes-$secondLastFilenameFlowTotalBytes;
        if($total_bytes<=0){$total_bytes = 0;}

        $info['flow'][$port]['output_tcp_bytes'] = $output_tcp_bytes;
        $info['flow'][$port]['output_udp_bytes'] = $output_udp_bytes;
        $info['flow'][$port]['input_tcp_bytes'] = $input_tcp_bytes;
        $info['flow'][$port]['input_udp_bytes'] = $input_tcp_bytes;
        $info['flow'][$port]['total_bytes'] = $total_bytes;
    }

    $info['file_name'] = $lastFilenameFlowInfo['file_name'];
    return $info;
}

/*
 * 获取流量信息
 */
function getFlowInfoByFile($filename){
    $html = file_get_contents($filename);
    //Chain OUTPUT  tcp
    preg_match_all('/(?P<pkts>\d{1,})(.*?)(?P<bytes>\d{1,})(.*?)tcp spt:(?P<port>\d{5})/',$html,$output_tcp_arr);
    //Chain OUTPUT  udp
    preg_match_all('/(?P<pkts>\d{1,})(.*?)(?P<bytes>\d{1,})(.*?)udp spt:(?P<port>\d{5})/',$html,$output_udp_arr);
    //Chain INPUT  tcp
    preg_match_all('/(?P<pkts>\d{1,})(.*?)(?P<bytes>\d{1,})(.*?)tcp dpt:(?P<port>\d{5})/',$html,$input_tcp_arr);
    //Chain INPUT  udp
    preg_match_all('/(?P<pkts>\d{1,})(.*?)(?P<bytes>\d{1,})(.*?)udp dpt:(?P<port>\d{5})/',$html,$input_udp_arr);

    $port_count = count($output_tcp_arr['port']);

    for($i=0;$i<$port_count;$i++){
        $port   = $output_tcp_arr['port'][$i];
        $info['flow'][$port]['output_tcp_bytes']  = $output_tcp_arr['bytes'][$i];
        $info['flow'][$port]['output_udp_bytes']  = $output_udp_arr['bytes'][$i];
        $info['flow'][$port]['input_tcp_bytes']  = $input_tcp_arr['bytes'][$i];
        $info['flow'][$port]['input_udp_bytes']  = $input_udp_arr['bytes'][$i];
        $file_name = basename($filename,".txt");
        $info['flow'][$port]['total_bytes'] = $info['flow'][$port]['output_tcp_bytes']+$info['flow'][$port]['output_udp_bytes']+$info['flow'][$port]['input_tcp_bytes']+$info['flow'][$port]['input_udp_bytes'];
        $info['file_name'] = $file_name;
        $info['port'][] = $port;
    }

    return $info;

}

/*
 * 获取最新的文件和离最新靠近的文件
 */
function getLastFileNameAndSecondLastFileName($file_arr){
//获取最新的文件和离最新靠近的文件
    $file_arr_count = count($file_arr);

//最新的文件
    if($file_arr_count > 1){
        $lastkey = $file_arr_count-1;
    }else{
        $lastkey = 0;
    }
    $lastfilename = $file_arr[$lastkey];

//离最新靠近的文件
    $secondLastfilename = "";
    if($lastkey!=0){
        $secondLastfilename = $lastkey-1;
        $secondLastfilename = $file_arr[$secondLastfilename];
    }

    $info['lastfilename'] = $lastfilename;
    $info['secondLastfilename'] = $secondLastfilename;
    return $info;
}

/***********************
第二种实现办法：用readdir()函数
 ************************/
function listDir($dir)
{
    $file_arr = array();
    if(is_dir($dir))
    {
        if ($dh = opendir($dir))
        {
            while (($file = readdir($dh)) !== false)
            {
                if((is_dir($dir."/".$file)) && $file!="." && $file!="..")
                {
                    $file_arr[] = $dir.'/'.$file;
                    //echo $file."\n";
                    listDir($dir."/".$file."/");
                }
                else
                {
                    if($file!="." && $file!="..")
                    {
                        $file_arr[] = $dir.'/'.$file;
                        //echo $file."\n";
                    }
                }
            }
            closedir($dh);
        }
    }

    return $file_arr;
}

?>