<?php
namespace app\api\model;

use think\Model;
use think\facade\Db;

class Order extends Model{
    /**
     * 订单支付成功
     * @param string $sn 订单号
     * @param string $payCode 第三方支付流水号
     */
    public function paySuccess($sn,$payCode,$fee=0){
        $orderInfo = Db::name("order")->where(['sn'=>$sn])->find();
        
        if($orderInfo && $orderInfo['status']==0){
            $model = $this;
            Db::transaction(function () use($model,$orderInfo,$payCode){
                $status = 1;//已支付
                if($orderInfo['topid']){//续保
                    $status = 3;//审核通过
                }
                Db::name("order")->where(['id'=>$orderInfo['id']])->update([
                    'status'=>$status,
                    'pay_code'=>$payCode,
                    'pay_time'=>date('Y-m-d H:i:s')
                ]);
                
                $productInfo = Db::name($orderInfo['tab'])->find($orderInfo['tabid']);
                $dt_begin=date("Y-m-d");
                $dt_end = date("Y-m-d",strtotime("+{$productInfo['num']} YEARS")-24*60*60);
                
                $status = 1;
                if($orderInfo['topid']){//续保
                    $status = 2;//生效中
                    
                    //进入福耀待处理订单库
                    for ($i = 0; $i < $productInfo['num']; $i++) {
                        $btime = strtotime("+$i YEARS");
                        $dtb = date("Y-m-d",$btime);
                        $dte = date("Y-m-d",strtotime("+1 YEARS",$btime-24*60*60));
                        Db::name($orderInfo['tab']."_year")->insert([
                            'order_id'=>$productInfo['order_id'],
                            'order_sn'=>$productInfo['order_sn'],
                            'car_num'=>$productInfo['car_num'],
                            'car_brand'=>$productInfo['car_brand'],
                            'car_series'=>$productInfo['car_series'],
                            'car_model'=>$productInfo['car_model'],
                            'car_imgurl'=>$productInfo['car_imgurl'],
                            'car_vin'=>$productInfo['car_vin'],
                            'name'=>$productInfo['name'],
                            'tel'=>$productInfo['tel'],
                            'price'=>$productInfo['price'],
                            'dt_begin'=>$dtb,
                            'dt_end'=>$dte,
                            'order_id'=>$productInfo['order_id'],
                            'status'=>0,
                            'user_id'=>$productInfo['user_id'],
                            'merchant_id'=>$productInfo['merchant_id'],
                            'create_time'=>time(),
                        ]);
                    }
                }
                
                //修改订单产品状态及生效时间
                Db::name($orderInfo['tab'])->where(["order_id"=>$orderInfo['id']])->update([
                    'dt_begin'=>$dt_begin,
                    'dt_end'=>$dt_end,
                    'status'=>$status,
                ]);
                
                //添加分佣信息
                $model->popularizeCommission($orderInfo);
            });
        }
    }
    
    /**
     * 推广分佣
     * @param array $orderInfo
     */
    public function popularizeCommission($orderInfo){
        //添加分佣信息
        if($orderInfo['merchant_id']){
            $merchants = merchant_tops($orderInfo['merchant_id']);
            if($merchants){//有商户数据
                
                
                $cost = config("sys.yidaodaka_cost");//成本
                $ratio = config("sys.popularize_ratio");//推广分佣比例
                $total = x_number_format(($orderInfo['total']-$cost)*$ratio/100);//分佣总金额
                
                $commission=[
                    'type'=>1,
                    'order_id'=>$orderInfo['id'],
                    'status'=>0,
                    'total'=>$orderInfo['total'],
                    'create_time'=>time(),
                    'total'=>$total,
                ];//佣金数据
                
                $datas = [];
                foreach ($merchants as $merchant){
                    $level = $merchant['level'];
                    if($level==2){
                        $money = x_number_format($total*$merchant['proportion']/100);
                        
                    }else{
                        $data = $datas[$level+1];//查找上级分配到的佣金
                        $money = x_number_format($data['money']*$merchant['proportion']/100);
                        $datas[$level+1]['money'] = $data['money']-$money;
                    }
                    
                    $commission['level'] = $level;
                    $commission['money'] = $money;
                    $commission['proportion'] = $merchant['proportion'];
                    $commission['merchant_id'] = $merchant['id'];
                    
                    if($merchant['status']==1){//正常状态下才有分佣
                        $commission['status'] = 0;
                    }else{
                        $commission['status'] = -1;
                    }
                    
                    $datas[$level]=$commission;
                }
                
                Db::name("commission")->insertAll($datas);
            }
        }
    }
    
    /**
     * 区域分佣
     * @param array $orderInfo
     */
    public function areaCommission($orderInfo){
        $cost = config("sys.yidaodaka_cost");//成本
        $ratio = config("sys.area_ratio");//区域分佣比例
        $ratioProvince = config("sys.area_ratio_province");//区域分佣比例 省
        $ratioCity = config("sys.area_ratio_city");//区域分佣比例 市
        $total = x_number_format(($orderInfo['total']-$cost)*$ratio/100);//分佣总金额
        
        $product = Db::name("yidaodaka")->where("order_id",$orderInfo['id'])->find();
        if(!$product){
           return; 
        }
        
        $carNum = $product['car_num'];//车牌号
        if(!$carNum) return;
        
        $cnLen = strlen("中");//中文长度
        $province = substr($carNum,0,$cnLen);//省份简称
        $city = substr($carNum,0,$cnLen+1);//市简称
        
        $datas = [];
        //省代理
        $merchantProvince = Db::name("merchant_area")->where("car_num",$province)->value("merchant_id");
        if($merchantProvince){
            $money = x_number_format($total*$ratioProvince/100);
            $commission=[
                'type'=>2,
                'order_id'=>$orderInfo['id'],
                'status'=>0,
                'total'=>$orderInfo['total'],
                'create_time'=>time(),
                'total'=>$total,
                'level'=>2,
                'money'=>$money,
                'proportion'=>$ratioProvince,
                'merchant_id'=>$merchantProvince
            ];
            
            $datas[]=$commission;
        }
        
        //市代理
        $merchantCity = Db::name("merchant_area")->where("car_num",$city)->value("merchant_id");
        if($merchantCity){
            $money = x_number_format($total*$ratioCity/100);
            $commission=[
                'type'=>2,
                'order_id'=>$orderInfo['id'],
                'status'=>0,
                'total'=>$orderInfo['total'],
                'create_time'=>time(),
                'total'=>$total,
                'level'=>1,
                'money'=>$money,
                'proportion'=>$ratioCity,
                'merchant_id'=>$merchantCity
            ];
            
            $datas[]=$commission;
        }
        
        if($datas){
            Db::name("commission")->insertAll($datas);
        }
            
    }
}