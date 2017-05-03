<?php

class TimerSvcTest extends PHPUnit_Framework_TestCase
{
    private $timerSvc;

    public function setUp()
    {
        $this->timerSvc = new TimerSvc();
        $this->appSvc = new AppSvc();
        $this->appSvc->del($this->appName);
        $this->appSvc->add($this->appName);
    }

    public function teardown()
    {
        $this->appSvc->del($this->appName);
    }

    public function test_all()
    {
        // 增加当前到期的任务
        $timer = $this->timerSvc->add($this->appName,$this->id,time(),null,json_decode($this->callback,true));
        $this->assertNotNull($timer);
        $result = $this->timerSvc->getByTimerId($timer->timerId);
        $this->assertNotNull($result);
        $result = $this->timerSvc->getById($this->appName,$this->id);
        $this->assertNotNull($result);
        $result = $this->timerSvc->getExpireTimer();
        $this->assertNotNull($result);

        $this->assertTrue($this->timerSvc->delFromCronList($timer->timerId));
        $result = $this->timerSvc->getExpireTimer();
        $this->assertNull($result);
        $result = $this->timerSvc->getByTimerId($timer->timerId);
        $this->assertNotNull($result);

        $this->assertTrue($this->timerSvc->delById($this->appName,$this->id));
        $result = $this->timerSvc->getByTimerId($timer->timerId);
        $this->assertNull($result);

        // 10秒后执行的任务
        $timer = $this->timerSvc->add($this->appName,$this->id,time() + 10,null,json_decode($this->callback,true));
        $this->assertNotNull($timer);
        $result = $this->timerSvc->getByTimerId($timer->timerId);
        $this->assertNotNull($result);
        $result = $this->timerSvc->getExpireTimer();
        $this->assertNull($result);

        // 更新这个10秒后执行的任务
        $timer->expireTime = date('Y-m-d H:i:s', time());
        $this->assertTrue($this->timerSvc->update($timer));
        $result = $this->timerSvc->getExpireTimer();
        $this->assertNotNull($result);

        $this->assertTrue($this->timerSvc->delById($this->appName,$this->id));
    }

    private $callback = "{\"type\":\"http\",\"params\":{\"method\":\"get\",\"url\":\"/cb.php\",\"host\":\"127.0.0.1\",\"port\":\"80\",\"domain\":\"abc.com\"},\"timeout\":\"\",\"retry\":\"\"}";
    private $appName = "cron_unit_test";
    private $id = "123456789";
}
