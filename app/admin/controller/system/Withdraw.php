<?php

namespace app\admin\controller\system;


use app\common\model\Info;
use \app\common\model\User as UserModel;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use app\common\model\Qblog;

/**
 * @ControllerAnnotation(title="提现管理")
 * Class Auth
 * @package app\admin\controller\system
 */
class Withdraw extends AdminController
{

    use \app\admin\traits\Curd;
    protected $sort = [
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new Qblog();
    }

    /**
     * @NodeAnotation(title="提现列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            [$page, $limit, $where, $excludeFields] = $this->buildTableParames(['month']);
            // todo TP6框架有一个BUG，非模型名与表名不对应时（name属性自定义），withJoin生成的sql有问题

            $where[] = ['q.bz','=',2]; //提现
            $where[] = ['q.isadmin','=',0];

            //连表status用主表的status
            for ($a=0;$a<count($where);$a++)
            {
                if($where[$a][0] == "status")
                {
                    $where[$a][0] = "q." . $where[$a][0];
                }
            }

            $count = $this->model
                ->alias('q')
                ->join('user u','q.uid = u.id')
                ->where($where)
                ->count();
            $list = $this->model
                ->alias('q')
                ->join('user u','q.uid = u.id')
                ->field("q.*,u.telnum,u.qbmoney")
                ->where($where)
                ->page($page, $limit)
                ->order("status asc,id desc")
                ->select();

            foreach($list as $k=>$v)
            {
                $list[$k]['add_time'] = date("Y-m-d H:i:s",$list[$k]['add_time']);
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
     * @NodeAnotation(title="提现操作")
     */
    public function passmoney($id)
    {
        $status = 1;
        $bz = 2;
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
        /*$money = $qblog['money'];
        $uid = $qblog['uid'];
        $usermoney = UserModel::updateQbmoney($uid, $money ,$retbz);
        if ($usermoney != 1){
            //回滚记录
            Qblog::where(array('id' => $id))->save(array('status' => '0','qr_time' => $times));
            if ($usermoney == 2) {
                $this->error('用户钱包实际余额不足,取现操作确认失败');
            }else{
                $this->error('用户钱包操作失败');
            }
        }*/
        $this->success('操作成功');
    }

    /**
     * @NodeAnotation(title="驳回操作")
     */
    public function rejectmoney($id)
    {
        $qblog = Qblog::where(array('id' => $id))->find();
        if (!$qblog) {
            $this->error('记录不存在');
        }
        $times=time();
        if($qblog['status'] == 2 || $qblog['status'] == 1){
            $this->error('记录操作状态异常');
        }
        $result = Qblog::where(array('id' => $id))->save(array('status' => '2','qr_time' => $times));
        $money = $qblog['money'];
        $uid = $qblog['uid'];
        $usermoney = UserModel::updateQbmoney($uid, $money * -1 ,1); //驳回则加回去
        if ($result && $usermoney){
            $this->success('操作成功');
        }
        $this->error('操作失败');
    }

}