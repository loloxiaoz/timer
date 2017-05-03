<?php

class CrontabTest extends PHPUnit_Framework_TestCase
{
    public function test_parse()
    {
        $after_time = TimeUtil::mktime($this->time);

        $this->assertEquals(Crontab::parse("11 10 20 * *",$after_time), $after_time + 1 * 60);
        $this->assertEquals(Crontab::parse("10 11 20 * *",$after_time), $after_time + 60 * 60);
        $this->assertEquals(Crontab::parse("10 9 20-21 * *",$after_time), $after_time + (24 - 1) * 60 * 60);
        $this->assertEquals(Crontab::parse("10 10 21 * *",$after_time), $after_time + 24 * 60 * 60);
        $this->assertEquals(Crontab::parse("10 10 21 * *",$after_time), $after_time + 24 * 60 * 60);
    }


    private $time = "2014-06-20 10:10:00";

}
