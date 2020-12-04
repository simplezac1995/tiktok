<?php
namespace app\admin\model;

use think\Model;

class PayLog extends Model{
    public function user(){
        return $this->hasOne(AdminUser::class,'id','create_user');
    }
}