<?php
namespace app\api\controller;

use app\api\BaseController;
use think\facade\Db;
use think\response\Json;

/**
 * 统计
 * @author Administrator
 *
 */
class Report extends BaseController{
    public function day(){
        $data=[
            'total'=>0.00,
            'task_income'=>0.00,
            'child_income'=>0.00,
            'expend'=>0.00,
            'invite'=>0.00,
        ];
        
        //取出当天所有流水数据
        $list = Db::name("account_log")->field("gp,type,money")->where([
            ['user_id','=',$this->userId],
            ['gp','between',[2,4]],
            ['create_time','>',strtotime(date("Y-m-d"))]
        ])->order("id")->select();
        
        foreach ($list as $val){
            if($val['type']==41){
                $data['task_income']+=$val['money'];
            }else if($val['gp']==3){
                $data['child_income']+=$val['money'];
            }else if($val['gp']==2){
                $data['expend']+=$val['money'];
            }else if($val['type']==42){
                $data['invite']+=$val['money'];
            }
            
        }
        
        $data['total'] = $data['task_income']+$data['child_income']+$data['expend']+$data['invite'];
        
        foreach ($data as $key=>$val){
            $val = number_format_x($val);
            $data[$key]=$val;
        }
        
        return action_succ($data);
    }
    /**
     * 周报表
     * @return Json
     */
    public function week(){
        $vips = get_vipcards();
        $k = 0;
        $datas = [];
        foreach ($vips as $key=>$val){
            if($k==0){
                $k++;
                continue;
            }
            $time = strtotime("-7 DAYS");
            $count = Db::name("user")->where("vipcard_id",$key)->where("create_time",">",$time)->whereRaw("top1=$this->userId or top2=$this->userId or top3=$this->userId")->count();
            $datas[]=[
                'vip'=>$val,
                'count'=>$count,
            ];
        }
        
        return action_succ($datas);
    }
    
    /**
     * 团队人员
     * @return Json
     */
    public function teamUsers(){
        $p = $this->request->post('page');
        $top = $this->request->post('top');
        
        if(!$p) $p=1;
        
        $pageSize = 20;
        $limit = ($p-1)*$pageSize;
        
        if($top==1){
            $wheres = [
                ['top1','=',$this->userId]
            ];
        }else if($top==2){
            $wheres = [
                ['top2','=',$this->userId]
            ];
        }else if($top==3){
            $wheres = [
                ['top3','=',$this->userId]
            ];
        }else{
            $wheres = [
                ['top1','=',$this->userId]
            ];
        }
        
        
        $model = Db::name("user")->field("id,username,name,create_time,vipcard_id,top1");
        
        $list = $model->where($wheres)->order("id desc")
        ->limit($limit,$pageSize)
        ->select();
        
        $vips = get_vipcards();
        
        $user = $this->getLoginUser();
        foreach ($list as $key=>$val){
            $val['cash'] = Db::name("cash")->where(["user_id"=>$val['id'],'status'=>'1'])->sum("money");
            $val['create_time'] = date('Y-m-d',$val['create_time']);
            $val['username'] = $this->formateTel($val['username']);
            
            if($val['top1']){
                if($val['top1']==$this->userId){
                    $val['top'] = $this->formateTel($user['username']);
                    $val['vip'] = $vips[$user['vipcard_id']];
                }else{
                    $top = Db::name("user")->field("username,vipcard_id")->where("id",$val['top1'])->find();
                    $val['top'] = $this->formateTel($top['username']);
                    $val['vip'] = $vips[$top['vipcard_id']];
                }
            }else{
                $val['top'] = "";
                $val['vip'] = "";
            }
            
            
            $list[$key]=$val;
        }
        
        return action_succ($list);
    }
    
    private function formateTel($tel){
        return tel_formate($tel);
    }
    
    /**
     * 团队报表
     * @return Json
     */
    public function team(){
        $data=[
            'balance'=>0.00,
            'recharge'=>0.00,
            'cash'=>0.00,
            'deposit'=>0,
            'tj1'=>0,
            'tj2'=>0,
            'tj3'=>0,
            'tx1'=>0,
            'tx2'=>0,
            'tx3'=>0,
            'team'=>0,
            'cash_count'=>0
        ];

        $is_agent = Db::name("user")->where("id", $this->userId)->value('is_agent');
        if($is_agent == 1){//代理展示数据
            $ids = $id = $this->userId;
            $line = [];//无限级下级
            $line[] = $id;
            while ($ids) {
                $users = Db::name("user")->field('id')->where("top1","in",$ids)->select()->toArray();
                if(empty($users)){
                    break;
                }
                $ids   = array_column($users, 'id');
                $line = array_merge($line, $ids);
            }

            $teamBalanceAgent = Db::name("account")->where("user_id", "in", $line)->sum("balance");//团队总余额
            $rechargeTotalAgent = Db::name("recharge")->where(['status'=>1])->where("user_id", "in", $line)->sum("money");//团队总充值
            $withdrawTotalAgent = Db::name("cash")->where(['status'=>1])->where("user_id", "in", $line)->sum("money");//团队总提现
            $teamCountAgent = count($line);

            $data['balance'] = $teamBalanceAgent;
            $data['recharge'] = $rechargeTotalAgent;
            $data['cash'] = $withdrawTotalAgent;
            $data['team'] = $teamCountAgent;

            $res = Db::query("select count(*) s from t_user where top1=:user_id", ['user_id' =>$this->userId]);//直推人数
            if($res){
                $data['tj1'] = $res[0]['s'];
            }
            
            $vips = get_vipcards();
            $vipid = array_key_first($vips);
            $res = Db::query("select count(*) s from (select id from t_user where vipcard_id>'{$vipid}' and top1={$this->userId} group by id) c");
            if($res && $res[0]['s']){
                $data['tx1'] = $res[0]['s'];
            }

            //二级推荐人数
            $res = Db::query("select count(*) s from t_user where top2=:user_id", ['user_id' =>$this->userId]);
            if($res){
                $data['tj2'] = $res[0]['s'];
            }
            $res = Db::query("select count(*) s from (select id from t_user where vipcard_id>'{$vipid}' and top2={$this->userId} group by id) c");
            if($res && $res[0]['s']){
                $data['tx2'] = $res[0]['s'];
            }

            //三级推荐人数
            $res = Db::query("select count(*) s from t_user where top3=:user_id", ['user_id' =>$this->userId]);
            if($res){
                $data['tj3'] = $res[0]['s'];
            }
            $res = Db::query("select count(*) s from (select id from t_user where vipcard_id>'{$vipid}' and top3={$this->userId} group by id) c");
            if($res && $res[0]['s']){
                $data['tx3'] = $res[0]['s'];
            }
            //提现人数
            $res = Db::query("select count(*) s from t_cash a inner join t_user b on a.user_id=b.id where a.status=1 and (b.top1={$this->userId} or b.top2={$this->userId} or b.top3={$this->userId})");
            if($res){
                $data['cash_count'] = $res[0]['s'];
            }
            
            $data['deposit'] = $data['tx1']+$data['tx2']+$data['tx3'];
        }else{
            //团队总余额
            $sql = "select sum(balance) s from t_account a inner join t_user b on a.user_id=b.id where b.top1={$this->userId} or b.top2={$this->userId} or b.top3={$this->userId}";
            $res = Db::query($sql);
            if($res && $res[0]['s']){
                $data['balance'] = $res[0]['s'];
            }
            
            //团队总充值
            $res = Db::query("select sum(money) s from t_recharge a inner join t_user b on a.user_id=b.id where a.status=1 and (b.top1={$this->userId} or b.top2={$this->userId} or b.top3={$this->userId})");
            if($res && $res[0]['s']){
                $data['recharge'] = $res[0]['s'];
            }
            
            //团队总提现
            $res = Db::query("select sum(money) s from t_cash a inner join t_user b on a.user_id=b.id where a.status=1 and (b.top1={$this->userId} or b.top2={$this->userId} or b.top3={$this->userId})");
            if($res && $res[0]['s']){
                $data['cash'] = $res[0]['s'];
            }
            
            //存款人数  先查初级会员VIPID  只要大于这个VIP的，说明是有充值过的
            $vips = get_vipcards();
            $vipid = array_key_first($vips);
    //         $sql = "select count(*) s from (select a.user_id s from t_recharge a inner join t_user b on a.user_id=b.id where b.vipcard_id>'{$vipid}'  and a.status=1 and (b.top1={$this->userId} or b.top2={$this->userId} or b.top3={$this->userId}) group by a.user_id) c";
            
    //         $res = Db::query($sql);
    //         if($res && $res[0]['s']){
    //             $data['deposit'] = $res[0]['s'];
    //         }
            
            //直推人数 一级
            $res = Db::query("select count(*) s from t_user where top1=:user_id", ['user_id' =>$this->userId]);
            if($res){
                $data['tj1'] = $res[0]['s'];
            }
            
            $res = Db::query("select count(*) s from (select id from t_user where vipcard_id>'{$vipid}' and top1={$this->userId} group by id) c");
            if($res && $res[0]['s']){
                $data['tx1'] = $res[0]['s'];
            }
            
            //二级推荐人数
            $res = Db::query("select count(*) s from t_user where top2=:user_id", ['user_id' =>$this->userId]);
            if($res){
                $data['tj2'] = $res[0]['s'];
            }
            
            $res = Db::query("select count(*) s from (select id from t_user where vipcard_id>'{$vipid}' and top2={$this->userId} group by id) c");
            if($res && $res[0]['s']){
                $data['tx2'] = $res[0]['s'];
            }
            
            //三级推荐人数
            $res = Db::query("select count(*) s from t_user where top3=:user_id", ['user_id' =>$this->userId]);
            if($res){
                $data['tj3'] = $res[0]['s'];
            }
            $res = Db::query("select count(*) s from (select id from t_user where vipcard_id>'{$vipid}' and top3={$this->userId} group by id) c");
            if($res && $res[0]['s']){
                $data['tx3'] = $res[0]['s'];
            }
            
            //团队人数
            $data['team'] = $data['tj1']+$data['tj2']+$data['tj3'];
    //         $res = Db::query("select count(*) s t_user where top1=:user_id and (b.top1=:user_id or b.top2=:user_id or b.top3=:user_id)", ['user_id' =>$this->userId]);
    //         if($res){
    //             $data['team'] = $res[0]['s'];
    //         }
            
            //提现人数
            $res = Db::query("select count(*) s from t_cash a inner join t_user b on a.user_id=b.id where a.status=1 and (b.top1={$this->userId} or b.top2={$this->userId} or b.top3={$this->userId})");
            if($res){
                $data['cash_count'] = $res[0]['s'];
            }
            
            $data['deposit'] = $data['tx1']+$data['tx2']+$data['tx3'];
        }
        return action_succ($data);
    }
    
    
}
