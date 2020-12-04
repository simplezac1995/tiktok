<?php
namespace app\api\controller;

use app\api\BaseController;
use think\facade\Cache;
use think\facade\Db;
use think\response\Json;

class Vipcard extends BaseController{
    
    /**
     * 获取系统所有VIP卡
     */
    public function vipcards(){
        //取用户当前等级
        $vipid= Db::name("user")->where("id",$this->userId)->value("vipcard_id");
        $res = get_vipcards();
        $vipcards =[];
        
        foreach ($res as $key=>$val){
            $vipcards[]=[
                'id'=>$key,
                'name'=>$val,
            ];
        }
        
        $curr=0;
        $currvip=[];
        foreach ($vipcards as $key=>$val){
            if($val['id']==$vipid){
                $curr=$key;
                $currvip=$val;
                break;
            }
        }
        return action_succ(['vipcards'=>$vipcards,'curr'=>$curr,'currvip'=>$currvip]);
    }
    
    /**
     * 获取会员当前等级下所有VIP等级
     * @return Json
     */
    public function getVipcards(){
        $userId = $this->request->post("user");
        $card_id = Db::name("user")->where('id',$userId)->value("vipcard_id");
        
        $vipcards = Cache::get("vipcards");
        if(!$vipcards){
            $vipcards = Db::name("vipcard")->order("id asc")->select();
            Cache::set("vipcards", $vipcards);
        }
        
        $nowVip = null;
        $datas=[];
        foreach ($vipcards as $val){
            $data = [
                'id'=>$val['id'],
                'name'=>$val['name'],
                'price'=>intval($val['price']),
                'usdt'=>intval($val['usdt']),
                'task_max'=>$val['task_max'],
                'task_money'=>$val['task_money'],
                'icon'=>$val['icon']?$val['icon']:'/images/icon.png',
            ];
            
            $data['icon'] = $this->request->root(true).$data['icon'];
            
            if($val['id']<=$card_id) {
                $nowVip=$data;
                continue;
            }
            
            $datas[]=$data;
        }
        
        return action_succ(['list'=>$datas,'nowvip'=>$nowVip]);
    }
    
    public function notice(){
        $card_id = Db::name("user")->where('id',$this->userId)->value("vipcard_id");
        $balance = Db::name("account")->where('user_id',$this->userId)->value("balance");
        $info = get_vipcards($card_id);
        if($balance>$info['show_notice']){
            $notice = nl2br($info['note']);
        }else{
            $notice = false;
        }
        
        return action_succ($notice);
    }
        
}
