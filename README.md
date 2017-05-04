# 定时任务系统

`````
## 创建回调对象
$callbackDTO            = new CallbackDTO;
$callbackDTO->method    = "get";
$callbackDTO->domain    = "api.match.mararun.cn";
$callbackDTO->port      = 8086;
$callbackDTO->url       = "/monitor";
$callbackDTO->retry     = 5;
$callbackDTO->result    = array("status"=>"OK");

## 创建应用
$appName    = "mara_match";
$sdkSvc     = CrontabClient::stdSvc();
$sdkSvc->delApp($appName);
$sdkSvc->addApp($appName);

## 创建定时器
$timer = $sdkSvc->addTimer($appName,time()+20,$callbackDTO);
`````

