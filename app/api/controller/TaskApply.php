<?php
namespace app\api\controller;

use app\api\BaseController;
use think\facade\Db;
use think\response\Json;

class TaskApply extends BaseController{
    private $msgs;
    protected function initialize(){
        $this->msgs = config("msg");
    }
    public function cancel(){
        $id = $this->request->post("id");
        $task = Db::name("task_apply")->where(['id'=>$id,'user_id'=>$this->userId])->find();
        if(!$task){
            return action_error($this->msgs['task_not_exist']);
        }
        
        if($task['status']!=0 && $task['status']!=1){
            return action_error($this->msgs['task_apply_cancel']);
        }
        
        Db::transaction(function () use($id,$task) {
            Db::name("task_apply")->where("id",$id)->delete();
            
            //任务申请日期
            $applyDt = date("Y-m-d",$task['create_time']);
            
            $today = date("Y-m-d");
            //如果任务是今天申请的则今天统计数量减掉
            if($applyDt==$today){
                $count = Db::name("user_count")->where("user_id",$this->userId)->find();
                if($count && $count['today']==$today && $count['today_task']>0){
                    Db::name("user_count")->where("id",$count['id'])->dec('today_task')->update();
                }
            }
        });
        
        
        
        return action_succ();
    }
    
    /**
     * 提交任务申请审核
     * @return Json
     */
    public function taskSubmit(){
        $id = $this->request->post("id");
        $task = Db::name("task_apply")->where(['id'=>$id,'user_id'=>$this->userId])->find();
        if(!$task){
            return action_error($this->msgs['task_not_exist']);
        }
        
        if($task['status']!=0 && $task['status']!=1){
            return action_succ();
        }
        
        Db::name("task_apply")->where("id",$id)->update(['submit_time'=>time(),'status'=>1]);
        
        return action_succ();
    }
    
    /**
     * 申请记录
     * @return Json
     */
    public function list(){
        $post = $this->request->post();
        $p = empty($post['page'])?1:$post['page'];
        $p = intval($p);
        $l = empty($post['limit'])?20:$post['limit'];
        $l = intval($l);
        
        $wheres = [
            ['user_id',"=",$this->userId]
        ];
        if(isset($post['status'])){
            $wheres[]=['status','=',$post['status']];
        }
        
        $b = ($p-1)*$l;
        
        $sql = Db::name("task_apply")->field("id")->where($wheres)->order("id desc")->limit($b,$l)->fetchSql(true)->select();
        $sql = "select a.* from `t_task_apply` a INNER JOIN ($sql) b on a.id=b.id order by b.id desc";
        

        $list=Db::query($sql);
        
        //字典数据
        $asks = config("sys.task_ask");
        
        
        $datas = [];
        foreach ($list as $val){
            $datas[]=[
                'id'=>$val['id'],
                'task_id'=>$val['task_id'],
                'task_sn'=>$val['task_sn'],
                'status'=>$val['status'],
                'money'=>$val['money'],
                'type'=>$val['type'],
                'ask'=>$asks[$val['ask']],
                'link'=>$val['link'],
            ];
        }
        
        return action_succ($datas);
    }
        
}
