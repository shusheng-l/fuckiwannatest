<?php

namespace app\index\controller;
use app\admin\model\MallGoods;
use app\common\model\Notice;
use app\common\model\SystemConfig;
use app\common\model\UserShowLoan;
use think\Request;

class Common extends PortBase
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    //logo
    public function getUserRegagree()
    {
        $info = array();
        if($this->params['status'] == 1)
        {
            $info["logo"] = SystemConfig::where(["name"=>"logo_image"])->value("value");
        }
        return $this->port(1, "data",$info);
    }

    //logo,msg open status
    public function getUserRegagreeSpec()
    {
        $info = array();
        if($this->params['status'] == 1)
        {
            //配置数据
            $configs = SystemConfig::where('name','in',['logo_image','msg_open','logo_title'])
                ->field("value")
                ->order("sort desc")
                ->select()
                ->toArray();
            $info["msg_status"] = $configs[0]['value'];
            $info["appname"] = $configs[1]['value'];
            $info["logo"] = $configs[2]['value'];
        }
        return $this->port(1, "data",$info);
    }

    //用户注册协议
    public function getRegisterContent()
    {
        $info = array();
        if($this->params['status'] == 1)
        {
            $info["content"] = SystemConfig::where(["name"=>"register_context"])->value("value");
        }
        return $this->port(1, "data",$info);
    }

    //首页，公告和大图
    public function getNotices()
    {
        $info = array();
        //未读消息
        $notice =array();
        $notice['isStatus'] = 0; //默认不显示
        $notice['title'] = "";
        $notice['content'] = "";
        $token = $this->params['token'];
        if($this->_checkLogin($token)) //验证权限
        {
            $showNotice = Notice::where(["uid"=>$this->userinfo["id"],"is_read"=>1])->field("id,title,content")->order("id desc")->find();
            if(isset($showNotice))
            {
                $notice['isStatus'] = 1;
                $notice['title'] = $showNotice["title"];
                $notice['content'] = $showNotice["content"];
                //修改状态
                Notice::where(["id"=>$showNotice["id"]])->save(["is_read"=>2,'read_time'=>time()]);
            }
        }
        $info["notice"] = $notice;
        //最新公告id=9
        $articleWhere["cate_id"] = 9;
        $loans = UserShowLoan::field("mobile,loan_num,createtime")
                            ->limit(20)
                            ->order("createtime desc")
                            ->select()
                            ->toArray();
        for ($a=0;$a<count($loans);$a++)
        {
            $loans[$a]["createtime"] = explode(" ",$loans[$a]["createtime"])[0];
            $loans[$a]["mobile"] = yc_phone($loans[$a]["mobile"]);
            $loans[$a]["loan_num"] = intval($loans[$a]["loan_num"]);
        }
        $info["article"] = $loans;

        //首页大图
        //相关配置
        $allInfoConfigs = SystemConfig::where('name','in',["index_banner",'site_copyright',"site_beian"])
            ->field("value")
            ->order("sort desc")
            ->select()
            ->toArray();
        $banners = $allInfoConfigs[0]['value'];
        $banners = explode("|",$banners);
        $info["banners"] = $banners;
        $info['site_copyright'] = $allInfoConfigs[1]['value'];
        $info['site_beian'] = $allInfoConfigs[2]['value'];
        //备案信息,版权信息
        return $this->port(1, "data",$info);
    }

    //某个公告
    public function getArticle()
    {
        $articleWhere['id'] = $this->params['aid'];
        $article = MallGoods::where($articleWhere)//自动剔除软删除数据
                            ->field("title as atitle,describe")
                            ->find();
        $info["article"] = $article;
        return $this->port(1, "data",$info);
    }
}
