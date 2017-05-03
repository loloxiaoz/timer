<?php

class CronConstants
{
    // redis 操作的相关常量
    const APP_DB_NAME = "cron.apps";        // 存储注册的应用列表
    const CRON_LIST = "cron.list";          // 调度的定时器列表
    const TIMER_SUFFIX = ".timer";          // 定时任务的后缀
    const APP_TIMERS_SUFFIX = ".timers";    // 应用的定时任务列表

    // 定时任务回调超时控制
    const DEFAULT_TIMEOUT = 1;              // 默认超时
    const DEFAULT_RETRY = 1;                // 默认重试次数

    // 任务队列
    const EXPIRE_QUEUE_NAME = "yx_cron_default";  // 待回调的任务队列

    // 锁相关
    const LOCK_EXPIRE = 1;                       // 锁超时时间

    // 统计相关
    const CRON_STAT_ERROR = "cron.stats.error"; // 统计信息
    const CALLBACK_ERROR = "call";              // 调用错误
    const TIMER_NOT_EXIST_ERROR = "timer_null"; // 定时器为空
    const OTHER_ERROR = "other";                // 调用错误

    // 回调错误重试
    const RETYR_INTERVAL = 60;                  // 重试间隔
}

class CronErrCode
{
    const PARAM_ERROR = 1001;
    const BIZ_ERROR = 1002;
}
