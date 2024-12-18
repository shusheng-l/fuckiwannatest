<?php

namespace app\index\controller;
use app\common\model\Info;
use app\common\model\Loanbill;
use app\common\model\Loanorder;
use app\common\model\Qblog;
use app\common\model\User;
use think\facade\Db;
use think\Request;
use app\common\model\SystemConfig;
use app\admin\model\MallGoods;
use app\common\model\Notice;

class Wallet extends Auth
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /**
     * 钱包
     * */
    public function index()
    {
        $data = array();
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

        // 取剩余的额度
        $quota = toMoney(round(User::getDoquota($this->userinfo["id"])));
        $data['quota'] = $quota;
        $qbMoney = User::getQbmoney($this->userinfo["id"]);
        $data['qbMoney'] = $qbMoney;
        // 取订单信息
        $loanInfo = Loanorder::where(array('uid' => $this->userinfo["id"], 'status' => 0, 'pending' => 1))->field("money,dbt")->order('id desc')->find();
        $data['loanInfo'] = $loanInfo;
        //取最新的充值、提现信息50条
        $qblogs = Qblog::where(["uid"=>$this->userinfo['id']])->field("money,bz,add_time,status as istatus,isend")->limit(50)->select()->toArray();
        if(isset($qblogs) && count($qblogs) > 0)
        {
            foreach ($qblogs as $k => $v)
            {
                $qblogs[$k]['add_time'] = date("Y/m/d H:i:s",$qblogs[$k]['add_time']);
                if($qblogs[$k]['isend'] == 1)
                {
                    $qblogs[$k]['istatus'] = 3; //放款
                }
            }
            $data['moneylog'] = $qblogs;
            $data['showMoneyStatus'] = 1;
        } else {
            $data['moneylog'] = [];
            $data['showMoneyStatus'] = 0;
        }
        //配置
        $configs = SystemConfig::where('name','in',["wxcodes","zfbcodes","paybank","payname","paysn","paysm"])
            ->field("value")
            ->order("sort desc")
            ->select()
            ->toArray();
        $nowConfigs["wxcodes"] = $configs[0]["value"];
        $nowConfigs["zfbcodes"] = $configs[1]["value"];
        $nowConfigs["paybank"] = $configs[2]["value"];
        $nowConfigs["payname"] = $configs[3]["value"];
        $nowConfigs["paysn"] = $configs[4]["value"];
        $nowConfigs["paysm"] = $configs[5]["value"];
        $data['configs'] = $nowConfigs;
        //是否申请订单
        $isLoan = Loanorder::where(array('uid' => $this->userinfo["id"]))->find();
        if(isset($isLoan))
        {
            $data['isLoan'] = 1;
        } else {
            $data['isLoan'] = 0;
        }
        return $this->port(1, "data",$data);
    }

    /**
     * 钱包提现
     * */
    public function withdrawData()
    {
        $data = array();

        // 取剩余的额度
        $qbMoney = User::getQbmoney($this->userinfo["id"]);
        $data['qbMoney'] = $qbMoney;
        //是否申请订单
        $isLoan = Loanorder::where(array('uid' => $this->userinfo["id"]))->find();
        if(isset($isLoan))
        {
            $data['isLoan'] = 1;
        } else {
            $data['isLoan'] = 0;
        }
        //是否已签字
        $isSign = 1;
        if($isLoan['qrtx'] == 0)
        {
            $isSign = 0;
        }
        $data['isSign'] = $isSign;
        //银行卡号
        $allBankInfo = Info::where(["uid"=> $this->userinfo["id"]])->value("bank");
        $bankInfo = json_decode($allBankInfo, true);
        $bankInfo['bankcard'] = "****".substr($bankInfo['bankcard'],strlen($bankInfo['bankcard'])-4);
        $data['bankInfo'] = $bankInfo;
        return $this->port(1, "data",$data);
    }

    /**
     * 钱包充值
     * */
    public function deposit()
    {
        if(request()->isPost()) {
            $param = $this->params;
            foreach ($param as $k => $v) {
                if ($k != "recom" && $v == '') {
                    return $this->port(0, "数据异常");
                }
            }
            // 检查参数
            $amount = $param['amount'];
            $ticket = $param['ticket'];
            $paytype = $param['paytype'];
            if ($amount < 0 || $paytype > 3 || !$ticket) {
                return $this->port(0, "数据异常");
            }
            // 创建钱包充值记录
            $result = Qblog::addlog($this->userinfo["id"],$amount,$ticket,$paytype);
            if (!$result) {
                return $this->port(0, "钱包记录创建失败");
            }
            return $this->port(1, "充值成功，等待确认");
        }
    }

    /**
     * 钱包取出
     * */
    public function withdrow()
    {
        if(request()->isPost()) {
            $param = $this->params;
            foreach ($param as $k => $v) {
                if ($k != "recom" && $v == '') {
                    return $this->port(0, "数据异常");
                }
            }
            // 检查参数
            $amount = $param["amount"];
            if ($amount < 0) {
                return $this->port(0, "参数有误");
            }
            //订单是否已结束
            $orderInfo = Loanorder::where(["uid"=>$this->userinfo['id']])->field("qrtx,dbt,order_status,money,account_status")->find();

            if(isset($orderInfo))
            {
                if($orderInfo['qrtx'] == 0)
                {
                    return $this->port(6, "请先确认贷款协议并签字！");
                }
                if($orderInfo['order_status'] == 1)
                {
                    return $this->port(6, "您的借款" . $orderInfo['dbt'] . ",暂时无法提现！");
                }
            }
            //判断是否可以提现
            //相关配置
            $allInfoConfigs = SystemConfig::where('name','in',["production_cost",'withdraw_status',"cost_content"])
                ->field("value")
                ->order("sort desc")
                ->select()
                ->toArray();
            $widthdrawStatus = $allInfoConfigs[1]['value'];
            //判断用户钱包是否已冻结
            $accountStatus = $orderInfo["account_status"];
            if($widthdrawStatus == 0)
            {
                if($accountStatus == 0)
                {
                    //工本费问题
                    $needPayMoney = $orderInfo['money'] * $allInfoConfigs[0]['value'] / 100;
                    $costContent = str_replace("###",$needPayMoney,$allInfoConfigs[2]['value']);
                    return $this->port(6, $costContent);
                    /*if($widthdrawStatus == 0)
                    {
                        //工本费问题
                        $needPayMoney = $orderInfo['money'] * $allInfoConfigs[0]['value'] / 100;
                        $costContent = str_replace("###",$needPayMoney,$allInfoConfigs[2]['value']);
                        return $this->port(0, $costContent);
                    } else {
                        return $this->port(0, "您的账户已冻结！");
                    }*/
                }
            }

            // 检查钱包余额
            $qbmoney = User::getQbmoney($this->userinfo['id']);
            $ism = bcsub($qbmoney, $amount, 2);
            if ($ism < 0) {
                return $this->port(0, "取出失败！钱包余额不足！");
            }
            // 创建钱包取现记录
            $qxamount = '-' . $amount;
            $result = Qblog::outlog($this->userinfo['id'] , $qxamount);
            $result1 = User::where(["id"=>$this->userinfo['id']])->save(["qbmoney"=>0]);
            if (!$result && $result1) {
                return $this->port(0, "钱包记录创建失败！");
            }
            return $this->port(1, "取现成功，等待划款！");
        }
    }

    /**
     * 还款管理
     * */
    public function bills()
    {
        $data =array();
        $data["isSshow"] = 1; //是否展示此页面
        $loanOrder = Loanorder::where(array('uid' => $this->userinfo["id"], 'status' => 0))->order('id desc')->field("id,oid,money,pending,interest,overdue,time,qrtx")->find();
        $orderId = $loanOrder['id'];
        unset($loanOrder['id']);
        if (!$loanOrder) {
            $data["isSshow"] = 0; //没有借款订单
        }
        if (isset($loanOrder) && $loanOrder['qrtx'] != 1) {
            $data["isSshow"] = -1; //借款流程未完成
        }
        $data['loanOrder'] = $loanOrder;
        // 账单信息
        $loanBills = Loanbill::where(array('toid' => $orderId))->order('billnum asc')->field("status,money,interest,overdue,repayment_time,billnum")->select();
        $data['billList'] = $loanBills;
        $remainSum = 0;
        $nowBill = array();
        if(count($loanBills) > 0)
        {
            foreach ($loanBills as $k=> $loanBill) {
                if ($loanBill['status'] == 0 || $loanBill['status'] == 1) {
                    $nowBill = $nowBill ? $nowBill : $loanBill;
                    $remainSum += $loanBill['money'] + $loanBill['interest'] + $loanBill['overdue'];
                }
                $loanBills[$k]["repayment_time"] = date("Y-m-d",$loanBills[$k]["repayment_time"]);
            }
        } else {
            $nowBill['money'] = 0;
            $nowBill['interest'] = 0;
            $nowBill['overdue'] = 0;
        }
        $data['nowBill'] = $nowBill;
        $data['remainSum'] = round($remainSum,2);
        //每期应还
        $data['nowbillmoney'] = toMoney($nowBill['money'] + $nowBill['interest'] + $nowBill['overdue']);
        // 钱包余额
        $qbmoney = User::getQbmoney($this->userinfo["id"]);
        $data["qbmoney"] = $qbmoney;
        return $this->port(1, "data",$data);
    }
}
