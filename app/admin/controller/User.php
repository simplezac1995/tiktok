<?php
namespace app\admin\controller;
use app\admin\DbController;
use think\exception\ValidateException;
use think\facade\Db;
class User extends DbController{
    public function list(){
        $model = $this->getModel();
        $model->alias("a")
        ->leftJoin("t_account b","b.user_id=a.id")
        ->field("a.id,a.username,a.name,a.tel,a.create_time,a.top1,a.ip,b.balance,b.income,(select name from t_vipcard c where c.id=a.vipcard_id) as vipcard");
        $res = parent::list();
        $data = $res->getData();
        
        $list = [];
        foreach ($data['data'] as $val){
            $val['team'] = Db::name("user")->whereOr(['top1'=>$val['id'],'top2'=>$val['id'],'top3'=>$val['id']])->count();
            if(!$val['top1'])$val['top1']="";
            
            $val['create_time'] = date("Y-m-d H:i:s",$val['create_time']);
            
            $list[]=$val;
        }
        
        $data['data']=$list;
        
        return json($data);
    }
    
    public function info(){
        $res = parent::info();
        $data = $res->getData();
        $info = $data['data'];
        unset($info['password']);
        //银行卡信息
        $bank = Db::name("bank")->where("user_id",$info['id'])->find();
        $usdt = Db::name("user_ustd")->where("user_id",$info['id'])->find();
        if($bank){
            $info['bank_name'] = $bank['bank_name'];
            $info['account'] = $bank['account'];
            $info['branch'] = $bank['branch'];
            $info['user_name'] = $bank['user_name'];
        }
        if($usdt){
            $info['usdt'] = $usdt['link'];
        }
        $info['create_time'] = date("Y-m-d H:i:s",$info['create_time']);
        if($info['login_time']) $info['login_time'] = date("Y-m-d H:i:s",$info['login_time']);
        return action_succ($info);
    }
    
    /**
     * 调整团队
     */
    public function changeTeam(){
        $top1 = $this->request->post("top1");
        $user_id = $this->request->post("user_id");
        
        //$user = Db::name("user")->find($user_id);
        $topUser = Db::name("user")->find($top1);
        if(!$topUser){
            return action_error("上级ID不存在");
        }
        
        $top2 = $topUser['top1'];
        $top3 = $topUser['top2'];
        
        if($user_id==$top1 || $user_id==$top2 || $user_id==$top3){
            return action_error("上级ID是当前用户的下级，不能调整");
        }
        
        $c = Db::transaction(function () use($user_id,$top1,$top2,$top3) {
            $c = Db::name("user")->where("id",$user_id)->update([
                'top1'=>$top1,
                'top2'=>$top2,
                'top3'=>$top3,
            ]);
            
            if($c){
                //修改我的直接下级会员的层级  top2=我的top1,top3=我的top2
                Db::name("user")->where("top1",$user_id)->update(['top2'=>$top1,'top3'=>$top2]);
                //修改我的间接下级会员的层级 top3=我的top1
                Db::name("user")->where("top2",$user_id)->update(['top3'=>$top1,]);
            }
            
            return $c;
        });
        
        
        
        return action_succ($c);
    }
    
    /**
     * 充值
     */
    public function recharge(){
        $money = $this->request->post("money");
        $user_id = $this->request->post("user_id");
        $memo = $this->request->post("memo");
        try {
            $this->validate(
                ['money'=>$money,'user_id'=>$user_id],
                ['money'=>'require|integer','user_id'=>'require|number']
            );
        } catch (ValidateException $e) {
            return action_error($e->getMessage());
        }
        
        $c = Db::transaction(function () use($money,$user_id,$memo){
            //修改账户余额
            $c = Db::name('account')->where('user_id', $user_id)->inc('balance', $money)->inc('recharge', $money)->update();
            //$c= Db::name('account')->getLastSql();
            //添加流水记录
            Db::name("account_log")->insert([
                'gp'=>1,
                'type'=>11,
                'user_id'=>$user_id,
                'money'=>$money,
                'create_time'=>time(),
                'memo'=>$memo
            ]);
            
            return $c;
        });
        
        return action_succ($c);
    }
    
    /**
     * 账户信息
     */
    public function account(){
        $userId = $this->request->get("user_id");
        
        $p = $this->request->post('page');
        if(!$p) $p=1;
        
        //每页显示数量
        $l = $this->request->post('limit');
        if(!$l)$l=20;
        
        $post = $this->request->post();
        
        $wheres = [
            ['user_id','=',$userId]
        ];
        
        if(!empty($post['date_range'])){
            $dates  = explode(" ~ ", $post['date_range']);
            $begin = strtotime($dates[0]);
            $end = strtotime($dates[1]." 23:59:59");
            
            $wheres[]=['create_time','>=',$begin];
            $wheres[]=['create_time','<=',$end];
        }
        
        if(!empty($post['type'])){
            $wheres[]=['type','=',$post['type']];
        }
        
        $model = Db::name("account_log");
        
        $res = $model->where($wheres)->order('create_time desc')->paginate([
            'list_rows'=> $l,
            'page' => $p,
        ])->toArray();
        
        $list = $res['data'];
        $data = array(
            'code'=>0,
            'msg'=>'',
            'count'=>$res['total'],
            'sql'=>$model->getLastSql()
        );
        $typs = config("sys.account_log_types");
        foreach ($list as $key=>$val){
            $val['create_time']=date("Y-m-d H:i:s",$val['create_time']);
            $val['type']  =$typs[$val['type']];
            $list[$key]=$val;
        }
        
        $data['data'] = $list;
        
        if($p==1){
            $account = Db::name("account")->where("user_id",$userId)->field("balance,income,recharge,spend")->find();
            $data['account']=$account;
        }
        
        $totality = Db::name("account_log")->where("user_id",$userId)->whereDay('create_time')->count();
        $earnings = Db::name("account_log")->where("user_id",$userId)->whereDay('create_time')->value('sum(money)');
        $data['totality']=$totality;
        $data['earnings']=$earnings;
        return json($data);
    }
    
    /**
     * 团队成员列表
     * @return Json
     */
    public function teamUser(){
        $userId = $this->request->get("user_id");
        $type = $this->request->get("type");
        
        $p = $this->request->post('page');
        if(!$p) $p=1;
        
        //每页显示数量
        $l = $this->request->post('limit');
        if(!$l)$l=20;
        
        $model = Db::name("user");
        $topClounm = "";
        if($type==1){
            $topClounm = "top1";
        }elseif($type==2){
            $topClounm = "top2";
        }elseif($type==3){
            $topClounm = "top3";
        }else{
            return action_error("参数错误");
        }
        $model->where($topClounm,$userId);
        // $res = $model->field("id,vipcard_id,create_time,username")->paginate([
        //     'list_rows'=> $l,
        //     'page' => $p,
        // ])->toArray();
        $res = $model->field("id,vipcard_id,create_time,username")->paginate([
            'list_rows'=> 10000,
            'page' => 1,
        ])->toArray();
        $list = $res['data'];
        $data = array(
            'code'=>0,
            'msg'=>'',
            'count'=>$res['total'],
            'sql'=>$model->getLastSql()
        );
        
        $vipcards = get_vipcards();
        foreach ($list as $key=>$val){
            $val['create_time']=date("Y-m-d H:i:s",$val['create_time']);
            $val['vipcard']  =empty($vipcards[$val['vipcard_id']])?'':$vipcards[$val['vipcard_id']];
            $val['team'] = Db::name("user")->whereOr(['top1'=>$val['id'],'top2'=>$val['id'],'top3'=>$val['id']])->count();
            $list[$key]=$val;
        }
        
        $data['data'] = $list;
        if($p==1){
            $totals = [];
            foreach ($vipcards as $key=>$val){
                $totals[]=[
                    'name'=>$val,
                    'count'=>Db::name("user")->where("vipcard_id",$key)->where($topClounm,$userId)->count(),
                ];
            }
            $data['total']=$totals;
        }
        
        return json($data);
    }
    
    public function del($id=null){
        return action_error("禁止删除");
    }
    
    protected function saveBefore($ary,$isadd=true){
        $reg ="/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,50}$/";
        if(!empty($ary['password'])){
            if(!preg_match($reg,$ary['password'],$m)){
                throw new \think\Exception("密码必须由数字和字母组成，且长度大于6位");
            }
            $ary['password'] = password_encrypt($ary['password']);
        }else{
            unset($ary['password']);
        }
        
        if(!empty($ary['password2'])){
            if(!preg_match($reg,$ary['password2'],$m)){
                throw new \think\Exception("提现密码必须由数字和字母组成，且长度大于6位");
            }
        }else{
            unset($ary['password2']);
        }
        
        unset($ary['create_time'],$ary['login_time']);
        if($isadd){
            //判断用户是否已经存在
            $count = Db::name("user")->where([
                ['username',"=",$ary['username']]
            ])->count();
            
            if($count>0){
                throw new \think\Exception("账号已存在");
            }
            
            //生成分享码
            $shareCode = $this->createCodeStr();
            while (Db::name('user')->where('share_code',$shareCode)->value("id")){
                $shareCode = $this->createCodeStr();
            }
            
            $ary['share_code'] = $shareCode;
        }else{
            unset($ary['username']);
        }
        
        return $ary;
    }
    
    protected function saveAfter($ary,$isadd=true){
        $accountId = false;//是否开通钱包
        $answerId = false;//是否有密保
        if($isadd){//新增时开通钱包账户
            
        }else{//修改
            //判断钱包账号是否存在
            $accountId = Db::name("account")->where('user_id',$ary['id'])->value("id");
            
            $answerId = Db::name("user_answer")->where('user_id',$ary['id'])->value("id");
        }
        
        $password = empty($ary['password2'])?false:password_encrypt($ary['password2']);//钱包密码
        if($accountId){
            if($password){
                Db::name("account")->where("id",$accountId)->update(['password'=>$password]);
            }
        }else{//开通钱包
            $info = [
                'user_id'=>$ary['id'],
                'balance'=>0,
                'frozen'=>0,
                'income'=>0
            ];
            
            if($password){
                $info['password'] = $password;
            }
            Db::name("account")->insert($info);
        }
        
        //修改银行卡信息
        $bank = [
            'user_id'=>$ary['id'],
            'bank_name'=>$ary['bank_name'],
            'branch'=>$ary['branch'],
            'user_name'=>$ary['user_name'],
            'account'=>$ary['account'],
        ];
        if($isadd){
            Db::name("bank")->insert($bank);
        }else{
            $bankId = Db::name("bank")->where("user_id",$ary['id'])->value("id");
            if($bankId){
                Db::name("bank")->where("id",$bankId)->update($bank);
            }else{
                Db::name("bank")->insert($bank);
            }
        }
        
        //密保
        if($ary['question']){
            $answer = [
                'question'=>$ary['question'],
                'answer'=>$ary['answer'],
            ];
            
            if($answerId){
                Db::name("user_answer")->where('id',$answerId)->update($answer);
            }else{
                $answer['user_id'] = $ary['id'];
                Db::name("user_answer")->insert($answer);
            }
        }

        if($ary['usdt']){
            Db::name("user_ustd")->where('user_id', $ary['id'])->update(['link'=>$ary['usdt']]);
        }
    }
    
    protected function getWhere(){
        $model = $this->getModel();
        
        $ary = $this->request->post();
        
        $where = [];
        
        
        if(!empty($ary['kw'])){
            $model->whereRaw("(username like '%{$ary['kw']}%' or name like '%{$ary['kw']}%' or tel like '%{$ary['kw']}%')");
        }
        
        if(!empty($ary['user_id'])){
            $where[]=['a.id','=',$ary['user_id']];
        }
        
        
        if(!empty($ary['vipcard_id'])){
            $where[]=['a.vipcard_id','=',$ary['vipcard_id']];
        }
        
        if(!empty($ary['date_range'])){
            $dates  = explode(" ~ ", $ary['date_range']);
            $begin = strtotime($dates[0]);
            $end = strtotime($dates[1]." 23:59:59");
            
            $where[]=['a.create_time','>=',$begin];
            $where[]=['a.create_time','<=',$end];
        }
        return $where;
    }
    
    private function createCodeStr($length = 8) {
        $chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $char= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
            $str.=$char;
        }
        
        return $str;
    }
}
