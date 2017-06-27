<?php

/*
 * @brief 统计信息
 */
class StatDao extends RedisBaseDao
{
    static public $ins;
    public static function ins()
    {
        if(self::$ins == null) {
            self::$ins = new StatDao();
        }
        return self::$ins;
    }

    public function incrError($app,$type)
    {
        $errorStatKey = $this->getErrorStatKey($app,$type);
        // 全局统计
        $redis  = KVStore::getInstance(KVStore::TIMER);
        $redis->hincrby(CronConstants::CRON_STAT_ERROR,"total",1);
        $redis->hincrby(CronConstants::CRON_STAT_ERROR,$errorStatKey,1);
        return true;
    }

    public function getAllStat()
    {
        $redis  = KVStore::getInstance(KVStore::TIMER);
        $result = $redis->hgetall(CronConstants::CRON_STAT_ERROR);
        return array(CronConstants::CRON_STAT_ERROR=>$result);
    }

    public function clearError()
    {
        $redis  = KVStore::getInstance(KVStore::TIMER);
        $redis->del(CronConstants::CRON_STAT_ERROR);
        return true;
    }

    private function getErrorStatKey($app,$type)
    {
        return $type . "." . $app;
    }

}
