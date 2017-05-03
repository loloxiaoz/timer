<?php
class TimeUtil
{
    static public function mktime($time)
    {
        list($year,$month,$day,$hour,$minute,$second) = preg_split("/[-: ]/",$time);
        return mktime($hour,$minute,$second,$month,$day,$year);
    }
}

class NetUtil
{
    /*
     * @brief 获取网卡的ip地址，如果有多个网卡，那么就获取第一个
     *
     * TODO gethostname() need php > 5.3
     */
    static public function getLocalIp()
    {
        $preg="/\A((([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\.){3}(([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\Z/";
        exec("ifconfig",$out,$stats);

        if (empty($out)) {
            return null;
        }

        for ($i = 0; $i < count($out); $i ++) {
            if(strstr($out[$i],'addr:')){
                $tmpArray = explode(":", $out[$i]);
                $tmpIp = explode(" ", $tmpArray[1]);
                if(preg_match($preg,trim($tmpIp[0])) && $tmpIp[0] != "127.0.0.1") {
                    return trim($tmpIp[0]);
                }
            }
        }

        return null;
    }

}
