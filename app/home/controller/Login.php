<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/2/21
 * Time: 10:47
 */

namespace app\home\controller;

use app\admin\model\admin\User;
use app\HomeController;
use think\facade\Cache;
use app\Request;
use app\home\help\Result;
use think\facade\Config;

class Login extends HomeController
{

    //注册
    public function register(\app\Request $request)
    {

        $post = $request->post();
        $validate = new \think\Validate();
        $validate->rule([
//            'name|登陆名' => 'require|unique:system_admin',
            'password|密码' => 'require',
            'phone|手机号' => 'require|number|unique:admin_user',
            'user_code|邀请码' => 'require',
            'code|验证码' => 'require',
        ]);
        if (!$validate->check($post)) {
            return Result::Error('1000', $validate->getError());
        }

        $num = Cache::get($post['phone']);
        if ($num != $post['code']) {
            return Result::Error('1000', '验证码错误,或已超时');
        }
        $str = md5(time());
        $token = substr($str, 5, 7);
        $user = [
            'name' => $post['phone'],
            'password' => $post['password'],
            'phone' => $post['phone'],
            'user_code' => $token,//推荐码
        ];


        $highercount = User::where('user_code', '=', $post['user_code'])->count();
        $higher = User::where('user_code', '=', $post['user_code'])->find();

        if ($highercount <= 0) {
            return Result::Error('1000', '推荐码不存在');
        }
        $user['pid'] = $higher['id'];
        $user['user_pid']=$higher['user_pid'].','.$higher['id'];

        $res = User::create($user);
        if ($res) {
            return Result::Success($res, '注册成功');
        } else {
            return Result::Error('1000', '注册失败');
        }

    }


    //登陆
    public function login(Request $request)
    {
        $post = $request->post();

        $validate = new \think\Validate();
        $validate->rule([
            'phone|登陆名' => 'require',
            'password|密码' => 'require',
        ]);
        if (!$validate->check($post)) {
            return Result::Error('1000', $validate->getError());
        }

        $user = User::where('phone', $post['phone'])->find();
        if ($user['phone'] != $post['phone'] || $user['password'] != $post['password']) {
            return Result::Error('1000', '账号或密码错误');
        }
        $user->token = password(time());
        $user->save();
        $user = User::where('token', $user['token'])->withoutField('content')->find();
        return Result::Success(['user' => $user], '登陆成功');

    }

    //忘记密码
    public function forget_password(Request $request)
    {
        $post = $request->post();
        $validate = new \think\Validate();
        $validate->rule([
//            'name|登陆名' => 'require|unique:system_admin',
            'password|密码' => 'require',
            'phone|手机号' => 'require|number',
            'code|验证码' => 'require',
        ]);
        if (!$validate->check($post)) {
            return Result::Error('1000', $validate->getError());
        }

        $num = Cache::get($post['phone']);
        if ($num != $post['code']) {
            return Result::Error('1000', '验证码错误,或已超时');
        } else {
            $user = User::where('phone', $post['phone'])->find();
            $user->password = md5($post['password']);
            $user->save();
            return Result::Success($user, '成功');
        }


    }

    //发送短信
    public function sms(Request $request)
    {
        $phone = input('post.phone');
        if (empty($phone) || !validatePhone($phone)) {
            return json(['code' => 100, 'msg' => '请输入正确的手机号!']);
        }
        $sign = Config::get('alisms.SignName');
        $code = Config::get('alisms.TemplateCode');
        $ak = Config::get('alisms.AccessKeyId');
        $sk = Config::get('alisms.Secret');
        $num = mt_rand(1000, 9999);
        // 请求的参数
        $params = [
            'phone' => $phone,
            'sign' => $sign,
            'code' => $code,
            'param' => json_encode([
                'code' => $num,
            ])
        ];
        $res = send_sms($ak, $sk, $params);
        if ($res['Code'] === 'OK') {
            Cache::set($phone, $num, 120);
            return Result::Success($num, '验证码发送成功');
        } else {
            return Result::Error('1000', '验证码发送失败,请稍后再试');
        }
    }

}