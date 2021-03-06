<?php

//@REST_RULE: /stat/$method
class CronStatRest extends XRuleService implements XService
{
    public function __construct()
    {
        $this->statSvc = new StatSvc();
        $this->logger = XLogKit::logger("scope");
    }

    public function all($xcontext, $request, $response)
    {
        $stats = $this->statSvc->getAll();
        $response->success($stats);
    }

    public function clear($xcontext, $request, $response)
    {
        $result = $this->statSvc->clear();
        if ($result) {
            $response->success("success");
        } else {
            $response->success("fail");
        }
    }
    private $statSvc;
    private $logger;
}

