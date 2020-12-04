<?php
declare (strict_types = 1);

namespace app\api\controller;
use think\facade\Db;

class Index
{
    public function index()
    {
//         echo '<script>location.href="/h5/"</script>';
//         exit;
        return 'Hello！';
    }
    
    
    public function mqrcode(){
        return '请使用微信扫码';
    }
    
    public function test(){
        $time="0123456789";
        return substr($time,-2);
    }
    
}
