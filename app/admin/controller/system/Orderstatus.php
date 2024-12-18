<?php


namespace app\admin\controller\system;


use app\admin\model\OrderStatus as OrderStatusModel;
use app\admin\traits\Curd;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * Class Admin
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="常用订单状态管理")
 */
class Orderstatus extends AdminController
{

    use Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new OrderStatusModel();
    }

}