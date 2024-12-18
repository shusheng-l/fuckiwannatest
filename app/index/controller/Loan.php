<?php

namespace app\index\controller;
use app\common\model\Info;
use app\common\model\Loanbill;
use app\common\model\Loanorder;
use think\facade\Db;
use think\Request;
use app\common\model\SystemConfig;
use app\admin\model\MallGoods;
use app\common\model\Notice;

class Loan extends Auth
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /**
     * 申请借款
     */
    public function apply() {
        if(request()->isPost())
        {
            $param = $this->params;
            foreach ($param as $k => $v) {
                if ($v == '') {
                    return $this->port(0, "数据异常！");
                }
            }
            $money = $param['amout']; //申请金额
            $time = $param['month']; //申请时间
            //根据用户状态设置当前借款配置
            $moneyScale = getMoneyScale($this->userinfo["id"]);
            //验证金额和时间
            if($money < $moneyScale['min'] || $money > $moneyScale['max'])
            {
                return $this->port(0, "数据异常！");
            }
            //月份配置
            $DeadlineList = getDeadlineList();
            if (!$DeadlineList || !$DeadlineList['list']) {
                return $this->port(2, "系统出错,请联系管理员！");
            }
            if (!is_array($DeadlineList['list'])) {
                return $this->port(2, "系统设置出错！");
            }
            if (!in_array($time, $DeadlineList['list'])) {
                return $this->port(0, "借款期限不符合规定！");
            }

            //是否已有借款数据
            $wh =array();
            $wh['uid'] =  array('eq',$this->userinfo['id']);
            $wh['status'] =  array('eq',0);
            $loanInfo = Loanorder::where($wh)->order('id desc')->find();
            if ($loanInfo) {
                $pending =$loanInfo['pending'];
                if($pending == '1'){
                    return $this->port(3, "您的借款已通过,快去提现吧！");
                }
                if($pending == '0'){
                    return $this->port(3, "您已有借款订单，请查看订单详情！");
                }
            }

            //验证是否已经完成借款资料
            $userInfos = Info::where(["uid"=>$this->userinfo['id']])->field("identity,contacts,bank,addess,admin_id")->find();
            if(empty($userInfos['identity']))
            {
                return $this->port(4, "请填写个人资料！");
            }
            if(empty($userInfos['contacts']))
            {
                return $this->port(5, "请填写家庭资料！");
            }
            if(empty($userInfos['bank']))
            {
                return $this->port(6, "请填写银行卡资料！");
            }

            // 判断是否已申请
            if(!empty($userInfos["addess"]))
            {
                return $this->port(3, "您的借款已申请！");
            } else {
                // 插入记录
                $data = [
                    'amount' => toMoney($money), // 借款总金额
                    'month' => $time			// 还款月数
                ];
                //借款记录保存到info表的addess字段只中
                $res = Info::setAddess($this->userinfo['id'], $data);
                if ($res) {
                    //直接生成订单Loanorder表
                    $info  = Info::getAuthInfo($this->userinfo['id']);
                    $bankinfo = json_decode($info['bank']);
                    $identityinfo = json_decode($info['identity']);
                    //相关配置
                    $infoConfigs = SystemConfig::where('name','in',["over_rate",'ypass',"dbt","sm","Loan_TYPE","color","bz"])
                                                ->field("value")
                                                ->order("sort desc")
                                                ->select()
                                                ->toArray();
                    //插入Loanorder表的数据
                    $loanData = [
                        'uid' => $this->userinfo['id'],
                        'oid' => generateOrderNo('LN', $this->userinfo['id']),
                        'tpass' => $infoConfigs[1]['value'], //未知
                        'dbt' => $infoConfigs[2]['value'], //审核时间通知
                        'error' => $infoConfigs[3]['value'], //短信内容通知
                        'money' => toMoney($money),
                        'name' => $identityinfo->username,
                        'bankname' => $bankinfo->bankname,
                        'banknum' => $bankinfo->bankcard,
                        'start_time' => getNextMonth(1),
                        'time' => $time,
                        'interest' => getInterest(),
                        'overdue' => $infoConfigs[0]['value'], //逾期利息
                        'timetype' => $infoConfigs[4]['value'], //贷款月计算，1为月
                        'add_time' => time(),
                        'aus' => 1,
                        'zt' => '',
                        'yqm' => '',
                        'tco' => $infoConfigs[5]['value'], //通知颜色
                        'vicsv' => '',
                        'xbzmark' => $infoConfigs[6]['value'], //审核时间通知
                        'admin_id' => $userInfos['admin_id'],
                    ];
                    $loanorder = new Loanorder();
                    $res = $loanorder->save($loanData);
                    if ($res) {
                        //发送短信提示->审核中
                        orderChangeSmSSend($this->userinfo['id'],1);
                        return $this->port(1, "借款订单生成成功！");
                    } else {
                        return $this->port(0, "借款订单生成失败！");
                    }
                } else {
                    return $this->port(0, "借款订单生成失败！");
                }
            }
        }
    }

    /**
     * 文章
     * */
    public function getArticle()
    {
        $data= [];
        //借款协议id和标题
        $data['articleAid'] = 12;
        $articleWhere['id'] = $data['articleAid'];
        $article = MallGoods::where($articleWhere)//自动剔除软删除数据
        ->value("title");
        $data['articleTtile'] = $article;
        return $this->port(1, "data",$data);
    }

    /**
     * 订单状态判定
     * */
    public function orderStatus()
    {
        $data= [];
        $where["uid"] = $this->userinfo['id'];
        $where["status"] = array("in","0,1");
        $loadOrder = Loanorder::where($where)->field("money,status,pending,qrtx,oid,bankname,banknum,time,add_time,dbt,error,pass_time,withdraw_time,order_status")->order('id desc')->find();
        if(!isset($loadOrder))
        {
            $data['status'] = 0; //未生成订单
            return $this->port(1, "data",$data);
        }
        $loadOrder = $loadOrder->toArray();
        $data['status'] = 1; //已生成订单
        //配置
        $configs = SystemConfig::where('name','in',['month_rate','orderRemark'])
            ->field("value")
            ->order("sort desc")
            ->select()
            ->toArray();
        $data['config']['orderRemark'] = array_filter(array_unique(explode("$$$",$configs[1]['value']))); //去重去空
        $data['config']['monthRate'] = (float)$configs[0]['value']; //费率
        //数据处理
        $loadOrder['banknum'] = substr($loadOrder['banknum'],-4); //保留银行卡后四位
        //审核时间
        $loadOrder["do_time1"] = explode(" ",date("Y-m-d H:i:s",$loadOrder['add_time']))[0]; //时间处理
        $loadOrder["do_time2"] = explode(" ",date("Y-m-d H:i:s",$loadOrder['add_time']))[1]; //时间处理
        //审核通过时间
        $loadOrder["over_time1"] = explode(" ",date("Y-m-d H:i:s",$loadOrder['pass_time']))[0]; //时间处理
        $loadOrder["over_time2"] = explode(" ",date("Y-m-d H:i:s",$loadOrder['pass_time']))[1]; //时间处理
        //提现时间
        $loadOrder["withdraw_time1"] = explode(" ",date("Y-m-d H:i:s",$loadOrder['withdraw_time']))[0]; //时间处理
        $loadOrder["withdraw_time2"] = explode(" ",date("Y-m-d H:i:s",$loadOrder['withdraw_time']))[1]; //时间处理
        //缴费开始和结束时间
        $loadOrder["start"] = date("Y-m-d",$loadOrder['add_time']);
        $loadOrder["end"] = date("Y-m-d",getNextMonth($loadOrder['time'],$loadOrder['add_time']));
        //每月还款日
        $loadOrder["back_month"] = "每月" . date("d",$loadOrder['add_time']) . "日";
        $data['order'] = $loadOrder;
        //借款协议id和标题
        $data['articleAid'] = 12;
        $articleWhere['id'] = $data['articleAid'];
        $article = MallGoods::where($articleWhere)//自动剔除软删除数据
                            ->value("title");
        $data['articleTtile'] = $article;
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
        //是否已改卡
        $bankStatus = Info::where(["uid"=>$this->userinfo['id']])->value("bank_status");
        $data['bankStatus'] = $bankStatus;
        return $this->port(1, "data",$data);
    }

    /**
     *确认提现
     * */
    public function withdraw()
    {
        if(request()->isPost()) {
            $param = $this->params;
            foreach ($param as $k => $v) {
                if ($v == '') {
                    return $this->port(0, "数据异常！");
                }
            }

            //借款订单数据
            $loanInfo = Loanorder::where(['uid' => $this->userinfo['id'], 'pending' => 1, 'qrtx' => 0])->order("id desc")->find();
            if(isset($loanInfo))
            {
                //配置数据
                $configs = SystemConfig::where('name','in',['qrtx_dbt','qrtx_sm','qrtx_color','qrtx_bz'])
                    ->field("value")
                    ->order("sort desc")
                    ->select()
                    ->toArray();
                // 启动事务
                Db::startTrans();
                //每期还款金额
                $periodMoney = toMoney(($loanInfo['money'] * getInterest() * $loanInfo['time'] + $loanInfo['money']) / $loanInfo['time']);
                // 确认提现
                $nowLoanInfo['qrtx'] = 1;
                $nowLoanInfo['sign'] = $param['sign'];
                $nowLoanInfo['withdraw_time'] = time();
                $nowLoanInfo['dbt'] = $configs[0]['value'];
                $nowLoanInfo['error'] = $configs[1]['value'];
                $nowLoanInfo['tco'] = $configs[2]['value'];
                $nowLoanInfo['xbzmark'] = $configs[3]['value'];
                $res = Loanorder::where(["id"=>$loanInfo["id"]])->save($nowLoanInfo);
                //用户信息表操作
                $res1 = Info::where(["uid"=>$this->userinfo["id"]])->save(['qrtx' => 1]);
                // 生成账单
                $loanBill = [];
                for ($i = 1; $i <= $loanInfo['time']; $i ++) {
                    $data['uid'] =  $loanInfo['uid'];
                    $data['toid'] = $loanInfo['id'];
                    $data['oid'] = generateOrderNo('LB', $this->userinfo['id']);
                    $data['billnum'] = $i;
                    $data['money'] = toMoney($loanInfo['money'] / $loanInfo['time']);
                    $data['interest'] = toMoney($loanInfo['money'] * getInterest());
                    $data['overdue'] = 0;
                    $data['repayment_time'] = getNextMonth($i);
                    $data['add_time'] = time();
                    $data['status'] = 0;
                    $loanBill[] = $data;
                }
                $loanBillModel = new Loanbill();
                if(count($loanBill) == 1)
                {
                    $res2 = $loanBillModel->save($loanBill[0]);
                } else {
                    $res2 = $loanBillModel->saveAll($loanBill);
                }
                if($res && $res1 && $res2)
                {
                    // 提交事务
                    Db::commit();
                    //发送短信提示->提现成功
                    orderChangeSmSSend($this->userinfo['id'],3);
                    return $this->port(1, "协议签署成功！");
                }
                // 回滚事务
                Db::rollback();
                return $this->port(0, "协议签署失败！");
            }
            return $this->port(0, "借款订单不存在！");
        }
    }


    /**
     *确认提现结果
     * */
    public function withdrawResult()
    {
        $data = [];
        $data['config'] = SystemConfig::where(["name"=>"production_cost"])->value("value");
        return $this->port(1, 'data', $data);
    }
}
