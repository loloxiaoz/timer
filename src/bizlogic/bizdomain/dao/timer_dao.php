<?php

/*
 * @brief 定时器的相关存储操作(Redis)
 */
class TimerDao extends RedisBaseDao
{
    static public $ins;

    public static function ins()
    {
        if(self::$ins == null) {
            self::$ins = new TimerDao();
        }
        return self::$ins;
    }

    public function add($timer)
    {
        $appName    = $timer->app;
        $timerId    = $timer->timerId;
        $value      = json_encode($timer->getPropArray());

        // 检查应用是否已经注册
        $db = KVStore::getInstance(KVStore::PLATOV2);
        if (!$db->hexists(CronConstants::APP_DB_NAME,$appName)) {
            $this->logger->warn("app {$appName} not exist");
            throw new Exception("app '{$appName}' have not registry");
        }
        try {
            // 增加定时任务
            $timerKey = $this->getTimerKey($timerId);
            $db->set($timerKey,$value);
        } catch (Exception $e) {
            $this->logger->error("add timer fail, app {$appName} timer[{$timerId}] {$value} errmsg=" . $e->getMessage());
            throw $e;
        }
        try {
            // 增加定时任务id到应用的定时任务列表
            $appTimersKey = $this->getAppTimersKey($appName);
            $db->zadd($appTimersKey,TimeUtil::mktime($timer->createTime),$timerId);
        } catch (Exception $e) {
            $this->logger->error("add timer to app timer list fail, app {$appName} timer[{$timerId}] {$value} errmsg=" . $e->getMessage());
            throw $e;
        }
        try {
            // 增加定时任务到调度列表
            $db->zadd($this->getCronListKey(),TimeUtil::mktime($timer->expireTime),$timerId);
        } catch (Exception $e) {
            $this->logger->error("add timer to cron list fail, app {$appName} timer[{$timerId}] {$value} errmsg=" . $e->getMessage());
            // TODO clear dirty data
            throw $e;
        }
        return $timer;
    }

    public function get($timerId)
    {
        $timerKey   = $this->getTimerKey($timerId);
        $db         = KVStore::getInstance(KVStore::PLATOV2);
        $result     = $db->get($timerKey);
        if (empty($result)) {
            return;
        }
        $arrs = json_decode($result,true);
        return new Timer($timerId,$arrs);
    }

    public function update($timer)
    {
        $appName    = $timer->app;
        $timerId    = $timer->timerId;
        $value      = json_encode($timer->getPropArray());
        $db         = KVStore::getInstance(KVStore::PLATOV2);

        try {
            // update timer
            $timerKey = $this->getTimerKey($timerId);
            $db->set($timerKey,$value);
        } catch (Exception $e) {
            $this->logger->error("update timer fail, app {$appName} timer[{$timerId}] {$value} errmsg=" . $e->getMessage());
            throw $e;
        }
        try {
            // update expiretime
            $db->zadd($this->getCronListKey(),TimeUtil::mktime($timer->expireTime),$timerId);
        } catch (Exception $e) {
            $this->logger->error("update timer to cron list fail, app {$appName} timer[{$timerId}] {$value} errmsg=" . $e->getMessage());
            throw $e;
        }

        return true;
    }

    public function del($timerId)
    {
        $db      = KVStore::getInstance(KVStore::PLATOV2);
        $appName = TimerDao::getAppName($timerId);
        try {
            $db->zrem($this->getCronListKey(),$timerId);
        } catch (Exception $e) {
            $this->logger->error("del timer from cron list fail, app {$appName} timer[{$timerId}] errmsg=" . $e->getMessage());
            throw $e;
        }
        try {
            $appTimersKey = $this->getAppTimersKey($appName);
            $db->zrem($appTimersKey,$timerId);
        } catch (Exception $e) {
            $this->logger->error("del timer from app timer list fail, app {$appName} timer[{$timerId}] errmsg=" . $e->getMessage());
            throw $e;
        }
        try {
            $timerKey = $this->getTimerKey($timerId);
            $db->del($timerKey);
        } catch (Exception $e) {
            $this->logger->error("del timer fail, app {$appName} timer[{$timerId}] errmsg=" . $e->getMessage());
            throw $e;
        }

        return true;
    }

    public function delFromCronList($timerId)
    {
        $db   = KVStore::getInstance(KVStore::PLATOV2);
        try {
            $timerId = $timerId;
            $db->zrem($this->getCronListKey(),$timerId);
        } catch (Exception $e) {
            $this->logger->error("del timer from cron list fail, app {$appName} timer[{$timerId}] errmsg=" . $e->getMessage());
            throw $e;
        }
        return true;
    }

    public function getExpireTimer()
    {
        $db   = KVStore::getInstance(KVStore::PLATOV2);
        // 从调度队列获取到点的执行任务id
        $data = $db->zRangeByScore($this->getCronListKey(),0,time(),array('limit'=>array(0,1)));
        if (empty($data)) {
            return;
        }

        // 获取相应的定时任务信息
        $timerId = $data[0];
        $timerKey = $this->getTimerKey($timerId);
        $result = $db->get($timerKey);
        if (empty($result)) {
            // 在调度队列存在timerId，但是获取不到定时器的内容，有可能哪个环节的bug，需要观察一下
            $db->zrem($this->getCronListKey(),$timerId);
            $this->logger->error("timer ${timerId} not exist");
            return;
        }

        $arrs = json_decode($result,true);
        return new Timer($timerId,$arrs);
    }

    public function lockCronList()
    {
        // 进行加锁，如果已经有其他client加锁，那么返回其他client的标示，不然返回这个client的标示
        // 获取cron_lock是否已经有人加锁
        //      如果有加锁:
        //          并且加锁对象是自己，那么重新设置过期时间，并返回自己的标示
        //          如果加锁对象是其他client，那么不做什么事情，返回加锁的那个client的标示o
        //      如果没有加锁：
        //          进行加锁set，保存自己client的标示，并设置过期时间

        $key        = "cron_lock";
        $redis      = KVStore::getInstance(KVStore::PLATOV2);
        $isLock    = $redis->setnx($key, time()+CronConstants::LOCK_EXPIRE);
        // 不能获取锁
        if(!$isLock){
            // 判断锁是否过期
            $lockTime = $redis->get($key);
            // 锁已过期，删除锁，重新获取
            if(time()>=$lockTime){
                $redis->del($key);
                $isLock = $redis->setnx($key, time()+CronConstants::LOCK_EXPIRE);
            }
        }
        $this->logger->debug("callLockCronList $isLock");
        return $isLock? true : false;
    }

    public function getCronListKey()
    {
        return CronConstants::CRON_LIST;
    }

    public function getAppTimersKey($appName)
    {
        return $appName . CronConstants::APP_TIMERS_SUFFIX;
    }

    public function getTimerKey($timerId)
    {
        return $timerId . CronConstants::TIMER_SUFFIX;
    }

    static public function getAppName($timerId)
    {
        list($appName,$id) = explode("_",$timerId);
        return $appName;
    }

    static public function getTimerId($appName,$id)
    {
        return $appName . "_" . $id;
    }
}
