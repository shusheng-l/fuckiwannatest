<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/30
 * Time: 10:47
 */
namespace app\common\json;

use app\common\tool\Code;

class PublicRe
{
    public static $re = [
        'ret' => Code::SUCCESS,
        'text' => '',
    ];

    //弹窗
    public static $rej = [
        'hasaffair' => 0,
        'affair' => [
            'title' =>'',
            'desc' => '',
            'link' => '',
            'rank' => 99,
            'level' => -1,
            'levelname' => '',
            'exp' => -1,
            'gold' => "-1",
            //2.0新增
            'cash' => "-1",
        ]
    ];

    public static $dayList = [];
    public static $timeArray = [];

    //
    public static function rejPublic($rej){

        $re = self::$rej;
        $re['hasaffair'] = isset($rej['hasaffair'])?$rej['hasaffair']:0;
        $re['affair']['title'] = isset($rej['affair']['title'])?$rej['affair']['title']:'';
        $re['affair']['desc'] = isset($rej['affair']['desc'])?$rej['affair']['desc']:'';
        $re['affair']['link'] = isset($rej['affair']['link'])?$rej['affair']['link']:'';
        $re['affair']['rank'] = isset($rej['affair']['rank'])?intval($rej['affair']['rank']):mt_rand(200,500);
        $re['affair']['level'] = isset($rej['affair']['level'])?intval($rej['affair']['level']):-1;
        $re['affair']['levelname'] = isset($rej['affair']['levelname'])?$rej['affair']['levelname']:'';
        $re['affair']['exp'] = isset($rej['affair']['exp'])?$rej['affair']['exp']:-1;
        $re['affair']['gold'] = isset($rej['affair']['gold'])?$rej['affair']['gold']:"-1";
        //2.0新增
        $re['affair']['cash'] = isset($rej['affair']['cash']) ? (string)$rej['affair']['cash'] : "-1";
        return $re;

    }
}