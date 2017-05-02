<?php

abstract class RedisBaseDao
{
    protected $logger;
    const SLOW_TIME = 50;

    protected function __construct()
    {
        $this->logger = XLogKit::logger("scope");
    }

    /*
     * @brief 数据查询，优先读取slave，如果slave失败，读取master
     *
     * @param $callable 要调用的方法
     * @param $params   要调用方式的参数
     *
     * @return $callable的操作结果
     */
    protected function queryRedis($callable,$params=array())
    {
        // 单点取主，从只做冷备
        return $this->call($callable,$params,$_SERVER["REDIS_MASTER_HOST"],$_SERVER["REDIS_MASTER_PORT"]);
    }

    /*
     * @brief 数据变更，需要操作master
     *
     * @param $callable 要调用的方法
     * @param $params   要调用方式的参数
     *
     * @return $callable的操作结果
     */
    protected function updateRedis($callable,$params=array())
    {
        // master 失败，那么master是需要进行恢复的
        return $this->call($callable,$params,$_SERVER["REDIS_MASTER_HOST"],$_SERVER["REDIS_MASTER_PORT"]);
    }

    protected function call($callable,$params=array(),$host,$port)
    {
        $start = floatval(microtime(true) * 1000);
        $redis = null;
        try {
            $redis = $this->createDb($host,$port);
            $result = $this->$callable($redis,$params);
            $this->destroyDb($redis);

            $end = floatval(microtime(true) * 1000);
            if ($end - $start > RedisBaseDao::SLOW_TIME) {
                $clz = get_class($this);
                $this->logger->warn("[{$clz}::{$callable}] ${host}:${port} too slow, usetime: " . intval($end - $start) . " ms");
            }

            $this->logger->debug("[{$clz}::{$callable}] ${host}:${port} usetime: " . intval($end - $start) . " ms");
            return $result;
        } catch (Exception $e) {
            if ($redis) {
                $this->destroyDb($redis);
            }
            $clz = get_class($this);
            $end = floatval(microtime(true) * 1000);
            $this->logger->error("[{$clz}::{$callable}] ${host}:${port} exception, usetime: " . intval($end - $start) . " ms errmsg=" . $e->getMessage());
            throw $e;
        }

    }

    protected function createDb($host,$port,$timeout=4)
    {
        $redis = new Redis();
        $redis->connect($host,$port,$timeout);
        return $redis;
    }

    protected function destroyDb(& $redis)
    {
        try {
            $redis->close();
        } catch (Exception $e) {
            $clz = get_class($this);
            $this->logger->error("[{$clz}::destroyDb] ${host}:${port} errmsg=" . $e->getMessage());
        }
        unset($redis);
    }

}
