<?php
declare (strict_types = 1);

namespace app\api\middleware;

use think\Response;
use think\facade\Db;
use org\ApiTool;

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
        
        $controller = $request->controller(true);
        
        $param = $request->post();
        
        $msgs = config("msg");
        
        if($controller!="login" && $controller!="open"){//非 login 控制类 需要进行数据验证
            
            if(empty($param['user'])){
                return json(['succ'=>false,'msg'=>$msgs['login_check'],'code'=>-1]);
            }
            
            if(empty($param['timestamp']) || empty($param['sign'])){
                return json(['succ'=>false,'msg'=>$msgs['param_error']]);
            }
            
            $userid = $param['user'];
            
            if (time() - $param['timestamp'] >= 3600) {
                return json(['succ'=>false,'msg'=>'timestamp error']);
            }
            
            $token = Db::name("user_token")->where(['user_id'=>$userid])->value("token");
            
            if(!$token){
                return json(['succ'=>false,'msg'=>$msgs['username_not_exist'],'code'=>-1]);
            }
            $tool = new ApiTool($token);
            
            $mysign = $tool->getSign($param);
            
            
            
            if ($param['sign'] != $mysign) {
                return json(['succ'=>false,'msg'=>$msgs['login_exp'],'code'=>-1]);
            }
        }
        
        
        return $next($request);
    }
}
