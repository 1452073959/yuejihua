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
use app\admin\model\member\Memberorder;
use app\admin\model\pay\Profit;
use app\home\help\Result;
use app\HomeController;
use think\facade\Log;
use think\Request;
use think\facade\Db;

class Member extends HomeController
{
    protected $middleware = ['\app\middleware\Check::class'];

    //开通会员
    public function open(Request $request)
    {

        $req = $request->param();
        // 启动事务
        Db::startTrans();
        try {
            $code = Memberorder::where('order_status', 2)->where('merber_code', $req['merber_code'])->find();
            $code->order_status = 4;
            $code->phone_merber = $req['phone'];
            $code->save();
            if (!$code) {
                return Result::Error('1000', '请确认权益码是否有效');
            }
            $user = User::where('phone', $req['phone'])->find();
            if (!$user) {
                return Result::Error('1000', '请确认账户是否存在');
            }
            if ($user['vip_label'] != 1) {
                return Result::Error('1000', '该用户已经是会员,请勿重复开通');
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

                $userrecruit_balance = User::where('id', $profit['user_id'])->find();
                $userrecruit_balance->recruit_balance = $userrecruit_balance['recruit_balance'] + $profit['profit'];
                $userrecruit_balance->save();
                $array = explode(',', $user['user_pid']);
                $array = array_slice($array, 0, 9);
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

                    $userrecruit_balance = User::where('id', $profit['user_id'])->find();
                    $userrecruit_balance->recruit_balance = $userrecruit_balance['recruit_balance'] + $profit['profit'];
                    $userrecruit_balance->save();
                }


            }
            // 提交事务
            Db::commit();
            return Result::Success($user, '开通成功,请重新登陆');

        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return Result::Error('1000', $e->getMessage());
        }
    }

    //修改卡信息
    public function user_edit()
    {
        $req = request()->param();
        if (isset($req['password'])) {
            $req['password'] = md5($req['password']);
//            $req['remark']=$req['password'];
        }
        $res = User::update($req);
        if ($res) {
            return Result::Success($res);
        } else {
            return Result::Error('1000', '失败');
        }
    }

    //购买会员
    public function memberpay(Request $request)
    {
        $user = $this->user($request);
        $str = md5(time());
        $code = substr($str, 5, 7);
        $out_trade_no = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//订单号，自己生成
        $mermber = new Memberorder();
        $mermber->no = $out_trade_no;
        $mermber->user_id = $user['id'];
        $mermber->merber_code = $code;
        $payorder = transfer6(0.1, $out_trade_no, $user['name'] . '的会员订单');
        $mermber->order_pay = $payorder;
        $mermber->save();
        return $payorder;
    }

    //权益码列表
    public function member_code_list(Request $request)
    {
        $user = $this->user($request);
        $req = $request->param();
        $member_code_list = Memberorder::where('user_id', $user['id'])->where('order_status', 'in', $req['status'])->field('order_status,id,merber_code,order_pay,phone_merber')->paginate(10);
        return Result::Success($member_code_list, '成功');
    }


    //注册
    public function code(Request $request)
    {

        $user = $this->user($request);

        $code = tudincode('https://' . $_SERVER['HTTP_HOST'] . '/scanCode/scan_code.html?' . 'code=' . $user['user_code']);
//        $code = 'https://' . $_SERVER['HTTP_HOST'] . '/scanCode/scan_code.html?' . 'code=' . $user['pushing_code'];
        return Result::Success(['base64img' => $code, 'url' => 'https://' . $_SERVER['HTTP_HOST'] . '/scanCode/scan_code.html?' . 'code=' . $user['user_code']]);

    }
    //招商收益
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
        //列表
        $res = Profit::with('card')
            ->where('user_id', $user['id'])
            ->whereBetweenTime('createtime', $firstTime, $lastTime)
            ->where('type', $req['type'])
            ->order('id', 'desc')->paginate(10)->toArray();

        $res1 = Profit::with('card')
            ->where('user_id', $user['id'])
            ->whereBetweenTime('createtime', $firstTime, $lastTime)
            ->where('type', 1)
            ->order('id', 'desc')->count();
        $res2 = Profit::with('card')
            ->where('user_id', $user['id'])
            ->whereBetweenTime('createtime', $firstTime, $lastTime)
            ->where('type', 2)
            ->order('id', 'desc')->count();
        //今日收益
        $res3 = Profit::with('card')
            ->where('user_id', $user['id'])
             ->whereDay('createtime')
            ->sum('profit');
        $sum = 0;
        foreach ($res['data'] as $k => $v) {
            $sum += $v['profit'];
        }
        return Result::Success(['data' => $res, 'count1' => $res1, 'count2' => $res2, 'sum' => $sum,'today'=>$res3]);

    }


}