<?php

namespace app\index\controller;
use app\common\model\SystemConfig;
use app\common\model\UserShowLoan;
use think\Request;

/**
 *自动执行的任务控制器
 */
class Task extends PortBase
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    //设置贷款假数据
    public function makeLoanData()
    {
        $data = [];
        //每次产生20条数据
        $mobiles = randomMobile(20);
        //获取配置参数
        $configs = SystemConfig::where('name','in',['min_loan','max_loan','span_loan'])
            ->field("value")
            ->order("sort desc")
            ->select()
            ->toArray();
        //获取随机值
        $topNum = intval(($configs[1]['value'] - $configs[0]['value']) / $configs[2]['value']);
        foreach ($mobiles as $k => $v)
        {
            $data[$k]['mobile'] = $v;
            $data[$k]['loan_num'] = $configs[0]['value'] + rand(0, $topNum) * $configs[2]['value'];
            $data[$k]['createtime'] = time();
        }
        UserShowLoan::insertAll($data);
    }
}
