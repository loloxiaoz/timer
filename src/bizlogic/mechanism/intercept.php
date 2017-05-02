<?php
class AutoCommit  implements XScopeInterceptor
{
    static $aps=null;
    private $needCommit=true;
    public function  _before($request ,$xcontext)
    {
        self::$aps = AppSession::begin();
        $xcontext->_autocommit=$this;
    }
    public function commitAndBegin()
    {
        self::$aps->commit();
        self::$aps=null;
        self::$aps = AppSession::begin();
    }
    public function cancleCommit()
    {
        $this->needCommit = false;
    }
    public function _after($request,$xcontext)
    {
        if($this->needCommit)
            self::$aps->commit();
        self::$aps=null;
        $xcontext->_autocommit=null;
    }
}
?>
