<?php

namespace app\admin\controller;

use app\admin\model\SystemConfig;
use app\common\controller\AdminController;
use app\common\model\Loanorder;
use think\App;

class Screen extends AdminController
{
    /**
     * 截图打印
     * @return string
     * @throws \Exception
     */
    public function movescreen($id)
    {
        $loanInfo = Loanorder::where(['id' => $id])->find();
        $moveTips = SystemConfig::where(["name"=>"move_tips"])->value("value");
        $this->assign('data', $loanInfo);
        //	$loanData = json_decode($loanInfo['data'], true);
        //	$txtime = date('Y/m/d', $loanInfo['add_time']).'</p>';
        $txtime = date('Y/m/d').'</p>';
        $view_hd  = '<div class="pp"><div class="tits">转账批次号：</div><div class="co">5015754555</div></div>';
        $view_hd .= '<div class="pp"><div class="tits">转账类型：</div><div class="co">签约金融企业--网贷放款预约转账</div></div>';
        $view_hd .= '<div class="pp"><div class="tits">转出账户：</div><div class="co">16602808015501</div></div>';
        $view_hd .= '<div class="pp"><div class="tits">收款人姓名：</div><div class="co">'. $loanInfo['name'].'</div></div>';
        $view_hd .= '<div class="pp"><div class="tits">收款银行：</div><div class="co">'. $loanInfo['bankname'].'</div></div>';
        $view_hd .= '<div class="pp"><div class="tits">收款账户：</div><div class="co">'. $loanInfo['banknum'].'</div></div>';
        $view_hd .= '<div class="pp"><div class="tits">币种：</div><div class="co">人民币元</div></div>';
        $view_hd .= '<div class="pp"><div class="tits">转账金额：</div><div class="co">'. toMoney($loanInfo['money']).'</div></div>';
        $view_hd .= '<div class="pp"><div class="tits">转账时间：</div><div class="co">'. $txtime.'</div></div>';
        $view_hd .= '<div class="pp"><div class="tits">执行方式：</div><div class="co">实时到账</div></div>';
        $view_hd .= '<div class="pp"><div class="tits">状态：</div><div class="co"><span class="tip1" style="color:#006600;">转账失败</span></div></div>';
        $view_hd .= '<div class="pp"><div class="tits">银行备注：</div><div class="co">'. $moveTips . '</div></div>';
        $view_hd .= '<div class="pp"><div class="tits">处理结果：</div><div class="co"><span style="color:#E53333;">未处理</span></div></div>';
        $view_hd .= '<div class="pp"><div class="tits">用户备注：</div><div class="co tip2"></div></div>';
        $this->assign('tpl', $view_hd);
        return $this->fetch();
    }

    /**
     * 保险打印
     * @return string
     * @throws \Exception
     */
    public function saftscreen($id)
    {
        $loanInfo = Loanorder::where(['id' => $id])->find();
        $this->assign('data', $loanInfo);
        //	$loanData = json_decode($loanInfo['data'], true);
        $txtime = date('Y/m/d；H:i:s', $loanInfo['add_time']).'</p>';
        $lix = 5; //可调整参数
        $l_money = $lix * $loanInfo['money'] * 0.01;
        $lix_money = toMoney($l_money);
        $lix_money_60 = toMoney($l_money * 0.6);
        $timeType = $loanInfo['timetype'] == 1 ? '月' : '日';
        if ($timeType == '月') {
            $endTime = strtotime('+' . intval($loanInfo['time']) . ' Month', $loanInfo['start_time']);
        } else {
            $endTime = strtotime('+' . intval($loanInfo['time']) . ' Day', $loanInfo['start_time']);
        }
        //var_dump($loanInfo);die;
        $view_hd  = '<td align="center" bgcolor="#fff">金融网贷商业险</td>';
        $view_hd .= '<td align="center" bgcolor="#fff">贷款订单号'. $loanInfo['oid'].'</br>贷款金额'. toMoney($loanInfo['money']).'元整</td>';
        $view_hd .= '<td align="center" bgcolor="#fff">'. $loanInfo['name'].'</td>';
        $view_hd .= '<td align="center" bgcolor="#fff">'. $lix_money.'元整</td>';
        $view_hd .= '<td align="center" bgcolor="#fff">'. date('Y年m月', $loanInfo['start_time']).'至'.date('Y年m月', $endTime).'</td>';
        $view_hd .= '<td align="center" bgcolor="#fff">未生效</td>';
        $view_hd .= '<td align="center" bgcolor="#fff">电子保单</td>';
        $view_hd .= '<td align="center" bgcolor="#fff"><span style="color:#003399;">贷款合同 已上传√</br>投保人资料 已上传√</span></td>';
        $lix_money_90 = "";
        $smm = $loanInfo['vicsv'];
        $this->assign('smm', $smm);
        $this->assign('smm6', $lix_money_90);
        $this->assign('tpl', $view_hd);
        return $this->fetch();
    }

}
