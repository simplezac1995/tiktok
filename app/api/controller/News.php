<?php
namespace app\api\controller;

use app\api\BaseController;
use think\facade\Db;
use think\response\Json;

class News extends BaseController{
    
    /**
     * 获取最新的通知
     * @return Json
     */
    public function getFirstNotice(){
        $data = Db::name("news")->where("notice",1)->order("id desc")->limit(1)->find();
        return action_succ($data);
    }
    
    
    /**
     * 获取资讯列表
     * @return Json
     */
    public function list(){
        
        $post = $this->request->post();
        $p = empty($post['page'])?1:$post['page'];
        $p = intval($p);
        
        $wheres = [];
        if(!empty($post['status'])){
            $wheres[]=['notice','=',1];
        }
        
        $list = Db::name('news')->where($wheres)->order('sort desc,id desc')->paginate([
            'list_rows'=> 20,
            'page' =>$p,
        ], true)->toArray();
        
        $list = $list['data'];
        $rooturl = $this->request->domain()."/";
        $dt = "";
        foreach ($list as $key=>$val){
            $rowdt = date("Y-m-d",$val['create_time']);
            if($rowdt==$dt){
                $rowdt="";
            }else{
                $dt=$rowdt;
            }
            $val['dt'] = $rowdt;
            $val['imgurl'] = $rooturl.$val['imgurl'];
            $list[$key]=$val;
        }
        return action_succ($list);
    }
    
    public function info(){
        $id = $this->request->post("id");
        $info = Db::name("news")->find($id);
        return action_succ($info);
    }
}
