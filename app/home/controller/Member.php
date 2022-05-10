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
use app\home\help\Result;
use app\HomeController;

class Member extends HomeController
{
    protected $middleware = ['\app\middleware\Check::class'];

    //开通会员
    public function open()
    {
        $user = $this->user(request());
//        if ($user['vip_label'] != 0) {
//            return Result::Success($user, '该用户已经是会员,请勿重复开通');
//        }

        $user->vip_label = 1;
        $user->save();

        if ($user) {
            $pid = User::find($user['pid']);
            if ($pid['vip_label'] == 1) {
                $data = ['user_id' => $pid['id'], 'amount' => 420, 'describe' => '招商分润'];
            } elseif ($pid['vip_label'] == 2) {
                $data = ['user_id' => $pid['id'], 'amount' => 480, 'describe' => '招商分润'];
            } elseif ($pid['vip_label'] == 3) {
                $data = ['user_id' => $pid['id'], 'amount' => 570, 'describe' => '招商分润'];
            }

            AccountHistory::create($data);
            $array=explode(',',$user['user_pid']);
            $data1=[];
            unset($array[$pid['id']]);
            foreach ($array as $k=>$v)
            {
                $data1[]= ['user_id' => $v, 'amount' => 30, 'describe' => '招商分润'];
            }


            $AccountHistory = new AccountHistory();
            $AccountHistory->saveAll($data1);

        }

        return Result::Success($user, '开通成功');
    }
}