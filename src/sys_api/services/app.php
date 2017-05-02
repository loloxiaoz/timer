<?php
/*
 * @brief 
 *
 * @author maijunsheng
 */
class cron_app extends XSimpleRest implements XService 
{       
    public function __construct()
    {
        $this->appSvc = new AppSvc();
        $this->logger = XLogKit::logger("scope");
    }

    public function add($request,$xcontext) 
    {
        $name = $request->name;
        if (!$name) {
            $xcontext->_result->error("name is null", CronErrCode::PARAM_ERROR,401);
            return;
        }

        $app = $this->appSvc->add($name,$request->comment);
        $xcontext->_result->success(json_encode($app->getPropArray()));
    }

    public function get($request,$xcontext) 
    {
        $name = $request->name;
        if (!$name) {
            $xcontext->_result->error("name is null", CronErrCode::PARAM_ERROR,401);
            return;
        }

        $app = $this->appSvc->get($name);

        if ($app) {
            $xcontext->_result->success(json_encode($app->getPropArray()));
        } else {
            $xcontext->_result->success("");
        }
    }

    public function del($request,$xcontext) 
    {
        $name = $request->name;
        if (!$name) {
            $xcontext->_result->error("name is null", CronErrCode::PARAM_ERROR,401);
            return;
        }

        $result = $this->appSvc->del($name);
        if ($result) {
            $xcontext->_result->success("success");
        } else {
            $xcontext->_result->success("fail");
        }
    }
    private $appSvc;
    private $logger;
}

