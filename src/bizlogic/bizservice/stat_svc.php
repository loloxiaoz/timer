<?php

/*
 * @brief cron stat 的相关逻辑处理
 */
class StatSvc 
{
    private $logger = null;

    function __construct()
    {
        $this->logger = XLogKit::logger("scope");
    }

    public function getAll()
    {
        return StatDao::ins()->getAllStat();
    }

    public function clearError()
    {
        return StatDao::ins()->clearError();
    }

    public function incrError($app,$type)
    {
        return StatDao::ins()->incrError($app,$type);
    }
}

