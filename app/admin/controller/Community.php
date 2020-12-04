<?php
namespace app\admin\controller;
use app\admin\DbController;
use think\facade\Cache;
class Community extends DbController{
    public function del($id=null){
        //删除缓存
        Cache::delete("vipcards");
        
        return parent::del($id);
    }
    protected function saveAfter($ary,$isadd=true){
        //删除缓存
        Cache::delete("vipcards");
    }
    
    protected function getOrder(){
        $ary = $this->request->post();
        
        $sort = [];
        
        if(isset($ary['o']) && $ary['o']){
            $temp = explode("|", $ary['o']);
            $sort=[$temp[0]=>$temp[1]];
        }else{
            $sort=['id'=>'asc'];
        }
        
        return $sort;
        
    }
}
