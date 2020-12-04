<?php
declare (strict_types = 1);

namespace app\api\controller;

use think\App;
use think\facade\Db;
use Exception;

class Timer
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
    public function __construct(App $app){
        $this->app     = $app;
        $this->request = $this->app->request;
    }
    
    public function index(){
        echo app()->request->domain();
        return 'Hello timer！';
    }
    
}
