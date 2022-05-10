<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/3/9
 * Time: 11:20
 */

namespace app;

use app\admin\model\admin\User;
use think\App;
use think\exception\ValidateException;
class HomeController
{

    public function user($request)
    {
        $token = $request->header('token');
        $user = User::where('token', $token)->find();

        return $user;
    }

}