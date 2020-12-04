<?php
namespace app\admin\controller;
use app\admin\DbController;
use think\facade\Db;
class TaskApply extends DbController{
    public function add($ary=[]){
        return action_error("禁止添加");
    }
    
    public function edit($ary=[]){
        return action_error("禁止修改");
    }
    
    public function del($id=null) {
        return action_error("禁止删除");
    }
    public function list(){
        //字典数据
        $status = config("sys.task_apply_status");
        $types = config("sys.task_type");
        $vipcards = get_vipcards();
        $vipcards[0] = '不限';
        $asks = config("sys.task_ask");
        
        $data=parent::listPage();
        $list = $data['data'];
        foreach ($list as $key=>$val){
            $val['type']=$types[$val['type']];
            $val['ask']=$asks[$val['ask']];
            $val['create_time']=date("Y-m-d H:i:s",$val['create_time']);
            $val['dt_end']=date("Y-m-d",$val['dt_end']);
            $val['vipcard'] = $vipcards[$val['vipcard_id']];
            $val['status'] = $status[$val['status']];
            $val['user'] = Db::name("user")->where("id",$val['user_id'])->value("username");
            $list[$key]=$val;
        }
        $data['data'] = $list;
        return json($data);
    }
    
    public function info(){
        $res = parent::info();
        $info = $res->getData();
        $info = $info['data'];
        
        //字典数据
        $types = config("sys.task_type");
        $vipcards = get_vipcards();
        $vipcards[0] = '不限';
        $asks = config("sys.task_ask");
        
        $info['type']=$types[$info['type']];
        $info['ask']=$asks[$info['ask']];
        $info['dt_end']=date("Y-m-d",$info['dt_end']);
        $info['vipcard'] = $vipcards[$info['vipcard_id']];
        $info['user'] = Db::name("user")->where("id",$info['user_id'])->value("username");
        
        return action_succ($info);
    }
    
    /**
     * 修改状态
     * @return Json
     */
    public function changeStatus(){
        $id = $this->request->post("id");
        $status = $this->request->post("status");
        $memo = $this->request->post("memo");
        $user = get_login_info();
        $info = Db::name("task_apply")->find($id);
        
        if($info['status']){
            return action_error("当前状态不能修改");
        }
        
        Db::name("task_apply")->where("id",$id)->update([
            'status'=>$status,
            'memo'=>$memo,
            'check_time'=>time(),
            'check_user'=>$user['id'],
        ]);
        
        if($status==2 && $info['status']!=2){
            //佣金计算
            Db::execute('call p_task_apply_brokerage(:id)',['id'=>$id]);
        }
        
        return action_succ();
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
        
        if(!empty($post['vipcard_id'])){
            $wheres[]=['vipcard_id','=',$post['vipcard_id']];
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
