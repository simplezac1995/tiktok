<?php
namespace app\admin\model;

use think\Model;

class AdminUser extends Model{
    public function getLastLoginAttr($val){
        if($val)return date("Y-m-d H:i:s",$val);
        else return '';
    }
}