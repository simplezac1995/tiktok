<?php
namespace app\api\controller;

use think\App;
use think\facade\Db;
use think\facade\Validate;

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
    
    private $msgs;
    
    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;
        $this->msgs = config("msg");
    }
    
    function forget(){
        $user = $this->request->post("username");
        $answer = $this->request->post("answer");
        $password = $this->request->post("password");
        
        
        if(!$user || !$answer || !$password){
            return action_error($this->msgs['param_error']);
        }
        
        
        $userId = Db::name("user")->where("username",$user)->value("id");
        
        if(!$userId){
            return action_error($this->msgs['username_not_exist']);
        }
        
        $question = Db::name("user_answer")->where("user_id",$userId)->find();
        
        if(!$question){
            return action_error($this->msgs['unset_answer']);
        }
        
        if($question['answer']!=$answer){
            return action_error($this->msgs['answer_error']);
        }
        
        Db::name("user")->where("id",$userId)->update(['password'=>password_encrypt($password)]);
        
        
        return action_succ();
    }
    
    /**
     * 根据用户名查找密保问题
     */
    function question(){
        $user = $this->request->post("username");
        $userId = Db::name("user")->where("username",$user)->value("id");
        
        if(!$userId){
            return action_error($this->msgs['username_not_exist']);
        }
        
        $question = Db::name("user_answer")->field("id,question")->where("user_id",$userId)->find();
        
        if(!$question){
            return action_error($this->msgs['unset_answer']);
        }
        
        return action_succ($question);
    }
    
    /**
     * 登入
     */
    function login(){
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        if(!$username || !$password){
            return action_error($this->msgs['param_error']);
        }
        
        $userInfo = Db::name("user")->where(["username"=>$username])->find();
        
        if(!$userInfo){
            return action_error($this->msgs['username_or_password_error']);
        }
        
        //当天密码错误资料超过10次，不允许再登入
        if($userInfo['error']>=100 && $userInfo['error_dt']==date("Y-m-d")){
            return action_error($this->msgs['login_password_error']);
        }
        
        if($userInfo['password']!=password_encrypt($password)){
            if($userInfo['error_dt']!=date("Y-m-d")){
                $error=1;
            }else{
                $error = $userInfo['error']+1;
            }
            
            if($error>=100){
                Db::name("user")->where(["id"=>$userInfo['id']])->update(['error'=>$error,'error_dt'=>date("Y-m-d")]);
                return action_error($this->msgs['login_password_error']);
            }
            
            Db::name("user")->where(["id"=>$userInfo['id']])->update(['error'=>$error,'error_dt'=>date("Y-m-d")]);
            return action_error($this->msgs['username_or_password_error']);
        }
        
        user_event($userInfo['id'], 'login');
        
        return action_succ($this->loginSuccBack($userInfo));
        
    }
    
    /**
     * 注册
     * @return boolean[]|string[]|boolean[]|array
     */
    function regist(){
        $username = $this->request->post("username");
        $password = $this->request->post("password");
        $name = $this->request->post("name");
        $question = $this->request->post("question");
        $answer = $this->request->post("answer");
        $sc = $this->request->post("sc");//注册时的分享码
        
        
        if(!$username || !$password ||!$name ||!$question ||!$answer ||!$sc){
            return action_error($this->msgs['param_error']);
        }
        
        
        $topUser = Db::name("user")->field("id,top1,top2,higher_top")->where("share_code",$sc)->find();
        if(!$topUser){
            return action_error($this->msgs['share_code_error']);
        }
        
        $top = $topUser['id'];
        
        $count = Db::name("user")->where("username",$username)->count();
        if($count){
            return action_error($this->msgs['login_tel_exist']);
        }
        
        //以第一个会员等级作为注册会员的初始等级
        $cards = get_vipcards();
        $vipcard_id = array_keys($cards)[0];
        
        //生成分享码
        $shareCode = $this->createCodeStr();
        while (Db::name('user')->where('share_code',$shareCode)->value("id")){
            $shareCode = $this->createCodeStr();
        }
        
        $ip = $this->request->ip();
        $dt = time();
        $userInfo = [
            'tel'=>$username,
            'username'=>$username,
            'password'=>password_encrypt($password),
            'name'=>$name,
            'login_time'=>$dt,
            'create_time'=>$dt,
            'top1'=>$top,
            'top2'=>$topUser['top1'],
            'top3'=>$topUser['top2'],
            'higher_top'=>$topUser['higher_top'],
            'vipcard_id'=>$vipcard_id,
            'sex'=>0,
            'address'=>'',
            'ip'=>$ip,
            'share_code'=>$shareCode
        ];
        
        $answer = [
            'question'=>$question,
            'answer'=>$answer,
        ];
        
        $userInfo = Db::transaction(function () use($userInfo,$answer) {
            $id = Db::name('user')->insertGetId($userInfo);
            $answer['user_id'] = $id;
            Db::name('user_answer')->insert($answer);
            $userInfo['id'] = $id;
            
            //开通钱包
            Db::name('account')->insert(['user_id'=>$id,'password'=>$userInfo['password']]);
            
            return $userInfo;
        });
        
        user_event($userInfo['id'], 'regist');
        user_event($userInfo['id'], 'login');
        
        return action_succ($this->loginSuccBack($userInfo,true));
    }
    
    
    /**
     * 登入成功后返回的数据
     * @param array $userInfo
     * @param boolean $regist 是否为注册
     * @return array
     */
    private function loginSuccBack($userInfo,$regist = false){

        $token = password_encrypt($userInfo['id'].time());
        $userInfo['token']=$token;
        // $ip = $this->request->ip();
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];;
        if(!$regist){
            //删了旧TOKEN 生成新token
            Db::name("user_token")->where(['user_id'=>$userInfo['id']])->delete();
            
            //修改用户登入时间
            Db::name("user")->where(["id"=>$userInfo['id']])->update(['error'=>0,'error_dt'=>null,'login_time'=>time(),'ip'=>$ip]);
        }
        
        Db::name("user_token")->insert(array('token'=>$token,'user_id'=>$userInfo['id'],'ip'=>$ip));
        
        $rooturl = $this->request->domain()."/";
        //头像处理
        $headimg = empty($userInfo['imgurl'])?false:$userInfo['imgurl'];
        if(!$headimg){
            $headimg = $rooturl."images/avatar.png";
        }elseif(strpos($headimg,"http")===false && strpos($headimg,"https")===false ){
            $headimg = $rooturl.$headimg;
        }
        
        $vipcards = get_vipcards();
        
        //返回数据
        $data = array(
            'id'=>$userInfo['id'],
            'name'=>$userInfo['name'],
            'tel'=>$userInfo['tel'],
            'username'=>$userInfo['username'],
            'imgurl'=>$headimg,
            'sex'=>$userInfo['sex'],
            'address'=>$userInfo['address'],
            'vip'=>$vipcards[$userInfo['vipcard_id']],
            'vipcard_id'=>$userInfo['vipcard_id'],
            'share_code'=>$userInfo['share_code'],
            'token'=>$token
        );
        
        return $data;
    }
    
    
    private function createCodeStr($length = 8) {
        $chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $char= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
            $str.=$char;
        }
        
        return $str;
    }
}
