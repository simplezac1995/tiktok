<?php
namespace app\admin\controller;
use app\admin\DbController;
use think\facade\Db;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
class Task extends DbController{
    public function list(){
        //字典数据
        $types = config("sys.task_type");
        $vipcards = get_vipcards();
        $vipcards[0] = '不限';
        $asks = config("sys.task_ask");
        
        $data = parent::listPage();
        $list = $data['data'];
        
        foreach ($list as $key=>$val){
            $val['type']=$types[$val['type']];
            $val['ask']=$asks[$val['ask']];
            $val['create_time']=date("Y-m-d H:i:s",$val['create_time']);
            $val['dt_end']=date("Y-m-d",$val['dt_end']);
            if(!empty($vipcards[$val['vipcard_id']])){
                $val['vipcard'] = $vipcards[$val['vipcard_id']];
            }else{
                $val['vipcard'] = $val['vipcard_id'];
            }
            
            $list[$key]=$val;
        }
        
        $data['data'] = $list;
        
        return json($data);
    }
    
    public function info(){
        $data = parent::infoData();
        $data['dt_end']=date("Y-m-d",$data['dt_end']);
        return action_succ($data);
    }
    
    /**
     * 导入订单
     * @return Json|unknown
     */
    public function import(){
        $file = $this->request->post("file");
        
        $userInfo = get_login_info();
        
        //文件根目录
        $fileRoot = config("filesystem.disks.local.root")."/";
        
        $file = $fileRoot.$file;
        
        if(!file_exists($file)){
            return action_error("文件不存在!!!");
        }
        
        $num=0;//总导入数据量
        $xlsx = new Xlsx();
        $spreadsheet = $xlsx->load($file);
        $sheet = $spreadsheet->getSheet(0);
        $sheetData = $sheet->toArray(null, true, true, true);
        $datas = [];
        $time = time();
        
        $sn = false;
        foreach ($sheetData as $key => $data) {
            if ($key<2){
                continue;
            }
            if(!$data['A'] && !$data['B'] && $key>10){//这两格没值退出循环 并且 大于10行，前面几行有可能是空数据
                break;
            }
            $ary = [
                'title'=>$data['A'],
                'money'=>$data['B'],
                'type'=>$data['C'],
                'vipcard_id'=>$data['D'],
                'ask'=>$data['E'],
                'link'=>$data['F'],
                'num'=>$data['G'],
                'dt_end'=>strtotime($data['H']),
                'last'=>$data['G'],
                'create_user'=>$userInfo['id'],
                'create_time'=>$time,
            ];
            
            if($sn){
                $sn = $sn+rand(1,100);
            }else{
                $sn = $this->createSn();
            }
            
            $ary['sn']=$sn;
            
            $datas[] = $ary;
        }
        
        
        if ($datas) {
            $n = Db::name('task')->insertAll($datas);
            $num += $n;
        }
        return action_succ(['num'=>$num]);
        
    }
    
    protected function saveBefore($ary,$isadd=true){
        if($isadd){
            $ary['last']=$ary['num'];
        }else{
            //编辑时 剩余数量大于最大领取数量,则修改剩余数量=最大领取数量
            if($this->editInfo['last']>$ary['num']){
                $ary['last']=$ary['num'];
            }
        }
        
        if($ary['vipcard_id']){
            $ary['money'] = Db::name("vipcard")->where("id",$ary['vipcard_id'])->value("task_money");
        }
        
        if($ary['type']==3){
            $ary['money']=3;
        }
        
        $ary['dt_end'] = strtotime($ary['dt_end']);
        
        $ary['sn'] = $this->createSn();
        
        return $ary;
    }
    
    protected function getWhere(){
        $post = $this->request->post();
        $wheres = [];
        if(!empty($post['kw'])){
            $wheres[]=['title','like',"%{$post['kw']}%"];
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
            $wheres[]=['dt_end','between',[$begin,$end]];
        }
        
        return $wheres;
    }
    
    private function createSn(){
        $last = Db::name("task")->order("id desc")->limit(1)->value("sn");
        if($last){
            $sn = $last+rand(1,100);
        }else{
            $sn = "1701363000";
        }
        
        return $sn;
    }
}

