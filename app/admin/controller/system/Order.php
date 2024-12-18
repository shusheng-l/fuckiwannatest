<?php

namespace app\admin\controller\system;

use \app\admin\model\OrderStatus;
use app\common\model\BankRecord;
use app\common\model\Info as InfoModel;
use app\common\model\Info;
use app\common\model\Loanbill;
use app\common\model\Loanorder as LoanorderModel;
use app\common\model\Loanorder;
use app\common\model\Qblog;
use \app\common\model\User as UserModel;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use app\common\model\SystemConfig;
use app\admin\model\SystemAdmin;
use app\admin\model\SystemAuth;
use think\Db;

/**
 * @ControllerAnnotation(title="借款列表")
 * Class Auth
 * @package app\admin\controller\system
 */
class Order extends AdminController
{

    use \app\admin\traits\Curd;
    protected $sort = [
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new LoanorderModel();
    }

    /**
     * @NodeAnotation(title="资料审核")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            [$page, $limit, $where, $excludeFields] = $this->buildTableParames(['month']);
            // todo TP6框架有一个BUG，非模型名与表名不对应时（name属性自定义），withJoin生成的sql有问题

            /**
             * 查看权限
             * */
            $adminId = session('admin')["id"];
            $authId = SystemAdmin::where(["id"=>$adminId])->value("auth_ids");
            $showAuth = SystemAuth::where(["id"=>$authId])->value("show_all");
            if($showAuth == 1) //不可
            {
                $where[] = ["i.admin_id","=",$adminId];
            } else {
                //连表status用主表的status
                for ($a=0;$a<count($where);$a++)
                {
                    if($where[$a][0] == "adminusername")
                    {
                        $where[$a][2] = str_replace('%','',$where[$a][2]);
                        $adminId = SystemAdmin::where(["username"=>$where[$a][2]])->value("id");
                        if(isset($adminId) && $adminId > 0)
                        {
                            $where[$a][0] = "l.admin_id";
                            $where[$a][1] = "=";
                            $where[$a][2] = $adminId;
                        } else {
                            $where[$a][0] = "l.admin_id";
                            $where[$a][1] = "=";
                            $where[$a][2] = 0;
                        }
                    }
                }
            }

            $where[] = ['l.aus',"=",1];
            $where[] = ['l.qrtx','=',1];//选择已经提现成功的借款订单

            $count = $this->model
                ->alias('l')
                ->join('user u','l.uid = u.id')
                ->join('info i','l.uid = i.uid')
                ->field("l.*,u.username,u.device,u.telnum")
                ->where($where)
                ->count();
            $list = $this->model
                ->alias('l')
                ->join('user u','l.uid = u.id')
                ->join('info i','l.uid = i.uid')
                ->where($where)
                ->page($page, $limit)
                ->field("l.*,u.username,u.device,u.telnum,i.contacts")
                ->order("i.id desc")
                ->select();

            //数据处理
            foreach($list as $k=>$v){
                $list[$k]['oid'] = $list[$k]['oid'] . "<br />备注：" . $list[$k]['remark'];
                //贷款总额
                $list[$k]['allLoanMoney'] = toMoney($v['money'] * $v['time'] * $v['interest']);
                //每期还款
                $bill = Loanbill::where(array('status' => array('in', '0,1'), 'toid' => $list[$k]['id']))->order('repayment_time asc')->find()->toArray();
                if(isset($bill))
                {
                    $list[$k]['quotama'] = toMoney($bill['money'] + $bill['interest'] + $bill['overdue']);
                }else {
                    $list[$k]['quotama'] = round(($list[$k]["money"] * $list[$k]["time"] * $list[$k]["interest"] + $list[$k]["money"]) / $list[$k]["time"], 2);
                }
                //提现时间
                if($list[$k]['withdraw_time'] != "")
                {
                    $list[$k]['withdraw_time'] = date("Y-m-d H:i:s",$list[$k]['withdraw_time']);
                } else {
                    $list[$k]['withdraw_time'] = "未提现";
                }
                //钱包余额
                $list[$k]['qbmoney'] = UserModel::getQbmoney($list[$k]["uid"]);

                $list[$k]['qbzx'] = 0;
                $list[$k]['qbtx'] = 0;
                //新充值
                $qbzx = Qblog::getQblogst($list[$k]['uid']);
                if($qbzx['error'] === "审核中"){
                    $list[$k]['qbzx'] = 1;
                }
                //新提现
                $qbtx = Qblog::getQblogtx($list[$k]['uid']);
                if($qbtx['error'] === "审核中"){
                    $list[$k]['qbtx'] = 1;
                }
                //所属客服
                $list[$k]['adminusername'] = SystemAdmin::where(["id"=>$list[$k]['admin_id']])->value("username");
                //用途
                $list[$k]['dwname'] = json_decode($list[$k]['contacts'],true)['dwname'];
            }

            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="查看资料")
     */
    public function showinfo($id)
    {
        $userId = $this->model->where(["id"=>$id])->value("uid");
        //用户提交审核个人信息
        $userInfo = Info::where(["uid"=>$userId])->field("uid,identity,contacts,bank,status")->find();
        $userModel = new UserModel();
        $row = $userModel->find($userInfo['uid']);
        empty($row) && $this->error('数据不存在');
        if($userInfo['identity'] != "")
        {
            $userInfo['identity'] = json_decode($userInfo['identity'],true);
        }
        if($userInfo['contacts'] != "")
        {
            $userInfo['contacts'] = json_decode($userInfo['contacts'],true);
        }
        if($userInfo['bank'] != "")
        {
            $userInfo['bank'] = json_decode($userInfo['bank'],true);
        }
        $row['userInfo'] = $userInfo;
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="修改订单状态")
     */
    public function changestatus($id)
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $id = $post["id"];
            unset($post["status"]);
            unset($post["id"]);
            if (!$post['color']) {
                $this->error('颜色RGB代码不能为空');
            }
            if (!$post['idbt']) {
                $this->error('订单状态不能为空');
            }
            if (!$post['ixsm']) {
                $this->error('订单说明不能为空');
            }

            $loanOrderInfo = Loanorder::where(["id"=>$id])->field("uid,admin_id")->find();
            $uid = $loanOrderInfo["uid"];
            /**
             * 操作权限
             * */
            $adminId = session('admin')["id"];
            //验证是否是超级管理员
            $adminAuth = session('admin')["auth_ids"];
            if($adminAuth != 1) //不是超级管理员
            {
                if($adminId != $loanOrderInfo["admin_id"]) //不可
                {
                    $this->error('您没有操作此数据的权限！');
                }
            }

            $res = Loanorder::where(["id"=>$id])->save(array( 'tco' => $post['color'],'dbt' => $post['idbt'],'error' => $post['ixsm'],'xbzmark' => $post['ixbzmark'],"order_status"=>1));
            //发送短信提示->状态失败
            if($res) orderChangeSmSSend($uid,4,$post['idbt'],$post['ixsm']);
            $res ? $this->success('订单操作成功') : $this->error('订单操作失败');
        }
        //所有可用订单状态
        $orderStatues = OrderStatus::field("id,title,status_title,remark,content,color")->order("sort desc")->select()->toArray();
        $nowOrderStatues = array();
        for ($a=0;$a<count($orderStatues);$a++)
        {
            $nowOrderStatues[$orderStatues[$a]['id']] = $orderStatues[$a];
        }
        $this->assign("orderStatuses",$nowOrderStatues);
        $this->assign('id', $id);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="用户余额显示")
     */
    public function changemoney($id)
    {
        $infos = Loanorder::where(array('id' => $id))->find();
        $username = $infos['name'];
        $uid = $infos['uid'];
        /* 钱包 充值取现记录  还款记录 */
        $qbmoney = UserModel::getInfo('id', $uid, 'qbmoney');
        $qbmark = UserModel::getInfo('id', $uid, 'qbmark');
        //充值记录列表
        $qblogczlist = Qblog::getQbloglist($uid,1);
        $this->assign('qblogczlist', $qblogczlist);
        //取现记录列表
        $qblogqxlist = Qblog::getQbloglist($uid,2);
        $this->assign('qblogqxlist', $qblogqxlist);
        //还款记录列表
        $hklist = Qblog::getQbbilllist($id);
        $this->assign('hklist', $hklist);

        $this->assign('qbmark', $qbmark);
        $this->assign('qbmoney', $qbmoney);
        $this->assign('uid', $uid);
        $this->assign('username', $username);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="余额操作")
     */
    public function savemoney()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $uid = $post['uid'];
            $money = $post['amount'];
            if (!$uid) {
                $this->error('请传递用户id');
            }
            $money = toMoney($money);
            $ismo = floatval(999999.00);
            $ism = bcsub($ismo, $money, 2);
            if ($ism < 1){
                $this->error('提交失败,单笔充值金额不能大于999999');
            }
            $result = Qblog::htaddlog($uid , $money);
            if (!$result) {
                $this->error('充值订单提交失败,请重试');
            }
            $usermoney = UserModel::updateQbmoney($uid, $money ,1);
            if ($usermoney != 1) {
                //回滚记录
                Qblog::where(array('id' => $result))->save(array('status' => '0','qr_time' => time()));
                $this->error('用户钱包充值失败');
            }
            $this->success('充值成功');
        }
    }

    /**
     * @NodeAnotation(title="后台划款")
     */
    public function czqxs()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $id = $post['id'];
            $bz = $post['bz'];
            $status = $post['sts'];
            $bzt ='';
            if($bz == 1){
                $bzt ='充值';
            }else if($bz == 2){
                $bzt ='取现';
            }
            $qblog = Qblog::where(array('id' => $id))->find();
            if (!$qblog) {
                $this->error($bzt.'记录不存在');
            }
            $times=time();
            $retbz = 0;
            $retbz = $qblog['bz'];
            if($retbz == '1' || $retbz == '2'){

            }else{
                $this->error($bzt.'记录操作状态异常');
            }

            $result = Qblog::where(array('id' => $id))->save(array('status' => '1','qr_time' => $times));
            if (!$result) {
                $this->error($bzt.'记录操作失败');
            }
            $money = $qblog['money'];
            $uid = $qblog['uid'];
            if($bz != 2) //提现已提前减少余额
            {
                $usermoney = UserModel::updateQbmoney($uid, $money ,$retbz);
            } else {
                $usermoney = 1;
            }
            if ($usermoney != 1){
                //回滚记录
                Qblog::where(array('id' => $id))->save(array('status' => '0','qr_time' => $times));
                if ($usermoney == 2) {
                    $this->error('用户钱包实际余额不足,取现操作确认失败');
                }else{
                    $this->error('用户钱包操作失败');
                }
            }
            $this->success('操作成功');
        }
    }

    /**
     * @NodeAnotation(title="后台删除记录")
     */
    public function dellog()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $id = $post['id'];
            $bz = $post['bz'];
            $bzt = '';
            if($bz == 1){
                $bzt = '充值';
            }else if($bz == 2){
                $bzt = '取现';
            }
            $loginfo = Qblog::where(array('id' => $id))->find();
            if (!$loginfo) {
                $this->error($bzt.'该记录不存在');
            }
            $result = Qblog::where(array('id' => $id))->delete();
            if (!$result) {
                $this->error($bzt.'订单操作失败');
            }
            $this->success($bzt.'删除成功');
        }
    }

    /**
     * @NodeAnotation(title="合同")
     */
    public function contract($id)
    {
        $loanInfo = Loanorder::where(array('id' => $id))->find();
        $this->assign('data', $loanInfo);
        //获取显示模板
        //相关配置
        $infoConfigs = SystemConfig::where('name','in',["contact_module",'true_code',"company_logor"])
            ->field("value")
            ->order("sort desc")
            ->select()
            ->toArray();
        $contractTpl = $infoConfigs[0]['value'];
        $contractTpl = htmlspecialchars_decode(htmlspecialchars_decode($contractTpl));
        //信用代码
        $trueCode = $infoConfigs[1]['value'];
        //公章
        $officeLoge = $infoConfigs[2]['value'];
        $officeLogeImg = '<img src="' . $officeLoge . '" style="width: 110px;position: absolute;left: 0;bottom: 0;" />';
        $loanData = json_decode($loanInfo['data'], true);
        $timeType = '月';
        if ($timeType == '月') {
            $endTime = strtotime('+' . intval($loanInfo['time']) . ' Month', $loanInfo['start_time']);
        } else {
            $endTime = strtotime('+' . intval($loanInfo['time']) . ' Day', $loanInfo['start_time']);
        }
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
        $this->assign('tpl', $contractTpl);

        $this->assign('id', $id);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="改卡")
     */
    public function changecard($id)
    {
        $this->assign('id', $id);
        $uid = Loanorder::where(array('id' => $id))->value("uid");
        $allBankInfo = Info::where(["uid"=>$uid])->field("bank,orgin_bank_type,orgin_bank_card")->find();
        $bankInfo = json_decode($allBankInfo['bank'], true);
        //记录
        $records = BankRecord::where(["uid"=>$uid])->limit(100)->select();
        $this->assign("uid",$uid);
        $this->assign("bankInfo",$bankInfo);
        $this->assign("records",$records);
        $this->assign("allBankInfo",$allBankInfo);
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            if($post['evbankname'] == "")
            {
                $this->error('请填写银行!');
            }
            if($post['evbanknum'] == "")
            {
                $this->error('请填写卡号!');
            }
            $bankInfo["bankname"] = $post['evbankname'];
            $bankInfo["bankcard"] = $post['evbanknum'];
            $res = Info::where(["uid"=>$uid])->save(["bank"=>json_encode($bankInfo),'add_time' => time(),'bank_status'=>1]);
            $res1 = Loanorder::where(array('id' => $id))->save(array('bankname' => $post['evbankname'],'banknum' => $post['evbanknum']));
            //添加记录
            $bankRecord['uid'] = $uid;
            $bankRecord["bankname"] = $post['evbankname'];
            $bankRecord["banknum"] = $post['evbanknum'];
            $bankRecord["time"] = time();
            $res2 = BankRecord::create($bankRecord);
            if($res && $res1 && $res2)
            {
                $this->success('操作成功');
            }
            $this->error('操作失败');
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除")
     */
    public function deleter($id)
    {
        Loanbill::where(array('toid' => 21))->delete();
        if ($this->request->isAjax()) {
            $info = Loanorder::where(array('id' => $id))->find();
            if (!$info) {
                $this->error('该订单不存在');
            }
            $res = Loanorder::where(array('id' => $id))->delete();
            if (!$res) {
                $this->error('订单操作失败');
            }
            // 账单删除
            $res1 = Loanbill::where(array('toid' => $info['id']))->delete();
            // 删除用户的申请
            $res2 = \app\common\model\User::where(array('id' => $info['uid']))->save(['quota' => '0','qbmoney'=>'0']);
            $res3 = Info::where(array('uid' => $info['uid']))->save(['addess' => '', 'status' => 0,"qrtx"=>0]);
            if($res && $res1 && $res2 && $res3)
            {
                $this->success('操作成功');
            }
            $this->error('操作失败');
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="账户状态操作")
     */
    public function doaccount($id)
    {
        if ($this->request->isAjax()) {
            $info = Loanorder::where(array('id' => $id))->find();
            if (!$info) {
                $this->error('该订单不存在');
            }
            if($info['account_status'] == 1)
            {
                $res = Loanorder::where(array('id' => $id))->save(["account_status"=>0]);
            } else {
                $res = Loanorder::where(array('id' => $id))->save(["account_status"=>1]);
            }
            if($res)
            {
                $this->success('操作成功');
            }
            $this->error('操作失败');
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="备注")
     */
    public function remark($id)
    {
        $this->assign('id', $id);
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            if($post['remark'] == "")
            {
                $this->error('请填写备注!');
            }
            $res = Loanorder::where(array('id' => $id))->save(array('remark' => $post['remark']));
            if($res)
            {
                $this->success('操作成功');
            }
            $this->error('操作失败');
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="借款详情")
     */
    public function loaninfo($id)
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            if($post['quota'] <= 0)
            {
                $this->error('请填写正确的授权额度!');
            }
            if($post['month'] < 1)
            {
                $this->error('请填写正确的授权期数!');
            }
            //用户id
            $info = Info::where(["id"=>$post['id']])->find();
            if(!isset($info))
            {
                $this->error('该用户不存在!');
            }

            /**
             * 操作权限
             * */
            /*$adminId = session('admin')["id"];
            //验证是否是超级管理员
            $adminAuth = session('admin')["auth_ids"];
            if($adminAuth != 1) //不是超级管理员
            {
                if($adminId != $info["admin_id"]) //不可
                {
                    $this->error('您没有操作此数据的权限！');
                }
            }*/

            $id = $post['id']; //info表id
            $uid = $info['uid']; //用户id
            $quota = $post['quota']; //额度
            $addess=json_decode($info['addess'],true);
            $addess['amount'] = toMoney($quota); //额度
            $addess['month'] = $post['month']; //期数
            $res = Info::where(array('id' => $id))->save(array('addess' => json_encode($addess),"update_time"=>time()));
            // 更新用户额度
            $res1 = UserModel::where(["id"=>$uid])->save(array('quota' => toMoney($quota),"update_time"=>time()));
            // 更新订单
            $loaninfo = Loanorder::where(array('uid' => $uid, 'pending' => 1, 'qrtx' => 1))->order('id desc')->find();
            $orderId = $loaninfo['id'];
            unset($loaninfo['id']);
            $nowLoaninfo['money'] = toMoney($quota);
            $nowLoaninfo['time'] = $post['month'];
            $res2 = Loanorder::where(["id"=>$orderId])->save($nowLoaninfo);

            //删除充值记录和余额
            $res5 = Qblog::where(array('uid' => $uid,'status' => 1, 'isadmin' => 1 ,'bz' => 1,'isend' => 1))->delete();
            $res6 = UserModel::where(["id"=>$uid])->save(["qbmoney"=>0]);
            //充值
            $res3 = Qblog::htaddlogSepc($uid,toMoney($quota));
            $res4 = UserModel::updateQbmoney($uid, toMoney($quota) ,1);
            if ($res && $res1 && $res2 && $res3 && $res4 && $res5/* && $res6*/) {
                //重新生成分期记录
                //借款订单数据
                $loanInfo = Loanorder::where(['uid' => $uid, 'pending' => 1, 'qrtx' => 1])->order("id desc")->find();
                if(isset($loanInfo))
                {
                    //每期还款金额
                    $periodMoney = toMoney(($loanInfo['money'] * getInterest() * $loanInfo['time'] + $loanInfo['money']) / $loanInfo['time']);
                    $res1 = Loanbill::where(['uid'=>$loanInfo['uid']])->delete();
                    // 生成账单
                    $loanBill = [];
                    for ($i = 1; $i <= $loanInfo['time']; $i ++) {
                        $data['uid'] =  $loanInfo['uid'];
                        $data['toid'] = $loanInfo['id'];
                        $data['oid'] = generateOrderNo('LB', $uid);
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

                    if(!$res1 || !isset($res2))
                    {
                        $this->error('操作失败');
                    }
                }
                //发送短信提示->审核通过
                orderChangeSmSSend($uid,2);
                //发送状态短信
                $userInfo = UserModel::where(["id"=>$uid])->field("telnum,username")->find();
                sendStatusMsg($userInfo['username'],$userInfo['telnum']);
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }

        $infos = $this->model
                    ->alias('l')
                    ->join('user u','l.uid = u.id')
                    ->join('info i','l.uid = i.uid')
                    ->where(["l.id"=>$id])
                    ->field("l.*,u.username,u.device,u.telnum,i.id as infoid")
                    ->find();

        //贷款总额
        $infos['allLoanMoney'] = toMoney($infos['money'] * $infos['time'] * $infos['interest']);
        //每期还款
        $bill = Loanbill::where(array('status' => array('in', '0,1'), 'toid' => $infos['id']))->order('repayment_time asc')->find()->toArray();
        if(isset($bill))
        {
            $infos['quotama'] = toMoney($bill['money'] + $bill['interest'] + $bill['overdue']);
        }else {
            $infos['quotama'] = round(($infos["money"] * $infos["time"] * $infos["interest"] + $infos["money"]) / $infos["time"], 2);
        }
        //钱包余额
        $infos['qbmoney'] = UserModel::getQbmoney($infos["uid"]);
        $this->assign("infos",$infos);
        return $this->fetch();
    }

}