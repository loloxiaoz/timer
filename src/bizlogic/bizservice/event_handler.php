<?php

/*
 * @brief cron queue消息处理
 */
class CronEventHandler
{
    public function __construct()
    {
        $this->timerSvc = new TimerSvc();
        $this->notifyHandler = new NotifyHandler();
        $this->logger = XLogKit::logger("scope");
        $this->errorLogger = XLogKit::logger("error");
    }

    public function logicHandle($msg)
    {
        $timerId = $msg["timerId"];
        $app = TimerDao::getAppName($timerId);

        $timer = $this->timerSvc->getByTimerId($timerId);
        if (!$timer) {
            // 定时任务不存在
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."] timer not exist, msg=" . json_encode($msg));
            StatDao::ins()->incrError($app,CronConstants::TIMER_NOT_EXIST_ERROR);
            return true; // 不能返回false，因为返回false那么队列会继续重试，而重试依然还是这个结果
        }

        if ($msg["expireTime"]) {
            $currentTimestamp = time();
            $expireTimestamp = TimeUtil::mktime($msg["expireTime"]);
            if ($currentTimestamp - $expireTimestamp > 30) {
                // 如果执行时间点和过期时间点相差时间比较大，那么需要进行一些记录
                $this->logger->info("[".__CLASS__."::".__FUNCTION__."] timer process after expire " . ($currentTimestamp - $expireTimestamp) . "s. msg=" . json_encode($msg));
            }
        }

        if (!$this->notifyHandler->isSupport($timer->callback["type"])) {
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."] timer type not support, msg=" . json_encode($msg) . " timer=" . json_encode($timer->getPropArray()));
            StatDao::ins()->incrError($app,CronConstants::OTHER_ERROR);
            return true;
        }

        // 进行回调
        $result = $this->notifyHandler->notify($timer);

        if (!$result) {
            // 回调失败
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."] timer notify fail, msg=" . json_encode($msg) . " timer=" . json_encode($timer->getPropArray()));
            StatDao::ins()->incrError($app,CronConstants::CALLBACK_ERROR);

            return $this->nofityRetry($timer);
        }

        // 计算下一次执行的时间
        if ($timer->rule) {
            $timer->expireTime = Crontab::parse($timer->rule);
            $timer->calltimes = 0;
            // 更新定时任务的下次执行时间
            $result = $this->timerSvc->update($timer);
            if (!$result) {
                $this->logger->error("[".__CLASS__."::".__FUNCTION__."] timer update expireTime fail, msg=" . json_encode($msg) . " timer=" . json_encode($timer->getPropArray()));
                StatDao::ins()->incrError($app,CronConstants::OTHER_ERROR);
            }
        } else {
            // 调用完成，删除定时任务
            $deleteResult = $this->timerSvc->delByTimerId($timer->timerId);
            if (!$deleteResult) {
                // 删除失败，那么redis会有些脏数据，不过为了防止对业务有影响，所以不能retry整个流程，
                // 同时为了避免复杂性，也不单独引入清除数据的机制，脏数据就脏数据吧，淡定
                $this->logger->error("[".__CLASS__."::".__FUNCTION__."] timer del timer fail, msg=" . json_encode($msg) . " timer=" . json_encode($timer->getPropArray()));
                StatDao::ins()->incrError($app,CronConstants::OTHER_ERROR);
            }
        }

        if ($result) {
            $this->logger->info("[".__CLASS__."::".__FUNCTION__."] timer notify success, msg=" . json_encode($msg) . " timer=" . json_encode($timer->getPropArray()));
            return true;
        } else {
            return false;
        }
    }

    /*
     * @brief 如果调用失败，那么进行超时重试
     */
    private function nofityRetry($timer)
    {
        $maxRetry = $timer->callback["retry"] ? $timer->callback["retry"] : 0;
        $props = $timer->getPropArray();
        $currentCalltimes = $props["calltimes"] ? $props["calltimes"] + 1 : 1;
        $params = $timer->callback["params"];

         //每调用10次，记一次Log
        if($currentCalltimes % 10 == 0){
            $this->errorLogger->error("[".__CLASS__."::".__FUNCTION__."] url= 'http://".$params["host"].":".$params["port"].$params["url"]."' -H 'Host:".$params["domain"]."' already_retry_times=$currentCalltimes max_retry_times=$maxRetry timerId=" . $timer->timerId);
        }

        // 如果当前调用次数超过之前的最大设定，就不进行重试了，因为有些业务不支持重试（会有风险）
        if ($maxRetry > 0 && $currentCalltimes >= $maxRetry) {
            $this->errorLogger->error("[".__CLASS__."::".__FUNCTION__."] url= 'http://".$params["host"].":".$params["port"].$params["url"]."' -H 'Host:".$params["domain"]."' already_retry_times=$currentCalltimes max_retry_times=$maxRetry timerId=" . $timer->timerId);
            $result = $this->timerSvc->delByTimerId($timer->timerId);
            if (!$result) {
                $this->logger->error("[".__CLASS__."::".__FUNCTION__."] timer del timer fail, msg=" . json_encode($msg) . " timer=" . json_encode($timer->getPropArray()));
                StatDao::ins()->incrError($app,CronConstants::OTHER_ERROR);
            }

            return true;
        }

        $expireTimestamp = TimeUtil::mktime($timer->expireTime) + CronConstants::RETYR_INTERVAL;  // 下一次重试时间
        $timer->expireTime = date('Y-m-d H:i:s', $expireTimestamp);
        $timer->calltimes = $currentCalltimes;
        $result = $this->timerSvc->update($timer);  // 调用失败，重试
        return $result;
    }

    public function handle($msg)
    {
        $stime     = microtime(true);
        try {
            $msgJson = json_decode($msg,true);
            $r = $this->logicHandle($msgJson);
            $etime     = microtime(true);
            $totalTime = sprintf("%.3f", $etime-$stime);

            if($r) {
                $this->logger->info("[".__CLASS__."::".__FUNCTION__."] succ useTime $totalTime,msg=".$msg);
            } else {
                $this->logger->error("[".__CLASS__."::".__FUNCTION__."] fail useTime $totalTime,msg=".$msg);
            }

            return $r;
        } catch(Exception $e) {
            $etime     = microtime(true);
            $totalTime = sprintf("%.3f", $etime-$stime);
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."] fail useTime $totalTime,errmsg=".$e->getMessage().", msg=".$msg);
            return false; // will be retry handle this msg
        }
    }

    private $timerSvc;
    private $notifyHandler;
    private $logger;
    private $errorLogger;
}

