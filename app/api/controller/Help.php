<?php
namespace app\api\controller;

use app\api\BaseController;
use think\facade\Db;
use think\response\Json;

class Help extends BaseController{
    
    /**
     * 获取帮助列表
     * @return Json
     */
    public function list(){
        
        $post = $this->request->post();
        $p = empty($post['page'])?1:$post['page'];
        $p = intval($p);
        
        $list = Db::name('help')->order('sort desc,id asc')->paginate([
            'list_rows'=> 20,
            'page' =>$p,
        ], true)->toArray();
        return action_succ($list['data']);
    }
        
}
