<?php

/*
 * @brief 定时计划的相关逻辑处理
 */
class TimerSvc
{
    private $logger = null;

    function __construct()
    {
        $this->logger = XLogKit::logger("scope");
    }

    /*
     * @brief 增加定时计划
     *
     * @param $appName 必选，应用名
     * @param $id      可选，应用指定的id，由自己去控制id的唯一性和维护，如果不指定，那么将自动产生一个id
     * @param $time    可选，如果没有指定rule，那么time是执行的时间，如果指定了rule，那么没有指定time的话表示当前时间开始，指定了表示是以time时间开始的执行时间计划
     * @param rule     可选，crontab的执行计划规则， * * * * * ，为分、小时、天、月、星期
     * @callback       必选，回调的方式
     *
     * @return         timer 的相关数据
     */
    public function add($appName,$id,$time,$rule,$callback)
    {
        if (empty($callback)) {
            throw new Exception("callback is null");
        }

        $expireTimestamp = $time;
        if ($rule) {
            $expireTimestamp = Crontab::convert($rule,$time);
        }

        $expireTime = date('Y-m-d H:i:s', $expireTimestamp);
        $timer = Timer::create($appName,$id,$rule,$expireTime,$callback);
        return TimerDao::ins()->add($timer);
    }

    /*
     * @brief 更新定时任务
     *
     * @param $timer    定时任务的相关信息
     *
     * @return true 表示更新成功，false表示更新失败
     */
    public function update($timer)
    {
        if (!$timer || !is_object($timer)) {
            throw new InvalidArgumentException("param timer is null");
        }

        return TimerDao::ins()->update($timer);
    }

    /*
     * @brief 根据应用名和id获取定时任务
     *
     * @param $appName  必选，应用名
     * @param $id       必选，任务id
     *
     * @return timer
     */
    public function getById($appName,$id)
    {
        $timerId = TimerDao::getTimerId($appName,$id);
        return $this->getByTimerId($timerId);
    }

    /*
     * @brief 根据timerId获取定时任务
     *
     * @param $timerId  必选，定时任务id，由app + $id构成
     *
     * @return timer
     */
    public function getByTimerId($timerId)
    {
        return TimerDao::ins()->get($timerId);
    }

    /*
     * @brief 根据appName和id删除定时任务
     *
     * @param $appName  必选，应用名
     * @param $id       必选，任务id
     *
     * @return true 表示删除成功，false表示删除失败
     */
    public function delById($appName,$id)
    {
        $timerId = TimerDao::getTimerId($appName,$id);
        return $this->delByTimerId($timerId);
    }

    /*
     * @brief 根据timerId删除定时任务
     *
     * @param $timerId  必选，定时任务id，由app + $id构成
     *
     * @return true 表示删除成功，false表示删除失败
     */
    public function delByTimerId($timerId)
    {
        $logger = XLogKit::logger("scope");
        $logger->info("timerId ".$timerId);
        return TimerDao::ins()->del($timerId);
    }

    /*
     * @brief 获取到点的定时任务
     */
    public function getExpireTimer()
    {
        return TimerDao::ins()->getExpireTimer();
    }

    /*
     * @brief 将定时任务从调度队列删除
     */
    public function delFromCronList($timerId)
    {
        return TimerDao::ins()->delFromCronList($timerId);
    }
}
