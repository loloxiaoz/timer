<?php
/*
 * @brief 
 *
 * @author maijunsheng
 */
class cron_stat extends XSimpleRest implements XService 
{       
    public function __construct()
    {
        $this->statSvc = new StatSvc();
        $this->logger = XLogKit::logger("scope");
    }

    public function get_all($request,$xcontext) 
    {
        $stats = $this->statSvc->getAll();

        if ($stats) {
            $xcontext->_result->success(json_encode($stats));
        } else {
            $xcontext->_result->success("");
        }
    }

    public function clear($request,$xcontext) 
    {
        $result = $this->statSvc->clear();
        if ($result) {
            $xcontext->_result->success("success");
        } else {
            $xcontext->_result->success("fail");
        }
    }

    private $statSvc;
    private $logger;
}

