<?php
require_once('/home/q/php/svc_utls/logger.php');
require_once("/home/q/php/gsdk_base/sdk_base.php");

class CronClient
{
    private $logger;
    private $httpClient;

    public function __construct($host,$logger=null,$proxy=null,$port=8360,$timeout=1,$caller="unknow",$server=null)
    {
        if ($logger) {
            $this->logger = $logger;
        } else {
            $this->logger = XLogKit::logger('mara_cron');
        }

        if ($_SERVER['ENV'] == "online" && $server == null) {
            $hostname = gethostname();
            if (stristr($hostname, "bjdt") != false) {
                // 启用bjdt 的lvs
                $server = "10.138.230.17";
            }
            else {
                // 启用vnet 的lvs
                $server = "10.117.81.253";
            }
        }

        $this->httpClient = new GHttpClient($host,$this->logger,$proxy,$port,$timeout,$caller,$server);
    }

    public function add($appName,$callback,$id="",$time="",$rule="")
    {
        if (empty($appName) || empty($callback)) {
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."] param error: appName=${appName} callback=${callback}");
            return;
        }

        if (empty($time) && empty($rule)) {
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."] param error: must has time or rule");
            return;
        }

        $callback = urlencode($callback);
        $url = "/cron/timer/add?app={$appName}&callback=${callback}&id=${id}&time=${time}&rule=${rule}";
        $data = $this->request($url);
        if ($data) {
            return json_decode($data,true);
        }
    }

    public function get($app="",$id="",$timerId="")
    {
        if (empty($timerId) && empty($id)){
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."] param error: must has timerId or id");
            return;
        }

        if (empty($timerId) && empty($app)) {
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."] param error: app is null");
            return;
        }

        $url = "/cron/timer/get?app={$app}&id=${id}&timerid=${timerId}";
        $data = $this->request($url);
        if ($data) {
            return json_decode($data,true);
        }
    }

    public function del($app="",$id="",$timerId="")
    {
        if (empty($timerId) && empty($id)){
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."] param error: must has timerId or id");
            return;
        }

        if (empty($timerId) && empty($app)) {
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."] param error: app is null");
            return;
        }

        $url = "/cron/timer/del?app={$app}&id=${id}&timerid=${timerId}";
        $data = $this->request($url);
        return $data == "success";
    }

    private function request($url)
    {
        $r =  $this->httpClient->get($url);
        if (!$r) {
            $this->logger->error("[".__CLASS__."::".__FUNCTION__."] request error, url=${url}");
            return;
        }

        $obj = json_decode($r);
        if ($obj->errno) {
            return;
        }
        return $obj->data;
    }

    /*
     * @brief 获取mara_cron服务域名
     *
     * @return host
     */
    public static function getHost(){
        $env = $_SERVER['ENV'];

        if($env === 'online') {
            $user_prefix = '';
        } else if ($env === 'demo') {
            $user_prefix = 'demo.';
        } else {
            $user_prefix = $_SERVER['DOMAIN_PREFIX'];
        }
        return "{$user_prefix}cron.w-svc.cn";
    }
}

class CronClientUtil
{
    /*
     * @brief 生成http回调的结构体
     */
    static public function buildHttpGetCallback($domain,$host,$port,$url,$result=null,$timeout=null,$retry=null)
    {
        return self::buildHttpCallback("get",$domain,$host,$port,$url,null,$result,$timeout,$retry);
    }

    static public function buildHttpPostCallback($domain,$host,$port,$url,$postData,$result=null,$timeout=null,$retry=null)
    {
        return self::buildHttpCallback("post",$domain,$host,$port,$url,$postData,$result,$timeout,$retry);
    }

    static private function buildHttpCallback($method,$domain,$host,$port,$url,$data,$result,$timeout,$retry)
    {
        if (empty($domain) || empty($host) || empty($port) || empty($url)) {
            throw new Exception("[".__CLASS__."::".__FUNCTION__."] param is null, domain=${domain} host=${host} port=${port} url=${url}");
        }

        if (($timeout && !is_numeric($timeout) || ($retry && !is_numeric($retry)))) {
            throw new Exception("[".__CLASS__."::".__FUNCTION__."] param must be null or numeric, timeout=${timout} retry=${retry}");
        }

        if ($data && !is_array($data)) {
            throw new Exception("[".__CLASS__."::".__FUNCTION__."] param must be null or array, timeout=${timout} retry=${retry}");
        }

        $callback = array("type"=>"http","timeout"=>$timeout,"retry"=>$retry,"params"=>array("method"=>$method,"domain"=>$domain,"host"=>$host,"port"=>$port,"url"=>$url,"result"=>$result));
        return json_encode($callback);
    }
}
