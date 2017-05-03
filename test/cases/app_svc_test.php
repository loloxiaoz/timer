<?php

class AppSvcTest extends PHPUnit_Framework_TestCase
{
    private $appSvc;

    public function setUp()
    {
        $this->appSvc = new AppSvc();
        $this->appSvc->del($this->name);
    }

    public function test_all()
    {
        $app = $this->appSvc->add($this->name);
        $this->assertNotNull($app);

        $this->assertTrue($this->appSvc->isExist($this->name));

        $result = $this->appSvc->get($this->name);
        $this->assertNotNull($result);
        $this->assertEquals($result->name,$this->name);

        $this->assertEquals(1,$this->appSvc->del($this->name));
    }

    private $name = "cron_unit";
}
