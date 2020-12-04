<?php
namespace app\admin\controller;

use think\facade\Db;
use app\admin\DbController;

class AdminUser extends DbController{
    public function changeStatus(){
        $ids = $this->request->post("ids");
        $status = $this->request->post("status");
        
        if($ids && in_array($status, ['0','1'])){
            Db::name("admin_user")->where("id","in",$ids)->save(['status'=>$status]);
            return action_succ();
        }else{
            return action_error("参数错误");
        }
    }
    
    public function list(){
        $response = parent::list();
        $data = $response->getData();
        
        $datas = $data['data'];
        
        foreach ($datas as $key=>$val){
            if($val['role_id']){
                $val['role_name'] = Db::name("role")->where("id",$val['role_id'])->value('name');
            }
            
            $datas[$key]=$val;
        }
        
        $data['data']=$datas;
        
        return json($data);
    }
    
    public function add($ary=null){
        $ary = $this->request->post();
        
        //判断用户是否已经存在
        $count = Db::name("admin_user")->where([
            ['user_name',"=",$ary['user_name']],
            ['id',"<>",$ary['id']]
        ])->count();
        
        if($count>0){
            return action_error("用户名已经存在");
        }
        
        return parent::add($ary);
    }
    
    public function edit($ary=null){
        $ary = $this->request->post();
        
        //判断用户是否已经存在
        $count = Db::name("admin_user")->where([
            ['user_name',"=",$ary['user_name']],
            ['id',"<>",$ary['id']]
        ])->count();
        
        if($count>0){
            return action_error("用户名已经存在");
        }
        
        
        return parent::edit($ary);
    }

    protected function saveBefore($ary, $isadd = true){
        if(isset($ary['password'])){
            if($ary['password']) $ary['password'] = password_encrypt($ary['password']);
            else unset($ary['password']);
        }
        
        if(!isset($ary['status'])){
            $ary['status']=0;
        }
        
        if(!empty($ary['role_id'])){
            
        }
        
        return $ary;
    }
    
    protected function getWhere(){
        $ary = $this->request->post();
        $where = [];
        
        if(!empty($ary['user_name'])){
            $where[]=['user_name',"=",$ary['user_name']];
        }
        
        if(!empty($ary['name'])){
            $where[]=['name',"=",$ary['name']];
        }
        
        return $where;
    }
}
