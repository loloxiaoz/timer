<?php

/*
 * @brief 应用信息存储(redis)
 */
class AppDao extends RedisBaseDao
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
        $name = $app->name;
        $arrs = $app->getPropArray();
        $value = json_encode($arrs);

        return $this->updateRedis("callAdd",array($name,$value));
    }

    public function del($name)
    {
        return $this->updateRedis("callDel",array($name));
    }

    public function get($name)
    {
        $result = $this->queryRedis("callGet",array($name));

        if (empty($result)) {
            return ;
        }

        $arrs = json_decode($result,true);
        return new App($name,$arrs);
    }

    public function isExist($name)
    {
        return $this->queryRedis("callIsExist",array($name));
    }

    public function callAdd($db,$params=array())
    {
        $name = $params[0];
        $value = $params[1];

        if ($db->hexists(CronConstants::APP_DB_NAME,$name)) {
            $this->logger->warn("[".__CLASS__."::".__FUNCTION__."]:app {$name} already exist");
            throw new Exception("app {$name} already exist");
        }

        $db->hset(CronConstants::APP_DB_NAME,$name,$value);
        return true;
    }

    public function callDel($db,$params=array())
    {
        $db->hdel(CronConstants::APP_DB_NAME,$params[0]);
        return true;
    }

    public function callGet($db,$params=array())
    {
        return $db->hget(CronConstants::APP_DB_NAME,$params[0]);
    }

    public function callIsExist($db,$params=array())
    {
        return $db->hexists(CronConstants::APP_DB_NAME,$params[0]);
    }
}
