<?php
namespace app\admin\controller;
use app\admin\DbController;
class Firecoin extends DbController{
    protected function getWhere(){
        $ary = $this->request->post();
        $where = [];
        if(!empty($ary['name'])){
            $where[]=['name','like',"%{$ary['name']}%"];
        }
        
        return $where;
    }
}
