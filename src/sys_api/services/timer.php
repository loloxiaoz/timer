<?php

//@REST_RULE: /timer/$method
class CronTimerRest extends XRuleService implements XService
{
    public function __construct()
    {
        $this->timerSvc = new TimerSvc();
    }

    public function add($xcontext, $request, $response)
    {
        $app = $request->app;
        Contract::notNull($app,CronErrCode::PARAM_ERROR);

        $time = $request->time;
        $rule = $request->rule;
        Contract::isTrue(!empty($time),"must has time param");
        Contract::isTrue($time<=cron_timer::MAX_TIMESTAMP,"time must < 2147454847 (2038-01-19 03:14:07), but was {$time}");
        $callback = $request->callback;
        Contract::notNull($callback,"callback is null");
        $callback = json_decode($callback,true);
        Contract::isTrue(CallbackUtil::isValid($callback),"callback format error");

        $id     = $request->id; // 如果应用方没有提供id，那么系统将自动生成
        $timer  = $this->timerSvc->add($app,$id,$time,$rule,$callback);
        $msg    = json_encode($timer->getPropArray());
        $response->success($msg);
    }

    public function get($xcontext, $request, $response)
    {
        $timerId = $request->timerid;
        Contract::notNull($timeId,"timeId 不能为空");
        $timer = $this->timerSvc->getByTimerId($timerId);
        $response->success(json_encode($timer->getPropArray()));
    }

    public function getByApp($xcontext, $request, $response)
    {
        $id     = $request->id;
        $app    = $request->app;
        Contract::notNull($id,"id不能为空");
        Contract::notNull($app,"app不能为空");
        $timer  = $this->timerSvc->getById($app,$id);
        $response->success(json_encode($timer->getPropArray()));
    }

    public function del($xcontext, $request, $response)
    {
        $timeId = $request->timeid;
        Contract::notNull($timeId,"timeId不能为空");
        $result = $this->timerSvc->delByTimerId($timerId);
        if($result){
            $response->success("success");
        } else {
            $response->success("fail");
        }
    }

    public function delByApp($xcontext, $request, $response)
    {
        $id     = $request->id;
        $app    = $request->app;
        Contract::notNull($id,"id不能为空");
        Contract::notNull($app,"app不能为空");
        $result = $this->timerSvc->delById($app,$id);
        if($result){
            $response->success("success");
        } else {
            $response->success("fail");
        }
    }

    const MAX_TIMESTAMP = 2147454847; // seconds, 2038-01-19 03:14:07
    private $timerSvc;
    private $logger;
}
