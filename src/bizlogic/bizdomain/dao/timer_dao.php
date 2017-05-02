<?php

/*
 * @brief 定时器的相关存储操作(Redis)
 */
class TimerDao extends RedisBaseDao
{
    private $env;
    static public $ins;
    public function ins($env="")
    {
        if(self::$ins == null) {
            self::$ins = new TimerDao();
        }
        return self::$ins;
    }

    public function add($timer)
    {
        return $this->updateRedis("callAdd",array($timer));
    }

    public function update($timer)
    {
        return $this->updateRedis("callUpdate",array($timer));
    }

    public function del($timerId)
    {
        return $this->updateRedis("callDel",array($timerId));
    }

    public function delFromCronList($timerId)
    {
        return $this->updateRedis("callDelFromCronList",array($timerId));
    }

    public function get($timerId)
    {
        return $this->queryRedis("callGet",array($timerId));
    }

    public function getExpireTimer()
    {
        return $this->queryRedis("callGetExpireTimer");
    }

    public function lockCronList($lockClient)
    {
        return $this->updateRedis("callLockCronList",array($lockClient));
    }

    public function callGetExpireTimer($db,$params=array())
    {
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
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."] timer ${timerId} not exist");
            return;
        }

        $arrs = json_decode($result,true);
        return new Timer($timerId,$arrs);
    }

    public function callAdd($db,$params=array())
    {
        $timer = $params[0];
        $appName = $timer->app;
        $timerId = $timer->timerId;
        $value = json_encode($timer->getPropArray());

        // 检查应用是否已经注册
        if (!$db->hexists(CronConstants::APP_DB_NAME,$appName)) {
            $this->logger->warn("[".__CLASS__."::".__FUNCTION__."]:app {$appName} not exist");
            throw new Exception("app '{$appName}' have not registry");
        }

        try {
            // 增加定时任务
            $timerKey = $this->getTimerKey($timerId);
            $db->set($timerKey,$value);
        } catch (Exception $e) {
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."]:add timer fail, app {$appName} timer[{$timerId}] {$value} errmsg=" . $e->getMessage());
            throw $e;
        }

        try {
            // 增加定时任务id到应用的定时任务列表
            $appTimersKey = $this->getAppTimersKey($appName);
            $db->zadd($appTimersKey,TimeUtil::mktime($timer->createTime),$timerId);
        } catch (Exception $e) {
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."]:add timer to app timer list fail, app {$appName} timer[{$timerId}] {$value} errmsg=" . $e->getMessage());
            throw $e;
        }

        try {
            // 增加定时任务到调度列表
            $db->zadd($this->getCronListKey(),TimeUtil::mktime($timer->expireTime),$timerId);
        } catch (Exception $e) {
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."]:add timer to cron list fail, app {$appName} timer[{$timerId}] {$value} errmsg=" . $e->getMessage());
            // TODO clear dirty data
            throw $e;
        }

        return $timer;
    }

    public function callDel($db,$params=array())
    {
        $timerId = $params[0];
        $appName = TimerDao::getAppName($timerId);

        try {
            $db->zrem($this->getCronListKey(),$timerId);
        } catch (Exception $e) {
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."]:del timer from cron list fail, app {$appName} timer[{$timerId}] errmsg=" . $e->getMessage());
            throw $e;
        }

        try {
            $appTimersKey = $this->getAppTimersKey($appName);
            $db->zrem($appTimersKey,$timerId);
        } catch (Exception $e) {
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."]:del timer from app timer list fail, app {$appName} timer[{$timerId}] errmsg=" . $e->getMessage());
            throw $e;
        }

        try {
            $timerKey = $this->getTimerKey($timerId);
            $db->del($timerKey);
        } catch (Exception $e) {
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."]:del timer fail, app {$appName} timer[{$timerId}] errmsg=" . $e->getMessage());
            throw $e;
        }

        return true;
    }

    public function callDelFromCronList($db,$params=array())
    {
        try {
            $timerId = $params[0];
            $db->zrem($this->getCronListKey(),$timerId);
        } catch (Exception $e) {
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."]:del timer from cron list fail, app {$appName} timer[{$timerId}] errmsg=" . $e->getMessage());
            throw $e;
        }

        return true;
    }

    public function callUpdate($db,$params=array())
    {
        $timer = $params[0];
        $appName = $timer->app;
        $timerId = $timer->timerId;
        $value = json_encode($timer->getPropArray());

        try {
            // update timer
            $timerKey = $this->getTimerKey($timerId);
            $db->set($timerKey,$value);
        } catch (Exception $e) {
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."]:update timer fail, app {$appName} timer[{$timerId}] {$value} errmsg=" . $e->getMessage());
            throw $e;
        }

        try {
            // update expiretime
            $db->zadd($this->getCronListKey(),TimeUtil::mktime($timer->expireTime),$timerId);
        } catch (Exception $e) {
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."]:update timer to cron list fail, app {$appName} timer[{$timerId}] {$value} errmsg=" . $e->getMessage());
            throw $e;
        }

        return true;
    }

    public function callGet($db,$params=array())
    {
        $timerId = $params[0];
        $timerKey = $this->getTimerKey($timerId);
        $result = $db->get($timerKey);
        if (empty($result)) {
            return;
        }

        $arrs = json_decode($result,true);
        return new Timer($timerId,$arrs);
    }

    public function callLockCronList($db,$params=array())
    {
        // 进行加锁，如果已经有其他client加锁，那么返回其他client的标示，不然返回这个client的标示
        // 获取cron_lock是否已经有人加锁
        //      如果有加锁:
        //          并且加锁对象是自己，那么重新设置过期时间，并返回自己的标示
        //          如果加锁对象是其他client，那么不做什么事情，返回加锁的那个client的标示o
        //      如果没有加锁：
        //          进行加锁set，保存自己client的标示，并设置过期时间
        $lua_str = 'local lock_key = "cron_lock"; local old_value = redis.call("get", lock_key); if (old_value) then if (old_value == ARGV[1]) then redis.call("expire", lock_key, ARGV[2]); return ARGV[1]; else return old_value; end; else redis.call("set",lock_key,ARGV[1]); redis.call("expire",lock_key,ARGV[2]); return ARGV[1]; end';

        $keys_values = Array($params[0],CronConstants::LOCK_EXPIRE);
        $result = $db->eval($lua_str,$keys_values,0);

        $this->logger->debug("[".__CLASS__."::".__FUNCTION__."] callLockCronList $params[0] $result");
        return $result;
    }

    public function getCronListKey()
    {
        return CronConstants::CRON_LIST . $this->env;
    }

    public function getAppTimersKey($appName)
    {
        return $appName . CronConstants::APP_TIMERS_SUFFIX . $this->env;
    }

    public function getTimerKey($timerId)
    {
        return $timerId . CronConstants::TIMER_SUFFIX . $this->env;
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
