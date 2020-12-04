<?php
namespace app\api\controller;

use think\facade\Cache;
use think\App;
use think\facade\Db;
use think\response\Json;
use think\facade\Config;

class Firecoin
{
    
    /**
     * 获取火币列表
     * @return Json
     */
    public function getFirecoin(){
//         $firecoin = Cache::get("firecoin");
//         if(!$firecoin){
//             $firecoin = Db::name("firecoin")->where('status', 1)->order("sort desc")->select();
//             Cache::set("firecoin", $firecoin);
//         }
        
        $firecoin = Db::name("firecoin")->where('status', 1)->order("sort desc")->select();
        $datas=[];
        foreach ($firecoin as $val){
            $data = [
                'id'=>$val['id'],
                'name'=>$val['name'],
                'icon'=>$val['icon']?$val['icon']:'/images/icon.png',
            ];
            
            $datas[]=$data;
        }
        
        return action_succ(['list'=>$datas]);
    }
        
}
