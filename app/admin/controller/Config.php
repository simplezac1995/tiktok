<?php
namespace app\admin\controller;
use app\admin\DbController;
use think\facade\Db;
use think\facade\Cache;
class Config extends DbController{
    public function bank(){
        return $this->save('bank');
    }
    
    public function base(){
        return $this->save('base');
    }
    
    public function ustd(){
        return $this->save('ustd');
    }
    
    protected function save($gp="base"){
        
        Db::name("config")->where("gp",$gp)->delete();
        
        $ary = $this->request->post();
        
        $datas = [];
        foreach ($ary as $key=>$val){
            if(is_array($val)) $val = json_encode($val,JSON_UNESCAPED_UNICODE);
            $datas[]=[
                'gp'=>$gp,
                'key'=>$key,
                'val'=>$val
            ];
        }
        
        Db::name("config")->insertAll($datas);
        
        Cache::delete("config_".$gp);
        
        return action_succ([]);
    }
}
