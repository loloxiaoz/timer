<?php

/*
 * @brief 应用信息存储(redis)
 */
class AppDao extends RedisBaseDao
{
    static public $ins;
    public static function ins()
    {
        if(self::$ins == null) {
            self::$ins = new AppDao();
        }
        return self::$ins;
    }
}
