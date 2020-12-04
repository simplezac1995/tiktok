<?php
namespace app\admin\controller;
use app\admin\DbController;
class Bank extends DbController{
    protected function getWhere() {
        $post = $this->request->post();
        $wheres = [];
        $wheres[]=['user_id','=',0];
        
        if(!empty($post['kw'])){
            $wheres[]=['account','like',"%{$post['kw']}%"];
        }
        
        return $wheres;
    }
}
