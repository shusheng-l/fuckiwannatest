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
use think\Facade\Db;

class User extends TimeModel
{

    protected $autoWriteTimestamp = true;

    protected $createTime = 'reg_time';
    protected $updateTime = 'last_time';

    //查找用户表字段值
    static function getInfo($par1, $par2 = null, $name = null)
    {
        $w = array();
        if (!$par1) {
            return false;
        }
        if (is_array($par1)) {
            $w = $par1;
            $info = self::where($w)->find();
            if (!$info) {
                return false;
            }
            if ($name && isset($info[$name])) {
                return $info[$name];
            }
            if ($par2 && !is_array($par2) && isset($info[$par2])) {
                return $info[$par2];
            }
            return $info;
        }
        if (!$par2) {
            return false;
        }
        $w = array($par1 => $par2);
        $info = self::where($w)->find();
        if (!$info) {
            return false;
        }
        if ($name && isset($info[$name])) {
            return $info[$name];
        }
        return $info;
    }

    //获取额度
    static function getDoquota($id = 0)
    {
        if (!$id) {
            return false;
        }
        //用户额度
        $quota = self::getInfo('id', $id, 'quota');
        if (!$quota) {
            return 0;
        }
        //借款订单表统计金额
        $has = Loanbill::where(array('uid' => $id, 'status' => array('in', '0,1')))->sum('money');
        return !(toMoney($quota - $has) >= 0) ? 0 : toMoney($quota - $has);
    }

    //	审批金额
    static function getDoquotama($id = 0)
    {
        if (!$id) {
            return false;
        }
        $quota = self::getInfo('id', $id, 'quota');
        if (!$quota) {
            return 0;
        }
        $has = 0 ;
        return !(toMoney($quota - $has) >= 0) ? 0 : toMoney($quota - $has);
    }


    //钱包余额
    static function getQbmoney($id = 0)
    {
        if (!$id) {
            return false;
        }
        $qbma = self::getInfo('id', $id, 'qbmoney');
        if (!$qbma) {
            $qbma = 0;
        }
        return toMoney($qbma);
    }

    //更新余额
    static function updateQbmoney($id, $money , $bz)
    {
        $qbmo = 1;
        if (!$id || !$money) {
            return false;
        }
        if ($bz == '1'){
            //充值
            $qbst = Db::name("user")->where(array('id' => $id))->inc('qbmoney',$money)->update();
            if (!$qbst) {
                return false;
            }
        }elseif ($bz == '2'){
            $qbmoney = Db::name("user")->where(array('id' => $id))->value("qbmoney");
            if($qbmoney < $money * -1) //检测余额
            {
                return false;
            }
            //取
            $qbst = Db::name("user")->where(array('id' => $id))->inc('qbmoney',$money)->update();
            if (!$qbst) {
                return false;
            }
        }else{
            return false;
        }

        return $qbmo;
    }

}