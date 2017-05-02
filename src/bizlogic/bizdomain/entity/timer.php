<?php

/*
 * @brief 定时任务
 */
class Timer extends PropertyObj
{
    public function __construct($timerId,$arrs=array())
    {
        parent::__construct();
        $this->timerId = $timerId;
        if(!empty($arrs) && is_array($arrs))
        {
            $prop = new PropertyObj($arrs);
            $this->merge($prop);
        }
    }

    static public function create($appName,$id,$rule,$expireTime,$callback)
    {
        if (empty($id)) {
            // 如果没有指定id，那么通过uuid发号器进行获取
            $env = $_SERVER['ENV'];
            $idc = $_SERVER['UUID_IDC'];
            if (empty($idc)) {
                throw new Exception("[".__CLASS__."::".__FUNCTION__."] uuid_idc is null");
            }
            $idGenerator = new XUuidGenerator($idc, $env);
            $id = $idGenerator->createID();

            if (empty($id)) {
                throw new Exception("[".__CLASS__."::".__FUNCTION__."] create timer fail, id generator fail");
            }
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
