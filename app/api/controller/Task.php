<?php
namespace app\api\controller;

use app\api\BaseController;
use think\facade\Db;
use think\response\Json;

class Task extends BaseController{
    
    private $msgs;
    protected function initialize(){
        $this->msgs = config("msg");
    }
    
    /**
     * 接单
     */
    public function taskApply() {
        $user = $this->getLoginUser();
        $id = $this->request->post("id");
        
        //接单时间限制
        $hour = intval(date("H"));
        $config = get_config();
        if($hour<intval($config['open_begin_hour']) || $hour>=intval($config['open_end_hour'])){
            return action_error($this->msgs['system_open_time']."{$config['open_begin_hour']}:00~{$config['open_end_hour']}:00");
        }
        
        //会员等级任务次数限制
        $vipcard = get_vipcards($user['vipcard_id']);
        $count = get_today_count($this->userId);
        if($count['today_task']>=$vipcard['task_max']){
            return action_error($this->msgs['taks_max']);
        }
        
        //任务详情
        $task = Db::name("task")->find($id);
        if(!$task){
            return action_error($this->msgs['task_not_exist']);
        }
        
        if($task['last']<=0){
            return action_error($this->msgs['task_no_surplus']);
        }
        
        if($task['dt_end']<time()){
            return action_error($this->msgs['task_exp']);
        }
        
        //任务等级限制
        if($user['vipcard_id']<$task['vipcard_id']){
            return action_error($this->msgs['task_vip_limit']);
        }
        
        //判断任务是否已接
        $hostory = Db::name("task_apply")->field("id")->where(['user_id'=>$this->userId,'task_id'=>$id])->limit(1)->find();
        if($hostory){
            return action_error($this->msgs['task_alread_apply'],[],2);
        }
        
        //判断是否存在未完成任务
        $nowTask = Db::name("task_apply")->field("id")->where(['user_id'=>$this->userId,'status'=>0])->limit(1)->find();
        if($nowTask){
            return action_error($this->msgs['task_not_complate']);
        }
        
        $data=[
            'user_id'=>$this->userId,
            'task_id'=>$task['id'],
            'task_sn'=>$task['sn'],
            'create_time'=>time(),
            'title'=>$task['title'],
            'money'=>$task['money'],
            'type'=>$task['type'],
            'vipcard_id'=>$task['vipcard_id'],
            'ask'=>$task['ask'],
            'link'=>$task['link'],
            'dt_end'=>$task['dt_end'],
        ];
        
        $id = Db::transaction(function () use($data) {
            Db::name("task")->where('id',$data['task_id'])->dec('last')->update();
            $id = Db::name("task_apply")->insertGetId($data);
            set_today_count($this->userId,"task", 1);
            return $id;
        });
        
        
        return action_succ($id);
    }
    
    /**
     * 任务列表
     * @return Json
     */
    public function list(){
        $post = $this->request->post();
        $p = empty($post['page'])?1:$post['page'];
        $p = intval($p);
        $l = empty($post['limit'])?20:$post['limit'];
        $l = intval($l);
        
        $wheres = [
            ['dt_end',">",time()]
        ];
        $sql = "select task_id from t_task_apply where user_id='{$this->userId}'";//子句条件
        if(!empty($post['type'])){
            $wheres[]=['type','=',$post['type']];
            $sql.=" and type={$post['type']}";
        }
        
        if(!empty($post['vipcard_id'])){
            $wheres[]=['vipcard_id','=',$post['vipcard_id']];
            $sql.=" and vipcard_id={$post['vipcard_id']}";
        }
        
        $b = ($p-1)*$l;
        
        $sql = Db::name("task")->whereRaw("id not in ($sql)")->field("id")->where($wheres)->order("id desc")->limit($b,$l)->fetchSql(true)->select();
        $sql = "select a.* from `t_task` a INNER JOIN ($sql) b on a.id=b.id order by b.id desc";
        

        $list=Db::query($sql);
        
        //字典数据
        $types = ['1'=>'Tiktok', '2'=>'FB', '3'=>'Newbie Task'];
        $vipcards = get_vipcards();
        $vipcards[0] = '不限';
        $asks = config("sys.task_ask");
        
        
        $datas = [];
        //第一页时获取特殊任务（新手任务）
        if($p==1){
            $sql = "select task_id from t_task_apply where user_id='{$this->userId}' and type=3";
            if(!empty($post['vipcard_id'])){
                //$sql.=" and vipcard_id={$post['vipcard_id']}";
            }
            $tasks = Db::name("task")->whereRaw("id not in ($sql)")->where("type",3)->select();
            if($tasks){
                foreach ($tasks as $val){
                    $datas[]=[
                        'id'=>$val['id'],
                        'sn'=>$val['sn'],
                        'money'=>$val['money'],
                        'type'=>$val['type'],
                        'name'=>$types[$val['type']],
                        'ask'=>$asks[$val['ask']],
                        'last'=>$val['last'],
                        'vipcard_id'=>$val['vipcard_id'],
                        'vip'=>$vipcards[$val['vipcard_id']]
                    ];
                }
            }
        }
        
        
        foreach ($list as $val){
            $datas[]=[
                'id'=>$val['id'],
                'sn'=>$val['sn'],
                'money'=>$val['money'],
                'type'=>$val['type'],
                'name'=>$types[$val['type']],
                'ask'=>$asks[$val['ask']],
                'last'=>$val['last'],
                'vipcard_id'=>$val['vipcard_id'],
                'vip'=>$vipcards[$val['vipcard_id']]
            ];
        }
        
        return action_succ($datas);
    }
        
}
