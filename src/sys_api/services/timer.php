<?php

class cron_timer extends XSimpleRest implements XService 
{       
    public function __construct()
    {
        $this->timerSvc = new TimerSvc();
        $this->logger = XLogKit::logger("scope");
    }

    public function add($request,$xcontext) 
    {
        $app = $request->app;
        if (!$app) {
            $xcontext->_result->error("app is null", CronErrCode::PARAM_ERROR,401);
            return;
        }

        $time = $request->time;
        $rule = $request->rule;
        if ((!$rule && !$time) || ($rule && $time)) {
            $xcontext->_result->error("must has [time|rule] param", CronErrCode::PARAM_ERROR,401);
            return;
        }

        if ($time && (!is_numeric($time) || $time > cron_timer::MAX_TIMESTAMP)){
            $xcontext->_result->error("time must be numeric, and then 0 < time < 2147454847 (2038-01-19 03:14:07), but was {$time}", CronErrCode::PARAM_ERROR,401);
            return;
        }

        /*
        $callback = array(
            'type' => 'http',
            'params' => array(
                'method" => "get",
                'url' => '/cb.php',
                'host' => '127.0.0.1',
                'port' => '80',
                'domain' => 'abc.com',
            )
            'timeout' => '',
            'retry' => ''
         )
        $callback = array(
            'type' => 'http',
            'params' => array(
                'method" => "post",
                'url' => '/cb.php',
                'host' => '127.0.0.1',
                'port' => '80',
                'domain' => 'abc.com',
                'data' => array (
                    "key" => "value"
                )
            )
            'timeout' => '',
            'retry' => ''
        )
         */
        $callback = $request->callback;
        if (!$callback) {
            $xcontext->_result->error("callback is null", CronErrCode::PARAM_ERROR,401);
            return;
        }
        $callback = json_decode($callback,true);

        try {
            if (!CallbackUtil::isValid($callback)) {
                $xcontext->_result->error("callback format error", CronErrCode::PARAM_ERROR,401);
                return;
            }
        } catch (Exception $e){
            $xcontext->_result->error("callback format error, errmsg=" . $e->getMessage(), CronErrCode::PARAM_ERROR,401);
            return;
        }

        $id = $request->id; // 如果应用方没有提供id，那么系统将自动生成

        try {
            $timer = $this->timerSvc->add($app,$id,$time,$rule,$callback);
            if (!$timer) {
                $xcontext->_result->error("add timer fail", CronErrCode::BIZ_ERROR,401);
                return;
            }
            
            $msg = json_encode($timer->getPropArray());
            $xcontext->_result->success($msg);
            $this->logger->info("[".__CLASS__."::".__FUNCTION__ . "] ${msg}");
        } catch(Exception $e) {
            $this->logger->error("[".__CLASS__."::".__FUNCTION__ . "] ". $e->getMessage() . " app {$app} timer " . $request->callback);
            $xcontext->_result->error($e->getMessage(), CronErrCode::BIZ_ERROR,401);
        }
    }

    public function get($request,$xcontext) 
    {
        $id = $request->id;
        $app = $request->app;
        $timerId = $request->timerid;

        if (empty($timerId) && empty($id)){
            $xcontext->_result->error("must has timerid or id", CronErrCode::PARAM_ERROR,401);
            return;
        }

        if (empty($timerId) && empty($app)) {
            $xcontext->_result->error("app is null", CronErrCode::PARAM_ERROR,401);
            return;
        }

        if ($timerId) {
            $timer = $this->timerSvc->getByTimerId($timerId);
        } else {
            $timer = $this->timerSvc->getById($app,$id);
        }

        if ($timer) {
            $xcontext->_result->success(json_encode($timer->getPropArray()));
        } else {
            $xcontext->_result->success("");
        }
    }

    public function del($request,$xcontext) 
    {
        $id = $request->id;
        $app = $request->app;
        $timerId = $request->timerid;

        if (empty($timerId) && empty($id)){
            $xcontext->_result->error("must has timerid or id", CronErrCode::PARAM_ERROR,401);
            return;
        }

        if (empty($timerId) && empty($app)) {
            $xcontext->_result->error("app is null", CronErrCode::PARAM_ERROR,401);
            return;
        }

        if ($timerId) {
            $result = $this->timerSvc->delByTimerId($timerId);
        } else {
            $result = $this->timerSvc->delById($app,$id);
        }

        if ($result) {
            $xcontext->_result->success("success");
        } else {
            $xcontext->_result->success("fail");
        }
    }

    const MAX_TIMESTAMP = 2147454847; // seconds, 2038-01-19 03:14:07
    private $timerSvc;
    private $logger;
}
