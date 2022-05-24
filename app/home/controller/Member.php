<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/3/14
 * Time: 10:27
 */

namespace app\home\controller;

use app\admin\model\AccountHistory;
use app\admin\model\admin\User;
use app\admin\model\pay\Profit;
use app\home\help\Result;
use app\HomeController;
use think\facade\Db;
use think\Request;

class Member extends HomeController
{
    protected $middleware = ['\app\middleware\Check::class'];

    //开通会员
    public function open()
    {
        // 启动事务
        Db::startTrans();
        try {
            $user = $this->user(request());
            if ($user['vip_label'] != 1) {
                return Result::Success($user, '该用户已经是会员,请勿重复开通');
            }

            $user->vip_label = 2;
            $user->save();

            if ($user) {
                $pid = User::find($user['pid']);
                //直属上级分润
                if ($pid['vip_label'] == 2) {
                    $profit = [
                        'user_id' => $pid['id'],//当前用户
                        'type' => 2,
                        'createtime' => time(),
                        'profit' => 420,
                        'tranTime' => date('Y-m-d H:i:s', time()),
                        'describe' => '招商分润' . $user['phone'],
                    ];
                } elseif ($pid['vip_label'] == 3) {
                    $profit = [
                        'user_id' => $pid['id'],//当前用户
                        'type' => 2,
                        'createtime' => time(),
                        'profit' => 480,
                        'tranTime' => date('Y-m-d H:i:s', time()),
                        'describe' => '招商分润' . $user['phone'],
                    ];
                } elseif ($pid['vip_label'] == 4) {
                    $profit = [
                        'user_id' => $pid['id'],//当前用户
                        'type' => 2,
                        'createtime' => time(),
                        'profit' => 570,
                        'tranTime' => date('Y-m-d H:i:s', time()),
                        'describe' => '招商分润' . $user['phone'],
                    ];
                }
                Profit::create($profit);
                $array = explode(',', $user['user_pid']);
                $array=array_slice($array,0,9);
                array_pop($array);
                foreach ($array as $k => $v) {
                    $profit = [
                        'user_id' => $v,//当前用户
                        'type' => 2,
                        'createtime' => time(),
                        'profit' => 30,
                        'tranTime' => date('Y-m-d H:i:s', time()),
                        'describe' => '招商分润' . $user['phone'],
                    ];
                    Profit::create($profit);
                }


            }
            // 提交事务
            Db::commit();
            return Result::Success($user, '开通成功');

        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $e->getMessage();
        }
    }

    //修改卡信息
    public function user_edit()
    {
        $req = request()->param();
        $res = User::update($req);
        if ($res) {
            return Result::Success($res);
        } else {
            return Result::Error('失败', 1000);
        }
    }


    //注册
    public function code(Request $request)
    {

        $user = $this->user($request);

        $code = tudincode('https://' . $_SERVER['HTTP_HOST'] . '/scanCode/scan_code.html?' . 'code=' . $user['user_code']);
//        $code = 'https://' . $_SERVER['HTTP_HOST'] . '/scanCode/scan_code.html?' . 'code=' . $user['pushing_code'];
        return Result::Success(['base64img' => $code, 'url' => 'https://' . $_SERVER['HTTP_HOST'] . '/scanCode/scan_code.html?' . 'code=' . $user['user_code']]);

    }


}