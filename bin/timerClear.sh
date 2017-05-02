#/bin/sh
#只接受一个timerId作为参数

timerId=$1;

if [ $# -ne 1 ];
then
    echo "wrong argument amount!";
    exit;
fi
`curl "http://cron.w-svc.cn/cron/timer/del?timerId=$timerId"`;
