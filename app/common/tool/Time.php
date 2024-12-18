<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/1
 * Time: 14:48
 */

namespace app\common\tool;


class Time
{
    public static $current = 0;
    public static function format($time){
        return date("Y-m-d H:i:s",$time);
    }

    public static function birthday($time){
        return Time::dayFormat($time);
    }

    public static function dayFormat($time){
        return date("Y-m-d",$time);
    }

    public static function friendly($sTime,$format=0){
        //sTime=源时间，cTime=当前时间，dTime=时间差
        $cTime        =    time();
        $dTime        =    $cTime - $sTime;
        // 计算两个时间之间的日期差
        $date1 = date_create(date("Y-m-d",$cTime));
        $date2 = date_create(date("Y-m-d",$sTime));
        $diff = date_diff($date1,$date2);
        $dDay = $diff->days;

        if($dTime == 0){
            return "1秒前";
        }elseif( $dTime < 60 && $dTime > 0 ){
            return $dTime."秒前";
        }
        elseif( $dTime < 3600 && $dTime > 0 ){
            return intval($dTime/60)."分钟前";
        }
        elseif( $dTime >= 3600 && $dDay == 0 )
        {
            return intval($dTime/3600)."小时前";
        }
        elseif( $dDay == 1 )
        {
            return date("昨天 H:i",$sTime);
        }
        elseif( $dDay == 2 )
        {
            return date("前天 H:i",$sTime);
        }
        elseif($format == 1){
            return date("m-d H:i",$sTime);
        }else{
            if(date('Y',$cTime)!=date('Y',$sTime)) // 不是今年
                return date("y-n-j",$sTime);
            else
                return date("n-j",$sTime);
        }
    }

    /**
     * 截止时间
     * @param $time
     * @return array|bool
     */
    public static function remainTime($time) {
        $timeNow = time();
        if($time < $timeNow) {
            return false;
        }

        $time = $time - $timeNow;
        $days = intval($time / 86400);
        $time -= $days * 86400;
        $hours = intval($time / 3600);
        $time -= $hours * 3600;
        $minutes = intval($time / 60);
        $time -= $minutes * 60;
        $seconds = $time;

        return array((int)$days, (int)$hours, (int)$minutes, (int)$seconds);
    }

    public static function format_micro_time(){
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
}