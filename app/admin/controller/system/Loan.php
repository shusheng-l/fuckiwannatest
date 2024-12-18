<?php

namespace app\admin\controller\system;


use app\admin\model\SystemAdmin;
use app\admin\model\SystemAuth;
use app\common\model\Info as InfoModel;
use app\common\model\Info;
use app\common\model\Loanorder;
use \app\common\model\User as UserModel;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use Symfony\Component\VarDumper\Tests\Fixture\DumbFoo;
use think\App;
use app\common\model\SystemConfig;
use app\common\model\Qblog;

/**
 * @ControllerAnnotation(title="借款管理")
 * Class Auth
 * @package app\admin\controller\system
 */
class Loan extends AdminController
{

    use \app\admin\traits\Curd;
    protected $sort = [
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new InfoModel();
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
            }
            //连表status用主表的status
            for ($a=0;$a<count($where);$a++)
            {
                if($where[$a][0] == "status")
                {
                    $where[$a][0] = "i." . $where[$a][0];
                }
                if($where[$a][0] == "qbtx")
                {
                    $where[$a][0] = "i.qrtx";
                }
                if($where[$a][0] == "adminusername")
                {
                    $where[$a][2] = str_replace('%','',$where[$a][2]);
                    $adminId = SystemAdmin::where(["username"=>$where[$a][2]])->value("id");
                    if(isset($adminId) && $adminId > 0)
                    {
                        $where[$a][0] = "i.admin_id";
                        $where[$a][1] = "=";
                        $where[$a][2] = $adminId;
                    } else {
                        $where[$a][0] = "i.admin_id";
                        $where[$a][1] = "=";
                        $where[$a][2] = 0;
                    }
                }
            }

            $count = $this->model
                ->alias('i')
                ->join('user u','i.uid = u.id')
                ->join('system_admin s','i.admin_id = s.id')
                ->field("i.*,u.username,u.device,u.telnum,s.username as adminusername")
                ->where($where)
                ->count();
            $list = $this->model
                ->alias('i')
                ->join('user u','i.uid = u.id')
                ->join('system_admin s','i.admin_id = s.id')
                ->where($where)
                ->page($page, $limit)
                ->field("i.*,u.username,u.device,u.telnum,s.username as adminusername")
                ->order("i.id desc")
                ->select();

            //数据处理
            foreach($list as $k=>$v){
                //预期金额
                if(isset(json_decode($v['addess'],true)['amount']))
                {
                    $list[$k]['amount'] = json_decode($v['addess'],true)['amount'];
                } else {
                    $list[$k]['amount'] = 0;
                }
                //审批金额
                $list[$k]['quotama'] = UserModel::getDoquota($v['uid']);
                //是否已提现
                $list[$k]['qbtx'] = 0; //默认未提现
                $qbtx = Loanorder::where(["uid"=>$v['uid']])->value("qrtx");
                if(isset($qbtx))
                {
                    $list[$k]['qbtx'] = $qbtx;
                }
                //资料是否完善
                $list[$k]['data_status'] = 0;
                if($v['identity'] != "" && $v['contacts'] != "" && $v['bank'] != "")
                {
                    $list[$k]['data_status'] = 1;
                }
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

        //用户提交审核个人信息
        $userInfo = $this->model->where(["id"=>$id])->field("uid,identity,contacts,bank,status")->find();
        $userModel = new UserModel();
        $row = $userModel->find($userInfo['uid']);
        empty($row) && $this->error('数据不存在');
        if($userInfo['identity'] != "")
        {
            $userInfo['identity'] = json_decode($userInfo['identity'],true);
            $identity = $userInfo['identity'];
        }
        if($userInfo['contacts'] != "")
        {
            $userInfo['contacts'] = json_decode($userInfo['contacts'],true);
        }
        if($userInfo['bank'] != "")
        {
            $userInfo['bank'] = json_decode($userInfo['bank'],true);
        }
        if ($this->request->isAjax()) { //post处理
            $post = $this->request->post();
            //个人资料
            $iData = [
                'username' => $post['username'],
                'usercard' => $post['usercard'],
                'cardphoto_1' => $identity['cardphoto_1'],
                'cardphoto_2' => $identity['cardphoto_2'],
                'takecardphoto' => $identity['takecardphoto'],
            ];
            //家庭资料
            $contacts = [
                'company' => $post['company'],
                'dwname' => $post['dwname'],
                'dwaddess_ssq' => $post['dwaddess_ssq'],
                'dwaddess_more' => $post['dwaddess_more'],
                'position' => $post['position'],
                'workyears' => $post['workyears'],
                'dwphone' => $post['dwphone'],
                'dwysr' => $post['dwysr'],
                'addess_ssq' => $post['addess_ssq'],
                'addess_more' => $post['addess_more'],
                'personname_1' => $post['personname_1'],
                'personname_2' => "",
                'personphone_1' => $post['personphone_1'],
                'personphone_2' => "",
                'persongx_1' => $post['persongx_1'],
                'persongx_2' => "",
            ];
            //银行卡
            $bankInfo = [
                'bankname' => $post['bankname'],
                'bankcard' => $post['bankcard'],
            ];
            //数据保存
            //判断用户名是否要更新
            $res = true;
            $saveArray = [];
            $isUpdate = false;
            if($row['username'] != $post['username'])
            {
                $isUpdate = true;
                $saveArray['username'] = $post['username'];
            }
            $reg_ssq = explode(' ', $post['addess_ssq']);
            if($row['reg_city'] != $reg_ssq[0])
            {
                $isUpdate = true;
                $saveArray['reg_city'] = $reg_ssq[0];
            }
            if($isUpdate == true) //是否要更新
            {
                $res = UserModel::where(["id"=>$userInfo['uid']])->save($saveArray);
            }
            //保存info表
            $res1 = Info::where(["uid"=>$userInfo['uid']])->save(['identity' => json_encode($iData),'contacts' => json_encode($contacts),'bank' => json_encode($bankInfo)]);
            if($res && $res1)
            {
                $this->success('操作成功');
            }
            $this->error('操作失败');

        }
        $row['userInfo'] = $userInfo;
        //配置
        $config = [];
        $configs = SystemConfig::where('name','in',['is_show_address','load_purpose','monthly_income','relation_ship',"is_show_jop",'bank_type'])
            ->field("value")
            ->order("sort desc")
            ->select()
            ->toArray();
        //配置数组
        $config['loadPurpose'] = array_filter(array_unique(explode(",",$configs[1]['value']))); //去重去空
        $config['monthlyIncome'] = array_filter(array_unique(explode(",",$configs[2]['value']))); //去重去空
        $config['relationShip'] = array_filter(array_unique(explode(",",$configs[3]['value']))); //去重去空
        $config['bankType'] = array_filter(array_unique(explode(",",$configs[5]['value']))); //去重去空
        $this->assign([
            'row' => $row,
            'config' => $config,
        ]);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="重置资料")
     */
    public function resetinfo($id)
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            if($post['action'] == "")
            {
                $this->error('请选择重置类型');
            }
            $info = $this->model->where(["id"=>$post['id']])->field("identity,contacts,bank,uid,admin_id")->find()->toArray();

            /**
             * 操作权限
             * */
            $adminId = session('admin')["id"];
            //验证是否是超级管理员
            $adminAuth = session('admin')["auth_ids"];
            if($adminAuth != 1) //不是超级管理员
            {
                if($adminId != $info["admin_id"]) //不可
                {
                    $this->error('您没有操作此数据的权限！');
                }
            }
            $uid = $info['uid']; //用户id
            unset($info['uid']);
            if(!isset($info))
            {
                $this->error('资料索引不存在');
            }
            if ($post['action'] == 'all') {
                foreach ($info as $key => $val) {
                    $info[$key] = '';
                }
            } else {
                $info[$post['action']] = '';
            }
            $info['addess'] = ''; //借款信息重置
            $info['status'] = 0; //状态重置
            $res = Info::update($info,["id"=>$post["id"]]);
            //订单数据删除
            Loanorder::where(['uid'=>$uid])->order("id desc")->delete();
            $res ? $this->success('操作成功') : $this->error('操作失败');
        }
        $this->assign('id', $id);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="添加客服")
     */
    public function addqq($id)
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            if($post['mark'] == "")
            {
                $this->error('请填写QQ客服号!');
            }
            $info = $this->model->where(["id"=>$post['id']])->find()->toArray();
            if(!isset($info))
            {
                $this->error('资料索引不存在');
            }
            $saveinfo['mark'] = $post['mark'];
            $res = Info::update($saveinfo,["id"=>$post["id"]]);
            $res ? $this->success('操作成功') : $this->error('操作失败');
        }
        $this->assign('id', $id);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="不符合条件")
     */
    public function reject($id)
    {
        if ($this->request->isAjax()) {

            /**
             * 操作权限
             * */
            $info = $this->model->where(["id"=>$id])->field("identity,contacts,bank,uid,admin_id")->find()->toArray();
            $adminId = session('admin')["id"];
            //验证是否是超级管理员
            $adminAuth = session('admin')["auth_ids"];
            if($adminAuth != 1) //不是超级管理员
            {
                if($adminId != $info["admin_id"]) //不可
                {
                    $this->error('您没有操作此数据的权限！');
                }
            }

            $res = Info::setStatus($id,-1);
            $res ? $this->success('操作成功') : $this->error('操作失败');
        }
    }

    /**
     * @NodeAnotation(title="二审通过并授额")
     */
    public function secondaudit($id)
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
            $info = $this->model->where(["id"=>$post['id']])->find();
            if(!isset($info))
            {
                $this->error('该用户不存在!');
            }

            /**
             * 操作权限
             * */
            $adminId = session('admin')["id"];
            //验证是否是超级管理员
            $adminAuth = session('admin')["auth_ids"];
            if($adminAuth != 1) //不是超级管理员
            {
                if($adminId != $info["admin_id"]) //不可
                {
                    $this->error('您没有操作此数据的权限！');
                }
            }

            $id = $post['id']; //info表id
            $uid = $info['uid']; //用户id
            $quota = $post['quota']; //额度
            //设置info状态，并更新受额数据
            //$res = Info::setStatus($id,2);
            $addess=json_decode($info['addess'],true);
            $addess['amount'] = toMoney($quota); //额度
            $addess['month'] = $post['month']; //期数
            $res = Info::where(array('id' => $id))->save(array('addess' => json_encode($addess),"status" => 2));
            // 更新用户额度
            $res1 = UserModel::where(["id"=>$uid])->save(array('quota' => toMoney($quota)));
            //获取配置参数
            $configs = SystemConfig::where('name','in',['adopt_dbt','adopt_sm','adopt_color','adopt_bz'])
                ->field("value")
                ->order("sort desc")
                ->select()
                ->toArray();
            // 更新订单
            $loaninfo = Loanorder::where(array('uid' => $uid, 'pending' => 0, 'status' => 0))->order('id desc')->find();
            $orderId = $loaninfo['id'];
            unset($loaninfo['id']);
            $nowLoaninfo['pending'] = 1;
            $nowLoaninfo['dbt'] = $configs[0]['value'];
            $nowLoaninfo['error'] = $configs[1]['value'];
            $nowLoaninfo['tco'] = $configs[2]['value'];
            $nowLoaninfo['xbzmark'] = $configs[3]['value'];
            $nowLoaninfo['money'] = toMoney($quota);
            $nowLoaninfo['time'] = $post['month'];
            $nowLoaninfo['pass_time'] = time();
            $res2 = Loanorder::where(["id"=>$orderId])->save($nowLoaninfo);

            //充值
            $res3 = Qblog::htaddlogSepc($uid,toMoney($quota));
            $res4 = UserModel::updateQbmoney($uid, toMoney($quota) ,1);
            if ($res && $res1 && $res2 && $res3 && $res4) {
                //发送短信提示->审核通过
                orderChangeSmSSend($uid,2);
                //是否发送短信
                $examineStatus = SystemConfig::where(['name'=>'examine_sms_status'])->value("value");
                if($examineStatus == 1)
                {
                    //发送状态短信
                    $userInfo = UserModel::where(["id"=>$uid])->field("telnum,username")->find();
                    sendStatusMsg($userInfo['username'],$userInfo['telnum']);
                }
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
        //预期金额
        $addess = Info::where(["id"=>$id])->value("addess");
        if(isset(json_decode($addess,true)['amount']))
        {
            $amount = json_decode($addess,true)['amount'];
            $month = json_decode($addess,true)['month'];
        } else {
            $amount = 0;
            $month = 0;
        }
        $this->assign([
            'id' => $id,
            'amount' => $amount,
            'month' => $month,
        ]);
        return $this->fetch();
    }

}