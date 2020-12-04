<?php
namespace app\api\controller;

use think\App;
use think\facade\Db;
use think\response\Json;
use think\facade\Config;

class Open
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;
    
    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;
    
    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;
    }
    
    /**
     * 用户事件
     * @return Json
     */
    function userEvent(){
        $merchant = $this->request->post("merchant");
        $user = $this->request->post("user");
        $event = $this->request->post("event");
        
        if(!$event) $event = "visit";
        
        if(!$merchant && !$user){
            return action_error(config("msg.param_error"));
        }
        
        user_event($user, $event,$merchant);
        
        return action_succ();
    }
    
    
    
    /**
     * 获取APP版本信息
     * @return Json
     */
    public function getApp(){
        $app = Db::name("app")->order("id desc")->find();
        $data=['link'=>'','version'=>'1.0.0','note'=>''];
        if($app){
            $data=['link'=>$app['link'],'version'=>$app['version'],'note'=>$app['note']];
        }
        
        return action_succ($data);
    }
    
    /**
     * 获取系统配制信息
     * @return Json
     */
    public function config(){
        $gp = $this->request->post("gp");
        if(!$gp) $gp="base";
        
        $config = get_config($gp);
        
        return action_succ($config);
    }
    
    /**
     * 密保问题列表
     * @return Json
     */
    public function questions(){
        $list = Config::get("sys.questions");
        return action_succ($list);
    }
    
    /**
     * 首页Banner列表
     * @return Json
     */
    public function banners(){
        $path = $this->app->getRootPath()."public/images/banners";
        $temp=scandir($path);
        $files=[];
        $rooturl = $this->request->domain()."/images/banners/";
        foreach ($temp as $v){
            if($v=='.' || $v=='..'){//判断是否为系统隐藏的文件.和..  如果是则跳过否则就继续往下走，防止无限循环再这里。
                continue;
            }
            
            $files[]=$rooturl.$v;
        }
        return action_succ($files);
    }
    
    /**
     * 银行列表
     * @return Json
     */
    public function banks(){
        $list = Config::get("sys.banks");
        $datas = [];
        foreach ($list as $val){
            $datas[]=['value'=>$val,'label'=>$val];
        }
        return action_succ($datas);
    }
}
