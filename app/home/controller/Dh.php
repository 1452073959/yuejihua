<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/4/27
 * Time: 16:18
 */

namespace app\home\controller;

use app\admin\model\admin\User;
use app\admin\model\pay\Order;
use app\admin\model\pay\Profit;
use app\admin\model\plan\OrderPlan;
use app\admin\model\plan\PlanDeal;
use app\admin\model\UserCard;
use app\home\help\Result;
use app\home\help\Yjh;
use app\HomeController;
use think\db\Query;
use think\facade\Db;

class Dh extends HomeController
{
    //代还消费
    public function pay($req, $user)
    {
//        $req = request()->param();
//        $user = $this->user(request());
        $out_trade_no = 'yjh' . date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//订单号，自己生成
//        dump($out_trade_no);die;
        $card = UserCard::where('card_no', $req['bankCardNo'])->find();
        if (!$card) {
            return '未找到该卡';
        }

        $reqData = [
            'bankCardNo' => $card['card_no'],//卡号
            'bindId' => $card['bindId'],//绑卡id
            'notifyUrl' => "https://tdnetwork.cn/api/notice/alipay1",//通知地址
            'orderAmount' => $req['orderAmount'] * 100,//订单基金额
            'orderNo' => $out_trade_no,//
            'rate' => 0.008,
            'subMerchantNo' => $user['subMerchantNo'],
            'cityId' => "420600"
        ];
        $a = new Yjh();
        $res = $a->pay($reqData);
        $order = new Order();
        $order->card_id = $card['id'];
        $order->user_id = $user['id'];
        $order->orderNo = $out_trade_no;
        $order->rate = 0.008;
        $order->orderAmount = $req['orderAmount'];

        if ($res['code'] == 0) {
            $order->pay_status = 2;
            $order->message = $res['message'];
            $order->save();
            return $res;
        } else {
            $order->pay_status = 1;
            $order->message = $res['message'];
            $order->save();
            return $res;
        }


    }

    //代还查询(消费
    public function consumePayCheck()
    {
        $req = request()->param();
        $user = $this->user(request());
        $postData = [
            'orderNo' => $req['orderNo'],
            'subMerchantNo' => $user['subMerchantNo']
        ];
        $a = new Yjh();
        $res = $a->consumePayCheck($postData);
        if ($res['code'] == 0) {
            return Result::Success($res, '成功');
        }
    }

    //代还偿还
    public function repay($req, $user)
    {
//        $req = request()->param();

//        $user = $this->user(request());
        $card = UserCard::where('card_no', $req['bankCardNo'])->find();
        if (!$card) {
            return Result::Error('1000', '未找到该卡');
        }
        $out_trade_no = 'yjh' . date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//订单号，自己生成
        $reqData = [
            "bankCardNo" => $card['card_no'],
            "bindId" => $card['bindId'],
            "fee" => 100,
            "orderAmount" => $req['orderAmount'] * 100,
            "orderNo" => $out_trade_no,
            "subMerchantNo" => $user['subMerchantNo'],
            'notifyUrl' => "https://tdnetwork.cn/api/notice/alipay1",//通知地址
        ];
        $a = new Yjh();
        $res = $a->repay($reqData);
        $order = new Order();
        $order->order_type = 2;
        $order->card_id = $card['id'];
        $order->user_id = $user['id'];
        $order->orderNo = $out_trade_no;
        $order->fee = 100;
        $order->orderAmount = $req['orderAmount'];
        $order->orderAmount = $req['orderAmount'];
        $order->save();
        if ($res['code'] == 0) {
            $order->pay_status = 2;
            $order->message = $res['message'];
            $order->save();
            return $res;
        } else {
            $order->pay_status = 1;
            $order->message = $res['message'];
            $order->save();
            return $res;
        }
    }

    //偿还查询
    public function queryrepay()
    {
        $req = request()->param();
        $user = $this->user(request());
        $postData = [
            'orderNo' => $req['orderNo'],
            'subMerchantNo' => $user['subMerchantNo']
        ];
        $a = new Yjh();
        $res = $a->queryrepay($postData);
        if ($res['code'] == 0) {
            return Result::Success($res, '成功');
        }
    }

//        还款计划
//金额,笔数,
    public function planadd()
    {
        $user = $this->user(request());
        //获取当前日
        $d = date('d', time());

        $req = request()->param();
        //获取当前卡
        $card = UserCard::where('id', $req['card_id'])->find()->toArray();
//        当前卡还款日剩余日期
        $remaining = $card['repayment_date'] - $d;
//        dump($card);
// 启动事务
        Db::startTrans();
        try {
            //创建还款计划
            $orderplan = [
                'user_id' => $user['id'],//用户
                'card_id' => $req['card_id'],//卡
                'bill_amount' => $req['bill_amount'],//账单金额
                'card_balance' => $req['card_balance'],//卡余额
                'pending_amount' => $req['bill_amount'],//代还金额
                'repayment_mode' => $req['repayment_mode'],//模式
                'create_time' => date('Y-m-d H:i:s', time()),//创建时间
                'plan_number' => $req['plan_number'],//还款次数
                'plan_no' => 'jihua' . date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT)//单号
            ];
            $orderplanId = Db::name('order_plan')->insertGetId($orderplan);
            $req['repayment_date'] = explode('@', $req['repayment_date']);

//            时间
            $date = date('H', time());
            for ($i = 0; $i <= $req['plan_number'] - 1; $i++) {
                $plan_details = [
                    'plan_id' => $orderplanId,
                    'plan_name' => 1 + $i . '笔',
                    'card_id' => $req['card_id'],//卡
                ];
                $plan_detailsid = Db::name('plan_details')->insertGetId($plan_details);
                if ($req['repayment_mode'] == 1) {
                    //消费
                    $money = ceil($req['bill_amount'] / $req['plan_number'] / 0.992) + 1;
                    $plan_deal_x = [
                        'trade_amount' => $money,
                        'trade_time' => $req['repayment_date'][$i] . ' ' . (1 + $i + $date . ':00:00'),
                        'actual_amount' => '0',
                        'trade_fee' => $money - ($req['bill_amount'] / $req['plan_number']),
                        'trade_type' => 1,
                        'card_id' => $req['card_id'],//卡
                        'plan_details_id' => $plan_detailsid,
                        'user_id' => $user['id']
                    ];

                    Db::name('plan_deal')->save($plan_deal_x);
                    //还款
                    $plan_deal_h = [
                        'trade_amount' => $req['bill_amount'] / $req['plan_number'],
                        'trade_time' => $req['repayment_date'][$i] . ' ' . (1 + $i + $date . ':30:00'),
                        'actual_amount' => $req['bill_amount'] / $req['plan_number'],
                        'trade_fee' => 1.00,
                        'trade_type' => 2,
                        'card_id' => $req['card_id'],//卡
                        'plan_details_id' => $plan_detailsid,
                        'user_id' => $user['id']
                    ];
                    Db::name('plan_deal')->save($plan_deal_h);
                } elseif ($req['repayment_mode'] == 2) {
                    $money = ceil($req['bill_amount'] / $req['plan_number'] / 2 / 0.992) + 0.5;
                    //消费
                    $plan_deal_x = [
                        'trade_amount' => $money,
                        'trade_time' => $req['repayment_date'][$i] . ' ' . (1 + $i + $date . ':00:00'),
                        'actual_amount' => '0',
                        'trade_fee' => $money - $req['bill_amount'] / $req['plan_number'] / 2,
                        'trade_type' => 1,
                        'card_id' => $req['card_id'],//卡
                        'plan_details_id' => $plan_detailsid,
                        'user_id' => $user['id']
                    ];
                    Db::name('plan_deal')->save($plan_deal_x);
                    $plan_deal_x = [
                        'trade_amount' => $money,
                        'trade_time' => $req['repayment_date'][$i] . ' ' . (1 + $i + $date . ':15:00'),
                        'actual_amount' => '0',
                        'trade_fee' => $money - $req['bill_amount'] / $req['plan_number'] / 2,
                        'trade_type' => 1,
                        'card_id' => $req['card_id'],//卡
                        'plan_details_id' => $plan_detailsid,
                        'user_id' => $user['id']
                    ];
                    Db::name('plan_deal')->save($plan_deal_x);

                    //还款
                    $plan_deal_h = [
                        'trade_amount' => $req['bill_amount'] / $req['plan_number'],
                        'trade_time' => $req['repayment_date'][$i] . ' ' . (1 + $i + $date . ':30:00'),
                        'actual_amount' => $req['bill_amount'] / $req['plan_number'],
                        'trade_fee' => 1.00,
                        'trade_type' => 2,
                        'card_id' => $req['card_id'],//卡
                        'plan_details_id' => $plan_detailsid,
                        'user_id' => $user['id']
                    ];
                    Db::name('plan_deal')->save($plan_deal_h);
                }
            }


            // 提交事务
            Db::commit();

            return Result::Success('', '创建成功');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $e->getMessage();
        }
    }

//    预览计划
    public function planshow()
    {
        $user = $this->user(request());
        //获取当前日
        $d = date('d', time());
        $req = request()->param();
        $order = OrderPlan::with(['details' => function (Query $query) {
            $query->with(['deal']);
        }, 'card'])->where('user_id', $user['id'])->where('plan_status', 1)->select();
        dump($order->toArray());

    }


    //执行计划消费
    public function planstart()
    {
        $plan = PlanDeal::with(['card', 'user', 'PlanDetails'])->where('trade_type', 1)->where('trade_status', 1)->select();

//      dump($plan->toArray());

        foreach ($plan as $k => $v) {
            //消费参数
            $arr = ['orderAmount' => $v['trade_amount'], 'bankCardNo' => $v['card']['card_no']];
            if (time() > strtotime($v['trade_time'])) {
                $a = $this->pay($arr, $v['user']);
                $res = PlanDeal::find($v['id']);
                //写入交易返回
                if ($a['code'] == 0) {
                    $res->trade_status = 2;
                }
                $res->message = json_encode($a, true);
                $res->save();
            }

        }
        return 'start';
    }

    //执行还款消费
    public function planover()
    {
        $plan = PlanDeal::with(['card', 'user', 'PlanDetails'])->where('trade_type', 2)->where('trade_status', 1)->select();

        foreach ($plan as $k => $v) {
            //还款参数
            $arr = ['orderAmount' => $v['trade_amount'], 'bankCardNo' => $v['card']['card_no']];
            if (time() > strtotime($v['trade_time'])) {
                $a = $this->repay($arr, $v['user']);
                $res = PlanDeal::find($v['id']);
                //写入交易返回
                if ($a['code'] == 0) {
                    $res->trade_status = 2;
                    $plan = OrderPlan::find($v['PlanDetails']['plan_id']);
                    $plan->pending_amount = $plan['pending_amount'] - $v['trade_amount'];
                    $plan->save();

                    $p = User::find($v['user']['pid']);
                    $d = $this->profit($p, $v['user']);
                }
                $res->message = json_encode($a, true);
                $res->save();
            }
        }

        return 'over';
    }

    //还款记录
    public function history()
    {
        $user = $this->user(request());
        //获取当前日
        $d = date('d', time());
        $req = request()->param();
        $order = OrderPlan::with(['details' => function (Query $query) {
            $query->with(['deal']);
        }, 'card'])->where('user_id', $user['id'])->where('plan_status', 1)->select()->toArray();

        dump($order);
        die;
    }


//    public function profit($p,$u,$trade_amount)
    public function profit()
    {
//        if ($p['vip_label'] > $u['vip_label']) {
//
//            //v3
//            if($p['vip_label']==4){
//                if($u['vip_label']==2){
//                    $profit_sum=((24-12)+1)/10000*$trade_amount;
//                }
//                if($u['vip_label']==3){
//                    $profit_sum=((24-17)+1)/10000* $trade_amount;
//                }
//            }
//            //v2
//            if($p['vip_label']==3){
//                if($u['vip_label']==2){
//                    $profit_sum=((17-12)+1)/10000* $trade_amount;
//                }
//            }
//        }
        $u = User::find(7);

        $user = explode(',', $u['user_pid']);
        foreach (array_reverse($user) as $k => $v) {
            $up=User::find($v);
            dump($up->toArray());
            if($up['vip_label']==4)

        }


        dump($user);
        die;


        $profit = new Profit();
        $profit->user_id = $v['user']['pid'];
        $profit->up_id = $v['user']['id'];
        $profit->card_id = $v['card']['id'];
        $profit->type = $v['user']['id'];
        $profit->profit = $profit_sum;
        $profit->amount = $v['trade_amount'];
        $profit->tranTime = date('Y-m-d H:i:s', time());
        $profit->describe = $v['user']['name'] . '还款' . $v['trade_amount'] . '分润';
    }


}