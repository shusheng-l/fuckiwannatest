<?php

namespace app\index\controller;
use app\common\model\SystemConfig;
use think\Request;

class UpdateApp extends PortBase
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }


    //在线更新
    public function appResource()
    {
        $param = $this->params;
        foreach ($param as $k => $v) {
            if ($v == '') {
                return $this->port(0, "数据异常！");
            }
        }
        //配置
        $configs = SystemConfig::where('name','in',['logo_title','app_version','app_update_url'])->field("value")->order("sort desc")->select()->toArray();
        $data = array();
        $appName = $configs[0]['value']; //app名称
        $appVersion = $configs[1]['value']; //app版本号
        $appUpdateUrl = $configs[2]['value'];
        //if($param['name'] != $appName) return $this->port(0, "数据异常！");
        if($param['version'] != $appVersion){ //检测是否更新
            $data['update'] = 1;
            $data['wgtUrl'] = $appUpdateUrl;
        } else {
            $data['update'] = 0;
            $data['wgtUrl'] = $appUpdateUrl;
        }
        return $this->port(1, "data",$data);
    }
}
