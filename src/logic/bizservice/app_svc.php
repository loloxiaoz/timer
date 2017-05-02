<?php

/*
 * @brief app 的相关逻辑处理
 */
class AppSvc 
{
    private $logger = null;

    function __construct()
    {
        $this->logger = XLogKit::logger("scope");
    }

    /*
     * @brief 增加app 
     */
    public function add($name,$comment=null)
    {
        if(empty($name)) {
            $this->logger->info("[".__CLASS__."::".__FUNCTION__."]: app name is null");
            return;
        }

        // 创建app对象，这里create不进行任何的持久化操作，只是一个很轻量级的create操作而已
        $app = App::create($name,$comment);
        // 写入存储
        $r = AppDao::ins()->add($app);
        if ($r) {
            return $app;
        }

        return;
    }

    /*
     * @brief 根据应用名或者应用信息
     *
     * @param $name 应用名
     *
     * @return app对象
     */
    public function get($name)
    {
        return AppDao::ins()->get($name);
    }

    /*
     * @brief 根据应用名删除应用
     *
     * @param $name 应用名
     *
     * @return true 删除成功, false删除失败
     */
    public function del($name)
    {
        $this->logger->info("[".__CLASS__."::".__FUNCTION__."]:appName ".$name);
        return AppDao::ins()->del($name);
    }

    /*
     * @brief 判断应用是否存在
     *
     * @param $name 应用名
     *
     * @return true 表示存在，false表示不存在
     */
    public function isExist($name)
    {
        return AppDao::ins()->isExist($name);
    }
}

