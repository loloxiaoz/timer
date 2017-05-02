<?php
/*
 * @brief 应用信息存储(redis)
 */
class BaseDao
{
    static public $ins;
    public function ins()
    {
        if(self::$ins == null) {
            self::$ins = new AppDao();
        }
        return self::$ins;
    }

    public function add($app)
    {
        $name   = $app->name;
        $arrs   = $app->getPropArray();
        $value  = json_encode($arrs);
        $redis  = KVStore::getInstance(KVStore::PLATOV2);
        if ($redis->hexists(CronConstants::APP_DB_NAME,$name)) {
            $this->logger->warn("app {$name} already exist");
            throw new Exception("app {$name} already exist");
        }
        return $redis->hset(CronConstants::APP_DB_NAME,$name,$value);
    }

    public function del($name)
    {
        $redis  = KVStore::getInstance(KVStore::PLATOV2);
        return  $redis->hdel(CronConstants::APP_DB_NAME,$name);
    }

    public function get($name)
    {
        $redis  = KVStore::getInstance(KVStore::PLATOV2);
        $result = $redis->hget(CronConstants::APP_DB_NAME,$name);
        if (empty($result)) {
            return ;
        }
        $arrs = json_decode($result,true);
        return new App($name,$arrs);
    }

    public function isExist($name)
    {
        $redis  = KVStore::getInstance(KVStore::PLATOV2);
        return $redis->hexists(CronConstants::APP_DB_NAME,$name);
    }
}