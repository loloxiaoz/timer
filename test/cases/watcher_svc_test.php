<?php

class WatcherSvcTest extends PHPUnit_Framework_TestCase
{
    private $appName = "cron_match_test";

    public function setUp()
    {
        $this->timerSvc = new TimerSvc();
        $this->appSvc   = new AppSvc();
        $this->appSvc->del($this->appName);
        $this->appSvc->add($this->appName);
    }

    public function test()
    {
        $callback = array("type"=>"http","params"=>array("result"=>array("status"=>"OK"),"domain"=>"api.match.mararun.cn","port"=>$_SERVER["PORT"],"url"=>"/monitor","method"=>"get"));
        // 增加当前到期的任务
        $timer = $this->timerSvc->add($this->appName,null,time()+2,null,$callback);
        $this->assertNotNull($timer);
    }
}


