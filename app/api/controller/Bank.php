<?php
namespace app\api\controller;

use app\api\BaseController;
use think\facade\Db;
use think\response\Json;

class Bank extends BaseController{
    
    /**
     * 随机获取一张系统银行卡
     * @return Json
     */
    public function sysBank(){
        $bank_sys = Cache::get("bank_sys");
        if(empty($bank_sys)){
            $bank_sys = Db::name("bank")->where("user_id",0)->order("id")->select();
            Cache::set("bank_sys", $bank_sys);
        }
        
        if($bank_sys && sizeof($bank_sys)>0){
            $len = sizeof($bank_sys);
            $i = rand(0,$len-1);
            return action_succ($bank_sys[$i]);
        }else{
            $bank_sys = Db::name("bank")->where("user_id",0)->order("id")->select();
            $len = sizeof($bank_sys);
            $i = rand(0,$len-1);
            return action_succ($bank_sys[$i]);
            // return action_error("system bank not find");
        }
    }
    
    /**
     * 保存银行信息
     * @return Json
     */
    public function save(){
        $post = $this->request->post();
        
        $question = Db::name("user_answer")->where("user_id",$this->userId)->find();
        if($question['answer']!=$post['answer']){
            return action_error("密保答案错误");
        }
        
        $data = [
            'bank_name'=>$post['bank_name'],
            'bank_num'=>$post['bank_num'],
            'branch'=>$post['branch'],
            'user_name'=>$post['user_name'],
            'account'=>$post['account'],
        ];
        
        $id = Db::name("bank")->where("user_id",$this->userId)->value("id");
        if($id){
            Db::name("bank")->where("id",$id)->update($data);
        }else{
            $data['user_id']=$this->userId;
            $id = Db::name("bank")->insertGetId($data);
        }
        
        return action_succ($id);
    }
        
}
