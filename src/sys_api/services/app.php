<?php

//@REST_RULE: /app/$method
class CronAppRest extends XRuleService implements XService
{
    public function __construct()
    {
        $this->appSvc = new AppSvc();
        $this->logger = XLogKit::logger("scope");
    }

    public function add($xcontext, $request, $response)
    {
        $name = $request->name;
        Contract::notNull($name,CronErrCode::PARAM_ERROR);
        $app = $this->appSvc->add($name,$request->comment);
        $response->success(json_encode($app->getPropArray()));
    }

    public function get($xcontext, $request, $response)
    {
        $name = $request->name;
        Contract::notNull($name,CronErrCode::PARAM_ERROR);
        $app = $this->appSvc->get($name);
        if ($app) {
            $response->success(json_encode($app->getPropArray()));
        } else {
            $response->success("");
        }
    }

    public function del($xcontext, $request, $response)
    {
        $name = $request->name;
        Contract::notNull($name,CronErrCode::PARAM_ERROR);
        $result = $this->appSvc->del($name);
        if ($result) {
            $response->success("success");
        } else {
            $response->success("fail");
        }
    }

    private $appSvc;
    private $logger;
}
