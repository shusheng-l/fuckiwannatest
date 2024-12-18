<?php

namespace app\index\controller;
use app\admin\model\MallCate;
use app\admin\model\SystemAdmin;
use app\common\model\Info;
use app\common\model\User;
use think\Request;
use app\common\model\SystemConfig;
use app\common\model\Sms;
use app\admin\model\MallGoods;
use app\common\model\Loanorder;
use app\common\model\Notice;
use think\facade\View;

class Member extends Auth
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /**
     * 会员信息
     */
    public function info() {
        if($this->userbackinfo['name'] == "")
        {
            $this->userbackinfo['name'] = "未认证";
        }
        $data = array(
            'member' => $this->userbackinfo,
        );
        //未读消息
        $notice =array();
        $notice['isStatus'] = 0; //默认不显示
        $notice['title'] = "";
        $notice['content'] = "";
        $showNotice = Notice::where(["uid"=>$this->userinfo["id"],"is_read"=>1])->field("id,title,content")->order("id desc")->find();
        if(isset($showNotice))
        {
            $notice['isStatus'] = 1;
            $notice['title'] = $showNotice["title"];
            $notice['content'] = $showNotice["content"];
            //修改状态
            Notice::where(["id"=>$showNotice["id"]])->save(["is_read"=>2,'read_time'=>time()]);
        }
        $data["notice"] = $notice;
        //余额
        $qbMoney = User::getQbmoney($this->userinfo["id"]);
        $data['qbMoney'] = $qbMoney;
        //我的借款
        $loanMoney = Loanorder::where(array('uid' => $this->userinfo["id"], 'status' => 0, 'pending' => 1))->order('id desc')->value("money");
        if($loanMoney)
        {
            $data['loanMoney'] = $loanMoney;
        } else {
            $data['loanMoney'] = 0;
        }
        return $this->port(1, 'data', $data);
    }

    /**
    *身份证信息
     */
    public function cardInfo() {
        $data = [
            'username' => "",
            'usercard' => "",
            'cardphoto_1' => "",
            'cardphoto_2' => "",
            'takecardphoto' => "",
            'identityStatus' => 0,
            'contactsStatus' => 0,
            'bankStatus' => 0,
        ];
        $checkInfo = Info::where(['uid'=>$this->userinfo['id']])->field("identity,contacts,bank,bank_status")->find();
        if(!empty($checkInfo['identity']))
        {
            $data['identityStatus'] = 1;
        }
        if(!empty($checkInfo['contacts']))
        {
            $data['contactsStatus'] = 1;
        }
        if(!empty($checkInfo['bank']))
        {
            $data['bankStatus'] = 1;
        }
        $cardInfo = Info::where(['uid'=>$this->userinfo['id']])->value("identity");
        //是否已审核
        $status = Info::hasSetIdentity($this->userinfo['id']);
        if($status > 0)
        {
            return $this->port(2, '身份信息已审核！', $data);
        }
        if($cardInfo != "" && $cardInfo != null)
        {
            $cardInfo = json_decode($cardInfo,true); //转为数组
            $data = $cardInfo;
        }
        //配置
        $configs = SystemConfig::where('name','in',['is_show_card','is_show_take_card'])
            ->field("value")
            ->order("sort desc")
            ->select()
            ->toArray();
        $data['showCard'] = $configs[0]['value'] == 1 ? true:false;
        $data['showTakeCard'] = $configs[1]['value'] == 1 ? true:false;
        return $this->port(1, 'data', $data);
    }

    /**
     * 身份证提交
     */
    public function saveInfo() {
        $param = $this->params;
        if ($param['name'] == "" || $param['card'] == "") {
            return $this->port(0, "数据异常！");
        }
        //是否已审核
        $status = Info::hasSetIdentity($this->userinfo['id']);
        if($status > 0)
        {
            return $this->port(0, '身份信息已审核！');
        }
        //数据
        $iData = [
            'username' => $param['name'],
            'usercard' => $param['card'],
            'cardphoto_1' => $param['cardphoto_1'],
            'cardphoto_2' => $param['cardphoto_2'],
            'takecardphoto' => $param['takecardphoto'],
        ];
        //保存用户表
        $res = Info::where(['uid'=>$this->userinfo['id']])->save(['identity' => json_encode($iData), 'add_time' => time()]);
        if($res)
        {
            User::where(['id'=>$this->userinfo['id']])->save(["username"=>$param['name']]);
            return $this->port(1, '身份证信息提交成功!');
        }
        return $this->port(0, '身份证信息提交失败!');
    }

    /**
    *家庭信息
     */
    public function unitinfo() {
        //是否已审核
        $status = Info::hasSetIdentity($this->userinfo['id']);
        if($status > 0)
        {
            return $this->port(2, '身份信息已审核！');
        }
        $configs = SystemConfig::where('name','in',['is_show_address','load_purpose','monthly_income','relation_ship',"is_show_jop"])
                                ->field("value")
                                ->order("sort desc")
                                ->select()
                                ->toArray();
        $data = [
            'identityStatus' => 0,
            'contactsStatus' => 0,
            'bankStatus' => 0,
        ];
        $checkInfo = Info::where(['uid'=>$this->userinfo['id']])->field("identity,contacts,bank,bank_status")->find();
        if(!empty($checkInfo['identity']))
        {
            $data['identityStatus'] = 1;
        }
        if(!empty($checkInfo['contacts']))
        {
            $data['contactsStatus'] = 1;
        }
        if(!empty($checkInfo['bank']))
        {
            $data['bankStatus'] = 1;
        }
        $data['config']['showAddress'] = $configs[0]['value'] == 1 ? true:false;
        $data['config']['loadPurpose'] = array_filter(array_unique(explode(",",$configs[1]['value']))); //去重去空
        $data['config']['monthlyIncome'] = array_filter(array_unique(explode(",",$configs[2]['value']))); //去重去空
        $data['config']['relationShip'] = array_filter(array_unique(explode(",",$configs[3]['value']))); //去重去空
        $data['config']['showJop'] = $configs[4]['value'] == 1 ? true:false;
        //原始信息
        $contactsInfo = Info::where(['uid'=>$this->userinfo['id']])->value("contacts");
        if($contactsInfo != "" && $contactsInfo != null)
        {
            $contactsInfo = json_decode($contactsInfo,true); //转为数组
            $data['config']['contactsInfo'] = $contactsInfo;
        } else {
            $data['config']['contactsInfo'] = [];
        }
        return $this->port(1, 'data',$data);
    }

    /**
     * 个人和家庭资料提交
     */
    public function saveUnitinfo()
    {
        //是否已审核
        $status = Info::hasSetIdentity($this->userinfo['id']);
        if($status > 0)
        {
            return $this->port(0, '身份信息已审核！');
        }
        $param = $this->params;
        $contacts = [
            'company' => $param['company'],
            'dwname' => $param['dwname'],
            'dwaddess_ssq' => $param['dwaddess_ssq'],
            'dwaddess_more' => $param['dwaddess_more'],
            'position' => $param['dwposition'],
            'workyears' => $param['workyears'],
            'dwphone' => $param['dwphone'],
            'dwysr' => $param['dwysr'],
            'addess_ssq' => $param['addess_ssq'],
            'addess_more' => $param['addess_more'],
            'personname_1' => $param['personname_1'],
            'personname_2' => $param['personname_2'],
            'personphone_1' => $param['personphone_1'],
            'personphone_2' => $param['personphone_2'],
            'persongx_1' => $param['persongx_1'],
            'persongx_2' => $param['persongx_2'],
        ];
        //保存用户表
        $res = Info::where(['uid'=>$this->userinfo['id']])->save(['contacts' => json_encode($contacts), 'add_time' => time()]);
        if($res)
        {
            $reg_ssq = explode(' ', $param['addess_ssq']);
            User::where(['id'=>$this->userinfo['id']])->save(["reg_city"=>$reg_ssq[0]]);
            return $this->port(1, '个人和家庭信息提交成功!');
        }
        return $this->port(0, '个人和家庭信息提交失败!');
    }

    /**
     *银行信息
     */
    public function bankInfo() {
        //是否已审核
        $status = Info::hasSetIdentity($this->userinfo['id']);
        $twoInfo = Info::where(['uid'=>$this->userinfo['id']])->field("identity,bank,bank_status")->find();
        if($twoInfo['bank_status'] == 0 && $status > 0)
        {
            return $this->port(3, '身份信息已审核！');
        }
        $config = SystemConfig::where(["name"=>"bank_type"])->value("value");
        $data = array();
        $data = [
            'identityStatus' => 0,
            'contactsStatus' => 0,
            'bankStatus' => 0,
        ];
        $checkInfo = Info::where(['uid'=>$this->userinfo['id']])->field("identity,contacts,bank,bank_status")->find();
        if(!empty($checkInfo['identity']))
        {
            $data['identityStatus'] = 1;
        }
        if(!empty($checkInfo['contacts']))
        {
            $data['contactsStatus'] = 1;
        }
        if(!empty($checkInfo['bank']))
        {
            $data['bankStatus'] = 1;
        }
        $data['config'] = array_filter(array_unique(explode(",",$config))); //去重去空

        $cardInfo = $twoInfo['identity'];
        $bankInfo = $twoInfo['bank'];
        if($cardInfo != "" && $cardInfo != null)
        {
            $cardInfo = json_decode($cardInfo,true); //转为数组
            $data['cardInfo'] = $cardInfo;
            $data['cardInfo']['usercard'] = "*********".substr($data['cardInfo']['usercard'],strlen($data['cardInfo']['usercard'])-4);
        } else {
            return $this->port(2, '请先进行身份信息认证！');
        }
        if($bankInfo != "" && $bankInfo != null)
        {
            $bankInfo = json_decode($bankInfo,true); //转为数组
            $data['bankInfo'] = $bankInfo;
            $data['bankStatus'] = $twoInfo['bank_status'];
        } else {
            $data['bankInfo'] = [];
        }
        return $this->port(1, 'data', $data);
    }

    /**
     * 银行卡信息
     * */
    public function saveBankinfo()
    {
        //是否已审核
        $status = Info::hasSetIdentity($this->userinfo['id']);
        if($status > 0)
        {
            return $this->port(0, '身份信息已审核！');
        }
        $param = $this->params;
        unset($param['token']);
        foreach ($param as $k => $v) {
            if ($v == '') {
                return $this->port(0, "数据异常！");
            }
        }
        //身份信息认证优先
        $twoInfo = Info::where(['uid'=>$this->userinfo['id']])->field("identity")->find();
        $cardInfo = $twoInfo['identity'];
        if($cardInfo == "" || $cardInfo == null)
        {
            return $this->port(0, '请先进行身份信息认证！');
        }
        //保存用户表
        $res = Info::where(['uid'=>$this->userinfo['id']])->save(['bank' => json_encode($param), 'add_time' => time(),'orgin_bank_type'=>$param['bankname'],'orgin_bank_card'=>$param['bankcard']]);
        if($res)
        {
            return $this->port(1, '银行卡信息提交成功!');
        }
        return $this->port(0, '银行卡信息提交失败!');
    }

    /**
     *验证贷款之前提交的信息
     */
    public function checkInfo() {
        $data = [
            'mobile' => $this->userbackinfo['account'],
            'identityText' => "未认证",
            'contactsText' => "未认证",
            'bankText' => "未认证",
            'identityStatus' => 0,
            'contactsStatus' => 0,
            'bankStatus' => 0,
        ];
        $cardInfo = Info::where(['uid'=>$this->userinfo['id']])->field("identity,contacts,bank,bank_status")->find();
        if(!empty($cardInfo['identity']))
        {
            $data["identityText"] = "已认证";
            $data['identityStatus'] = 1;
        }
        if(!empty($cardInfo['contacts']))
        {
            $data["contactsText"] = "已认证";
            $data['contactsStatus'] = 1;
        }
        if(!empty($cardInfo['bank']))
        {
            $data["bankText"] = "已认证";
            $data['bankStatus'] = 1;
            if($cardInfo['bank_status'] == 1)
            {
                $data['bankStatus'] = 0;
            }
        }

        return $this->port(1, 'data', $data);
    }

    /**
     * 获取验证码
     */
    public function verifycoder() {
        $mobile = $this->userinfo['telnum'];
        $code =  mt_rand(111111, 999999);
        $content = '【商城】您的验证码是：' . $code . "，勿告知他人！15分钟内有效！";
        $res = sendSms($mobile, $content, $code);
        if($res){
            return $this->port(1, '短信发送成功');
        }else{
            return $this->port(0, '短信发送失败');
        }
    }

    /**
     * 修改密码
     */
    public function changePwd() {
        if(request()->isPost())
        {
            $param = $this->params;
            foreach ($param as $k => $v) {
                if ($v == '') {
                    return $this->port(0, "数据异常！");
                }
            }
            //验证邮箱
            if($param['pwd'] != $param['repwd'])
            {
                return $this->port(0, "两次密码不一致!");
            }
            //邮箱验证码验证
            $megWhere['telnum'] = $this->userinfo["telnum"];
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
            $member = User::where(['telnum' => $this->userinfo["telnum"], 'status' => 1])->find();
            if(empty($member)){
                return $this->port(0, "暂无此账号！");
            }
            $pwd = password_hash(md5($param['pwd'].$this->userinfo['salt']), PASSWORD_DEFAULT);
            $data = array('password' => $pwd);
            //更新用户
            $res = User::where(['id'=>$this->userinfo['id']])->save($data);
            if($res){
                return $this->port(1, "登录密码重置成功！");
            }
            return $this->port(0, "登录密码重置失败！");
        }
    }

    //首页配置
    public function configInfo()
    {
        $configs = SystemConfig::where('name','in',['month_rate','over_rate','time_frame','min_loan',"max_loan","span_loan","default_loan"])
            ->field("value")
            ->order("sort desc")
            ->select()
            ->toArray();
        $data = array();
        $data['config']['month_rate'] = $configs[0]['value']; //月息
        $data['config']['over_rate'] = $configs[1]['value']; //逾期费率
        $data['config']['time_frame'] = array_filter(array_unique(explode(",",$configs[2]['value']))); //去重去空 //期限范围
        $data['config']['min_loan'] = $configs[3]['value']; //单次借款最低金额
        $data['config']['max_loan'] = $configs[4]['value']; //单次借款最高金额
        $data['config']['span_loan'] = $configs[5]['value']; //借款金额选择跨度
        $data['config']['default_loan'] = $configs[6]['value']; //默认显示金额
        //借款协议id和标题
        $data['articleAid'] = 11;
        $articleWhere['id'] = $data['articleAid'];
        $article = MallGoods::where($articleWhere)//自动剔除软删除数据
                            ->value("title");
        $data['articleTtile'] = $article;
        return $this->port(1, 'data', $data);
    }


    //客服
    public function serverInfo()
    {
        $data = array();
        //客服地址
        $adminId = Info::where(["uid"=>$this->userinfo["id"]])->value("admin_id");
        $serverUrl = SystemAdmin::where(["id"=>$adminId])->value("server_url");
        $data["serverUrl"] = $serverUrl;
        //工作时间
        $doTime = SystemConfig::where(["name"=>"server_time"])->value("value");
        $data["doTime"] = $doTime;
        //所有文章
        $cates = MallCate::where("id","<>",10)->field("id,title,image")->select()->toArray();
        for ($a=0;$a<count($cates);$a++)
        {
            $cates[$a]["article"] = MallGoods::where(["cate_id"=>$cates[$a]["id"]])->field("id as articleId,title")->select();
            unset($cates[$a]["id"]);
        }
        $data["cates"] = $cates;
        //未读消息
        $notice =array();
        $notice['isStatus'] = 0; //默认不显示
        $notice['title'] = "";
        $notice['content'] = "";
        $showNotice = Notice::where(["uid"=>$this->userinfo["id"],"is_read"=>1])->field("id,title,content")->order("id desc")->find();
        if(isset($showNotice))
        {
            $notice['isStatus'] = 1;
            $notice['title'] = $showNotice["title"];
            $notice['content'] = $showNotice["content"];
            //修改状态
            Notice::where(["id"=>$showNotice["id"]])->save(["is_read"=>2,'read_time'=>time()]);
        }
        $data["notice"] = $notice;
        return $this->port(1, 'data', $data);
    }

    //合约
    public function contract()
    {
        $loanInfo = Loanorder::where(array('uid' => $this->userinfo['id']))->order("id desc")->find();
        if(!isset($loanInfo))
        {
            die;
        }
        View::assign('data', $loanInfo);
        //获取显示模板
        //相关配置
        $infoConfigs = SystemConfig::where('name','in',["contact_module",'true_code',"company_logor"])
            ->field("value")
            ->order("sort desc")
            ->select()
            ->toArray();
        $contractTpl = $infoConfigs[0]['value'];
        $contractTpl = htmlspecialchars_decode(htmlspecialchars_decode($contractTpl));
        $loanData = json_decode($loanInfo['data'], true);
        $timeType = '月';
        if ($timeType == '月') {
            $endTime = strtotime('+' . intval($loanInfo['time']) . ' Month', $loanInfo['start_time']);
        } else {
            $endTime = strtotime('+' . intval($loanInfo['time']) . ' Day', $loanInfo['start_time']);
        }
        //信用代码
        $trueCode = $infoConfigs[1]['value'];
        //公章
        $officeLoge = $infoConfigs[2]['value'];
        $officeLogeImg = '<img src="' . $officeLoge . '" style="width: 110px;position: absolute;left: 0;bottom: 0;" />';
        $sign = '<img src="' . $loanInfo['sign'] . '" style="width: 110px;" />';
        $userInfo = Info::getAuthInfo($loanInfo['uid']);
        $addessInfo = json_decode($userInfo['contacts'], true);
        $contractTpl = str_replace('｛统一社会信用代码｝', $trueCode, $contractTpl);
        $contractTpl = str_replace('｛借款人名称｝', $loanInfo['name'], $contractTpl);
        $contractTpl = str_replace('｛借款人身份证号｝', json_decode($userInfo['identity'],true)['usercard'], $contractTpl);
        $contractTpl = str_replace('｛借款人手机号｝', $userInfo['telnum'], $contractTpl);
        $contractTpl = str_replace('｛借款金额大写｝', get_amount(round($loanInfo['money'],2)), $contractTpl);
        $contractTpl = str_replace('｛借款金额小写｝', $loanInfo['money'], $contractTpl);
        $contractTpl = str_replace('｛借款期限类型｝', $timeType, $contractTpl);
        $contractTpl = str_replace('｛借款利息｝', floatval($loanInfo['interest']), $contractTpl);
        $contractTpl = str_replace('｛借款开始日｝', date('Y年m月d日', $loanInfo['start_time']), $contractTpl);
        $contractTpl = str_replace('｛借款结束日｝', date('Y年m月d日', $endTime), $contractTpl);
        $contractTpl = str_replace('｛借款人用户名｝', $userInfo['username'], $contractTpl);
        $contractTpl = str_replace('｛收款银行账号｝', $loanInfo['banknum'], $contractTpl);
        $contractTpl = str_replace('｛收款银行开户行｝', $loanInfo['bankname'], $contractTpl);
        $contractTpl = str_replace('｛逾期利息｝', floatval($loanInfo['overdue']), $contractTpl);
        $contractTpl = str_replace('｛公司公章｝', $officeLogeImg, $contractTpl);
        $contractTpl = str_replace('｛借款人签名｝', $sign, $contractTpl);
        $contractTpl = str_replace('｛合同签订日期｝', date('Y 年 m 月 d 日', $loanInfo['add_time']), $contractTpl);
        $contractTpl = str_replace('｛借款人住所｝', $addessInfo['addess_ssq'] . $addessInfo['addess_more'], $contractTpl);
        View::assign('tpl', $contractTpl);

        View::assign('id', $loanInfo['id']);
        return View::fetch();
    }
}
