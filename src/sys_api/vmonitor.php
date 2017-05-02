<?php
require_once('pylon/pylon.php');
XPylon::console_serving('mara_cron');

//写你的检查逻辑,如果正常,就输出ok
//检查redis的可用性
$host = $_SERVER["REDIS_MASTER_HOST"];
$port = $_SERVER["REDIS_MASTER_PORT"];
$timeout = 3;
$retry = 2;

while(($retry--) > 0){
    try {
        $redis = new Redis();
        $reids = $redis->connect($host,$port,$timeout);

        $redis->set('mara_cron.test',22);
        $res = $redis->get('mara_cron.test');
        if($res == 22) {
            echo "ok";
            return;
        }
    } catch (Exception $e) {
    }
}

echo "error";
