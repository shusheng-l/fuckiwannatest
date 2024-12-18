<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/23
 * Time: 13:13
 */

namespace app\common\tool;


class UserCenter
{
    public static $_instance;
    /**
     * 当前用户
     * @var
     */
    private $_user;

    /**
     * 当前金币
     * @var
     */
    private $_gold = "-1";

    /**
     * 当前经验
     * @var
     */
    private $_experience = -1;

    /**
     * 当前等级
     * @var
     */
    private $_level = -1;

    /**
     * 当前等级名称
     * @var
     */
    private $_levelName;

    /**
     * 用户等级排名
     * @var
     */
    private $_rank;

    public function __construct()
    {
        $this->_user = User::current_user();
        $this->_gold = "-1";
        $this->_experience = -1;
        $this->_level = -1;
        $this->_levelName = '';
        $this->_rank = mt_rand(200,500);
    }

    // 创建__clone方法防止对象被复制克隆
    public function __clone()
    {
        throw new \Exception( 'Clone is not allow', E_USER_ERROR );
    }

    // 获取单例
    public static function getInstance()
    {
        if ( !(self::$_instance instanceof self) ) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

}