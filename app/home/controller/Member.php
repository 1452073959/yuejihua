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
                        'profit' => 420,
                        'tranTime' => date('Y-m-d H:i:s', time()),
                        'describe' => '招商分润' . $pid['phone'],
                    ];
                } elseif ($pid['vip_label'] == 3) {
                    $profit = [
                        'user_id' => $pid['id'],//当前用户
                        'type' => 2,
                        'profit' => 480,
                        'tranTime' => date('Y-m-d H:i:s', time()),
                        'describe' => '招商分润' . $pid['phone'],
                    ];
                } elseif ($pid['vip_label'] == 4) {
                    $profit = [
                        'user_id' => $pid['id'],//当前用户
                        'type' => 2,
                        'profit' => 570,
                        'tranTime' => date('Y-m-d H:i:s', time()),
                        'describe' => '招商分润' . $pid['phone'],
                    ];
                }
                Profit::create($profit);
                $array = explode(',', $user['user_pid']);

              array_pop($array);
                foreach ($array as $k => $v) {
                    $profit = [
                        'user_id' => $v,//当前用户
                        'type' => 2,
                        'profit' => 30,
                        'tranTime' => date('Y-m-d H:i:s', time()),
                        'describe' => '招商分润' . $pid['phone'],
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
        }
    }
}