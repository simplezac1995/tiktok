<?php
namespace app\admin\controller;
use app\admin\DbController;
class Ad extends DbController{
    public function list(){
        $data = parent::listData();
        $types = config("sys.ad_type");
        $list = $data['data'];
        foreach ($list as $key=>$val){
            $val['type'] = $types[$val['type']];
            $list[$key]=$val;
        }
        $data['data']=$list;
        return json($data);
    }
    protected function getWhere(){
        $ary = $this->request->post();
        $where = [];
        if(!empty($ary['name'])){
            $where[]=['title','like',"%{$ary['name']}%"];
        }
        
        if(!empty($ary['type'])){
            $where[]=['type','=',$ary['type']];
        }
        
        return $where;
    }
}
