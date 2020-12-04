<?php
namespace app\api\controller;

use think\facade\Cache;
use think\App;
use think\facade\Db;
use think\response\Json;
use think\facade\Config;

class Community 
{
    
    /**
     * 获取社群列表
     * @return Json
     */
    public function getCommunity(){
        $community = Db::name("community")->where('status', 1)->order("sort desc")->select();
        
        $datas=[];
        foreach ($community as $val){
            $data = [
                'id'=>$val['id'],
                'name'=>$val['name'],
                'service'=>$val['service'],
                'linkurl'=>$val['linkurl'],
                'icon'=>$val['icon']?$val['icon']:'/images/icon.png',
            ];
            
            $datas[]=$data;
        }
        
        return action_succ(['list'=>$datas]);
    }
        
}
