<?php

//@REST_RULE: /cron/stat/$method
class CronStatRest extends XRuleService implements XService
{
    public function __construct()
    {
        $this->statSvc = new StatSvc();
        $this->logger = XLogKit::logger("scope");
    }

    public function get_all($xcontext, $request, $response)
    {
        $stats = $this->statSvc->getAll();
        $response->success(json_encode($stats));
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

