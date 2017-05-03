<?php

/*
 * @brief 回调
 */
interface Notifier
{
    public function notify($timer);
}

/*
 * @brief http 回调
 *
 */
class HttpNotifier implements Notifier
{
    public function __construct()
    {
        $this->logger = XLogKit::logger("scope");
    }

    public function notify($timer)
    {
        $callback   = $timer->callback;
        $params     = $callback["params"];
        $domain     = $params["domain"];
        $host       = $params["host"];
        $port       = $params["port"];
        $url        = $params["url"];
        $method     = $params["method"];

        $timeout = empty($callback["timeout"]) ? CronConstants::DEFAULT_TIMEOUT : $callback["timeout"];

        // 如果没有指定，那么结果需要是[200,300)范围的状态码，并且返回的数据不能为空.
        // 如果指定了，那么要求接口的规则满足 {"errno":"","msg":"",data:""}的返回格式，会提取data的数据进行匹配
        $expectResult = $params["result"];
        $result = ""; // 返回的结果

        $httpConf = new XHttpConf();
        $httpConf->conf($domain, $this->logger);
        $httpConf->proxy   = null;
        $httpConf->port    = $port;
        $httpConf->timeout = $timeout;
        $httpConf->server  = "mara_cron";
        $httpConf->caller  = $host;
        $httpClient = new XHttpCaller($httpConf);

        if ($method == "get") {
            $result = $httpClient->get($url,$timeout);
        } elseif ($method == "post") {
            $postData = $params["data"];
            $result = $httpClient->post($url,$postData,$timeout);
        }

        // 所有没有返回结果的，都会被当成失败(约定的规则)
        if (empty($result)) {
            $this->logger->warn("http call fail, timer=" . json_encode($timer->getPropArray()));
            return false;
        }

        // 如果没有指定expect的result，那么只要http code是[200,300)，结果不为空，那么表示成功
        if (empty($expectResult)) {
            return true;
        }

        // 希望没有应用方给了错误的url，或者错误的expect result，有的话莫怪多调了。。。

        // 规则是：{"errno":"","msg":"","data":""}
        $obj = json_decode($result);
        if ($obj->errno) {
            // errno 不为0，表示调用有问题
            $this->logger->warn("http call fail, errno is not 0, timer=" . json_encode($timer->getPropArray()) . " result=${result}");
            return false;
        }

        // 提取结果的data数据进行匹配
        if ($expectResult != $obj->data) {
            $this->logger->warn("http call fail, result not match, timer=" . json_encode($timer->getPropArray()) . " result=${result}");
            return false;
        }

        return true;

    }

    private $logger;
}

/*
 * @brief 回调处理
 */
class NotifyHandler
{
    private $notifiers = array();

    public function __construct()
    {
        // 暂时只增加http回调支持，有必要再支持queue的方式，如无必要，莫提前加
        $this->notifiers["http"] = new HttpNotifier();
    }

    public function notify($timer)
    {
        $type = $timer->callback["type"];

        $notifier = $this->notifiers[$type];
        if (!$notifier) {
            throw new Exception("notifier {$type} not exist");
        }

        return $notifier->notify($timer);
    }

    /*
     * @brief 是否支持该类型的回调
     */
    public function isSupport($type)
    {
        $notifier = $this->notifiers[$type];
        if (!$notifier) {
            return false;
        }
        return true;
    }

}
