<?php
namespace app\admin\controller;
use app\admin\DbController;
class Guide extends DbController{
    protected function getWhere(){
        $ary = $this->request->post();
        $where = [];
        if(!empty($ary['name'])){
            $where[]=['title','like',"%{$ary['name']}%"];
        }
        
        return $where;
    }
}
