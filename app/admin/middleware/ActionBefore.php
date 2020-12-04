<?php
declare (strict_types = 1);

namespace app\admin\middleware;

use think\Response;

class ActionBefore
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        //登入者信息
        $loginInfo = get_login_info();
        
        $controller = $request->controller(true);
        $action  = $request->action();
        $isajax = $request->isAjax();
        
        //判断登陆是否过期
        if(!$loginInfo){
            if($isajax || $action=="upload"){
                return action_error('登入过期',[],'-1');
            }else{
                //return action_error('登入过期',[],'-1');
                return Response::create("<script>top.location.href='/login'</script>");
                //return redirect('/login');
            }
            
        }
        
        
        if($controller!="index" && $action!="upload"){//非 index 控制类 需要进行权限判断
            $power = $controller.".".$action;
            if(!has_power($power)){//无权限
                if($isajax){
                    return action_error('权限错误',[],'2');
                }else{
                    return redirect('/error/authority');
                }
            }
        }
        
        
        return $next($request);
    }
}
