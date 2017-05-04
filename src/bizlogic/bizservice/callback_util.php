<?php

/*
 * @brief 回调操作的相关辅助类
 */
class CallbackUtil
{
    /*
     * @brief 验证callback的格式是否正确
     */
    static public function isValid($callback)
    {
        $type = $callback["type"];
        if (!$type) {
            throw new Exception("callback type is null");
        }

        // 暂时只支持http
        if ($type == "http") {
            return self::isValidHttpCallback($callback);
        } else {
            throw new Exception("callback type $type is error");
        }
    }

     static private function isValidHttpCallback($callback)
    {
        $params = $callback["params"];
        if (!$params) {
            throw new Exception("callback params is null");
        }

        $method = $params["method"];
        if (!$method || ($method != self::HTTP_GET && $method != self::HTTP_POST)) {
            throw new Exception("callback params: method {$method} error, must be get or post");
        }

        $url = $params["url"];
        if (!$url) {
            throw new Exception("callback params: url is null");
        }

        $domain = $params["domain"];
        if (!$domain) {
            throw new Exception("callback params: domain is null");
        }

        $port = $params["port"];
        if (!$port || !is_numeric($port)) {
            throw new Exception("callback params: port is null or is not numeric");
        }

        if (($method == self::HTTP_POST) && $params["data"] && !is_array($params["data"])) {
            throw new Exception("callback params: data format error");
        }

        $timeout = $callback["timeout"];
        if ($timeout && (!is_numeric($timeout) || $timeout > self::MAX_TIMEOUT)) {
            throw new Exception("callback timeout must be numeric, and then < " . self::MAX_TIMEOUT);
        }

        $retry = $callback["retry"];
        if ($retry && (!is_numeric($retry) || $retry > self::MAX_RETRY)) {
            throw new Exception("callback retry must be numeric, and then < " . self::MAX_RETRY);
        }

        return true;
    }

    const MAX_TIMEOUT = 10;
    const MAX_RETRY = 60;
    const HTTP_GET = "get";
    const HTTP_POST = "post";
}
