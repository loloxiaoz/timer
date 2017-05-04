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
        $callback = array("type"=>"http","retry"=>3,"params"=>array("result"=>array("status"=>"OK"),"domain"=>"api.match.mararun.con","port"=>$_SERVER["PORT"],"url"=>"/monitor","method"=>"get"));
        // 增加当前到期的任务
        $timer = $this->timerSvc->add($this->appName,null,time()+2,null,$callback);
        $this->assertNotNull($timer);
    }

    public function testApi()
    {
        $callbackDTO = new CallbackDTO;
        $callbackDTO->method    = "get";
        $callbackDTO->domain    = "api.match.mararun.con";
        $callbackDTO->port      = 8086;
        $callbackDTO->url       = "/monitor";
        $callbackDTO->retry     = 5;
        $callbackDTO->result    = array("status"=>"OK");

        $sdkSvc = CrontabClient::stdSvc();
        $sdkSvc->delApp($this->appName);
        $sdkSvc->addApp($this->appName);
        $timer = $sdkSvc->addTimer($this->appName,time()+20,$callbackDTO);
        $this->assertNotNull($timer);
    }
}


