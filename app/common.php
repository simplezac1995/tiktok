<?php
//公共文件
use think\facade\Cache;
use think\facade\Db;


/**
 * 加密密码
 * @param String $pwd
 * @return string
 */
function password_encrypt($pwd){
    return md5(md5($pwd));
}


/**
 * 请求事件成功返回
 * @param array $data
 * @param string $msg
 * @return Json
 */
function action_succ($data=[],$msg="成功"){
    return json(['succ'=>true,'code'=>0,'msg'=>$msg,'data'=>$data]);
}


/**
 * 请求事件失败返回
 * @param array $data
 * @param string $msg
 * @return Json
 */
function action_error($msg="失败",$data=[],$code=1){
    return json(['succ'=>false,'code'=>$code,'msg'=>$msg,'data'=>$data]);
}


/*
 *	递归创建目录
 *	必须是绝对目录
 */
function xmkdir($pathurl)
{
    $path = "";
    $str = explode(DIRECTORY_SEPARATOR,$pathurl);
    foreach($str as $k=>$dir)
    {
        if (!$k || empty($dir)) continue;
        $path .= DIRECTORY_SEPARATOR.$dir;
        if (!is_dir($path)){
            mkdir($path);
            @chmod($path,0777);
        }
    }
}


/**
 * 数字格式化（去掉小数点最后的0）
 * @param number $number 原始数字
 * @param number $float 保留小数位数
 * @return number
 */
function x_number_format($number, $float = 2) {
    if (!is_numeric($number)) return $number;
    $number = sprintf("%." . $float . "f", $number);
    $number = rtrim(rtrim($number, "0"), ".");
    return $number;
}

/**
 * 数字格式化
 * @param number $number 原始数字
 * @param number $float 保留小数位数
 * @return number
 */
function x_number_format2($number, $float = 2) {
    if (!is_numeric($number)) return $number;
    $number = sprintf("%." . $float . "f", $number);
    return $number;
}

/**
 * 获取系统配制参数
 * @param string $gp
 * @return array
 */
function get_config($gp="base"){
    // $config = Cache::get("config_".$gp);
    // if(!$config){
        $list = Db::name("config")->where("gp",$gp)->select();
        
        $data=[];
        foreach ($list as $val){
            if(strpos($val['key'],"_note")){
                $val['val']=nl2br($val['val']);
            }
            $data[$val['key']]=$val['val'];
        }
        $config = $data;
    //     Cache::set("config_".$gp, $data);
    // }
    
    return $config;
}

/**
 * 获取VIP等级信息
 * @param boolean $id
 * @return mixed|unknown[]
 */
function get_vipcards($id=false){
    $vipcards = Cache::get("vipcards");
    if(!$vipcards){
        $vipcards = Db::name("vipcard")->order("id asc")->select();
        Cache::set("vipcards", $vipcards);
    }
    
    $data=[];
    
    foreach ($vipcards as $val){
        if(!$id){
            $data[$val['id']]=$val['name'];
        }else if($id==$val['id']){
            $data=$val;
            break;
        }
    }
    
    return $data;
}


/**
 * 数字格式化
 * @param number $number 原始数字
 * @param number $float 保留小数位数
 * @return number
 */
function number_format_x($number, $float = 2) {
    if (!is_numeric($number)) return $number;
    $number = sprintf("%." . $float . "f", $number);
    $number = rtrim(rtrim($number, "0"), ".");
    return $number;
}