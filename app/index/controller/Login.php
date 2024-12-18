<?php

namespace app\index\controller;
use app\admin\model\SystemAdmin;
use app\admin\model\SystemConfig;
use app\common\model\Info;
use app\common\model\Sms;
use app\common\model\User;
use think\Request;
use think\facade\Session;
use think\facade\Config;
use think\cache\driver\Redis;

class Login extends PortBase
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    //发送短信
    public function verifyCode()
    {
        $mobile = $this->params['mobile'];
        $temp =  $this->params['temp'];
        if (empty($mobile)) return $this->port(0, '请输入手机号!');
        if (empty($temp)) return $this->port(0, '发送短信类型错误!');

        $member = User::where(["telnum"=>$mobile])->find();
        if($temp=='sms_forget' && empty($member)){
            return $this->port(0, '此手机号未注册!');
        }
        if($temp=='sms_reg' && !empty($member)){
            return $this->port(0, '此手机号已注册，请直接登录!');
        }

        $code =  mt_rand(111111, 999999);
        //获取模板
        $module = SystemConfig::where(["name"=>"msg_module"])->value("value");
        $content = str_replace("###",$code,$module);
        $res = sendSms($mobile, $content, $code);
        if($res){
            return $this->port(1, '短信发送成功');
        }else{
            return $this->port(0, '短信发送失败');
        }
    }

    /**
     * 注册
     */
    public function register() {
        if(request()->isPost())
        {
            $param = $this->params;
            foreach ($param as $k => $v) {
                if ($k != "recom" && $k != "verifycode" && $v == '') {
                    return $this->port(0, "数据异常");
                }
            }
            //验证码判断
            $msgStatus = SystemConfig::where(["name"=>"msg_open"])->value("value");
            if($msgStatus == 1 && $param["verifycode"] == "")
            {
                return $this->port(0, "数据异常");
            }
            //验证手机
            if(!is_mobile_phone($param["account"]))
            {
                return $this->port(0, "手机号码格式不正确!");
            }

            //验证码验证
            if($msgStatus == 1){
                $megWhere['telnum'] = $param["account"];
                $megWhere['code'] = $param['verifycode'];
                $sendTime = Sms::where($megWhere)->order('send_time desc')->field('send_time,id,status')->find();
                if(!isset($sendTime)) //短信15分钟内有效
                {
                    return $this->port(0, "验证码错误，请重新发送!");
                }
                if($sendTime['status'] == 1)
                {
                    return $this->port(0, "验证码已使用，请重新发送!");
                }
                if(strtotime($sendTime['send_time']) + 60*15 < time())
                {
                    return $this->port(0, "验证码已过期，请重新发送!");
                }
                //修改该验证码状态
                Sms::update([
                    'status' => 1,
                ],['id' => $sendTime['id']]);
            }

            $member = User::where(['telnum' => $param['account']])->find();
            if(!empty($member)){
                return $this->port(0, "该手机号已注册!");
            }

            //加密
            $param['salt'] = vae_set_salt(20);
            $param['pwd'] = password_hash(md5($param['pwd'].$param['salt']), PASSWORD_DEFAULT);
            $param['reg_time'] = time();
            $param['reg_ip'] = get_client_ip();

            $data = array(
                'device' => $param['device'],
                'username' => "", //注册时为空
                'telnum' => $param['account'],
                'password' => $param['pwd'],
                'status' => 1,
                'salt' => $param['salt'],
                'reg_time' => $param['reg_time'],
                'reg_ip' => $param['reg_ip'],
            );

            //用户信息
            $user= User::create($data);
            if($user->id){
                //获取随机管理员id
                $adminId = SystemAdmin::where(["status"=>1,"server_status"=>1])->where("server_url","<>","")->order("server_num asc,sort desc")->value("id"); //查找客服最少的
                if(!isset($adminId))
                {
                    $adminId = 0; //没有
                } else {
                    //累加
                    SystemAdmin::where(["id"=>$adminId])->inc('server_num')->update();
                }
                // 添加个人资料记录
                Info::create(['uid' => $user->id, 'mark' => '',"admin_id"=>$adminId]);
                //直接登录
                $hash_token = $this->loginToken($param['account'],$user->id);
                return $this->port(1, '恭喜您注册成功!',[
                    'token'   => base64_encode(json_encode(['id' => $user->id, 'token' => $hash_token]))
                ]);
            }
            $this->apiReturn('200',"注册失败，请重新操作!");
            return $this->port(0, "注册失败，请重新操作!");
        }
    }

    /**
     * 登录
     */
    public function login() {
        $account = trim($this->param('account'));
        $password =  trim($this->param('password'));
        if (!$account){
            return $this->port(0, "数据异常！");
        }
        if (!$password){
            return $this->port(0, "数据异常！");
        }
        $userinfo = User::where(['telnum' => $account])->find();
        if (!$userinfo){
            return $this->port(0, "暂无此用户！");
        };
        //验证账户
        $password = md5($password . $userinfo['salt']);
        if (!password_verify($password, $userinfo['password'])) {
            return $this->port(0, "登录密码错误，请重新输入!");
        }
        //状态异常
        if($userinfo['status'] != 1) return $this->port(0, '账号已被锁定,无法登陆!');
        //设置登录状态
        $hash_token = $this->loginToken($account,$userinfo['id']);
        return $this->port(1, '登录成功',[
            'token'   => base64_encode(json_encode(['id' => $userinfo['id'], 'token' => $hash_token]))
        ]);
    }

    /**
     * 忘记密码修改密码
     */
    public function forgetPwd() {
        if(request()->isPost())
        {
            $param = $this->params;
            foreach ($param as $k => $v) {
                if ($v == '') {
                    return $this->port(0, "数据异常！");
                }
            }
            //验证邮箱
            if(!is_mobile_phone($param["account"]))
            {
                return $this->port(0, "手机号码格式不正确!");
            }
            //邮箱验证码验证
            $megWhere['telnum'] = $param["account"];
            $megWhere['code'] = $param['verifycode'];
            $sendTime = Sms::where($megWhere)->order('send_time desc')->field('send_time,id,status')->find();
            if(!isset($sendTime)) //短信15分钟内有效
            {
                return $this->port(0, "验证码错误，请重新发送!");
            }
            if($sendTime['status'] == 1)
            {
                return $this->port(0, "验证码已使用，请重新发送!");
            }
            if(strtotime($sendTime['send_time']) + 60*15 < time())
            {
                return $this->port(0, "验证码已过期，请重新发送!");
            }
            //修改该验证码状态
            Sms::update([
                'status' => 1,
            ],['id' => $sendTime['id']]);
            $member = User::where(['telnum' => $param['account'], 'status' => 1])->find();
            if(empty($member)){
                return $this->port(0, "暂无此账号！");
            }
            $pwd = password_hash(md5($param['pwd'].$member['salt']), PASSWORD_DEFAULT);
            $data = array('password' => $pwd);
            //更新用户
            $res = User::where(['id'=>$member['id']])->save($data);
            if($res){
                return $this->port(1, "登录密码重置成功！");
            }
            return $this->port(0, "登录密码重置失败！");
        }
    }

    /**
     * 生成登录成功的session和token相关
     * */
    public function loginToken($account,$id)
    {
        $token = $this->uniqidReal();
        $time = time();
        $ip = get_client_ip();
        //更新登录状态
        User::where(['id'=>$id])->save(['last_time' => $time, 'last_ip' => $ip]);
        //设置入redis缓存
        $redis = new Redis();
        $redis->set('uni-app.'.md5('uniapp' . $account),[$token, $time, $ip]);
        $hash_token = password_hash($token . $time . $account . $ip, PASSWORD_DEFAULT);
        return $hash_token;
    }

    /**
     * 生成唯一的uuid值
     * @param  integer $lenght 生成的uuid长度
     * @return
     */
    public function uniqidReal($lenght = 13) {
        if (function_exists("random_bytes")) {
            $bytes = random_bytes(ceil($lenght / 2));
        } elseif (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($lenght / 2));
        } else {
            throw new Exception("no cryptographically secure random function available");
        }
        return substr(bin2hex($bytes), 0, $lenght);
    }

}
