# 定时任务系统

## 创建回调对象
`````
$callbackDTO            = new CallbackDTO;
$callbackDTO->method    = "get";                    //http方法
$callbackDTO->domain    = "api.match.loloxiaoz.com";
$callbackDTO->port      = 8086;
$callbackDTO->url       = "/monitor";
$callbackDTO->retry     = 5;                        //重试次数
$callbackDTO->result    = array("status"=>"OK");    //期望结果
`````

## 创建应用
`````
$appName    = "match";
$sdkSvc     = CrontabClient::stdSvc();
$sdkSvc->delApp($appName);
$sdkSvc->addApp($appName);
`````

## 创建定时器
`````
$expireTime = time()+20; //20s后生效
$timer = $sdkSvc->addTimer($appName,$expireTime,$callbackDTO);
`````
