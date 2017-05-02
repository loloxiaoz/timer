<?php

/*
 * @brief 统计信息
 */
class StatDao extends RedisBaseDao
{
    static public $ins;
    public function ins()
    {
        if(self::$ins == null) {
            self::$ins = new StatDao();
        }
        return self::$ins;
    }

    public function incrError($app,$type)
    {
        $errorStatKey = $this->getErrorStatKey($app,$type);
        return $this->updateRedis("callIncrError",array($errorStatKey));
    }

    public function getAllStat()
    {
        return $this->queryRedis("callGetAllStat",array());
    }

    public function clearError()
    {
        return $this->updateRedis("callClearError",array());
    }

    public function callIncrError($db,$params=array())
    {
        // 全局统计
        $db->hincrby(CronConstants::CRON_STAT_ERROR,"total",1);
        $db->hincrby(CronConstants::CRON_STAT_ERROR,$params[0],1);
        return true;
    }

    public function callGetAllStat($db,$params=array())
    {
        $result = $db->hgetall(CronConstants::CRON_STAT_ERROR);
        return array(CronConstants::CRON_STAT_ERROR=>$result);
    }

    public function callClearError($db,$params=array())
    {
        $db->del(CronConstants::CRON_STAT_ERROR);
        return true;
    }

    private function getErrorStatKey($app,$type)
    {
        return $type . "." . $app;
    }

}
