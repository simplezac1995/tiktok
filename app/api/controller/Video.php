<?php
namespace app\api\controller;

use app\api\BaseController;
use think\facade\Db;
use think\response\Json;

class Video extends BaseController{
    
    /**
     * 获取视频列表
     * @return Json
     */
    public function list(){
        
        $post = $this->request->post();
        $p = empty($post['page'])?1:$post['page'];
        $p = intval($p);
        if($p>1) return action_succ([]);
        
//         $list = Db::name('video')->order('sort desc,id asc')->paginate([
//             'list_rows'=> 20,
//             'page' =>$p,
//         ], true)->toArray();
//         $list = $list['data'];
//         $rooturl = $this->request->domain()."/";
//         foreach ($list as $key=>$val){
//             //视频路径处理
//             $link = $val['link'];
//             if(strpos($link,"http")===false && strpos($link,"https")===false){
//                 $link = $rooturl.$link;
//                 $val['link'] = $link;
//             }
//             $list[$key]=$val;
//         }
        $rooturl = $this->request->domain()."/";
        $list=[
            ['imgurl'=>$rooturl.'images/video/1.png'],
            ['imgurl'=>$rooturl.'images/video/2.png'],
            ['imgurl'=>$rooturl.'images/video/3.png'],
            //['imgurl'=>$rooturl.'images/video/4.png'],
        ];
        return action_succ($list);
    }
        
}
