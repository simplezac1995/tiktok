<?php
namespace app\admin\controller;

use think\App;
use think\facade\Db;

class Login
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
    public function index()
    {
        session(null);
        return view();
    }
    
    public function check(){
        $name = $this->request->post("username");
        $pass = $this->request->post("password");
        $captcha = $this->request->post("captcha");
        
        $pass = password_encrypt($pass);
        if(!captcha_check($captcha)){
            return action_error("验证码错误"); 
        };
        
        $userInfo = Db::name("admin_user")->where(['user_name'=>$name,'password'=>$pass])->find();
        if($userInfo){
            
            if($userInfo['status']==0){
                return action_error("账号已补冻结");
            }
            
            $powers = "";
            
            if($userInfo['role_id']){
                $powers = Db::name("role")->where("id",$userInfo['role_id'])->value("powers");
            }
            
            // $ip = $_SERVER['HTTP_X_REAL_IP'];
            $ip = $this->request->ip();
            Db::name("admin_user")->where("id",$userInfo['id'])->update([
                'powers'=>$powers,
                'last_login'=>time(),
                'last_ip'=>$ip
            ]);
            
            $userInfo['powers'] = $powers;
            set_login_info($userInfo);
            return action_succ([],"登入成功");
        }else{
            return action_error("用户名或密码错误");
        }
    }
}
