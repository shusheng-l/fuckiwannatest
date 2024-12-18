<?php
// +----------------------------------------------------------------------
// | vaeThink [ Programming makes me happy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 http://www.vaeThink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
namespace app\index\controller;

use app\common\model\User;
use think\Request;
use app\common\json\PublicRe;
use think\cache\driver\Redis;

class PortBase
{
    protected $rows;
    protected $page;
    protected $field;
    protected $userinfo;
    protected $userbackinfo;
    protected $_session;
    protected $_token;
    protected $confinfo;
    public $params;
    public $request;
    public $lang;

    public function __construct(Request $request)
    {
        $this->request = $request;
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        $this->rows = !empty($this->param('rows')) ? $this->param('rows') : 5;
        $this->page = !empty($this->param('page')) ? $this->param('page') : 1;
        $this->field = !empty($this->param('field')) ? json_decode($this->param('field'),true) : '*';
        if($this->request->isPost())
        {
            $json = $this->request->post();
            $this->params = $json;
        } else {
            $param = $this->request->param();
            $this->params = $param;
        }
    }

    protected static function port($code=1, $msg="OK", $data=[], $url='', $httpCode=200, $header = [], $options = []){
        $port = vae_assign($code, $msg, $data, $url, $httpCode, $header, $options);
        return $port;
    }

    /**
     * @param null $data
     * @param string $status
     * @param null $message
     * @return array
     */
    public function apiReturn($status = '', $message = null,$data = null)
    {
        $status === null and $status = 0;
        $re = [
            'code' => (int)$status,
            'info' => $message,
        ];
        $data !== null and $re['data'] = $data;
        $rs = array_merge($re,PublicRe::$rej);
        echo json_encode($rs);
        exit();
    }

    protected static function param($key=""){
        $param = vae_get_param();
        if(!empty($key) and isset($param[$key])){
            $param = $param[$key]; 
        } else if(!empty($key) and !isset($param[$key])){
            $param = null;
        }
        return $param;

    }

    protected  function _checkLogin($token) {
        if (empty($token)) return false;
        $req = (array)json_decode(base64_decode($token));
        if (empty($req) || empty($req['id']) || empty($req['token'])) return false;
        $mid = $req['id'];
        $token = html_entity_decode($req['token']);
        $userinfo = User::where((['id' => $mid,'status'=>1]))->find();
        if (empty($userinfo)) return false;
        $this->userinfo = $userinfo;
        $account = $this->userinfo['telnum'];
        //redis读取
        $redis = new Redis();
        $uk = $redis->get('uni-app.'.md5('uniapp' . $account));
        if (password_verify($uk[0] . $uk[1] . $account . $uk[2], $token)) {
            $this->userinfo = $userinfo;
            //希望显示的信息
            $this->userbackinfo = [
                'account' => $userinfo['telnum'],
                'name' => $userinfo['username'],
                'time' => explode(" ",$userinfo['reg_time'])[0],
            ];
            $this->_token = base64_encode(json_encode(['id' => $userinfo['id'], 'token' => password_hash($uk[0] . $uk[1] . $account . $uk[2], PASSWORD_DEFAULT)]));
            //更新登录状态
            User::where(['id'=>$mid])->save(['last_time' => time(), 'last_ip' => get_client_ip()]);
            return true;
        } else {
            return false;
        }
    }

}