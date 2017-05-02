<?php

class HttpNotifierTest  extends PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        $this->notifier = new HttpNotifier();
    }

    public function test_notify()
    {
        // 正确的请求
        $callback = array("type"=>"http","params"=>array("domain"=>"maijunsheng.cron.w-svc.cn","host"=>"127.0.0.1","port"=>"8360","url"=>"/cron/timer/del","data"=>array("app"=>"cron","id"=>"123456"),"method"=>"post"));
        $timer = new Timer("cron_unit_test_123456789");
        $timer->callback = $callback;
        $this->assertTrue($this->notifier->notify($timer));

        $notifyHandler = new NotifyHandler();
        $this->assertTrue($notifyHandler->notify($timer));

        // 端口有问题
        $callback = array("type"=>"http","params"=>array("domain"=>"maijunsheng.cron.w-svc.cn","host"=>"127.0.0.1","port"=>"8361","url"=>"/cron/timer/del","data"=>array("app"=>"cron","id"=>"123456"),"method"=>"post"));
        $timer->callback = $callback;
        $this->assertFalse($this->notifier->notify($timer));

        // 没有带入id, 所以会获取到400的http status
        $callback = array("type"=>"http","params"=>array("domain"=>"maijunsheng.cron.w-svc.cn","host"=>"127.0.0.1","port"=>"8360","url"=>"/cron/timer/del","data"=>array("app"=>"cron"),"method"=>"post"));
        $timer->callback = $callback;
        $this->assertFalse($this->notifier->notify($timer));

        // 包含正确的expect result
        $callback = array("type"=>"http","params"=>array("result"=>"success","domain"=>"maijunsheng.cron.w-svc.cn","host"=>"127.0.0.1","port"=>"8360","url"=>"/cron/timer/del","data"=>array("app"=>"cron","id"=>"123456"),"method"=>"post"));
        $timer->callback = $callback;
        $this->assertTrue($this->notifier->notify($timer));

        // 包含错误的expect result
        $callback = array("type"=>"http","params"=>array("result"=>"fail","domain"=>"maijunsheng.cron.w-svc.cn","host"=>"127.0.0.1","port"=>"8360","url"=>"/cron/timer/del","data"=>array("app"=>"cron","id"=>"123456"),"method"=>"post"));
        $timer->callback = $callback;
        $this->assertFalse($this->notifier->notify($timer));
    }

    private $notifier;

}
