<?php
// +----------------------------------------------------------------------
// | vaeThink [ Programming makes me happy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 http://www.vaeThink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 听雨 < 389625819@qq.com >
// +---------------------------------------------------------------------
namespace app\index\controller;
use think\Request;

class Auth extends PortBase
{
    public function __construct(Request $request) {
        parent::__construct($request);
        $token = $this->params['token'];
        if ($this->_checkLogin($token)) {
            if ($this->userinfo['status'] == 0) return $this->port(0, "该账户已禁用！");
        } else {
            return $this->port(0, "无效登录！");
        }
    }

}
