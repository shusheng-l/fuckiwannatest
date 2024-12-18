<?php

namespace app\admin\controller\system;


use app\admin\model\SystemConfig;
use app\common\model\Info;
use \app\common\model\User as UserModel;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use app\common\model\Qblog;

/**
 * @ControllerAnnotation(title="用户管理")
 * Class Auth
 * @package app\admin\controller\system
 */
class User extends AdminController
{

    use \app\admin\traits\Curd;
    protected $sort = [
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new UserModel();
        $this->moneyModel = new Qblog();
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            [$page, $limit, $where, $excludeFields] = $this->buildTableParames(['month']);
            // todo TP6框架有一个BUG，非模型名与表名不对应时（name属性自定义），withJoin生成的sql有问题
            $count = $this->model
                ->where($where)
                ->count();
            $list = $this->model
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select();

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
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        //用户提交审核个人信息
        $userInfo = Info::where(["uid"=>$id])->field("identity,contacts,bank,status")->find();
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
            if($adminId != $loanOrderInfo["admin_id"]) //不可
            {
                $this->error('您没有操作此数据的权限！');
            }

            $res = Loanorder::where(["id"=>$id])->save(array( 'tco' => $post['color'],'dbt' => $post['idbt'],'error' => $post['ixsm'],'xbzmark' => $post['ixbzmark'],"order_status"=>1));
            //发送短信提示->状态失败
            if($res) orderChangeSmSSend($uid,4);
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
     * @NodeAnotation(title="用户账户")
     */
    public function money($id)
    {
        $username = \app\common\model\User::where(["id"=>$id])->value("telnum");
        $uid = $id;
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
     * @NodeAnotation(title="重置密码")
     */
    public function repaypw($id)
    {
        if ($this->request->isAjax()) {
            $newPwd = SystemConfig::where(["name"=>"repay_pw"])->value("value");
            $param['salt'] = vae_set_salt(20);
            $param['password'] = password_hash(md5($newPwd.$param['salt']), PASSWORD_DEFAULT);
            $res = \app\common\model\User::where(["id"=>$id])->update($param);
            $res ? $this->success('操作成功') : $this->error('操作失败');
        }
    }

}