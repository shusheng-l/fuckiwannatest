<?php

namespace app\admin\controller\system;

use app\common\model\Notice as noticeModel;
use app\common\controller\AdminController;
use think\App;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;

/**
 * @ControllerAnnotation(title="站内信列表")
 * Class Auth
 * @package app\admin\controller\system
 */
class Noticelist extends AdminController
{

    use \app\admin\traits\Curd;
    protected $sort = [
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new noticeModel();
    }

    /**
     * @NodeAnotation(title="站内信")
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
                ->alias('n')
                ->join('user u','n.uid = u.id')
                ->field("n.*,u.username,u.telnum")
                ->where($where)
                ->count();
            $list = $this->model
                ->alias('n')
                ->join('user u','n.uid = u.id')
                ->where($where)
                ->page($page, $limit)
                ->field("n.*,u.username,u.telnum")
                ->order("n.id desc")
                ->select();

            //数据处理
            foreach($list as $k=>$v){
                //读取时间
                if($list[$k]['read_time'] != "")
                {
                    $list[$k]['read_time'] = date("Y-m-d H:i:s",$list[$k]['read_time']);
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

}