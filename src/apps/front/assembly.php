<?php
class front_assembly
{
    static public function setup($cacheSvc=null)
    {
        CommonAssembly::setup($cacheSvc);
        XAop::pos(XAop::LOGIC)->append_by_match_uri(".*", new RebuildRequestParams);
    }
}
