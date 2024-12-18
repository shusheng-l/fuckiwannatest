<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/27
 * Time: 14:12
 */

namespace app\common\tool;


class Code
{
    const SUCCESS = 0;
    const FAIL         = 1;
    const LOGIN_ERROR  = 2;

    const SIGN_ERROR   = 400;
    const FORBID       = 403;
    const NOT_FOUND    = 404;
    const REQUEST_ERROR = 405;//请求方式报错
    const DB_EXCEPTION = 600;

    const LOGIN_FIRST  = 2005;
    const BIND_PHONE_FIRST = 2006;//需要先绑定手机
    const LOGIN_TOKEN_INVALID = 2007;//登录TOKEN失效
    const VERIFY_REAL_PERSON = 2008;//需要实名认证

    const USERNAME_NULL         = 700;//用戶名为空
    const USERNAME_EXISTS       = 701;//用戶名已存在
    const SEND_VERIFY_CODE_ERROR = 702;
    const VERIFY_CODE_TIMEOUT    = 703;
    const VERIFY_CODE_ERROR      = 704;

    const MOBILE_REGISTERED     = 705;
    const MOBILE_WRONG          = 706;

    const VERIFY_CODE_EMPTY      = 707;

    const USER_NOT_EXISTS       = 708;
    const USER_IS_FORBID        = 709;//用户处于禁言状态

    const MOBILE_ELSEWHERE      = 710;//异地手机号

    const POST_NEW_THREAD_FAIL  = 800;
    const REPLY_THREAD_FAIL     = 801;
    const COLLECT_FORUM_ERROR   = 802;
    const FORUM_NOT_EXISTS      = 803;
    const COLLECT_THREAD_ERROR  = 804;
    const PING_THREAD_ERROR     = 805;
    const GET_USER_THREAD_ERROR = 806;
    const GET_USER_REPLIES_ERROR = 807;
    const SEND_SMS_FAIL         = 808;
    const BIND_MOBILE_FAIL      = 809;
    const NOT_ALLOW_POST        = 810;
    const AUTH_CODE_FAIL         = 811;

    const PING_COMMENT_ERROR     = 1605;  //点赞评论

    const REWARD_FAIL           = 812;
    const REWARD_MYSELF_ERROR   = 813;//自己给自己打赏
    const REWARD_LOW_LEVEL      = 814;//打赏者等级不够
    const GOLD_NOT_ENOUGH       = 815;//金币不足
    const REWARD_FIVE_SECOND    = 816;//5秒内不能重复打赏
    const REWARD_NOT_OPEN       = 817;//打赏功能未开启
    const REWARD_TEXT_NULL      = 818;//打赏文案为空
    const REWARDED_FORBID       = 819;//账号冻结 不能被打赏
    const REWARD_LIMIT_MONEY    = 820;//

    const FORBID_CREATE_SIDE    = 821;//禁止发布本地圈
    const FORBID_REPLY_SIDE     = 822;//禁止评论本地圈

    //支付相关
    const BALANCE_NOT_ENOUGH    = 821;//账户余额不足
    const GOLD_USER_INFO_FAIL   = 822;//user表对应到common_user表失败
    const ORDER_NOT_BELONG_USER = 823;//订单不属于当前用户
    const PAY_TYPE_ERROR        = 824;//支付方式出错
    const ORDER_NOT_FIND        = 825;//找不到订单
    const ORDER_STATUS_ERROR    = 826;//订单状态不对，例：付款时状态不是待支付
    const ORDER_NEED_SEND_ADDRESS=827;//订单还未选择收货地址、自提地址
    const PAY_PASSWORD_ERROR    = 828;//支付密碼错误

    //第三方账户尚未绑定用户
    const WEIBO_NOT_BIND        = 1008;

    /**
     * 帖子不存在
     */
    const THREAD_NOT_EXISTS     = 900;

    const IOS     = 1;
    const ANDROID = 2;


    const EDIT_FAIL = 950;      //修改失败
    const RECORD_EXISTS = 960;        //数据已存在
    const IN_BAD_LIST_ALREADY = 961;  //已经在黑名单中
    const NOT_IN_BAD_LIST = 962;      //不在黑名单中

    const ADD_FAIL = 970;   //添加失败

    const PARAMETER_ERROR = 1100;    //参数错误
    const PARAMETER_MISS = 1101;    //参数缺失
    const PARAMETER_WRONG_TYPE = 1102;   //参数类型错误

    const RECORD_NOT_EXISTS = 1200;     //查询数据不存在
    const SIDE_NOT_EXISTS = 1201;       //本地圈不存在
    const SIDE_ERROR = 1202;            //本地圈状态无效

    const TAG_NOT_EXISTS = 1211;       //话题不存在
    const TAG_FOLLOW_MAX = 1211;       //话题关注数达到上限

    const REPORT_FAIL = 1300;               //数据上传失败
    const REPORT_USER_GPS_FAIL = 1301;      //用户坐标上传失败
    const DEL_USER_GPS_FAIL = 1302;      //删除用户坐标信息失败

    const REWARD_REPEAT_ERROR = 1501; // 重复打赏

    //1510-1550  邂逅
    const MEET_TIMES_TOO_MUCH_TODAY = 1510;   //今天邂逅次数用完

    //1560-1600  打招呼
    const SAY_HI_TIMES_MORE_TODAY = 1560;      //今天打招呼次数用完
    const SAY_HI_TIMES_MORE_ONE_TODAY = 1561;  //今天对某个用户打招呼的次数用完
    const SAY_HI_BLACK_LIST = 1562;  //被加入黑名单
}