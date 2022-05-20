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


    //收益中心
    public function dealhistory()
    {
        $user = $this->user(request());
        $req = request()->param();
        //当天开始时间
        $start_time = strtotime(date("Y-m-d", time()));
        //当天结束之间
        $end_time = $start_time + 60 * 60 * 24;
//        $firstTime = \think\facade\Request::post('firstTime', $start_time);
//        $lastTime = \think\facade\Request::post('lastTime', $end_time);
        if ($req['firstTime'] == '') {
            $firstTime = $start_time;
        } else {
            $firstTime = $req['firstTime'];
        }
        if ($req['lastTime'] == '') {
            $lastTime = $end_time;
        } else {
            $lastTime = $req['lastTime'];
        }

        $res = Profit::with('card')
            ->where('user_id', $user['id'])
            ->whereBetweenTime('createtime', $firstTime, $lastTime)
            ->where('type', $req['type'])
            ->order('id', 'desc')->paginate(10);
        return Result::Success($res);

    }
}