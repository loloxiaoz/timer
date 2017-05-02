<?php
class RebuildRequestParams implements XScopeInterceptor
{
    public function _before($request,$xcontext)
    {
        $drop_key = array(
            'do' => 1,
            '_app' => 1,
            '_method' => 1,
            '_caller' => 1,
            '_params' => 1,
            'client_ver' => 1,
            'api_ver' => 1,
            'uri' => 1,
        );
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        if($method == 'put' || $method == 'delete'){
            parse_str(file_get_contents('php://input'), $req);
            foreach($req as $k => $v){
                if(!$request->$k){
                    $request->$k = $v;
                }
            }
        }
        $sign = $request->sign;
        $data = array();
        foreach($drop_key as $key => $value){
            if($request->have($key)){
                unset($request->attr[$key]);
            }
        }
    }

    public function _after($request,$xcontext)
    {
    }
}
