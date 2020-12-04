<?php
namespace app\admin\controller;
use app\admin\DbController;
use think\facade\Db;
class Feedback extends DbController{
    public function list(){
        $res = parent::listData();
        $list = [];
        foreach ($res['data'] as $val){
            $user = Db::name("user")->field("name,tel")->where("id",$val['user_id'])->find();
            $val['username'] = $user['name'];
            $val['usertel']=$user['tel'];
            if($val['status']==0){
                $val['status_name']='<span style="color:red">待处理</span>';
            }else{
                $val['status_name']='<span style="">已处理</span>';
            }
            $list[]=$val;
        }
        
        $res['data']=$list;
        
        return json($res);
    }
    
    /**
     * 详情
     */
    public function info(){
        $info = parent::infoData();
        
        $user = Db::name("user")->field("name,tel")->find($info['user_id']);
        
        $info['username'] = $user['name'];
        $info['usertel'] = $user['tel'];
        
        if($info['status']==0){
            $info['status_name']='待处理';
        }else{
            $info['status_name']='已处理';
        }
        
        return action_succ($info);
    }
    
    /**
     * 处理
     * @return Json|unknown
     */
    public function check(){
        $id = $this->request->post("id");
        $memo = $this->request->post("memo");
        $status = $this->request->post("status");
        
        $userInfo = get_login_info();
        Db::name("feedback")->where("id",$id)->update([
            'status'=>$status,
            'memo'=>$memo,
            'check_time'=>time(),
            'check_user'=>$userInfo['id'],
        ]);
        
        return action_succ();
    }
    
    protected function getWhere(){
        $post = $this->request->post();
        $wheres = [];
        
        if(!empty($post['status'])){
            $wheres[]=['status','=',$post['status']];
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
