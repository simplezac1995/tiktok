<?php
/**
 * 报价
 * @param array $car 车型信息
 * @return number
 */
use think\facade\Db;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;

/**
 * 记录用户事件
 * @param int $userId
 * @param String $event
 * @param int $merchantId
 */
function user_event($userId,$event,$merchantId=0){
    $data = [
        'user_id'=>$userId,
        'event'=>$event,
        'create_time'=>time(),
        'dt'=>date("Y-m-d"),
    ];
    
    Db::name("user_event")->insert($data);
}

/**
 * 获取用户当天统计数据
 * @param int $user_id
 */
function get_today_count($user_id){
    $today = date("Y-m-d");
    $count = Db::name("user_count")->where("user_id",$user_id)->find();
    if(!$count){
        $id = Db::name("user_count")->insertGetId([
            'user_id'=>$user_id,
            'today'=>$today,
        ]);
        
        $count = Db::name("user_count")->where("id",$id)->find();
    }
    $isTodayData = false;//是否是今天的数据
    if($count['today']==$today){
        $isTodayData = true;
    }
    $data = ['today'=>$today];
    foreach ($count as $key=>$val){
        if(strpos($key, "today_")===0){
            if($isTodayData){
                $data[$key]=$val;
            }else{
                $data[$key]=0;
            }
            
        }
    }
    if(!$isTodayData){
        Db::name("user_count")->where("id",$count['id'])->update($data);
    }
    
    return $data;
}

/**
 * 设置今日统计数据
 * @param int $user_id
 * @param string $name 表格对应字段 取 today_name
 * @param float $step 添加值
 */
function set_today_count($user_id,$name,$step){
    $today = date("Y-m-d");
    $count = Db::name("user_count")->where("user_id",$user_id)->find();
    $field = 'today_'.$name;//目标字段
    if(!$count){
        Db::name("user_count")->insert([
            'user_id'=>$user_id,
            'today'=>$today,
            $field=>$step
        ]);
    }else{
        if($count['today']!=$today){
            //不是当天日期，修改所有当天数据为0
            $tabFields = ['today'=>$today];
            foreach ($count as $key=>$val){
                if(strpos($key, "today_")===0){
                    $tabFields[$key]=0;
                }
            }
            $tabFields[$field] = $step;
            Db::name("user_count")->where("id",$count['id'])->update($tabFields);
        }else{
            Db::name("user_count")->where("id",$count['id'])->update([
                $field=>Db::raw($field."+".$step),
            ]);
        }
    }
}


/**
 * 生成用户二维码
 * @param int $id
 * @return mixed
 */
function create_user_qrcode($id,$size=500){
    $app = app();
    $rootPath = $app->getRootPath()."public";
    $rooturl = $app->request->domain();
    // $rooturl2 = str_replace("enapi.", "www.", $rooturl);
    $rooturl2 = 'https://www.ugdtt.com';
    
    
    $savePath = config("filesystem.disks.public.root").DIRECTORY_SEPARATOR."userimg".DIRECTORY_SEPARATOR.$id.DIRECTORY_SEPARATOR."qrcode";
    xmkdir($savePath);
    $savePath = $savePath.DIRECTORY_SEPARATOR.$size.".png";
    if(!file_exists($savePath)){
        $sc = Db::name("user")->where('id',$id)->value("share_code");
        $qrCode = new QrCode();
        $qrCode->setText($rooturl2."/#/?sc=".$sc);
        $qrCode->setSize($size);
        $qrCode->setMargin(1);
        $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH());
        //如果要加上logo水印，则在调用setLogoPath和setLogoSize方法
//         $logoPath = $rootPath."/images/qrcode_logo.png";
//         $logoWidth = intval($size/5);
//         $qrCode->setLogoPath($logoPath);
//         $qrCode->setLogoSize($logoWidth);
        
        
        $qrCode->writeFile($savePath);
    }
    
    
    $rootPath = str_replace("\\", "/", $rootPath);
    $savePath = str_replace("\\", "/", $savePath);
    
    $url = str_replace($rootPath, $rooturl, $savePath);

    return $url;
}


function tel_formate($tel){
    //后面改成有可能是邮箱
    if(strpos($tel, "@")){//邮箱处理
        $temp = explode("@", $tel);
        $e = substr($temp[0],0,3);
        $e = str_pad($e, strlen($temp[0]),"*",STR_PAD_RIGHT);
        return $e."@".$temp[1];
    }else{//手机号处理
        $e = substr($tel,-4);
        //return str_pad($e, strlen($tel),"*",STR_PAD_LEFT);
        return "****".$e;
    }
    
}