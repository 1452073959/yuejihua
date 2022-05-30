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
            'password' => md5($post['password']),
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
        if ($user['phone'] != $post['phone'] || $user['password'] != md5($post['password'])) {
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

    /**
     * @NodeAnotation(title="文件异步上传")
     */
    function file()
    {
        $file = request()->file('file');
        try {
            validate(['file' => [
                // 限制文件大小(单位b)，这里限制为4M
                'fileSize' => 2 * 1024 * 1024,
                // 限制文件后缀，多个后缀以英文逗号分割
                'fileExt' => 'png,jpg,jpeg'
                // 更多规则请看“上传验证”的规则，文档地址https://www.kancloud.cn/manual/thinkphp6_0/1037629#_444
            ]])->check(['file' => $file]);
//            //文文件类型
//            $mime_type = $file->getOriginalExtension();

            if (null === $file) {
                // 异常代码使用UPLOAD_ERR_NO_FILE常量，方便需要进一步处理异常时使用
                return Result::Error('1000', '未选择文件');
            }
            if (!is_null($file)) {
                //文件名
                $fileName = $file->getOriginalName();
                // 上传到本地服务器
                $info = \think\facade\Filesystem::disk('public')->putFileAs('/upload/avatar', $file, $fileName);
                return Result::Success(['path' => 'http://' . $_SERVER['HTTP_HOST'] . '/' . $info], '上传成功');
            }
        } catch (\Exception $e) {
            // 如果上传时有异常，会执行这里的代码，可以在这里处理异常
            return Result::Error('1000', $e->getMessage());
        }
    }

    //版本更新
    public function version(Request $request)
    {
        return Result::Success( ['link' => 'https://tudin.oss-cn-hangzhou.aliyuncs.com/beta/sourceletterlife1.0.1.apk', 'version_number' => '优化已知问题', 'version' => '1.0.1'], '成功');

    }


}