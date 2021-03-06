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
            if (!$code) {
                return Result::Error('1000', '请确认权益码是否有效');
            }
            $code->order_status = 4;
            $code->phone_merber = $req['phone'];
            $code->save();


            $user = User::where('phone', $req['phone'])->find();
            if (!$user) {
                return Result::Error('1000', '请确认账户是否存在');
            }
            if ($user['vip_label'] != 1) {
                return Result::Error('1000', '该用户已经是会员,请勿重复开通');
            }

            $user->vip_label = 2;
            $user->save();
            if ($code['is_special'] == 2) {
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
                    } else {
                        return Result::Error('1000', '上级不是会员!');
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
        $req = $request->param();
        $code = Memberorder::where('order_status', 2)->where('user_id', $user['id'])->count();
        if($code==1&&$user['vip_label']==1){
            return Result::Error(1000,'购买更多,请先激活会员权益!');
        }
        $str = md5(time());
        $code = substr($str, 5, 7);
        $out_trade_no = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//订单号，自己生成
        $mermber = new Memberorder();
        $mermber->no = $out_trade_no;
        $mermber->user_id = $user['id'];
        $mermber->is_special = $req['is_special'];
        $mermber->merber_code = $code;
        if ($req['is_special'] == 1) {
            $payorder = transfer6(1, $out_trade_no, $user['name'] . '的会员订单');
        } else {
            $payorder = transfer6(998, $out_trade_no, $user['name'] . '的会员订单');
        }
        $mermber->order_pay = $payorder;
        $mermber->save();
        return $payorder;
    }

    public function special()
    {
        return Result::Success(['is_special' => 0], '成功');
    }

    //权益码列表
    public function member_code_list(Request $request)
    {
        $user = $this->user($request);
        $req = $request->param();
        $member_code_list = Memberorder::where('user_id', $user['id'])->where('order_status', 'in', $req['status'])->field('order_status,id,merber_code,order_pay,phone_merber')->paginate(10);
        return Result::Success($member_code_list, '成功');
    }


    //注册二维码
    public function code(Request $request)
    {

        $user = $this->user($request);
        if($user['vip_label']==1){
            return Result::Error('1000','请先开通会员');
        }
        $code = tudincode('http://' . $_SERVER['HTTP_HOST'] . '/h5/index.html#/?' . 'code=' . $user['user_code']);
//        $code = 'https://' . $_SERVER['HTTP_HOST'] . '/scanCode/scan_code.html?' . 'code=' . $user['pushing_code'];
        return Result::Success(['base64img' => $code, 'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/h5/index.html#/?' . 'code=' . $user['user_code']]);

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
        return Result::Success(['data' => $res, 'count1' => $res1, 'count2' => $res2, 'sum' => round($sum, 2), 'today' => $res3]);

    }


    //提现
    public function tixian(Request $request)
    {
        $req = $request->param();
        $user = $this->user($request);
        $validate = new \think\Validate();
        $validate->rule([
            'name|姓名' => 'require',
            'account|账号' => 'require',
            'money|金额' => 'require',
            'type|提现类型' => 'require',
        ]);
        if (!$validate->check($req)) {
            return Result::Error('1000', $validate->getError());
        }
        Db::startTrans();
        try {
            //获取代理的一级代理
            $res = User::where('id', $user['id'])->find();
            if ($req['type'] == 1) {
                $res->profit_balance = $res['profit_balance'] - round($req['money'], 2);
                $res->save();
                if ($res['profit_balance'] < 0) {
                    // 回滚事务
                    Db::rollback();
                    return Result::Error('1000', '分润余额不足');
                }
            }
            if ($req['type'] == 2) {
                $res->recruit_balance = $res['recruit_balance'] - round($req['money'], 2);
                $res->save();
                if ($res['recruit_balance'] < 0) {
                    // 回滚事务
                    Db::rollback();
                    return Result::Error('1000', '招商余额不足');
                }
            }
            $resultCode = transfer4($req['account'], $req['name'], round($req['money'], 2));
            if (!empty($resultCode) && $resultCode->code == 10000) {
                $tixian = [
                    'user_id' => $user['id'],
                    'type' => $req['type'],
                    'name' => $req['name'],
                    'account' => $req['account'],
                    'money' => $req['money'],
                    'time' => date('Y-m-d H:i:s', time())
                ];
                Db::name('tixian_history')->insert($tixian);
                // 提交事务
                Db::commit();
                return Result::Success($resultCode->msg, '打款成功');
            } else {
                // 回滚事务
                Db::rollback();
                return Result::Error('1000', $resultCode->sub_msg);
            }
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return Result::Error('1000', $e->getMessage());
        }

    }

    //提现记录
    public function tixian_history(Request $request)
    {
        $user = $this->user($request);
        $data = Db::name('tixian_history')->where('user_id', $user['id'])->paginate(10);
        return Result::Success($data, '成功');
    }


    //用户信息
    public function userinfo(Request $request)
    {
        $user = $this->user($request);
        return Result::Success($user, '成功');
    }


}