<?php
namespace app\admin\controller;
use app\admin\DbController;
use think\facade\Db;
class Cash extends DbController{
    
    public function exportToday(){
        $time = strtotime(date("Y-m-d"));
        $list = Db::name("cash")->where('create_time','>',$time)->order("id")->select();
        
        //字典
        $status = config("sys.cash_status");
        
        $data=[];
        $data[]=["ID","会员ID","电话","提现方式","提现金额","USDT个数","手续费","实际提现金额",
        // "店番","银行","支店","姓名","账号",
        "USDT地址","提交时间","状态"];
        foreach ($list as $val){
            $user = Db::name("user")->field("id,username,tel")->where("id",$val['user_id'])->find();
            $val['username'] = $user['username'];
            $val['tel']=$user['tel'];
            $val['status_name']=$status[$val['status']];
            // if($val['channel']=='1'){
            $val['money_real']=x_number_format2($val['money']-$val['fee']);
            // }else{
            //     $val['money_real']=0;
            // }
            
            $val['channel'] = $val['channel']==2?'USDT':'线下银行卡';
            $val['create_time'] = date("Y-m-d H:i:s",$val['create_time']);
            
            
            $data[]=[
                $val['id'],$user['id'],$val['username'],$val['channel'],$val['money'],$val['money2'],$val['fee'],$val['money_real'],
                // $val['user_bank_num'],$val['user_bank_name'],$val['user_bank_branch'],$val['user_bank_username'],$val['user_bank_account'],
                $val['ustd_link'],$val['create_time'],$val['status_name']
            ];
        }
        
        $title = "提现数据".date("m月d日");
        export_excel($data,$title);
    }
    
    public function list(){
        //字典
        $status = config('sys.cash_status');
        $res = parent::listData();
        $list = [];

        $vipcardList = Db::name("vipcard")->field("name,id")->select()->toArray();
        $vipcardArr = array_column($vipcardList, 'name', 'id');
        foreach ($res['data'] as $val){
            $user = Db::name("user")->field("username,tel,is_inside,vipcard_id")->where("id",$val['user_id'])->find();
            $val['username'] = $user['username'];
            $val['tel']=$user['tel'];
            $val['status_name']=$status[$val['status']];
            $val['higher_top'] = Db::name("user")->where("id",$val['user_id'])->value("higher_top");
            if($val['status']==0){
                $val['status_name']='<span style="color:red">待审核</span>';
            }
            
            // if($val['channel']=='1'){
                $val['money_real']=x_number_format2($val['money']-$val['fee']);
            // }else{
            //     $val['money_real']=0;
            // }
            
            $val['channel'] = $val['channel']==2?'USDT':'线下银行卡';
            $val['is_inside'] = $user['is_inside']==1?'是':'否';

            $val['level'] = $vipcardArr[$user['vipcard_id']];
            $list[]=$val;
        }
        
        $res['data']=$list;
        
        return json($res);
    }
    
    /**
     * 列表统计数据
     */
    public function listReport(){
        $wheres = $this->getWhere();
        $money = Db::name("cash")->where($wheres)->sum("money");
        $fee = Db::name("cash")->where($wheres)->sum("fee");
        $real = x_number_format($money-$fee);
        
        $totalUsdt = Db::name("cash")->where($wheres)->where("channel",2)->sum("money2");
        return action_succ([
            'money'=>$money,
            'fee'=>$fee,
            'real'=>$real,
            'totalUsdt'=>$totalUsdt,
        ]);
    }
    
    /**
     * 详情
     */
    public function info(){
        //字典
        $status = config('sys.cash_status');
        
        
        $info = parent::infoData();
        
        $user = Db::name("user")->field("name,tel")->find($info['user_id']);
        
        $info['username'] = $user['name'];
        $info['tel'] = $user['tel'];
        
        $info['status_name']=$status[$info['status']];
        
        return action_succ($info);
    }
    
    /**
     * 批量审核
     */
    public function batchCheck(){
        $ids = $this->request->post("ids");
        $memo = $this->request->post("memo");
        $s = $this->request->post("status");
        
        $status = -1;//驳回
        if($s=="1"){
            $status=1;//通过
        }
        $userInfo = get_login_info();
        foreach ($ids as $id){
            $cashInfo = Db::name("cash")->find($id);
            if(!$cashInfo){
                continue;
            }
            
            if($cashInfo['status']!="0"){
                continue;
            }
            
            Db::transaction(function () use($cashInfo,$status,$memo,$userInfo){
                Db::name("cash")->where("id",$cashInfo['id'])->update([
                    'status'=>$status,
                    'memo'=>$memo,
                    'check_time'=>time(),
                    'check_user'=>$userInfo['id'],
                ]);
                $money = $cashInfo['money'];
                $user_id = $cashInfo['user_id'];
                if($status==-1){//审核不通过，余额回流
                    //修改账户余额
                    Db::name("account")->where('user_id',$user_id)->inc('balance', $money)->update();
                    
                    //添加流水记录
                    Db::name("account_log")->insert([
                        'gp'=>5,
                        'type'=>52,
                        'user_id'=>$user_id,
                        'money'=>$money,
                        'create_time'=>time(),
                    ]);
                }
                
                
            });
        }
        
        return action_succ();
    }
    
    /**
     * 审核
     * @return Json|unknown
     */
    public function check(){
        $id = $this->request->post("id");
        $memo = $this->request->post("memo");
        $s = $this->request->post("status");
        
        $cashInfo = Db::name("cash")->find($id);
        if(!$cashInfo){
            return action_error("申请不存在");
        }
        
        if($cashInfo['status']!="0"){
            return action_error("状态异常，禁止审核");
        }
        
        $status = -1;//驳回
        if($s=="1"){
            $status=1;//通过
        }
        
        Db::transaction(function () use($cashInfo,$status,$memo){
            $userInfo = get_login_info();
            Db::name("cash")->where("id",$cashInfo['id'])->update([
                'status'=>$status,
                'memo'=>$memo,
                'check_time'=>time(),
                'check_user'=>$userInfo['id'],
            ]);
            $money = $cashInfo['money'];
            $user_id = $cashInfo['user_id'];
            if($status==-1){//审核不通过，余额回流
                //修改账户余额
                Db::name("account")->where('user_id',$user_id)->inc('balance', $money)->update();
                
                //添加流水记录
                Db::name("account_log")->insert([
                    'gp'=>5,
                    'type'=>52,
                    'user_id'=>$user_id,
                    'money'=>$money,
                    'create_time'=>time(),
                ]);
            }
            
            
        });
            
        return action_succ();
    }
    
    public function add($ary=[]){
        return action_error("禁止添加申请");
    }
    
    public function edit($ary=[]){
        return action_error("禁止修改申请");
    }
    
    public function del($id=null) {
        return action_error("禁止删除申请");
    }
    protected function getWhere(){
        $ary = $this->request->post();
        $where = [];
        
        if(!isset($ary['status'])){
            $where[]=['status','=',0];
        }else if($ary['status']=="2"){
            $where[]=['status','=',-1];
        }else if($ary['status'] || $ary['status']==="0"){
            $where[]=['status','=',$ary['status']];
        }
        
        if(!empty($ary['user_id'])){
            $where[]=['user_id','=',$ary['user_id']];
        }
        
        if(!empty($ary['tel'])){
            $this->getModel()->whereRaw("user_id in (select id from t_user where username like '{$ary['tel']}%')");
        }
        
        
        if(!empty($ary['date_range'])){
            $dates  = explode(" ~ ", $ary['date_range']);
            $begin = strtotime($dates[0])-1;
            $end = strtotime($dates[1]." 23:59:59")+1;
            $where[]=['create_time','between',[$begin,$end]];
        }
        return $where;
    }
}
