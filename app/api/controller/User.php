<?php
namespace app\api\controller;

use app\api\BaseController;
use think\facade\Db;
use think\response\Json;
use Grafika\Grafika;
use think\facade\Config;

class User extends BaseController{
    private $msgs;
    protected function initialize(){
        $this->msgs = config("msg");
    }
    
    /**
     * 今日直属下级充值任务
     */
    public function todayRechargeTask(){
        $msg=Config::get("msg");
        $data = [
            'count'=>0,//充值数量
            'set'=>0,//任务数量
            'reward'=>0,//奖励
            'status'=>$msg['today_recharge_task_status_uncomplete'],//状态
        ];
        
        
        $config = get_config();
        if(!empty($config['today_task_recharge_count'])){
            $data['set'] = $config['today_task_recharge_count'];
        }
        
        if(!empty($config['today_task_recharge_reward'])){
            $data['reward'] = $config['today_task_recharge_reward'];
        }
        
        //直属下级充值人数
        $time = strtotime(date("Y-m-d"));
        $sql = "select count(1) s from (select a.user_id from t_recharge a inner join t_user b on a.user_id=b.id where a.status=1 and a.create_time>=$time and b.top1=$this->userId group by a.user_id) b";
        $res = Db::query($sql);
        if($res && $res[0]['s']){
            $count = $res[0]['s'];
            $data['count']=$count;
            if($count>=$config['today_task_recharge_count']){
                $data['status']=$msg['today_recharge_task_status_complete'];
            }
        }
        
        return action_succ($data);
    }
    
    /**
     * 开通助手
     * @return Json
     */
    public function assistantOpen(){
        
        $account = Db::name("account")->where("user_id",$this->userId)->find();
        $money = 300;
        
        if($account['balance']<$money){
            return action_error($this->msgs['balance_error']);
        }
        
        $info = Db::name("user_assistant")->where("user_id",$this->userId)->find();
        $status = false;
        if($info){
            if($info['dt_end']>time()){
                $status = true;
            }else{
                Db::name("user_assistant")->where("user_id",$this->userId)->delete();
            }
        }
        
        if(!$status){
            
            Db::transaction(function () use ($account,$money) {
                $user_id = $account['user_id'];
                Db::name("user_assistant")->insert([
                    'user_id'=>$account['user_id'],
                    'dt_end'=>strtotime("+10 DAYS")
                ]);
                
                //扣除账户余额
                Db::name("account")->where('id',$account['id'])->dec('balance', $money)->update();
                
                //添加流水记录
                Db::name("account_log")->insert([
                    'gp'=>6,
                    'type'=>62,
                    'user_id'=>$user_id,
                    'money'=>0-$money,
                    'create_time'=>time(),
                ]);
            });
            $status = true;
        }
        return action_succ($status);
    }
    
    /**
     * 获取助手状态
     * @return Json
     */
    public function assistantStatus(){
        $info = Db::name("user_assistant")->where("user_id",$this->userId)->find();
        $status = false;
        if($info){
            if($info['dt_end']>time()){
                $status = true;
            }else{
                Db::name("user_assistant")->where("user_id",$this->userId)->delete();
            }
        }
        
        return action_succ($status);
    }
    
    /**
     * 生成二维码
     * @return Json
     */
    public function createQrcode(){
        $qrcode = create_user_qrcode($this->userId,120);
        return action_succ($qrcode);
    }
    
    /**
     * 生成海报
     * @return Json
     */
    public function createPosters(){
        $rootPath = $this->app->getRootPath()."public";
        $rooturl = $this->request->domain();
        
        //文件保存根路径
        $saveRootPath = $rootPath.DIRECTORY_SEPARATOR."upload".DIRECTORY_SEPARATOR."userimg".DIRECTORY_SEPARATOR.$this->userId.DIRECTORY_SEPARATOR."poster";
        xmkdir($saveRootPath);
        
        //海报图
        $posers = [
            "/images/poster/1.png",
            "/images/poster/2.png",
            "/images/poster/3.png",
        ];
        
        $datas=[];
        foreach ($posers as $img){
            $savePath = $saveRootPath.DIRECTORY_SEPARATOR.md5($img).".jpg";
            
            if(!file_exists($savePath)){
                $area = [
                    'width'=>196,
                    'x1'=>30,
                    'y1'=>490
                ];
                
                $qrcode = create_user_qrcode($this->userId,$area['width']);
                
                //$rooturl2 = str_replace("api.","h5.",$rooturl);
                //二维码路径
                $qrcode = str_replace($rooturl, $rootPath, $qrcode);
                
                //背景图片路径
                $bgi = $rootPath.$img;
                
                $editor = Grafika::createEditor();
                $editor->open($image1 , $bgi);
                $editor->open($image2 , $qrcode);
                $editor->blend ( $image1, $image2 , 'normal', 1, 'top-left',$area['x1'],$area['y1']);
                $editor->save($image1,$savePath,'jpeg');
            }
            
            $rootPath = str_replace("\\", "/", $rootPath);
            $savePath = str_replace("\\", "/", $savePath);
            
            $url = str_replace($rootPath, $rooturl, $savePath);
            
            $datas[]=[
                'name'=>"",
                'image'=>$url,
            ];
        }
        
        
        return action_succ($datas);
    }
    
    /**
     * 保存用户个人信息
     * @return Json
     */
    public function save(){
        $post = $this->request->post();
        $data = [
            'name'=>$post['name'],
            'tel'=>$post['tel'],
            'sex'=>$post['sex'],
            'address'=>$post['address']
        ];
        Db::name("user")->where("id",$this->userId)->update($data);
        return action_succ();
    }
    
    /**
     * 设置用户头像
     * @return Json
     */
    public function setHead(){
        $head = $this->request->post("head");
        
        if($head){
            $rooturl = $this->request->domain();
            $head = str_replace($rooturl, "", $head);
            Db::name("user")->where('id',$this->userId)->update(['imgurl'=>$head]);
        }
        
        return action_succ();
    }
    
    /**
     * 获取系统所有头像
     */
    public function getHeads(){
        $path = $this->app->getRootPath()."public/images/heads";
        $temp=scandir($path);
        $files=[];
        $rooturl = $this->request->domain()."/images/heads/";
        foreach ($temp as $v){
            if($v=='.' || $v=='..'){//判断是否为系统隐藏的文件.和..  如果是则跳过否则就继续往下走，防止无限循环再这里。
                continue;
            }
            
            $files[]=$rooturl.$v;
        }
        return action_succ($files);
    }
    /**
     * 修改登入密码
     * @return Json
     */
    public function updateLoginPassword(){
        $answer = $this->request->post("answer");
        $password = $this->request->post("password");
        if(!$answer || !$password){
            return action_error($this->msgs['param_error']);
        }
        
        $question = Db::name("user_answer")->where("user_id",$this->userId)->find();
        if($question['answer']!=$answer){
            return action_error($this->msgs['answer_error']);
        }
        
        Db::name("user")->where("id",$this->userId)->update(['password'=>password_encrypt($password)]);
        
        return action_succ();
    }
    
    /**
     * 修改提现密码
     * @return Json
     */
    public function updateCashPassword(){
        $answer = $this->request->post("answer");
        $password = $this->request->post("password");
        if(!$answer || !$password){
            return action_error($this->msgs['param_error']);
        }
        
        $question = Db::name("user_answer")->where("user_id",$this->userId)->find();
        if($question['answer']!=$answer){
            return action_error($this->msgs['answer_error']);
        }
        
        Db::name("account")->where("user_id",$this->userId)->update(['password'=>password_encrypt($password)]);
        
        return action_succ();
    }
    
    /**
     * 密保问题
     * @return Json
     */
    public function question(){
        $question = Db::name("user_answer")->field("id,question")->where("user_id",$this->userId)->find();
        return action_succ($question);
    }

    /**
     * 账号余额
     * @return Json
     */
    public function balance(){
        //余额
        $balance = Db::name("account")->where("user_id",$this->userId)->value("balance");
        return action_succ($balance);
    }
    
    /**
     * 会员卡
     * @return Json
     */
    public function vipcard(){
        //取用户当前等级
        $vipid= Db::name("user")->where("id",$this->userId)->value("vipcard_id");
        $info = get_vipcards($vipid);
        
        $icon=$info['icon']?$info['icon']:'/images/icon.png';
        
        $info['icon'] = $this->request->root(true).$icon;
        
        return action_succ($info);
    }
    
    /**
     * 会员USTD设置信息
     * @return Json
     */
    public function getUstd(){
        $info= Db::name("user_ustd")->where("user_id",$this->userId)->find();
        return action_succ($info);
    }
    
    /**
     * 保存会员USTD设置信息
     * @return Json
     */
    public function saveUstd(){
        $link = $this->request->post("link");
        $answer = $this->request->post("answer");
        $question = Db::name("user_answer")->where("user_id",$this->userId)->find();
        if($question['answer']!=$answer){
            return action_error($this->msgs['answer_error']);
        }
        
        $info= Db::name("user_ustd")->where("user_id",$this->userId)->find();
        if($info){
            Db::name("user_ustd")->where("user_id",$this->userId)->update([
                'link'=>$link
            ]);
        }else{
            Db::name("user_ustd")->insert([
                'user_id'=>$this->userId,
                'link'=>$link
            ]);
        }
        return action_succ();
    }
    
    /**
     * 剩余任务数
     */
    public function taskLast(){
        //用户信息
        $vipcard = Db::name("user")->where('id',$this->userId)->value("vipcard_id");
        $vipcard = get_vipcards($vipcard);
        $task_max = $vipcard['task_max'];
        
        $count = get_today_count($this->userId);
        $last = $task_max-$count['today_task'];
        if($last<0) $last=0;
        
        return action_succ($last);
    }
    
    /**
     * 获取当天统计数据
     * @return Json
     */
    public function todayCount(){
        $userId = $this->request->post("user");
        //用户信息
        $vipcard = Db::name("user")->where('id',$userId)->value("vipcard_id");
        $vipcard = get_vipcards($vipcard);
        $task_max = $vipcard['task_max'];
        
        //余额
        $balance = Db::name("account")->where("user_id",$userId)->value("balance");
        
        //今日收益
        $count = get_today_count($userId);
        $last = $task_max-$count['today_task'];
        if($last<0) $last=0;
        $data=[
            'balance'=>$balance,
            'income'=>$count['today_income'],
            'task'=>$count['today_task'],
            'last'=>$last
        ];
        
        return action_succ($data);
    }
    
    /**
     * 获取银行卡
     * @return Json
     */
    public function bank(){
        $userId = $this->request->post("user");
        $data = Db::name("bank")->where("user_id",$userId)->find();
        return action_succ($data);
    }
        
}
