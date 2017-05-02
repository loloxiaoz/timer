<?php

/*
 * @brief crontab 规则解析
 */
class Crontab {
    /**
     *  Finds next execution time(stamp) parsin crontab syntax, 
     *  after given starting timestamp (or current time if ommited)
     * 
     *  @param string $_cron_string:
     *
     *      0     1    2    3    4
     *      *     *    *    *    *  
     *      -     -    -    -    -
     *      |     |    |    |    |
     *      |     |    |    |    +----- day of week (0 - 6) (Sunday=0)
     *      |     |    |    +------- month (1 - 12)
     *      |     |    +--------- day of month (1 - 31)
     *      |     +----------- hour (0 - 23)
     *      +------------- min (0 - 59)
     *  @param int $_after_timestamp timestamp [default=current timestamp]
     *  @return int unix timestamp - next execution time will be greater 
     *              than given timestamp (defaults to the current timestamp)
     *  @throws InvalidArgumentException 
     */
    public static function parse($_cron_string,$_after_timestamp=null)
    {
        if(!preg_match('/^((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)$/i',trim($_cron_string))){
            throw new InvalidArgumentException("Invalid cron string: ".$_cron_string);
        }
        if($_after_timestamp && !is_numeric($_after_timestamp)){
            throw new InvalidArgumentException("\$_after_timestamp must be a valid unix timestamp ($_after_timestamp given)");
        }
        $cron   = preg_split("/[\s]+/i",trim($_cron_string));

        // 解析规则，转成对应的计划时间点
        $cron_minutes = self::_parseCronNumbers($cron[0],0,59);
        $cron_hours = self::_parseCronNumbers($cron[1],0,23);
        $cron_days = self::_parseCronNumbers($cron[2],1,31);
        $cron_months = self::_parseCronNumbers($cron[3],1,12);
        $cron_weeks = self::_parseCronNumbers($cron[4],0,6);

        // 如果没有指定了开始的执行时间，那么从现在当前时间开始
        $time  = empty($_after_timestamp)?time():$_after_timestamp;
        $year = intval(date('Y',$time));
        $month = intval(date('n',$time));
        $day = intval(date('j',$time));
        $hour = intval(date('G',$time));
        $minute = intval(date('i',$time));
        //$second = intval(date('s',$time));
        $second = 0;

        // 假如定时计划是 [10,50] 分钟执行，如果:
        //  当前是10分钟，那么minute_index = 0, is_current_minute = true; 
        //  当前是36分钟，那么minute_index = 1, is_current_minute = false;
        //  当前是56分钟，那么minute_index = -1, is_current_minute = false;
        list($minute_index,$is_current_minute) = self::_search($cron_minutes,$minute,true);
        list($hour_index,$is_current_hour) = self::_search($cron_hours,$hour,($minute_index != -1));
        list($day_index,$is_current_day) = self::_search($cron_days,$day,($hour_index != -1));
        list($month_index,$is_current_month) = self::_search($cron_months,$month,($day_index != -1));

        // 当前月
        if ($is_current_month) {
            // 当前天
            if ($is_current_day) {
                // 当前小时
                if ($is_current_hour) {
                    $time = mktime($hour,$cron_minutes[$minute_index],$second,$month,$day,$year);
                } else {
                    // 不同小时，因为是不同小时，所以从最早的那分钟开始，比如分钟是10,20,30，那么10分钟开始
                    $time = mktime($cron_hours[$hour_index],$cron_minutes[0],$second,$month,$day,$year);
                }

                if (in_array(date('w',$time),$cron_weeks)) { // 是否星期（一到六）正确
                    return $time;
                }
                // 执行计划时间点不是今天，需要继续查找最近的执行时间点
                $day_index ++;
            }

            $is_current_day = false;
            for ($i = $day_index; $i < count($cron_days); $i ++) {
                // 查找执行计划的指定的下一天
                $time = mktime($cron_hours[0],$cron_minutes[0],$second,$month,$cron_days[$i],$year);
                if (in_array(date('w',$time),$cron_weeks)) { // 是否星期（一到六）正确
                    return $time;
                }
            }

            // 当前月不需要执行计划，所以继续查找设定的其他月
            if ($month_index == count($cron_months) - 1) {
                $month_index = -1;
            } else {
                $month_index ++;
            }
            $is_current_month = false;
        }

        $currentYear = $year;
        // 极端情况只能超前一年的时间(应该没有人会使用这些系统去实现类似于20年后的我这些坑爹的功能吧)，怕别人的规则设置有问题，比如指定了day和week，然后day和week一直对应不上
        while ($year == $currentYear) {
            if ($month_index == -1) {
                // 假如设定执行是3,5月份，现在是6月份，那么下一次计划的执行至少是明年
                $month_index = 0;
                $year ++;
            }

            for ($i = $month_index; $i < count($cron_months); $i ++) {
                $m = $cron_months[$i];

                for ($j = 0; $j < count($cron_days); $j ++) {
                    $d = $cron_days[$j];
                    $time = mktime($cron_hours[0],$cron_minutes[0],$second,$m,$d,$year);
                    if (in_array(date('w',$time),$cron_weeks)) { // 是否星期（一到六）正确
                        return $time;
                    }
                }
            }
            $month_index = -1;
        }

        return null;
    }

    /*
     * @brief 查找当前值在数组的位置
     *  
     *    假如 $cron_rules = {10,20,30,40,50}. 
     *    那么:
     *      当 $current = 10, $contain_current = false，那么返回 {0,true)
     *      当 $current = 10, $contain_current = true, 那么返回 {1,false}
     *      当 $current = 45, 那么返回 {4,false}
     *      当 $current = 50, 那么返回 {4,false}
     *      当 $current = 55, 那么返回 {-1,false}
     */
    static private function _search($cron_rules,$current,$contain_current) 
    {
        $index = -1;
        $is_current = false;
        for ($i = 0; $i < count($cron_rules); $i ++) {
            $v = $cron_rules[$i];
            if ($v < $current) {
                continue;
            }
            if ($v == $current) {
                if ($contain_current) {
                    $is_current = true;
                } else {
                    continue;
                }
            }
            $index = $i;
            break;
        }

        return array($index,$is_current);
    }

    /**
     * @brief 根据规则，转换成具体执行的时间点
     *
     * @param string $s cron string element
     * @param int $min minimum possible value
     * @param int $max maximum possible value
     * @return int parsed number 
     */
    private static function _parseCronNumbers($s,$min,$max)
    {
        $result = array();

        $v = explode(',',$s);
        foreach($v as $vv){
            $vvv  = explode('/',$vv);
            $step = empty($vvv[1])?1:$vvv[1];
            $vvvv = explode('-',$vvv[0]);
            $_min = count($vvvv)==2?$vvvv[0]:($vvv[0]=='*'?$min:$vvv[0]);
            $_max = count($vvvv)==2?$vvvv[1]:($vvv[0]=='*'?$max:$vvv[0]);

            if ($_min < $min || $_min > $max || $_max < $min || $_max  > $max) {
                throw new InvalidArgumentException("Invalid cron string: ".$s);
            }

            if ($_min > $_max) {
                for($i=$_min;$i<=$max;$i+=$step){
                    $result[$i]=intval($i);
                }
                for($i=$min;$i<=$_max;$i+=$step){
                    $result[$i]=intval($i);
                }
            } else {
                for($i=$_min;$i<=$_max;$i+=$step){
                    $result[$i]=intval($i);
                }
            }
        }
        ksort($result);
        return array_values($result);
    }    
}

