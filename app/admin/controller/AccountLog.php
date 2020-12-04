<?php
namespace app\admin\controller;
use app\admin\DbController;
use think\facade\Db;
class AccountLog extends DbController{
    public function list(){
        //字典数据
        $types = config("sys.account_log_types");
        
        $data = parent::listPage("data");
        $list = $data['data'];
        foreach ($list as $key=>$val){
            $val['type'] = $types[$val['type']];
            $val['create_time'] = date("Y-m-d H:i:s",$val['create_time']);
            $user = Db::name("user")->field("username,name")->find($val['user_id']);
            if($user){
                $val['username'] = $user['username'];
                $val['name'] = $user['name'];
            }
            
            if($val['money']>0){
                $val['money']='<span style="color:#009688">+'.$val['money'].'</span>';
            }else{
                $val['money']='<span style="color:#FF5722">-'.$val['money'].'</span>';
            }
            
            $list[$key]=$val;
        }
        
        $data['data'] = $list;
        
        return json($data);
    }
    
    protected function getWhere(){
        $post = $this->request->post();
        $wheres = [];
        
        if(!empty($post['status'])){
            $wheres[]=['status','=',$post['status']];
        }
        
        if(!empty($post['type'])){
            $wheres[]=['type','=',$post['type']];
        }
        
        if(!empty($post['user_id'])){
            $wheres[]=['user_id','=',$post['user_id']];
        }
        
        if(!empty($post['date_range'])){
            $dates  = explode(" ~ ", $post['date_range']);
            $begin = strtotime($dates[0])-1;
            $end = strtotime($dates[1]." 23:59:59")+1;
            $wheres[]=['create_time','between',[$begin,$end]];
        }
        
        return $wheres;
    }
}
