<?php

//@REST_RULE: /monitor
class Monitor extends XSimpleService implements XService
{
    public function _head($xcontext, $request, $response)
    {
        $response->success("");
    }

    public function _get($xcontext, $request, $response)
    {
        $response->success(array('status' => 'OK'));
    }
}
