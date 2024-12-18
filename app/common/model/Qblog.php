<?php

// +----------------------------------------------------------------------
// | EasyAdmin
// +----------------------------------------------------------------------
// | PHP交流群: 763822524
// +----------------------------------------------------------------------
// | 开源协议  https://mit-license.org 
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zhongshaofa/EasyAdmin
// +----------------------------------------------------------------------


namespace app\common\model;

class Qblog extends TimeModel
{

    protected $autoWriteTimestamp = true;

    // 用户添加充值记录
    static function addlog($uid , $money, $upimg, $type)
    {
        return self::create(array('uid' => $uid, 'money' => $money, 'status' => 0, 'add_time' => time(), 'pzimg' => $upimg ,'bz' => 1,'type' => $type));
    }

    // 用户添加取现记录
    static function outlog($uid , $money)
    {
        return self::create(array('uid' => $uid, 'money' => $money, 'status' => 0, 'add_time' => time(), 'pzimg' => '' ,'bz' => 2));
    }

    // 取出充值记录
    static function getQblogst($uid = 0){
        $rea =array();
        $rea['error'] = 0;
        $wh =array();
        $wh['uid'] =  array('eq',$uid);
        $wh['bz'] =  array('eq',1);	// 充值
        $jre = self::where($wh)->order('id desc')->find();
        if($jre){
            $status =$jre['status'];
            if($status == 0){
                //有新待审核
                $rea =array();
                $rea['error'] = "审核中";
                $rea['money'] = $jre['money'];
            }else if($status == 1){ //已审核
                $rea =array();
                $rea['error'] = "已审核";
                $rea['money'] = $jre['money'];
            } else { //已驳回
                $rea =array();
                $rea['error'] = "已驳回";
                $rea['money'] = $jre['money'];
            }
        }
        return $rea;
    }

    // 取出取现记录
    static function getQblogtx($uid = 0){
        $rea =array();
        $rea['error'] = 0;
        $wh =array();
        $wh['uid'] =  array('eq',$uid);
        $wh['bz'] =  array('eq',2);	// 提现
        $jre = self::where($wh)->order('id desc')->find();
        if($jre){
            $status =$jre['status'];
            if($status == 0){
                //有新待审核提现
                $rea =array();
                $rea['error'] = "审核中";
                $rea['money'] = $jre['money'];
            }else if($status == 1){
                $rea =array(); //已审核
                $rea['error'] = "已审核";
                $rea['money'] = $jre['money'];
            } else {
                $rea =array();//已驳回
                $rea['error'] = "已驳回";
                $rea['money'] = $jre['money'];
            }
        }
        return $rea;
    }

    // 取出钱包记录
    static function getQbloglist($uid,$bz){
        if (!$uid) {
            return false;
        }
        $bz = intval($bz);
        $uid = intval($uid);
        $wh =array();
        $wh['uid'] =  array('eq',$uid);
        $wh['bz'] =  array('eq',$bz);
        /*$wh['isadmin'] =  array('lt','9');*/
        $qbList = self::where($wh)->order('id desc')->limit(100)->select();
        return $qbList;
    }

    //还款记录
    static function getQbbilllist($oid){
        if (!$oid) {
            return false;
        }
        $billList = Loanbill::where(array('toid' => $oid))->order('billnum asc')->select();
        $i = 0;
        while ($i < count($billList)) {
            $billList[$i]['allmoney'] = toMoney($billList[$i]['money'] + $billList[$i]['interest'] + $billList[$i]['overdue']);
            $i = $i + 1;
        }
        return $billList;
    }

    // 人工充值记录
    static function htaddlog($uid , $money)
    {
        $id = self::create(array('uid' => $uid, 'money' => $money, 'status' => 1, 'add_time' => time(), 'isadmin' => 1 ,'bz' => 1));
        return $id;
    }

    // 授权金额人工充值记录
    static function htaddlogSepc($uid , $money)
    {
        $id = self::create(array('uid' => $uid, 'money' => $money, 'status' => 1, 'add_time' => time(), 'isadmin' => 1 ,'bz' => 1,'isend' => 1));
        return $id;
    }
}