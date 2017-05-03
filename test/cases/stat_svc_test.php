<?php

class StatSvcTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->statSvc = new StatSvc();
        $this->statSvc->clearError();
    }

    public function test_all()
    {
        $this->assertTrue($this->statSvc->incrError($this->app, CronConstants::CALLBACK_ERROR));
        $result = $this->statSvc->getAll();
        $this->assertTrue($result[CronConstants::CRON_STAT_ERROR]["total"] == 1);
        $this->statSvc->clearError();
        $result = $this->statSvc->getAll();
        $this->assertTrue(empty($result[CronConstants::CRON_STAT_ERROR]));
    }

    private $app = "cron";
    private $statSvc;
}
