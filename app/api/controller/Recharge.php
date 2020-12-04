<?php
namespace app\api\controller;

use app\api\BaseController;
use think\facade\Config;
use think\facade\Db;
use think\response\Json;
use think\facade\Cache;

class Recharge extends BaseController{
    private $msgs;
    protected function initialize(){
        $this->msgs = config("msg");
    }
    
    /**
     * 购买记录
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
        
        $model = Db::name("recharge")->field("id,vipcard_id,money,create_time,status");
        
        $list = $model->where($wheres)->order("id desc")
        ->limit($limit,$pageSize)
        ->select();
        
        $statues = Config::get("sys.recharge_status");
        $vips = get_vipcards();
        foreach ($list as $key=>$val){
            $val['create_time'] = date('Y-m-d',$val['create_time']);
            $val['vip'] = $vips[$val['vipcard_id']];
            $val['status_name'] = $statues[$val['status']];
            $list[$key]=$val;
        }
        
        return action_succ($list);
    }
    
    /**
     * 获取最新充值消息
     * @return Json
     */
    public function newRecharge(){
        // $datas = Cache::get("newRecharge");
        // if(!$datas){
        //     $vipcards = get_vipcards();
        //     $list = Db::name("recharge")->where("vipcard_id",">",0)->field("user_id,vipcard_id,create_time,user_bank_username as user")->order("id desc")->limit(5)->select();
        //     $datas = [];
        //     foreach ($list as $val){
        //         $card = $vipcards[$val['vipcard_id']];
        //         $secand = time()-$val['create_time'];
        //         $time = floor($secand/60);
        //         $fix = $this->msgs['second'];
                
        //         if($time>=60){
        //             $time = floor($time/60);
        //             $fix = $this->msgs['minute'];
        //             if($time>=60){
        //                 $time = floor($time/60/60);
        //                 $fix = $this->msgs['hour'];
        //             }
        //         }
        //         $fix = $time >1? $fix."s": $fix;
        //         $tel = Db::name("user")->where("id",$val['user_id'])->value("username");
        //         $tel = tel_formate($tel);
        //         $datas[]=str_replace(['{p1}','{p2}'], [$tel,$card], $this->msgs['buy_notice']).$time.' '.$fix." ago！！！";
        //     }
            
        //     Cache::set("newRecharge",$datas,60);
        // }

        $datas = Cache::get("newRecharge");
        if(!$datas){
            $vipcards = get_vipcards();
            $list = [];
            $i = 0;
            while($i<200){
                $i++;

                $list[$i]['create_time'] = strtotime(date("Y-m-d"),time())+3600+rand(60, 3600);//当日凌晨的时间戳
                if($i%2==0){
                    $randkey = $this->randomkeys(6);
                    $list[$i]['user'] = $randkey.'@gmail.com';
                }else{
                    $randkey = $this->randomnum(8);
                    $list[$i]['user'] = $randkey;
                }
            }


            shuffle($list);
            $datas = [];
            foreach ($list as $val){
                $card = $vipcards[rand(1898, 1900)];
                $secand = time()-$val['create_time'];
                $time = floor($secand/60);
                $fix = $this->msgs['second'];
                
                if($time>=60){
                    $time = floor($time/60);
                    $fix = $this->msgs['minute'];
                    if($time>=60){
                        $time = floor($time/60/60);
                        $fix = $this->msgs['hour'];
                    }
                }
                $fix = $time >1? $fix."s": $fix;
                $tel = $val['user'];
                $tel = tel_formate($tel);
                $datas[]=str_replace(['{p1}','{p2}'], [$tel,$card], $this->msgs['buy_notice']).$time.' '.$fix." ago！！！";
            }
            Cache::set("newRecharge",$datas,60);
        }

        $hour = intval(date("H"));
        $config = get_config();
        if($hour<intval($config['open_begin_hour']) || $hour>=intval($config['open_end_hour'])){
            $datas = [];
        }
        return action_succ($datas);
    }
    
    /**
     * 保存充值申请
     * @return Json
     */
    public function save(){
        
        //提现时间限制
        $hour = intval(date("H"));
        $config = get_config();
        if($hour<intval($config['open_begin_hour']) || $hour>=intval($config['open_end_hour'])){
            return action_error($this->msgs['system_open_time']."{$config['open_begin_hour']}:00~{$config['open_end_hour']}:00");
        }
        
        $memo = $this->request->post("memo");
        $vipcard_id = $this->request->post("vipcard_id");
        $bank_id = $this->request->post("bank_id");
        $sn = $this->request->post("sn");
        $channel = $this->request->post("channel");
        
        if(!$vipcard_id || !$bank_id || !$sn || !$channel){
            return action_error($this->msgs['param_error']);
        }
        
        //会员银行卡
        $userBank = Db::name("bank")->where("user_id",$this->userId)->find();
        if(!$userBank){
            $userBank=[
                'bank_name'=>'',
                'branch'=>'',
                'account'=>'',
                'user_name'=>'',
                'bank_num'=>'',
            ];
            //return action_error("未绑定银行卡");
        }
        
        //会员USTD
        $userUstd = Db::name("user_ustd")->where("user_id",$this->userId)->find();
        if(!$userUstd){
            $userUstd=[
                'link'=>'',
            ];
        }
        
        $sysBank = Db::name("bank")->where(['user_id'=>0,'id'=>$bank_id])->find();
        
        $card = get_vipcards($vipcard_id);
        
        $money = 0;
        if($channel==2){ //USTD 时转美元
            $money = $card['usdt'];
        }
        
        $data=[
            'user_id'=>$this->userId,
            'sn'=>$sn,
            'channel'=>$channel,
            'vipcard_id'=>$vipcard_id,
            'user_bank_name'=>$userBank['bank_name'],
            'user_bank_branch'=>$userBank['branch'],
            'user_bank_account'=>$userBank['account'],
            'user_bank_username'=>$userBank['user_name'],
            'user_bank_num'=>$userBank['bank_num'],
            'user_usdt_link'=>$userUstd['link'],
            'money'=>$card['price'],
            'money2'=>$money,
            'create_time'=>time(),
            'memo'=>$memo,
            'sys_bank_name'=>$sysBank['bank_name'],
            'sys_bank_branch'=>$sysBank['branch'],
            'sys_bank_account'=>$sysBank['account'],
            'sys_bank_username'=>$sysBank['user_name'],
            'sys_bank_num'=>$sysBank['bank_num'],
        ];
        
        $id = Db::name("recharge")->insertGetId($data);
        
        return action_succ($id);
    }
     
    public function randomkeys($length) {
        $returnStr='';
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        for($i = 0; $i < $length; $i ++) {
            $returnStr .= $pattern[mt_rand (0, 61)]; //生成php随机数
        }
        return $returnStr;
    }   

    public function randomnum($length) {
        $returnStr='';
        $pattern = '1234567890';
        for($i = 0; $i < $length; $i ++) {
            $returnStr .= $pattern[mt_rand (0, 9)]; //生成php随机数
        }
        return $returnStr;
    }   
}
