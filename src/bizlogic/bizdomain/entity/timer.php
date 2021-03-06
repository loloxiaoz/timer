<?php

/*
 * @brief 定时任务
 */
class Timer extends XProperty
{
    public function __construct($timerId,$arrs=array())
    {
        parent::__construct();
        $this->timerId = $timerId;
        if(!empty($arrs) && is_array($arrs))
        {
            $prop = new XProperty($arrs);
            $this->merge($prop);
        }
    }

    static public function create($appName,$id,$rule,$expireTime,$callback)
    {
        if (empty($id)) {
            $id = date("YmdHis").rand(10000,99999);
        }

        $timerId = TimerDao::getTimerId($appName,$id);
        $timer = new Timer($timerId);
        $timer->app = $appName;
        $timer->rule = $rule;
        $timer->expireTime = $expireTime;
        $timer->createtime = date("Y-m-d H:i:s");
        $timer->updatetime = date("Y-m-d H:i:s");
        $timer->callback = $callback;
        return $timer;
    }
}
