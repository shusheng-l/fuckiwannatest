<?php

namespace app\admin\controller;


use app\admin\model\SystemAdmin;
use app\admin\model\SystemQuick;
use app\common\controller\AdminController;
use app\common\model\Loanorder;
use app\common\model\Qblog;
use app\common\model\Sms;
use app\common\model\User;
use think\App;
use think\facade\Env;

class Index extends AdminController
{

    /**
     * 后台主页
     * @return string
     * @throws \Exception
     */
    public function index()
    {
        return $this->fetch('', [
            'admin' => session('admin'),
        ]);
    }

    /**
     * 后台欢迎页
     * @return string
     * @throws \Exception
     */
    public function welcome()
    {
        $quicks = SystemQuick::field('id,title,icon,href')
            ->where(['status' => 1])
            ->order('sort', 'desc')
            ->limit(8)
            ->select();
        $this->assign('quicks', $quicks);

        //php获取今日开始时间戳和结束时间戳
        $beginToday = mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        //用户总量
        $allUserss = User::count();
        //今日使用客户
        $todayUseUsers = User::whereBetweenTime("last_time",$beginToday,$endToday)->count();
        //今日注册客户
        $todayRegUsers = User::whereBetweenTime("reg_time",$beginToday,$endToday)->count();
        //今日已提现用户
        $todayWithdrawUsers = Loanorder::whereBetweenTime("withdraw_time",$beginToday,$endToday)->count();
        //今日待审核
        $checkOrders = Loanorder::where(["pending"=>0])->count();
        //短信条数
        $msgs = Sms::count();
        //今日钱包总充值金额
        $rechargeMoneys = Qblog::whereBetweenTime("add_time",$beginToday,$endToday)->where(["bz" => 1,"status"=>1,"isend"=>0])->sum("money");
        //短信平台余额
        $smsMoneyLst = getMsgMoneyLst();
        $this->assign([
            "allUserss" => $allUserss,
            "todayUseUsers" => $todayUseUsers,
            "todayRegUsers" => $todayRegUsers,
            "todayWithdrawUsers" => $todayWithdrawUsers,
            "checkOrders" => $checkOrders,
            "msgs" => $msgs,
            "rechargeMoneys" => $rechargeMoneys,
            "smsMoneyLst" => $smsMoneyLst,
        ]);
        return $this->fetch();
    }

    /**
     * 修改管理员信息
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editAdmin()
    {
        $id = session('admin.id');
        $row = (new SystemAdmin())
            ->withoutField('password')
            ->find($id);
        empty($row) && $this->error('用户信息不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $this->isDemo && $this->error('演示环境下不允许修改');
            $rule = [];
            $this->validate($post, $rule);
            try {
                $save = $row
                    ->allowField(['head_img', 'phone', 'remark', 'update_time'])
                    ->save($post);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * 修改密码
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editPassword()
    {
        $id = session('admin.id');
        $row = (new SystemAdmin())
            ->withoutField('password')
            ->find($id);
        if (!$row) {
            $this->error('用户信息不存在');
        }
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $this->isDemo && $this->error('演示环境下不允许修改');
            $rule = [
                'password|登录密码'       => 'require',
                'password_again|确认密码' => 'require',
            ];
            $this->validate($post, $rule);
            if ($post['password'] != $post['password_again']) {
                $this->error('两次密码输入不一致');
            }

            // 判断是否为演示站点
            $example = Env::get('easyadmin.example', 0);
            $example == 1 && $this->error('演示站点不允许修改密码');

            try {
                $save = $row->save([
                    'password' => password($post['password']),
                ]);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            if ($save) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

}
