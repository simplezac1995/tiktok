<?php
declare (strict_types = 1);

namespace app\api\controller;

use think\App;

class Qrcode
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;
    
    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;
    
    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;
    }
    public function index(){
        $sc = $this->request->get("sc");
        $url = "/h5/#/";
        if($sc){
            $url.="?sc=".$sc;
        }
        echo '<script>location.href="'.$url.'"</script>';
        exit;
    }
}
