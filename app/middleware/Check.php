<?php
declare (strict_types = 1);

namespace app\middleware;
use app\admin\model\admin\User;

class Check
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
        //

        if (!$token = $request->header('token')) {
           return \app\home\help\Result::Error('1000', '请先登录');
        }else{
            $token = $request->header('token');
            $user = User::where('token', $token)->find();
            if(!$user){
                return \app\home\help\Result::Error('888', '登陆已过期请重新登录');
            }else{
                return $next($request);
            }
        }


    }
}
