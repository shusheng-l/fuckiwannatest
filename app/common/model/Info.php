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

class Info extends TimeModel
{

    static function hasSetIdentity($uid)
    {
        return self::where(['uid'=>$uid])->value("status");
    }

    //是否有借款记录在info表的addess字段只中
    static function hasSetAddess($uid = 0)
    {
        $addess = self::where(array('uid' => $uid))->value("addess");
        if (!empty($addess)) {
            return true;
        }
        return false;
    }

    //借款记录保存到info表的addess字段只中
    static function setAddess($uid = 0, $arr = array())
    {
        $result = self::where(array('uid' => $uid))->save(array('addess' => json_encode($arr), 'add_time' => time(),"status"=>1));
        return $result;
    }

    //获取整条info记录还是info中的某个值
    static function getAuthInfo($uid = 0, $name = null)
    {
        $userAuth = self::where(array('i.uid' => $uid))
                    ->alias('i')
                    ->join('user u','u.id = i.uid')
                    ->find();
        if (!$name) {
            return $userAuth;
        }
        if (!isset($userAuth[$name])) {
            return false;
        }
        return $userAuth[$name];
    }

    //设置状态
    static function setStatus($id = 0, $status = 0)
    {
        $result = self::where(array('id' => $id))->save(array('status' => $status));
        return $result;
    }
}