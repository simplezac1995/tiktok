<?php
namespace app\admin\controller;

use think\facade\Db;
use app\admin\DbController;

class Role extends DbController{
    public function list(){
        $response = parent::list();
        return $response;
    }
    
    public function add($ary=null){
        $ary = $this->request->post();
        
        
        //判断用户是否已经存在
        $count = Db::name("admin_user")->where([
            ['name',"=",$ary['name']]
        ])->count();
        
        if($count>0){
            return action_error("角色名已经存在");
        }
        
        return parent::add($ary);
    }
    
    public function edit($ary=null){
        $ary = $this->request->post();
        
        //判断用户是否已经存在
        $count = Db::name("admin_user")->where([
            ['user_name',"=",$ary['name']],
            ['id',"<>",$ary['id']]
        ])->count();
        
        if($count>0){
            return action_error("角色名已经存在");
        }
        
        
        return parent::edit($ary);
    }
    
    protected function saveBefore($ary,$isadd=true){
        if(!empty($ary['powers'])){//非系统人员禁止将 系统专属权限 加到角色里
            $powers = explode(",", $ary['powers']);
            foreach ($powers as $key=>$val){
                $sys = Db::name("power")->where("power",$val)->value("sys");
                if($sys==1) unset($powers[$key]);
            }
            
            $ary['powers']=implode(",", $powers);
        }
        
        return $ary;
    }
    
    protected function getWhere(){
        $ary = $this->request->post();
        $where = [];
        
        //$userInfo = get_login_info();
        //$where[]=['company_id',"=",$userInfo['company_id']];
        
        if(!empty($ary['name'])){
            $where[]=['name',"=",$ary['name']];
        }
        
        return $where;
    }
}
