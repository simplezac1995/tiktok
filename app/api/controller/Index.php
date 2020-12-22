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
    
    public function setUserTop(){
        $where = [];
        $where['top1'] = 0;
        $where['top2'] = 0;
        $where['top3'] = 0;
        $userList = Db::name('user')->field('id')->where($where)->select()->toArray();//最极限的id数组
        if(!empty($userList)){
            foreach ($userList as $key => $value) {
                $ids = [];
                $ids[] = $value['id'];
                $down = [];
                while ($ids) {
                    $list = Db::name('user')->field('id')->where('top1', 'in', $ids)->select()->toArray();//下级的所有id
                    if(!empty($list)){
                        $down = array_merge($down, array_column($list, 'id'));
                        $ids  = array_column($list, 'id');
                    }else{
                        break;
                    }
                }

                $res  = Db::name('user')->where('id', 'in', $down)->update(['higher_top'=>$value['id']]);
            }
        }

    }
}
