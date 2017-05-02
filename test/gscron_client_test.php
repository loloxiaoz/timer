<?php
require_once dirname(__FILE__) . '/../sdk/mara_cron_client.php';

class CronClientTest extends PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        $host = CronClient::getHost();
        $this->client = new CronClient($host);
    }

    public function test_add()
    {
        $callback = CronClientUtil::buildHttpGetCallback(CronClient::getHost(),"10.16.73.13",8360,"/cron/timer/del?id=123456789&app={$this->appName}",null,null);
        $result = $this->client->add($this->appName,$callback,$this->id,time() + 10);
        // var_dump($result);
        $this->assertNotNull($result);
/*        $result = $this->client->get($this->appName,$this->id);
        $this->assertNotNull($result);
        $result = $this->client->del($this->appName,$this->id);
        $this->assertTrue($result);
        $result = $this->client->get($this->appName,$this->id);
        $this->assertNull($result);
 */
    }

    public function test_logmonitor()
    {
        // var_dump(CronClient::getHost());
        $callback = CronClientUtil::buildHttpGetCallback(CronClient::getHost(),"10.16.73.13",18360,"/cron/timer/del?id=987654321&app={$this->appName}",null,null,20);
         // var_dump($callback);
        $result = $this->client->add($this->appName,$callback,"987654321",time() + 20);
         // var_dump($result);
        $this->assertNotNull($result);
    }

    private $id = "123456789";
    private $appName = "cron";
    private $client;

}
