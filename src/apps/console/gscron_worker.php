<?php
require_once dirname(__FILE__) . '/init.php';

/*
 * @brief 主要负责从队列获取已经到执行点的定时任务，然后进行回调
 */
class CronWorker
{
    private $logger = null;

    function __construct() 
    {
        $this->logger = XLogKit::logger("scope");
    }

    public function run() 
    {
        $hydraConf =  QHydraConf::init('mara_cron',$this->logger);
        $hydraWorker     = new QHydraWorker($hydraConf);

        $this->logger->info("Watcher {$this->clientInfo} unable get lock, other process is running");
        $hydraWorker->subscribe(CronConstants::EXPIRE_QUEUE_NAME,array(new CronEventHandler(),'handle'), true);
        $hydraWorker->wait_event();
    }
}

$worker = new CronWorker();
$worker->run();
