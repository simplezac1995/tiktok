<?php
namespace app\admin\controller;

use think\facade\View;

class Error
{
    public function __call($method, $args)
    {
        $request = request();
        $controller = $request->controller(true);
        $query = request()->baseUrl();
        
        if(!get_login_info()){
            if($request->isAjax()){
                return action_error('登入过期',[],'-1');
            }else{
                return "<script>top.location.href='/login'</script>";
                //return redirect('/login');
            }
            
        }
        
        //如果控制器 为 pages 直接转到对应视图页
        if($controller=="pages"){
            $view = str_replace(["/$controller",".html"], "", $query);
            $params = explode("/", $view);
            
            
            View::assign('controller',$params[1]);
            View::assign('action',$params[2]);
            
            View::assign('userInfo',get_login_info());
            
            return view($view);
        }
        
        return 'error request!';
    }
    
    public function authority(){
        return '权限错误';
    }
}