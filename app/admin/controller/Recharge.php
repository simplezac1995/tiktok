<?php
namespace app\admin\controller;
use app\admin\DbController;
use think\facade\Db;
class Recharge extends DbController{
    public function add($ary=[]){
        return action_error("禁止添加申请");
    }
    
    public function edit($ary=[]){
        return action_error("禁止修改申请");
    }
    
    public function del($id=null) {
        return action_error("禁止删除申请");
    }
    
    public function exportToday(){
        $time = strtotime(date("Y-m-d"));
        $list = Db::name("recharge")->where('create_time','>',$time)->order("id")->select();
        
        //字典
        $status = config("sys.recharge_status");
        $vipcards = get_vipcards();
        $vipcards[0] = '无';
        
        $data=[];
        $data[]=["ID","会员名称","购买等级","支付方式","充值金额","USDT个数","充值账户","USDT地址","状态","提交时间"];
        foreach ($list as $key=>$val){
            
            $val['username'] = Db::name("user")->where("id",$val['user_id'])->value("username");
            $val['status_name'] = $status[$val['status']];
            $val['vipcard'] = $vipcards[$val['vipcard_id']];
            $val['channel'] = $val['channel']==2?'USDT':'线下银行卡';
            $val['create_time'] = date("Y-m-d H:i:s",$val['create_time']);
            
            $sysbank = "银行：{$val['sys_bank_name']}\n支店：{$val['sys_bank_branch']}\n店番：{$val['sys_bank_num']}\n姓名：{$val['sys_bank_username']}\n账号：{$val['sys_bank_account']}";
            
            $data[]=[
                $val['id'],$val['username'],$val['vipcard'],$val['channel'],$val['money'],$val['money2'],$sysbank,$val['user_usdt_link'],$val['status_name'],$val['create_time']
            ];
        }
        
        $title = "充值数据".date("m月d日");
        export_excel($data,$title);
    }
    
    
    public function  list(){
        $res = parent::listData();
        $list = $res['data'];
        //字典
        $status = config("sys.recharge_status");
        $vipcards = get_vipcards();
        $vipcards[0] = '无';
        $config = get_config("ustd");
        foreach ($list as $key=>$val){
            $val['username'] = Db::name("user")->where("id",$val['user_id'])->value("username");
            $val['status_name'] = $status[$val['status']];
            $val['higher_top'] = Db::name("user")->where("id",$val['user_id'])->value("higher_top");
            $val['vipcard'] = $vipcards[$val['vipcard_id']];
            $val['channel'] = $val['channel']==2?'USDT':'线下银行卡';
            //$val['create_time'] = date("Y-m-d H:i:s",$val['create_time']);
            if(!$val['status']) $val['status_name'] = '<div style="color:red">'.$val['status_name'].'</div>';
            if($val['channel']!='USDT'){
                $val['sysbank'] = "银行：{$val['sys_bank_name']}</br>支店：{$val['sys_bank_branch']}</br>店番：{$val['sys_bank_num']}</br>姓名：{$val['sys_bank_username']}</br>账号：{$val['sys_bank_account']}";
                $val['user_usdt_link'] = "";
                $val['sys_usdt_link'] = "";
            }else{
                $val['sysbank'] = "";
                $val['sys_usdt_link'] = $config['recharge_link'];
            }
            $list[$key]=$val;
        }
        
        $res['data']=$list;
        return $res;
    }
    
    /**
     * 列表统计数据
     */
    public function listReport(){
        $wheres = $this->getWhere();
        $totalMoney = Db::name("recharge")->where($wheres)->sum("money");
        $totalUsdt = Db::name("recharge")->where($wheres)->where("channel",2)->sum("money2");
        return action_succ([
            'totalMoney'=>$totalMoney,
            'totalUsdt'=>$totalUsdt
        ]);
    }
    
    /**
     * 详情
     */
    public function info(){
        //字典
        $status = config('sys.recharge_status');
        $vipcards = get_vipcards();
        $vipcards[0] = '无';
        
        
        $info = parent::infoData();
        
        $user = Db::name("user")->field("name,tel")->find($info['user_id']);
        
        $info['username'] = $user['name'];
        $info['tel'] = $user['tel'];
        $info['vipcard'] = $vipcards[$info['vipcard_id']];
        
        $info['status_name']=$status[$info['status']];
        
        $info['channel'] = $info['channel']==2?'USDT':'线下银行卡';
        
        return action_succ($info);
    }
    
    /**
     * 批量审核
     * @return Json
     */
    public function batchCheck(){
        $ids = $this->request->post("ids");
        $status = $this->request->post("status");
        $memo = $this->request->post("memo");
        $s = $this->request->post("status");
        $status = -1;//驳回
        if($s=="1"){
            $status=1;//通过
        }
        
        $userInfo = get_login_info();
        $check_user = $userInfo['id'];
        
        foreach ($ids as $id){
            $info = Db::name("recharge")->find($id);
            if($info['status']){
                continue;
            }
            
            Db::transaction(function () use($status,$info,$memo,$check_user) {
                
                Db::name("recharge")->where("id",$info['id'])->update([
                    'status'=>$status,
                    'check_user'=>$check_user,
                    'check_time'=>time(),
                    'check_memo'=>$memo,
                ]);
                
                if($status==1 && $info['status']!=1){
                    if($info['vipcard_id']){//会员升级充值
                        $c=Db::name("user")->where("id",$info['user_id'])->update(['vipcard_id',$info['vipcard_id']]);
                    }else{
                        //修改账户余额
                        $money = $info['money'];
                        $user_id = $info['user_id'];
                        $c = Db::name('account')->where('user_id', $user_id)->inc('balance', $money)->inc('recharge', $money)->update();
                        //添加流水记录
                        Db::name("account_log")->insert([
                            'gp'=>1,
                            'type'=>12,
                            'user_id'=>$user_id,
                            'money'=>$money,
                            'create_time'=>time(),
                            'memo'=>'充值申请审核通过'
                        ]);
                    }
                    
                    return $c;
                }
            });
        }
        
        return action_succ();
    }
    
    /**
     * 审核
     * @return Json
     */
    public function check(){
        $id = $this->request->post("id");
        $memo = $this->request->post("memo");
        $s = $this->request->post("status");
        $status = -1;//驳回
        if($s=="1"){
            $status=1;//通过
        }
        
        $info = Db::name("recharge")->find($id);
        
        if($info['status']){
            return action_error("状态异常，禁止审核");
        }
        
        
        $userInfo = get_login_info();
        $check_user = $userInfo['id'];
        //$memo = $this->request->post("memo");
        Db::transaction(function () use($status,$info,$memo,$check_user) {
            
            Db::name("recharge")->where("id",$info['id'])->update([
                'status'=>$status,
                'check_user'=>$check_user,
                'check_time'=>time(),
                'check_memo'=>$memo
            ]);
            
            if($status==1 && $info['status']!=1){
                if($info['vipcard_id']){//会员升级充值
                    $c=Db::name("user")->where("id",$info['user_id'])->update(['vipcard_id'=>$info['vipcard_id']]);
                }else{
                    //修改账户余额
                    $money = $info['money'];
                    $user_id = $info['user_id'];
                    $c = Db::name('account')->where('user_id', $user_id)->inc('balance', $money)->inc('recharge', $money)->update();
                    //添加流水记录
                    Db::name("account_log")->insert([
                        'gp'=>1,
                        'type'=>12,
                        'user_id'=>$user_id,
                        'money'=>$money,
                        'create_time'=>time(),
                        'memo'=>'充值申请审核通过'
                    ]);
                }
                
                //佣金计算
                Db::execute('call p_recharge_brokerage(:id)',['id'=>$info['id']]);
                
                //今日任务直属下级充值奖励
                $this->todayReward($info['user_id'], $info['create_time']);
                
                return $c;
            }
        });
        
        return action_succ();
    }
    
    /**
     * 今日任务直属下级充值奖励
     * @param int $user_id 充值人ID
     * @param int $time 充值时间
     */
    private function todayReward($user_id,$time){
        $config = get_config();
        if(empty($config['today_task_recharge_count']) || empty($config['today_task_recharge_reward'])){
            //没设置人数或奖励
            return;
        }
        
        $userId = Db::name("user")->where("id",$user_id)->value("top1");
        if($userId){
            //直属下级充值人数
            $time = strtotime(date("Y-m-d",$time));
            $sql = "select count(1) s from (select a.user_id from t_recharge a inner join t_user b on a.user_id=b.id where a.status=1 and a.create_time>=$time and b.top1=$userId group by a.user_id) b";
            $res = Db::query($sql);
            if($res && $res[0]['s']){
                $count = $res[0]['s'];
                if($count==$config['today_task_recharge_count']){//这里不能用大于等于，否则会重复发放奖励
                    //修改账户余额
                    $money = $config['today_task_recharge_reward'];
                    Db::name('account')->where('user_id', $userId)->inc('balance', $money)->inc('income', $money)->update();
                    //添加流水记录
                    Db::name("account_log")->insert([
                        'gp'=>7,
                        'type'=>71,
                        'user_id'=>$userId,
                        'money'=>$money,
                        'create_time'=>time(),
                        'memo'=>'直属下级充值奖励'
                    ]);
                }
            }
        }
    }
    
    protected function getWhere(){
        $post = $this->request->post();
        $wheres = [];
        
        if(!isset($post['status'])){
            $wheres[]=['status','=',0];
        }else if($post['status']=="2"){
            $wheres[]=['status','=',-1];
        }else if($post['status'] || $post['status']==="0"){
            $wheres[]=['status','=',$post['status']];
        }
        
        if(!empty($post['user_id'])){
            $wheres[]=['user_id','=',$post['user_id']];
        }
        
        if(!empty($post['tel'])){
            $this->getModel()->whereRaw("user_id in (select id from t_user where username like '{$post['tel']}%')");
        }
        
        if(!empty($post['date_range'])){
            $dates  = explode(" ~ ", $post['date_range']);
            $begin = strtotime($dates[0])-1;
            $end = strtotime($dates[1]." 23:59:59")+1;
            $wheres[]=['create_time','between',[$begin,$end]];
        }
        
        return $wheres;
    }
}
