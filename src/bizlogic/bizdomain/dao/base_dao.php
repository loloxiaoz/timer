<?php
/*
 * @brief 应用信息存储(redis)
 */
class RedisBaseDao
{
    static public $ins;
    public static function ins()
    {
        if(self::$ins == null) {
            self::$ins = new RedisBaseDao();
        }
        return self::$ins;
    }

    protected function __construct()
    {
        $this->logger = XLogKit::logger("scope");
    }

    public function add($app)
    {
        $name   = $app->name;
        $arrs   = $app->getPropArray();
        $value  = json_encode($arrs);
        $redis  = KVStore::getInstance(KVStore::TIMER);
        if ($redis->hexists(CronConstants::APP_DB_NAME,$name)) {
            $this->logger->warn("app {$name} already exist");
            throw new Exception("app {$name} already exist");
        }
        return $redis->hset(CronConstants::APP_DB_NAME,$name,$value);
    }

    public function del($name)
    {
        $redis  = KVStore::getInstance(KVStore::TIMER);
        return  $redis->hdel(CronConstants::APP_DB_NAME,$name);
    }

    public function get($name)
    {
        $redis  = KVStore::getInstance(KVStore::TIMER);
        $result = $redis->hget(CronConstants::APP_DB_NAME,$name);
        if (empty($result)) {
            return ;
        }
        $arrs = json_decode($result,true);
        return new App($name,$arrs);
    }

    public function isExist($name)
    {
        $redis  = KVStore::getInstance(KVStore::TIMER);
        return $redis->hexists(CronConstants::APP_DB_NAME,$name);
    }
}
