<?php

namespace app\index\controller;
use think\Request;

class Api extends Auth
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    //上传文件
    public function upload()
    {
        $res = vae_upload();
        if($res['code'] == 1){
            return $this->port(1, '图片上传成功!',$res['data']);
        }
        return $this->port(0, $res['msg']);
    }

}
