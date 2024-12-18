<?php
// 应用公共文件

use app\common\service\AuthService;
use think\facade\Cache;
use app\common\model\SystemConfig;
use app\common\model\Info;
use app\common\model\User;
use app\admin\model\NoticeTemplate;
use app\common\model\Notice;

if (!function_exists('__url')) {

    /**
     * 构建URL地址
     * @param string $url
     * @param array $vars
     * @param bool $suffix
     * @param bool $domain
     * @return string
     */
    function __url(string $url = '', array $vars = [], $suffix = true, $domain = false)
    {
        return url($url, $vars, $suffix, $domain)->build();
    }
}

if (!function_exists('password')) {

    /**
     * 密码加密算法
     * @param $value 需要加密的值
     * @param $type  加密类型，默认为md5 （md5, hash）
     * @return mixed
     */
    function password($value)
    {
        $value = sha1('blog_') . md5($value) . md5('_encrypt') . sha1($value);
        return sha1($value);
    }

}

if (!function_exists('xdebug')) {

    /**
     * debug调试
     * @param string|array $data 打印信息
     * @param string $type 类型
     * @param string $suffix 文件后缀名
     * @param bool $force
     * @param null $file
     */
    function xdebug($data, $type = 'xdebug', $suffix = null, $force = false, $file = null)
    {
        !is_dir(runtime_path() . 'xdebug/') && mkdir(runtime_path() . 'xdebug/');
        if (is_null($file)) {
            $file = is_null($suffix) ? runtime_path() . 'xdebug/' . date('Ymd') . '.txt' : runtime_path() . 'xdebug/' . date('Ymd') . "_{$suffix}" . '.txt';
        }
        file_put_contents($file, "[" . date('Y-m-d H:i:s') . "] " . "========================= {$type} ===========================" . PHP_EOL, FILE_APPEND);
        $str = (is_string($data) ? $data : (is_array($data) || is_object($data)) ? print_r($data, true) : var_export($data, true)) . PHP_EOL;
        $force ? file_put_contents($file, $str) : file_put_contents($file, $str, FILE_APPEND);
    }
}

if (!function_exists('sysconfig')) {

    /**
     * 获取系统配置信息
     * @param $group
     * @param null $name
     * @return array|mixed
     */
    function sysconfig($group, $name = null)
    {
        $where = ['group' => $group];
        $value = empty($name) ? Cache::get("sysconfig_{$group}") : Cache::get("sysconfig_{$group}_{$name}");
        if (empty($value)) {
            if (!empty($name)) {
                $where['name'] = $name;
                $value = \app\admin\model\SystemConfig::where($where)->value('value');
                Cache::tag('sysconfig')->set("sysconfig_{$group}_{$name}", $value, 3600);
            } else {
                $value = \app\admin\model\SystemConfig::where($where)->column('value', 'name');
                Cache::tag('sysconfig')->set("sysconfig_{$group}", $value, 3600);
            }
        }
        return $value;
    }
}

if (!function_exists('array_format_key')) {

    /**
     * 二位数组重新组合数据
     * @param $array
     * @param $key
     * @return array
     */
    function array_format_key($array, $key)
    {
        $newArray = [];
        foreach ($array as $vo) {
            $newArray[$vo[$key]] = $vo;
        }
        return $newArray;
    }

}

if (!function_exists('auth')) {

    /**
     * auth权限验证
     * @param $node
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    function auth($node = null)
    {
        $authService = new AuthService(session('admin.id'));
        $check = $authService->checkNode($node);
        return $check;
    }

}


//返回json格式的数据
function vae_assign($code=1, $msg="OK", $data=[], $url='', $httpCode=200, $header = [], $options = []){
    $res['code']=$code;
    $res['msg']=$msg;
    $res['url']=$url;
    if(is_object($data)){
        $data=$data->toArray();
    }
    $res['data']=$data;
    $response = \think\Response::create($res, "json",$httpCode, $header, $options);
    throw new \think\exception\HttpResponseException($response);
}

//针对layui数据列表的返回数据方法
function vae_assign_table($code=0, $msg='', $data, $httpCode=200, $header = [], $options = []){
    $res['code'] = $code;
    $res['msg'] = $msg;
    if(is_object($data)) {
        $data = $data->toArray();
    }
    if(!empty($data['total'])){
        $res['count'] = $data['total'];
    } else {
        $res['count'] = 0;
    }
    $res['data'] = $data['data'];
    $response = \think\Response::create($res, "json",$httpCode, $header, $options);
    throw new \think\exception\HttpResponseException($response);
}

//获取url参数
function vae_get_param($key=""){
    return request()->param($key);
}

//随机字符串，默认长度10
function vae_set_salt($num = 10){
    $str = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890';
    $salt = substr(str_shuffle($str), 10, $num);
    return $salt;
}

//发送短信
function sendSms($mobile, $content, $code, $type=0)
{
    $statusStr = array( //做参照
        "0" => "短信发送成功",
        "-1" => "参数不全",
        "-2" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！",
        "30" => "密码错误",
        "40" => "账号不存在",
        "41" => "余额不足",
        "42" => "帐户已过期",
        "43" => "IP地址限制",
        "50" => "内容含有敏感词"
    );
    $smsapi = "http://www.smsbao.com/"; //短信网关
    //配置数据
    $configs = SystemConfig::where('name','in',['msg_user','msg_pw'])
        ->field("value")
        ->order("sort desc")
        ->select()
        ->toArray();
    $user = $configs[0]['value']; //短信平台帐号
    $pass = md5($configs[1]['value']); //短信平台密码
    $phone = $mobile;
    $sendurl = $smsapi."sms?u=".$user."&p=".$pass."&m=".$phone."&c=".urlencode($content);
    $result =file_get_contents($sendurl) ;
    if($result == 0){ //0:代表发送成功
        //保存
        $smsLogs = new \app\common\model\Sms();
        $smsLogs->save([
            'telnum'=> $mobile,
            'code' => $code,
            'type' => 1, //1代表手机
            'content' => $content,
            'send_time' => time(),
            'status' => 0, //未使用
        ]);
        return true;
    }else{
        return false;
    }

}

/**
 * 验证输入的手机号码是否合法
 * @access public
 * @param string $mobile_phone
 * 需要验证的手机号码
 * @return bool
 */
function is_mobile_phone($mobile_phone)
{
    $chars = "/^13[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|12[0-9]{1}[0-9]{8}$|14[0-9]{1}[0-9]{8}$|16[0-9]{1}[0-9]{8}$|19[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$|17[0-9]{1}[0-9]{8}$/";
    if(preg_match($chars, $mobile_phone))
    {
        return true;
    }
    return false;
}

//获取客户端IP
function get_client_ip() {
    static $ip = NULL;
    if ($ip !== NULL)
        return $ip;
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos = array_search('unknown', $arr);
        if (false !== $pos)
            unset($arr[$pos]);
        $ip = trim($arr[0]);
    }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $ip = (false !== ip2long($ip)) ? $ip : '0.0.0.0';
    return $ip;
}

/**
 * @param $birthday 出生年月日（1992-1-3）
 * @return string 年龄
 */
function countage($birthday){
    $year=date('Y');
    $month=date('m');
    if(substr($month,0,1)==0){
        $month=substr($month,1);
    }
    $day=date('d');
    if(substr($day,0,1)==0){
        $day=substr($day,1);
    }
    $arr=explode('-',$birthday);
    $age=$year-$arr[0];
    if($month<$arr[1]){
        $age=$age-1;
    }elseif($month==$arr[1]&&$day<$arr[2]){
        $age=$age-1;
    }
    return $age;
}

//文件上传
function vae_upload(){
    if(request()->file('file')){
        $file = request()->file('file');
        //验证文件规则->是图片
        $result=validate(['file' => ['fileSize:10240000,fileExt:jpg,png']])->check(['file' => $file]);
        if($result)
        {
            //上传开始
            $savename = \think\facade\Filesystem::disk('public')->putFileAs( 'topic/'.date("Ymd",time()), $file,time().vae_set_salt(10).'.jpg');
            $res['code'] = 1;
            $res['data'] = '/' . $savename;
            return $res;
        } else {
            $res['code'] = 0;
            $res['msg'] = "请上传图片!";
            return $res;
        }
    }else{
        $res['code'] = 0;
        $res['msg'] = "没有上传图片!";
        return $res;
    }
}

//随机生成n条手机号
function randomMobile($n)
{
    $tel_arr = array(
        '130','131','132','133','134','135','136','137','138','139','144','147','150','151','152','153','155','156','157','158','159','176','177','178','180','181','182','183','184','185','186','187','188','189',
    );
    for($i = 0; $i < $n; $i++) {
        $tmp[] = $tel_arr[array_rand($tel_arr)] . mt_rand(1000,9999) . mt_rand(1000,9999);
    }
    return array_unique($tmp);
}

//自定义函数手机号影藏前五位
function yc_phone($str)
{
    $resstr = substr_replace($str, '***', 3, 5);
    return $resstr;
}

//获取借款值度
function getMoneyScale($uid){
    //申请借款所需配置
    $configs = SystemConfig::where('name','in',['min_loan',"max_loan","span_loan"])
        ->field("value")
        ->order("sort desc")
        ->select()
        ->toArray();
    $config = array();
    $config['min_loan'] = $configs[0]['value']; //单次借款最低金额
    $config['max_loan'] = $configs[1]['value']; //单次借款最高金额
    $config['span_loan'] = $configs[2]['value']; //借款金额选择跨度

    $bz =Info::hasSetIdentity($uid); //用户状态
    $kt ='0.00';
    if($bz == 2) //状态改变后配置
    {
        $doquota = User::getDoquota($uid);
        $doquotas = $doquota;
        $doquota =intval($doquota);
        $min = 0;
        $max = $config['max_loan']; //$doquota
        $step = $doquota;
        $kt = $doquotas;
        $bz ='2';
    } else {
        $min = $config['min_loan']; //后台默认配置
        $max = $config['max_loan'];
        $step = $config['span_loan'];
    }

    return array('min'=> $min,'max'=>$max,'step'=>$step,'bz'=>$bz,'kt'=>$kt);
}

//金额格式化
function toMoney($num){
    $num_tmp = number_format($num,2,'.','');
    if($num_tmp < $num) return $num_tmp + 0.01;
    return $num_tmp;
}

//获取期限列表带单位，月
function getDeadlineList(){
    $str = '个月';
    $list = SystemConfig::where(["name"=>"time_frame"])->value("value");
    $list = array_filter(array_unique(explode(",",$list))); //去重去空 //期限范围
    return array(
        'str'	=>	$str,
        'list'	=>	$list
    );
}

/**
 * 生成唯一的订单号
 */
function generateOrderNo($prefix, $id)
{
    $str = $prefix . date('YmdHis') . substr($id . rand(100000000, 9999999999), 0, 8);
    return $str;
}

/**
 * 当前日期加上N个月
 * @param $dt 格式化后的日期
 * @param $delta 变化量
 */
function getNextMonth($delta, $dt = '')
{
    if ($dt) {
        return strtotime("+$delta month", $dt);
    } else {
        return strtotime("+$delta month");
    }
}

//获取月借款利息
function getInterest(){
    return SystemConfig::where(["name"=>"month_rate"])->value("value");
}

/**
 * 订单状态变化短信提示
 * type短信内容：1审核中 2.审核通过 3提现成功 4失败状态
 * */
function orderChangeSmSSend($uid,$type=1,$failTitle="",$failContent="")
{
    //获取短信模板
    $temple = NoticeTemplate::where(["type"=>$type])->field("title,content")->order("id desc")->find();
    if($type != 4)
    {
        //平台消息提示
        Notice::create(["title"=>$temple["title"],"type"=>$type,"content"=>$temple["content"],"uid"=>$uid,"create_time"=>time()]);
    } else {
        //平台消息提示
        Notice::create(["title"=>$failTitle,"type"=>$type,"content"=>$failContent,"uid"=>$uid,"create_time"=>time()]);
    }

    //***暂时留着，等测试提供短信接口****
    return true;
}

/**
 * 数字金额转换成中文大写金额,要求小数位数为两位
 * @param $num 要转换的小写数字或小写字符串
 * @return 大写金额字符串
 */
function get_amount($num){
    $c1 = "零壹贰叁肆伍陆柒捌玖";
    $c2 = "分角元拾佰仟万拾佰仟亿";
    $num = round($num, 2);
    $num = $num * 100;
    if (strlen($num) > 11) {
        return "数据太长，没有这么大的钱吧，检查下";
    }
    $i = 0;
    $c = "";
    while (1) {
        if ($i == 0) {
            $n = substr($num, strlen($num)-1, 1);
        } else {
            $n = $num % 10;
        }
        $p1 = substr($c1, 3 * $n, 3);
        $p2 = substr($c2, 3 * $i, 3);
        if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
            $c = $p1 . $p2 . $c;
        } else {
            $c = $p1 . $c;
        }
        $i = $i + 1;
        $num = $num / 10;
        $num = (int)$num;
        if ($num == 0) {
            break;
        }
    }
    $j = 0;
    $slen = strlen($c);
    while ($j < $slen) {
        $m = substr($c, $j, 6);
        if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
            $left = substr($c, 0, $j);
            $right = substr($c, $j + 3);
            $c = $left . $right;
            $j = $j-3;
            $slen = $slen-3;
        }
        $j = $j + 3;
    }
    if (substr($c, strlen($c)-3, 3) == '零') {
        $c = substr($c, 0, strlen($c)-3);
    }
    if (empty($c)) {
        return "零元整";
    }else{
        return $c . "整";
    }
}

//获取访问ip
function getIP()
{
    static $realip;
    if (isset($_SERVER)){
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
            $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $realip = $_SERVER["HTTP_CLIENT_IP"];
        } else {
            $realip = $_SERVER["REMOTE_ADDR"];
        }
    } else {
        if (getenv("HTTP_X_FORWARDED_FOR")){
            $realip = getenv("HTTP_X_FORWARDED_FOR");
        } else if (getenv("HTTP_CLIENT_IP")) {
            $realip = getenv("HTTP_CLIENT_IP");
        } else {
            $realip = getenv("REMOTE_ADDR");
        }
    }
    return $realip;
}

/**
 * 百度地图API函数，获取IP地址所在城市
 */
function ip_to_address($ip){
    $key='82lnaE9tNagTjwbHK4wtxvloMDAOwCKl';
    $address_tmp=file_get_contents("http://api.map.baidu.com/location/ip?ak={$key}&ip={$ip}&coor=bd09ll");
    return json_decode($address_tmp, true);

}

/**
 * 发送post
 * */
function send_post($url, $post_data) {
    $postdata = http_build_query($post_data);
    $options = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-type:application/x-www-form-urlencoded',
            'content' => $postdata,
            'timeout' => 15 * 60 // 超时时间（单位:s）
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return $result;
}

/**
 * 发送状态短信
 * */
function sendStatusMsg($username,$mobile)
{
    //短信接口1
/*    $smsapi = "http://211.149.147.79:8080/SendMessage/SendSms_API"; //短信网关
    $user = "BZHTZ4"; //短信平台帐号
    $pass = "123456"; //短信平台密码
    $passwd = strtoupper(md5($user . $pass)); //加密好
    $msg = "尊敬的" . $username . "先生/女士，您的申请已成功，请注意查收。(通知短信，请勿回复!)"; //短信内容
    $mobs = $mobile; //手机号码
    $ts = "";
    $dtype = ""; //普通字串类型
    $extno = ""; //扩展码默认空
    $post_data = array(
        'user' => $user,
        'passwd' => $passwd,
        'msg' => $msg,
        'mobs' => $mobs,
        'ts' => $ts,
        'dtype' => $dtype,
        'extno' => $extno,
    );
    $result =  send_post($smsapi, $post_data);
    $result = explode(",",$result);
    if($result[0] == 0)
    {
        //保存
        $smsLogs = new \app\common\model\Sms();
        $smsLogs->save([
            'telnum'=> $mobile,
            'code' => "",
            'type' => 1, //1代表手机
            'content' => $msg,
            'send_time' => time(),
            'status' => 1, //已使用
        ]);
        return true;
    }
    return false;*/

    //短信接口2
    /*$smsapi = "http://47.96.79.213:8088/sms.aspx"; //短信网关
    $userid = 372; //用户id
    $account = "17721225082"; //短信平台帐号
    $password = "17721225082"; //短信平台密码
    $mobile = $mobile; //手机号码
    $msg = "【云通知】您的申请已通过，请及时登入APP。"; //短信内容
    $sendTime = "";
    $action = "send";
    $extno = ""; //扩展码默认空
    $post_data = array(
        'action' => $action,
        'userid' => $userid,
        'account' => $account,
        'password' => $password,
        'mobile' => $mobile,
        'content' => $msg,
        'sendTime' => $sendTime,
        'extno' => $extno,
    );
    $result = send_post($smsapi, $post_data);
    if(strpos($result,'Success') !== false){
        //保存
        $smsLogs = new \app\common\model\Sms();
        $smsLogs->save([
            'telnum'=> $mobile,
            'code' => "",
            'type' => 1, //1代表手机
            'content' => $msg,
            'send_time' => time(),
            'status' => 1, //已使用
        ]);
        return true;
    }else{
        return false;
    }*/

    //相关配置
    $allInfoConfigs = SystemConfig::where('name','in',["examine_msg_user",'examine_msg_pw',"examine_msg_module","examine_msg_user_id"])
        ->field("value")
        ->order("sort desc")
        ->select()
        ->toArray();
    //短信接口3
    $smsapi = "http://47.96.79.213:8088/sms.aspx"; //短信网关
    $userid = $allInfoConfigs[3]['value']; //用户id
    $account = $allInfoConfigs[0]['value']; //短信平台帐号
    $password = $allInfoConfigs[1]['value']; //短信平台密码
    $mobile = $mobile; //手机号码
    $msg = $allInfoConfigs[2]['value']; //短信内容
    $sendTime = "";
    $action = "send";
    $extno = ""; //扩展码默认空
    $post_data = array(
        'action' => $action,
        'userid' => $userid,
        'account' => $account,
        'password' => $password,
        'mobile' => $mobile,
        'content' => $msg,
        'sendTime' => $sendTime,
        'extno' => $extno,
    );
    $result = send_post($smsapi, $post_data);
    if(strpos($result,'Success') !== false){
        //保存
        $smsLogs = new \app\common\model\Sms();
        $smsLogs->save([
            'telnum'=> $mobile,
            'code' => "",
            'type' => 1, //1代表手机
            'content' => $msg,
            'send_time' => time(),
            'status' => 1, //已使用
        ]);
        return true;
    }else{
        return false;
    }

}

/**
 * 获取短信平台余额
 * */
function getMsgMoneyLst()
{
    //相关配置
    $allInfoConfigs = SystemConfig::where('name','in',["examine_msg_user",'examine_msg_pw',"examine_msg_module","examine_msg_user_id"])
        ->field("value")
        ->order("sort desc")
        ->select()
        ->toArray();
    //短信接口2
    $smsapi = "http://47.96.79.213:8088/sms.aspx"; //短信网关
    $userid = $allInfoConfigs[3]['value']; //用户id
    $account = $allInfoConfigs[0]['value']; //短信平台帐号
    $password = $allInfoConfigs[1]['value']; //短信平台密码
    $post_data = array(
        'action' => "overage",
        'userid' => $userid,
        'account' => $account,
        'password' => $password,
    );

    $result = send_post($smsapi, $post_data);
    if(strpos($result,'Sucess') !== false){
        $sum = explode("<overage>",$result)[1];
        $sum = explode("</overage>",$sum)[0];
        return $sum;
    }else{
        return "获取余额失败";
    }

}