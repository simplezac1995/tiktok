<?php
namespace app\admin\controller;

use app\admin\BaseController;
use think\facade\Db;
use think\facade\View;
use think\facade\Cache;
use think\response\Json;

class Index extends BaseController
{
    public function welcome()
    {
        return view();
    }

    public function index()
    {
        $userInfo = get_login_info();
        $config = get_config();
        $sysname = !empty($config['system_name'])?$config['system_name']:'管理系统';
        View::assign("sysname",$sysname);
        View::assign("username",$userInfo['user_name']);
        return view();
    }

    public function console()
    {
        $userInfo = get_login_info();
        $config = get_config();
        $sysname = !empty($config['system_name'])?$config['system_name']:'管理系统';
        View::assign("sysname",$sysname);
        View::assign("username",$userInfo['user_name']);
        return view();
    }
    /**
     * 系统配制信息
     * @return Json
     */
    public function configs(){
        $gp = $this->request->get("gp");
        if(!$gp) $gp="base";
        
        $list = Db::name("config")->where("gp",$gp)->select();
        
        $data=[];
        foreach ($list as $val){
            $data[$val['key']]=$val['val'];
        }
        
        return action_succ($data);
    }

    /**
     * 后台菜单
     * @return Json
     */
    public function menu(){
        // $meuns = [
        //     [
        //         "title"=>"控制台",
        //         "href"=>"index/console.html",
        //         "fontFamily"=>"ok-icon",
        //         "icon"=>"&#xe654;",
        //         "spread"=>true,
        //         "isCheck"=>true
        //     ]
        // ];
        
        // $meuns = array_merge($meuns,$this->getMenuChild());
        
        return json($this->getMenuChild());
    }
    
    private function getMenuChild($top=0){
        $userInfo = get_login_info();
        $modle = Db::name("power")->where("top",$top)->where("menu",1);
        if(isset($userInfo['company_id']) && $userInfo['company_id']){//非系统管理员只能看或管理非系统专属权限
            $modle->where("sys","0");
        }
        
        $modle->order("sort desc,id");
        
        $powers = $modle->select();
        
        $datas  = [];
        foreach ($powers as $val){
            
            if(!has_power($val['power'])){
                continue;
            }
            
            $data=[
                "title"=>$val['name'],
                "href"=>$val['url'],
                "fontFamily"=>$val['font_family'],
                "icon"=>$val['icon'],
                "spread"=>false,
                "isCheck"=>false
            ];
            
            $childs = $this->getMenuChild($val['id']);
            
            if($childs){
                $data['children'] = $childs;
            }
            
            $datas[]=$data;
        }
        
        return $datas;
    }
    
    
    public function setpwd(){
        $userInfo = get_login_info();
        if($this->request->isAjax()){//保存
            $oldPwd = $this->request->post("oldPwd");
            $pass = $this->request->post("pass");
            
            $oldPwd = password_encrypt($oldPwd);
            if($oldPwd==$userInfo['password']){
                $pass = password_encrypt($pass);
                Db::name("admin_user")
                ->where(["id"=>$userInfo['id']])
                ->update(['password'=>$pass]);
                
                $userInfo['password'] = $pass;
                set_login_info($userInfo);
                return action_succ("密码修改成功");
            }else{
                return action_error("原密码错误");
            }
        }else{//显示
            View::assign("username",$userInfo['user_name']);
            return view();
        }
        
    }
    
    /**
     * 验证密码是否正确
     */
    public function checkPassword(){
        $pass = $this->request->post("password");
        
        $pass = password_encrypt($pass);
        
        $userInfo = get_login_info();
        
        if($userInfo['password']==$pass){
            return action_succ([],"密码正确");
        }else{
            return action_error("密码错误");
        }
    }
    
    /**
     * 获取省份列表
     */
    public function getProvinces(){
        $list = Db::name("city")->where('level',1)->select();
        
        $data=[];
        foreach ($list as $val){
            $data[] = [
                'name'=>$val['area_name'],
                'value'=>$val['area_code']
            ];
        }
        
        return action_succ($data);
    }
    
    /**
     * 获取城市列表
     */
    public function getCitys(){
        $index = $this->request->post("area_index");
        $list = Db::name("city")->where('area_index',$index)->select();
        
        $data=[];
        foreach ($list as $val){
            $data[] = [
                'name'=>$val['area_name'],
                'value'=>$val['area_code']
            ];
        }
        
        return action_succ($data);
    }
    
    /**
     * 获取角色列表
     */
    public function getRoles(){
        $list = Db::name("role")->field("id,name")->select();
        $data=[];
        foreach ($list as $val){
            $data[] = [
                'name'=>$val['name'],
                'value'=>$val['id']
            ];
        }
        
        return action_succ($data,"获取成功");
    }
    
    /**
     * 获取部门列表
     */
    public function getDepts(){
        $companyId = $this->request->post("company_id");
        
        $userInfo = get_login_info();
        if(isset($userInfo['company_id']) && $userInfo['company_id']) $companyId = $userInfo['company_id'];
        
        if($companyId){
            $list = Db::name("dept")->field("id value,name")->where("company_id",$companyId)->select();
        }else{
            $list=[];
        }
        
        
        
        return action_succ($list,"获取成功");
    }
    
    
    /**
     * 获取权限树（全部）
     * @return Json
     */
    public function powerTree(){
        
        $def = [];//默认选中值
        
        $type = $this->request->post("type");
        $id = $this->request->post("id");
        if($type=="role" && $id){
            $powers = Db::name("role")->where("id",$id)->value('powers');
            $def = explode(",", $powers);
        }
        
        
        $childs = $this->getPowerTreeChild(0,$def);
        $data=[
            'id'=>0,
            'value'=>0,
            'title'=>'根权限',
            'name'=>'根权限',
            'power'=>'root'
        ];
        
        if($childs){
            $data['spread']=true;
            $data['children'] = $childs;
        }
        
        return action_succ([$data]);
    }
    
    private function getPowerTreeChild($top=0,$def=[]){
        $userInfo = get_login_info();
        $modle = Db::name("power")->where("top",$top);
        if(isset($userInfo['company_id']) && $userInfo['company_id']){//非系统管理员只能看或管理非系统专属权限
            $modle->where("sys","0");
        }
        
        $modle->order("sort desc,id");
        $powers = $modle->select();
        $datas  = [];
        foreach ($powers as $val){
            
            //兼容不同的树
            $data=[
                'id'=>$val['id'],
                'value'=>$val['id'],
                'title'=>$val['name'],
                'name'=>$val['name'],
                'power'=>$val['power']
            ];
            
            $childs = $this->getPowerTreeChild($val['id'],$def);
            
            if($childs){
                $data['spread']=true;
                $data['children'] = $childs;
            }else if(in_array($val['power'], $def)){
                //只有在没有子权限的情况下才能选中，否则所有子权限都会被选中
                $data['checked'] = true;
            }
            
            $datas[]=$data;
        }
        
        return $datas;
    }
    
    
    /**
     * 获取部门权限树（全部）
     * @return Json
     */
    public function deptTree(){
        $datas = [];
        
        if(config("app.multi_company")){
            $userInfo = get_login_info();
            if(isset($userInfo['company_id']) && $userInfo['company_id']){//非系统管理员只能看或管理非系统专属权限
                $companys = Db::name("company")->where('id',$userInfo['company_id'])->select();
            }else{
                $companys = Db::name("company")->select();
            }
        }
        
        
        $def = [];//默认选中值
        
        $companys[]=['id'=>0,'name'=>'管理系统'];
        foreach ($companys as $company){
            $data=[
                'id'=>"c_".$company['id'],
                'value'=>"c_".$company['id'],
                'title'=>$company['name'],
                'name'=>$company['name']
            ];
            
            $childs = $this->getDeptTreeChild(0,$def,$company['id']);
            
            if($childs){
                $data['spread']=true;
                $data['children'] = $childs;
            }
            
            $datas[]=$data;
        }
        
        
        
        
        
        
        
        return action_succ($datas);
    }
    
    private function getDeptTreeChild($top=0,$def=[],$company_id=0){
        
        $modle = Db::name("dept")->where("top",$top);
        if(config("app.multi_company")){
            $modle->where('company_id',$company_id);
        }
        
        $modle->order("sort desc,id");
        $powers = $modle->select();
        $datas  = [];
        foreach ($powers as $val){
            
            //兼容不同的树
            $data=[
                'id'=>$val['id'],
                'value'=>$val['id'],
                'title'=>$val['name'],
                'name'=>$val['name']
            ];
            
            $childs = $this->getPowerTreeChild($val['id'],$def,$company_id);
            
            if($childs){
                $data['spread']=true;
                $data['children'] = $childs;
            }else if(in_array($val['id'], $def)){
                //只有在没有子权限的情况下才能选中，否则所有子权限都会被选中
                $data['checked'] = true;
            }
            
            $datas[]=$data;
        }
        
        return $datas;
    }
    
    /**
     * 加载文件 主要用于显示 非 public目录下的图片或视频
     * @return Json
     */
    public function loadFile(){
        $rootPath = config("filesystem.disks.local.root");
        $file = $this->request->param("file");
        $file = $rootPath.str_replace("storage", "", $file);
        if(!file_exists($file)){
            return action_error("文件不存在");
        }
        
        
        ini_set('memory_limit','512M');
        $basename = basename($file);
        $exp = explode(".", $basename)[1];
        
        if(strtolower($exp)=="mp4"){
            header("Content-type: video/mp4");
        }else{
            header("Content-type: image/$exp");
        }
        header("Accept-Ranges: bytes");
        // ------ 开启缓冲区
        ob_start();
        $size = filesize($file);
        
        if(isset($_SERVER['HTTP_RANGE'])){
            header("HTTP/1.1 206 Partial Content");
            list($name, $range) = explode("=", $_SERVER['HTTP_RANGE']);
            list($begin, $end) =explode("-", $range);
            if($end == 0) $end = $size - 1;
        }
        else {
            $begin = 0; $end = $size - 1;
        }
        
        header("Content-Length: " . ($end - $begin + 1));
        header("Content-Disposition: filename=".$basename);
        header("Content-Range: bytes ".$begin."-".$end."/".$size);
        
        $fp = fopen($file, 'r');
        
        fseek($fp, $begin);
        $contents = '';
        
        while(!feof($fp)) {
            $p = min(1024, $end - $begin + 1);
            //$begin += $p;
            $contents .= fread($fp, $p);
            //echo fread($fp, $p);
        }
        ob_end_clean();
        ob_clean();
        fclose($fp);
        exit($contents);
    }
    
    public function vipcards(){
        $list = Db::name("vipcard")->field("id,name")->order("id asc")->select();
        $data=[];
        foreach ($list as $val){
            $data[] = [
                'name'=>$val['name'],
                'id'=>$val['id']
            ];
        }
        
        return action_succ($data);
    }
    
    /**
     * 首页统计数据
     */
    public function totalReport(){
        $data=[
            'balance'=>0,//会员总余额
            'recharge'=>0, //充值总额
            'rechargeTotal'=>0,//充值单量
            'cash'=>0, //提现总额
            'regist'=>0, //注册人数
            'todayRegist'=>0, //今日注册
            'todayRecharge'=>0, //今日充值总额
            'todayCash'=>0, //今日提现
            'todayTaskUser'=>0, //今日完成任务人数
            'todayTask'=>0, //今日完成任务数
            'todayTaskIncome'=>0, //今日任务收益
            'todayTeamIncome'=>0, //今日团队佣金
        ];
        
        $dtb = strtotime(date("Y-m-d"));
        $dte = time();
        $todayWhere=[
            ['create_time','between',[$dtb,$dte]]
        ];
        
        $data['recharge'] = Db::name("recharge")->where(['status'=>1])->sum("money");
        $data['rechargeTotal'] = Db::name("recharge")->where(['status'=>1])->count("*");
        $res = Db::query("select sum(money-fee) s from t_cash where status=1");
        if($res && $res[0]['s']){
            $data['cash'] = $res[0]['s'];
        }
        
        $data['regist'] = Db::name("user")->order('id')->count("*");
        $data['todayRegist']=Db::name("user_event")->where("event","regist")->where($todayWhere)->count("*");
        $data['todayRecharge'] = Db::name("recharge")->where(['status'=>1])->where($todayWhere)->sum("money");
        $data['todayCash'] = Db::name("cash")->where(['status'=>1])->where($todayWhere)->sum("money");
        
        $data['todayTaskUser'] = Db::name("task_apply")->field("user_id")->where(['status'=>2])->where($todayWhere)->group("user_id")->count("user_id");
        
        $data['todayTask'] = Db::name("task_apply")->where(['status'=>2])->where($todayWhere)->count("*");
        $data['todayTaskIncome'] = Db::name("account_log")->where(['gp'=>4])->where($todayWhere)->sum("money");
        $data['todayTeamIncome'] = Db::name("account_log")->whereRaw("gp=2 or gp=3")->where($todayWhere)->sum("money");
        
        $data['balance'] = Db::name("account")->order('id')->sum("balance");
        
        //$lastSql = Db::getLastSql();
        
        foreach ($data as $key=>$val){
            $val = number_format_x($val);
            $data[$key]=$val;
        }
        
        return action_succ($data);
    }
    
    /**
     * 按年份每月统计注册量
     * @return Json
     */
    public function registYearReport(){
        $order = ['order'=>[],'sale'=>[]];
        $user = ['regist'=>[],'visit'=>[]];
        $month = date("n");
        
        for ($i = 1; $i <= 12; $i++) {
            if($i>$month) break;
            $m = $i>9?$i:("0".$i);
            
            $where = [
                ['create_time','>=',strtotime(date("Y-$m-01"))],
                ['create_time','<=',strtotime(date("Y-$m-t 23:59:59"))],
            ];
            
            //本月销量
            $order['order'][]= Db::name("order")->where($where)->where('status',"in",[1,2,3,4,6])->count();
            
            //本月订单数
            $order['sale'][] = Db::name("order")->where($where)->where('status',"in",[1,2,3,4,6])->sum("total");
            
            //本月注册
            $user['regist'][] = Db::name("user_event")->where($where)->where('event','regist')->count();
            //本月访问
            $user['visit'][] = Db::name("user_event")->where($where)->where('event','visit')->count();
        }
        
        return action_succ(['order'=>$order,'user'=>$user],"获取成功");
    }
    
    /**
     * 注册统计
     * @return Json
     */
    public function registReport(){
        
        $dts = $this->getMonthDays(date("Y-m"));
        
        $datas = [
            'x'=>[],
            'y'=>[],
        ];
        foreach ($dts as $val){
            $datas['x'][]=$val['txt'];
            $datas['y'][] = Db::name("user_event")->where("event",'regist')->where("create_time","between",[$val['b'],$val['e']])->count("*");
        }
        return action_succ($datas);
    }
    
    /**
     * 登入统计
     * @return Json
     */
    public function loginReport(){
        
        $dts = $this->getMonthDays(date("Y-m"));
        
        $datas = [
            'x'=>[],
            'y'=>[],
        ];
        foreach ($dts as $val){
            $datas['x'][]=$val['txt'];
            $datas['y'][] = Db::name("user_event")->where("event",'login')->where("create_time","between",[$val['b'],$val['e']])->count("*");
        }
        return action_succ($datas);
    }

    /**
     * 每日实际完成任务统计
     * @return Json
     */
    public function tasksuccReport(){
        
        $dts = $this->getMonthDays(date("Y-m"));
        $datas = [
            'x'=>[],
            'y'=>[],
        ];
        foreach ($dts as $val){
            $todayWhere=[
                ['create_time','between',[$val['b'],$val['e']]]
            ];
            
            $count = Db::name("task_apply")->field("user_id")->where(['status'=>2])->where($todayWhere)->group("user_id")->count("user_id");

            $datas['x'][]=$val['txt'];
            $datas['y'][] = $count;
        }
        return action_succ($datas);
    }

    /**
     * 充值金额统计
     * @return Json
     */
    public function rechargeMoneyReport(){
        
        $dts = $this->getMonthDays(date("Y-m"));
        
        $datas = [
            'x'=>[],
            'y'=>[],
        ];
        foreach ($dts as $val){
            $datas['x'][]=$val['txt'];
            $money = 0;
            $money = Db::name("recharge")->where("status",1)->where("create_time","between",[$val['b'],$val['e']])->sum("money");
            $datas['y'][] = $money;
        }
        return action_succ($datas);
    }
    
    /**
     * 充值数量统计
     * @return Json
     */
    public function rechargeCountReport(){
        
        $dts = $this->getMonthDays(date("Y-m"));
        
        $datas = [
            'x'=>[],
            'y'=>[],
        ];
        foreach ($dts as $val){
            $datas['x'][]=$val['txt'];
            $count = Db::name("recharge")->where("status",1)->where("create_time","between",[$val['b'],$val['e']])->count("*");
            $datas['y'][] = $count;
        }
        return action_succ($datas);
    }
    
    /**
     * 获取月份的天数
     * @param string $month 格式：yyyy-mm
     * @return number[][]
     */
    private function getMonthDays($month){
        $datas = [];
        $lastDay = date("t",strtotime($month."-01"));
        for ($i = 1; $i <= $lastDay; $i++) {
            $day = $month."-".$i;
            $datas[]=[
                'txt'=>$i,
                'b'=>strtotime($day),
                'e'=>strtotime($day." 23:59:59")
            ];
        }
        
        return $datas;
    }
}
