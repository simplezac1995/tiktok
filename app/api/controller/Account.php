<?php
namespace app\api\controller;

use app\api\BaseController;
use think\facade\Config;
use think\facade\Db;
use think\response\Json;

class Account extends BaseController{
    /**
     * 钱包首页
     * @return Json
     */
    public function index(){
        $merchantId = $this->request->post("loginMerchant");
        
        $userInfo = Db::name("merchant")->find($merchantId);
        
        $totayTime = strtotime(date("Y-m-d"));
        
        
        $account = Db::name("account")->where('merchant_id',$merchantId)->find();
        //钱包余额
        $balance = $account['balance'];
        
        //收入总额
        $income = $account['income'];
        
        //今日收益
        $todayIncomeCount = Db::name("account_log")->where('create_time','>=',$totayTime)->where('merchant_id',$merchantId)->sum("money");
        
        
        //待定收益
        $pendingIncomeCount = merchant_income_count($merchantId, $userInfo['level'],[
            ['status',"=",0]
        ]);
        
        $data = [
            'balance'=>$balance,
            'income'=>$income,
            'todayIncomeCount'=>$todayIncomeCount,
            'pendingIncomeCount'=>$pendingIncomeCount,
        ];
        
        return action_succ($data);
    }
        
    /**
     * 流水明细
     * @return Json
     */
    public function logs(){
        $p = $this->request->post('page');
        $status = $this->request->post('status');
        $type = $this->request->post('type');
        
        if(!$p) $p=1;
        
        $pageSize = 20;
        $limit = ($p-1)*$pageSize;
        
        $wheres = [
            ['user_id','=',$this->userId]
        ];
        
        if($status || $status==="0"){
            $wheres[]=['status','=',$status];
        }
        
        if($type){
            $wheres[]=['type','=',$type];
        }
        
        $model = Db::name("account_log")->field("*");
        
        $list = $model->where($wheres)->order("id desc")
        ->limit($limit,$pageSize)
        ->select();
        
        $types = Config::get("sys.account_log_types");
        
        foreach ($list as $key=>$val){
            $val['type'] = $types[$val['type']];
            $val['create_time'] = date('Y-m-d',$val['create_time']);
            $list[$key]=$val;
        }
        
        return action_succ($list);
    }
    
}
