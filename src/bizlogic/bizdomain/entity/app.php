<?php

/*
 * @brief 应用信息
 *
 */
class App extends XProperty
{
    public function __construct($name,$arrs=array())
    {
        parent::__construct();
        $this->name = $name;
        if(!empty($arrs) && is_array($arrs))
        {
            $prop = new XProperty($arrs);
            $this->merge($prop);
        }
    }

    static public function create($name,$comment=null)
    {
        $app = new App($name);
        $app->name    = $name;
        $app->comment = $comment;
        $app->createtime = date("Y-m-d H:i:s");
        $app->updatetime = date("Y-m-d H:i:s");

        return $app;
    }
}
