<?php
namespace app\api\controller;

use app\api\BaseController;
use think\facade\Config;
use think\facade\Db;
use think\response\Json;

class Cash extends BaseController{
    
    /**
     * 提现记录
     * @return Json
     */
    public function list(){
        $p = $this->request->post('page');
        
        if(!$p) $p=1;
        
        $pageSize = 20;
        $limit = ($p-1)*$pageSize;
        
        $wheres = [];
        $wheres[]=['user_id','=',$this->userId];
        //$wheres[]=['status','=',1];
        
        $model = Db::name("cash")->field("id,money,create_time,fee,status");
        
        $list = $model->where($wheres)->order("id desc")
        ->limit($limit,$pageSize)
        ->select();
        
        $statues = Config::get("sys.cash_status");
        foreach ($list as $key=>$val){
            $val['create_time'] = date('Y-m-d H:i',$val['create_time']);
            $val['status_name'] = $statues[$val['status']];
            $list[$key]=$val;
        }
        
        return action_succ($list);
    }
    
    /**
     * 保存提现申请信息
     * @return Json
     */
    public function save(){
        $msgs = config("msg");
        //提现时间限制
        $hour = intval(date("H"));
        $config = get_config();
        
        //系统开放时间限制
        if($hour<intval($config['open_begin_hour']) || $hour>=intval($config['open_end_hour'])){
            return action_error($msgs['system_open_time']."{$config['open_begin_hour']}:00~{$config['open_end_hour']}:00");
        }
        
        
        
        $post = $this->request->post();
        $config = get_config();
        
        //提现金额必须是整数
        $money = intval($post['money']);
        if($money!=$post['money']){
            return action_error($msgs['cash_money_int']);
        }
        
        
        $channel = $post['channel'];
        $password = $post['password'];
        
        if(!$password || !$channel || !$money){
            return action_error($msgs['param_error']);
        }
        
        $fee = $config['cash_fee'];//提现费用
        $money2=0;
        if($channel==1){
            //最小提现金额限制
            if($money<$config['cash_min']){
                return action_error($msgs['cash_money_min'].$config['cash_min']);
            }
            
            //提现金额倍数限制
            if($money%$config['cash_multiple']){
                return action_error(str_replace("{p1}", $config['cash_multiple'], $msgs['cash_money_multiple']));
            }
        }else{
            $ustd = get_config("ustd");
            $money2 = $money;
            $money = floor($money2/$ustd['rate']);
            $fee=$config['cash_fee'];
        }
        
        
        //LV.0 不能提现
        $vipid = Db::name("user")->where("id",$this->userId)->value("vipcard_id");
        if($vipid=="1897"){
            return action_error($msgs['cash_vip_error']);
        }
        
        //每日提交次数限制
        if(!empty($config['cash_day_max'])){
            $count = Db::name("cash")->where("user_id",$this->userId)->where("create_time",">",strtotime(date('Y-m-d')))->count();
            if($count>=$config['cash_day_max']){
                return action_error($msgs['cash_today_max']);
            }
        }
        
        //账户信息
        $account = Db::name("account")->field("id,balance,password")->where("user_id",$this->userId)->find();
        $balance = $account['balance'];
        
        //余额必须大于提现金额
        if($balance<$money){
            return action_error($msgs['balance_error']);
        }
        
        if(password_encrypt($password)!=$account['password']){
            return action_error($msgs['cash_password_error']);
        }
        
        //绑定银行卡
        $userBank = Db::name("bank")->where("user_id",$this->userId)->find();
        if(!$userBank){
            if($channel==1){
                return action_error($msgs['bind_bank']);
            }else{
                $userBank=[
                    'bank_name'=>'',
                    'branch'=>'',
                    'account'=>'',
                    'user_name'=>'',
                ];
            }
            
        }
        
        //用户USTD信息
        $userUstd = Db::name("user_ustd")->where("user_id",$this->userId)->find();
        if(!$userUstd){
            if($channel==2){
                return action_error($msgs['set_usdt']);
            }else{
                $userUstd=[
                    'link'=>'',
                ];
            }
        }
        
        $data = [
            'channel'=>$channel,
            'user_bank_num'=>$userBank['bank_num'],
            'user_bank_name'=>$userBank['bank_name'],
            'user_bank_branch'=>$userBank['branch'],
            'user_bank_username'=>$userBank['user_name'],
            'user_bank_account'=>$userBank['account'],
            'ustd_link'=>$userUstd['link'],
            'user_id'=>$this->userId,
            'money'=>$money,
            'money2'=>$money2,
            'create_time'=>time(),
            'fee'=>$fee,
        ];
        
        $cashId = Db::transaction(function () use($data,$account){
            $cashId = Db::name("cash")->insertGetId($data);
            
            $user_id = $data['user_id'];
            $money = $data['money'];
            
            //修改账户余额
            Db::name("account")->where('id',$account['id'])->dec('balance', $money)->update();
            
            //添加流水记录
            Db::name("account_log")->insert([
                'gp'=>5,
                'type'=>51,
                'user_id'=>$user_id,
                'money'=>0-$money,
                'create_time'=>time(),
            ]);
            
            return $cashId;
        });
        
        return action_succ($cashId);
    }
        
}
