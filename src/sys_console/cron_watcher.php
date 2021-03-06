<?php
require_once dirname(__FILE__) . '/init.php';

/*
 * @brief 探测到点的定时任务，把任务通过队列分发
 */
class Watcher
{
    private $logger = null;
    private $timerSvc = null;
    private $count = 0;  // 主要用于辅助记录一些log而已，没其他意思
    private $lockTime = 0; // 加锁时间点
    private $clientInfo = null;

    function __construct()
    {
        $this->logger = XLogKit::logger("scope");
        $pid = getmypid();
        $ip = NetUtil::getLocalIp();
        if (empty($ip)) {
            $ip = gethostname();
        }
        if (empty($ip)) {
            $time = time();
            $this->logger->info("Watcher getLocalIp is null, so use time $time instend of ip");
            $this->clientInfo = $time . "_" . $pid;
        } else {
            $this->clientInfo = $ip . "_" . $pid;
        }
        $this->timerSvc = new TimerSvc();
    }

    public function run()
    {
        $this->logger->info("Watcher start {$this->clientInfo}");

        while(1) {
            $this->count = $this->count >= 100000000 ? 1 : $this->count + 1;
            try {
                // 需要保证只有一个watcher在运行。
                if (!$this->lock()) {
                    // 加锁不成功，已经有其他client在运行了，那么可以暂时歇息一下
                    $this->justWaitForAMinute(CronConstants::LOCK_EXPIRE+1);
                    continue;
                }
                $this->processExpireTimer();
            } catch (Exception $e) {
                $this->logger->error("Watcher run error: msg=" . $e->getMessage());
                $this->justWaitForAMinute();
            }
        }
    }

    public function processExpireTimer()
    {
        // 获取到时间点的任务
        $timer = $this->timerSvc->getExpireTimer();
        if(empty($timer)) {
            // 没有到点的任务
            // 为了防止过多的输出log，又要验证一下进程是一直在work状态的，所以每2minute输出一次log
            if ($this->count % 120 == 0) {
                $this->logger->info("no timer expire");
            }
            $this->justWaitForAMinute(); // sleep 1 seconds
            return;
        }

        // 队列的数据格式，其实要把整个消息都塞到queue，或者传递timerId都没有太明显的优缺点
        // 这里暂时先传递timerId，为了后续timer的格式的可调整性
        $msg        = json_encode(array("timerId"=>$timer->timerId,"time"=>date("Y-m-d H:i:s"),"expireTime"=>$timer->expireTime));
        $handleSvc  = new CronEventHandler();
        $handleSvc->handle($msg);
        $this->logger->info("timer {$timer->timerId} expire. data=" . $msg);
    }

    protected function lock()
    {
        // 刚加锁不久，直接返回，如果一直没有加锁上，那么lockTime一直都是初始化0，那么基本上不会进行到这个逻辑
        if (time() - $this->lockTime < CronConstants::LOCK_EXPIRE / 2) {
            return true;
        }
        // 尝试进行加锁
        $result = TimerDao::ins()->lockCronList();
        if ($result) {
            $this->lockTime = time(); // 加锁成功，更新加锁的时间点
            return true;
        }
        return false;
    }

    protected function justWaitForAMinute($count=1)
    {
        usleep (1000000 * $count);
    }

}

$watcher = new Watcher();
$watcher->run();
