<?php
class AutoCommit  extends XInterceptor
{
    static $aps=null;
    private $needCommit=true;

    static public function begin()
    {
        static::$aps = XAppSession::begin();
    }

    static public function end()
    {
        static::$aps->commit();
        static::$aps=null;
    }

    static public function commitAndBegin()
    {
        static::$aps->commit();
        static::$aps = null;
        static::$aps = XAppSession::begin();
    }

    static public function cancelCommit()
    {
        static::$aps=null;
    }

    public function _before($xcontext,$request,$response)
    {
        static::$aps = XAppSession::begin();
    }
    public function _after($xcontext,$request,$response)
    {
        if($this->needCommit)
        {
            static::$aps->commit();
        }
        static::$aps=null;
        $xcontext->autocommit=null;
    }
    public function _exception($e, $xcontext, $request, $response)
    {
        $this->needCommit = false;
    }

    static function log($action)
    {
        LogKit::main()->info("app session {$action}".getmypid());
    }
}
