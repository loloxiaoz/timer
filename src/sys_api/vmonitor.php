<?php
require_once('pylon/pylon.php');

//写你的检查逻辑,如果正常,就输出ok
//检查redis的可用性
$timeout = 3;
$retry = 2;

while(($retry--) > 0){
    try {
        $redis = KVStore::getInstance(KVStore::PLATOV2);
        $redis->set('cron.monitor',22);
        $res = $redis->get('cron.monitor');
        if($res == 22) {
            echo "ok";
            return;
        }
    } catch (Exception $e) {
    }
}

echo "error";
