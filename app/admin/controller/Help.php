<?php
namespace app\admin\controller;
use app\admin\DbController;
class Help extends DbController{
    protected function saveBefore($ary,$isadd=true){
        if($isadd){
            if(isset($ary['sort']) && !$ary['sort']){
                $ary['sort']=100;
            }
        }
        return $ary;
    }
    
    protected function getWhere(){
        $ary = $this->request->post();
        $where = [];
        if(!empty($ary['name'])){
            $where[]=['title','like',"%{$ary['name']}%"];
        }
        
        return $where;
    }
}
