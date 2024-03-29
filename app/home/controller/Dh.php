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
use app\admin\model\plan\PlanDetails;
use app\admin\model\UserCard;
use app\home\help\Dh2;
use app\home\help\Dh4;
use app\home\help\Result;
use app\home\help\Dhxe;
use app\HomeController;
use think\db\Query;
use think\facade\Db;
use think\facade\Log;

class Dh extends HomeController
{
    public function pay($req)
    {
        $card = UserCard::where('id', $req['id'])->find();
        $reqData = [
            'merchantNo' => $req['merchantNo'],//平台下发用户标识
            'payCardId' =>$card['payCardId'],//支付卡签约ID
            'notifyUrl' => 'http://47.114.116.249:1314/home/login/hknotice',//		回调地址
            'orderNo' => $out_trade_no = 'xf' . date('Ymd') . time() . rand(1, 999999),//订单号，自己生成//订单号，自己生成,//		订单流水号
            'storeNo' =>$req['city_code'],//获取城市六位地区编码
            'bankAccount' =>$card['card_no'],//卡号
            'payType' => 'YK',//YK代还 WK快捷
            'acqCode' => $req['channel'],//固定值YK必填，WK 快捷请咨询商务
            'orderAmt' => $req['orderAmount']*100,//交易金额 单位：分
            'rate' => 0.80,//交易费率 格式 如0.60, 万六十
            'pro' => 1,//交易代付费 单笔手续费1 单位元,与代付费保持一致
            'merchType' =>0,//固定值 0
        ];
        $a = new Dh4();
        $res = $a->pay($reqData);
        return ['no'=>$out_trade_no,'res'=>$res];
    }


    //还款
    public function hk($req)
    {
        $card = UserCard::where('id', $req['id'])->find();
        $reqData = [
            'merchantNo' => $req['merchantNo'],//平台下发用户标识
            'payCardId' =>$card['payCardId'],//支付卡签约ID
            'notifyUrl' => 'http://47.114.116.249:1314/home/login/hknotice',//		回调地址
            'acqCode' => $req['channel'],//固定值YK必填，WK 快捷请咨询商务
            'platOrderList' => '',//
            'orderNo' => $out_trade_no = 'hk' . date('Ymd') . time() . rand(1, 999999),//订单号，自己生成//订单号，自己生成,//		订单流水号
            'bankAccount' =>$card['card_no'],//卡号
            'orderAmt' =>$req['orderAmount']*100,//交易金额 单位：分
            'rate' => 0.5,//交易费率 格式 如0.60, 万六十
            'pro' => 0.5//交易代付费 单笔手续费1 单位元,与代付费保持一致
        ];
        $a = new Dh4();
        $res = $a->hk($reqData);
        return ['no'=>$out_trade_no,'res'=>$res];
    }








    //分割,以下失效-------------------------------------------------------------------























    //代付
    public function payOrderCreate($req)
    {
        if($req['channel']=='xt04'){
            $a = new Dhxe();
        }
        if($req['channel']=='xt24'||$req['channel']=='xt31'||$req['channel']=='xt34'){
            $a = new Dh2();
        }
        $user = $this->user(request());
//        $req = request()->param();
        $card = UserCard::where('id', $req['id'])->find();
        $out_trade_no = 'xf' . date('Ymd') . time() . rand(1, 999999);//订单号，自己生成//订单号，自己生成
        $data = [
            'orderNo' => $out_trade_no,//订单号
            'idCard' => $card['idCardNo'],//身份证号
            'agencyCode' =>$req['channel'],//通道编码
            'accountNo' => $card['card_no'],//卡号
            'holderName' => $card['card_name'],//持卡人姓名
            'tel' => $card['tel'],//电话
            'orderAmount' => $req['orderAmount'],//代付金额
            'rate' => 0.8,//手续费
            'city' => $req['city'],//手续费
            'cvn' => $card['cvn2'],//cvn
            'validDate' => $card['expiration_date'],//卡有效期
            'notifyUrl' => 'https://tdnetwork.cn/api/notice/alipay1',
        ];
        $res = $a->payOrderCreate($data);
        return ['no'=>$out_trade_no,'res'=>$res];
    }


    //代还
    public function transferCreate($req)
    {

        if($req['channel']=='xt04'){
            $a = new Dhxe();
        }
        if($req['channel']=='xt24'||$req['channel']=='xt31'||$req['channel']=='xt34'){
            $a = new Dh2();
        }
        $user = $this->user(request());
//        $req = request()->param();
        $card = UserCard::where('id', $req['id'])->find();
        $out_trade_no = 'hk' . date('Ymd') . time() . rand(1, 999999);//订单号，自己生成//订单号，自己生成
        $data = [
            'orderNo' => $out_trade_no,//订单号
            'idCard' => $card['idCardNo'],//身份证号
            'agencyCode' =>$req['channel'],//通道编码
            'accountNo' => $card['card_no'],//卡号
            'holderName' => $card['card_name'],//持卡人姓名
            'tel' => $card['tel'],//电话
            'orderAmount' => $req['orderAmount'],//代付金额
            'feeAmount' => 1,//手续费
            'notifyUrl' => 'https://tdnetwork.cn/api/notice/alipay1',
        ];
        $res = $a->transferCreate($data);
        return ['no'=>$out_trade_no,'res'=>$res];
    }


    //查询余额
    public function balanceQuery()
    {
        $a = new Dhxe();
        $user = $this->user(request());
        $req = request()->param();
        $card = UserCard::where('id', $req['id'])->find();
        $data = [
            'cardNo' => $card['idCardNo'],//订单号
            'agencyCode' => 'xt04',//通道编码
        ];
        $res = $a->balanceQuery($data);
//        dump($res);die;
        return Result::Success($res[1]['content'], $res[1]['resMsg']);
    }

    //查询订单/
    public function queryxt24($orderNo)
    {
        $a = new Dh2();
        $query = ['orderNo' => $orderNo['no']];
        if ($orderNo['trade_type'] == 1) {
            $res = $a->payOrderQuery($query);
        } else {
            $res = $a->transferQuery($query);
        }
        return $res;
    }

    public function query04($orderNo)
    {
        $a = new Dhxe();
        $query = ['orderNo' => $orderNo['no']];
        if ($orderNo['trade_type'] == 1) {
            $res = $a->payOrderQuery($query);
        } else {
            $res = $a->transferQuery($query);
        }
        return $res;
    }

    public function cx()
    {
        $plan = PlanDeal::where('trade_status', 2)->select();
        foreach ($plan as $k => $v) {
            if($v['channel']=='xt04'){
                $res = $this->query04($v);
            }
            if($v['channel']=='xt24'||$v['channel']=='xt31'||$v['channel']=='xt34'){
                $res = $this->queryxt24($v);
            }

            dump($res);
            if ($res[1]['content']['orderStatus'] == 's') {
                $v->message = $res[1]['content']['desc'];
                $v->trade_status = 3;
                Log::write('查询订单' . $v['no'] . $res[1]['content']['desc']);
            } else {
                $v->trade_status = 3;
                $v->message = $res[1]['content']['desc'];
                Log::write('查询订单' . $v['no'] . $res[1]['resMsg']);
            }
            $v->save();
        }
        return 'ok';
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
                'city' => $req['city'],//城市
                'city_code' => $req['city_code'],//城市编号
                'bill_amount' => $req['bill_amount'],//账单金额
                'card_balance' => $req['card_balance'],//卡余额
                'pending_amount' => $req['bill_amount'],//代还金额
                'repayment_mode' => $req['repayment_mode'],//模式
                'create_time' => date('Y-m-d H:i:s', time()),//创建时间
                'plan_number' => $req['plan_number'],//还款次数
                'plan_no' => 'jihua' . date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT)//单号
            ];
//            dump($orderplan);die;
            $orderplanId = Db::name('order_plan')->insertGetId($orderplan);


            $req['repayment_date'] = explode('@', $req['repayment_date']);

//            时间
            $date = date('H', time());
            $plan_deal_h = [];
            $plan_deal_x = [];
//            dump($req['repayment_date']);die;
            $money=  randomDivInt($req['plan_number'],$req['bill_amount']);
            for ($i = 0; $i <= $req['plan_number'] - 1; $i++) {
                $plan_details = [
                    'plan_id' => $orderplanId,
                    'plan_name' => 1 + $i . '笔',
                    'card_id' => $req['card_id'],//卡
                ];

                $plan_detailsid = Db::name('plan_details')->insertGetId($plan_details);
                if ($req['repayment_mode'] == 1) {
                    //消费
//                    $money = ceil($req['bill_amount'] / $req['plan_number'] / 0.992) + 1;


//                    dump($money);
                    //日期
                    //最后一个日期
                    $next = isset($req['repayment_date'][$i + 1]) ? $req['repayment_date'][$i + 1] : $req['repayment_date'][$i];
                    if ($req['repayment_date'][$i] == $next) {

                        $plan_deal_x = [
                            'trade_amount' => ceil($money[$i]/0.992)+1,
                            'trade_time' => isset($plan_deal_h['trade_time']) ? date('Y-m-d H:i:s', strtotime($plan_deal_h['trade_time']) + 7200) : date('Y-m-d H:i:s', strtotime($req['repayment_date'][$i]) + 25200 + ((1 + $i) * 900)),
                            'actual_amount' => '0',
                            'trade_fee' => ($money[$i]/0.992)* 0.008 ,
                            'trade_type' => 1,
                            'city' => $req['city'],
                            'city_code' => $req['city_code'],//城市编号
                            'card_id' => $req['card_id'],//卡
                            'plan_details_id' => $plan_detailsid,
                            'user_id' => $user['id']
                        ];
                        Db::name('plan_deal')->save($plan_deal_x);
                        //还款
                        $plan_deal_h = [
                            'trade_amount' =>  $money[$i],
                            'trade_time' => date('Y-m-d H:i:s', strtotime($plan_deal_x['trade_time']) + 7200),
                            'actual_amount' =>  $money[$i]-1,
                            'trade_fee' => 1.00,
                            'trade_type' => 2,
                            'card_id' => $req['card_id'],//卡
                            'plan_details_id' => $plan_detailsid,
                            'user_id' => $user['id']
                        ];
                        Db::name('plan_deal')->save($plan_deal_h);
                    } else {
                        $plan_deal_x = [
                            'trade_amount' => ceil($money[$i]/0.992)+1,
                            'trade_time' => isset($plan_deal_h['trade_time']) ? date('Y-m-d H:i:s', strtotime($plan_deal_h['trade_time']) + 7200) : date('Y-m-d H:i:s', strtotime($req['repayment_date'][$i]) + 25200 + 900),
                            'actual_amount' => '0',
                            'trade_fee' => ($money[$i]/0.992)* 0.008,
                            'trade_type' => 1,
                            'city' => $req['city'],
                            'city_code' => $req['city_code'],//城市编号
                            'card_id' => $req['card_id'],//卡
                            'plan_details_id' => $plan_detailsid,
                            'user_id' => $user['id']
                        ];
                        Db::name('plan_deal')->save($plan_deal_x);
                        //还款
                        $plan_deal_h = [
                            'trade_amount' =>  $money[$i],
                            'trade_time' => date('Y-m-d H:i:s', strtotime($plan_deal_x['trade_time']) + 7200),
                            'actual_amount' => $money[$i]-1,
                            'trade_fee' => 1.00,
                            'trade_type' => 2,
                            'card_id' => $req['card_id'],//卡
                            'plan_details_id' => $plan_detailsid,
                            'user_id' => $user['id']
                        ];
                        Db::name('plan_deal')->save($plan_deal_h);
                        $plan_deal_h = [];//重新开始计算
                    }

                } elseif ($req['repayment_mode'] == 2) {

                    //消费
                    $next = isset($req['repayment_date'][$i + 1]) ? $req['repayment_date'][$i + 1] : $req['repayment_date'][$i];
                    if ($req['repayment_date'][$i] == $next) {
                        $plan_deal_x1 = [
                            'trade_amount' =>  ceil($money[$i]/2/0.992)+1,
                            'trade_time' => isset($plan_deal_h['trade_time']) ? date('Y-m-d H:i:s', strtotime($plan_deal_h['trade_time']) + 7200) : date('Y-m-d H:i:s', strtotime($req['repayment_date'][$i]) + 25200 + ((1 + $i) * 900)),
                            'actual_amount' => '0',
                            'trade_fee' =>  ($money[$i]/2/0.992) * 0.008,
                            'trade_type' => 1,
                            'city' => $req['city'],
                            'city_code' => $req['city_code'],//城市编号
                            'card_id' => $req['card_id'],//卡
                            'plan_details_id' => $plan_detailsid,
                            'user_id' => $user['id']
                        ];
                        Db::name('plan_deal')->save($plan_deal_x1);
                        $plan_deal_x2 = [
                            'trade_amount' => ceil($money[$i]/2/0.992),
                            'trade_time' => date('Y-m-d H:i:s', strtotime($plan_deal_x1['trade_time']) + 7200),
                            'actual_amount' => '0',
                            'trade_fee' =>  ($money[$i]/2/0.992) * 0.008,
                            'trade_type' => 1,
                            'city' => $req['city'],
                            'city_code' => $req['city_code'],//城市编号
                            'card_id' => $req['card_id'],//卡
                            'plan_details_id' => $plan_detailsid,
                            'user_id' => $user['id']
                        ];
                        Db::name('plan_deal')->save($plan_deal_x2);

                        //还款
                        $plan_deal_h = [
                            'trade_amount' => $money[$i],
                            'trade_time' => date('Y-m-d H:i:s', strtotime($plan_deal_x2['trade_time']) + 7200),
                            'actual_amount' =>  $money[$i]-1,
                            'trade_fee' => 1.00,
                            'trade_type' => 2,
                            'card_id' => $req['card_id'],//卡
                            'plan_details_id' => $plan_detailsid,
                            'user_id' => $user['id']
                        ];

                        Db::name('plan_deal')->save($plan_deal_h);
                    } else {
                        $plan_deal_x1 = [
                            'trade_amount' =>  ceil($money[$i]/2/0.992)+1,
                            'trade_time' => isset($plan_deal_h['trade_time']) ? date('Y-m-d H:i:s', strtotime($plan_deal_h['trade_time']) + 7200) : date('Y-m-d H:i:s', strtotime($req['repayment_date'][$i]) + 25200 + 900),
                            'actual_amount' => '0',
                            'trade_fee' =>  ($money[$i]/2/0.992) * 0.008,
                            'trade_type' => 1,
                            'city' => $req['city'],
                            'city_code' => $req['city_code'],//城市编号
                            'card_id' => $req['card_id'],//卡
                            'plan_details_id' => $plan_detailsid,
                            'user_id' => $user['id']
                        ];
                        Db::name('plan_deal')->save($plan_deal_x1);
                        $plan_deal_x2 = [
                            'trade_amount' =>  ceil($money[$i]/2/0.992),
                            'trade_time' => date('Y-m-d H:i:s', strtotime($plan_deal_x1['trade_time']) + 7200),
                            'actual_amount' => '0',
                            'city' => $req['city'],
                            'city_code' => $req['city_code'],//城市编号
                            'trade_fee' => ($money[$i]/2/0.992) * 0.008,
                            'trade_type' => 1,
                            'card_id' => $req['card_id'],//卡
                            'plan_details_id' => $plan_detailsid,
                            'user_id' => $user['id']
                        ];

                        Db::name('plan_deal')->save($plan_deal_x2);
                        //还款
                        $plan_deal_h = [
                            'trade_amount' => $money[$i],
                            'trade_time' => date('Y-m-d H:i:s', strtotime($plan_deal_x2['trade_time']) + 7200),
                            'actual_amount' =>  $money[$i]-1,
                            'trade_fee' => 1.00,
                            'trade_type' => 2,
                            'card_id' => $req['card_id'],//卡
                            'plan_details_id' => $plan_detailsid,
                            'user_id' => $user['id']
                        ];
                        Db::name('plan_deal')->save($plan_deal_h);
                        $plan_deal_h = [];//重新开始计算
                    }

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
        $req = request()->param();
        $order = OrderPlan::with(['details' => function (Query $query) {
            $query->with(['deal']);
        }, 'card'])->where('user_id', $user['id'])->where('plan_status', 1)->order('id', 'desc')->find();

        foreach ($order['details'] as $k => $v) {
            foreach ($v['deal'] as $k1 => $v1) {
                if ($v1['trade_type'] == 1) {
                    $order['total'] += $v1['trade_amount'];
                }
            }
        }
        $order['total'] = round($order['total'], 2);
        return Result::Success($order, '成功');
    }

    //确认极化
    public function confirmplan()
    {
        $req = request()->param();
        // 启动事务
        Db::startTrans();
        try {
            $order = OrderPlan::find($req['id']);
            $order->plan_status = 3;
            $order->channel =$req['channel'];
            $order->save();
            $plandeta = PlanDetails::where('plan_id', $order['id'])->select()->toArray();
            $plandeta = array_column($plandeta, 'id');
            $plandeal = PlanDeal::where('plan_details_id', 'in', $plandeta)->select();
            foreach ($plandeal as $k => $v) {
                $v->trade_status = 1;
                $v->channel = $req['channel'];
                $v->save();
            }
            // 提交事务
            Db::commit();
            return Result::Success($order, '成功');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $e->getMessage();
        }

    }

    //取消极化
    public function Cancelplan()
    {
        $req = request()->param();
        // 启动事务
        Db::startTrans();
        try {
            $order = OrderPlan::find($req['id']);
            $order->plan_status = 2;
            $order->save();
            $plandeta = PlanDetails::where('plan_id', $order['id'])->select()->toArray();
            $plandeta = array_column($plandeta, 'id');
            $plandeal = PlanDeal::where('plan_details_id', 'in', $plandeta)->select();
            foreach ($plandeal as $k => $v) {
                if ($v['trade_status'] != 2 || $v['trade_status'] != 3) {
                    $v->trade_status = 4;
                    $v->save();
                }
            }
            // 提交事务
            Db::commit();
            return Result::Success($order, '成功');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $e->getMessage();
        }
    }


    //执行计划消费
    public function planstart()
    {
        $plan = PlanDeal::with(['card', 'user', 'PlanDetails'])->where('trade_type', 1)->where('trade_status', 1)->select();

        foreach ($plan as $k => $v) {
            //   dump($v->toArray());
            // 启动事务
            Db::startTrans();
            try {
                //消费参数
                $arr = ['orderAmount' => $v['trade_amount'], 'id' => $v['card']['id'], 'channel' => $v['channel'],'merchantNo'=>$v['user']['subMerchantNo'],'city_code'=>$v['city_code']];

                if (time() > strtotime($v['trade_time'])) {
                    $a = $this->pay($arr);
                    dump($a);
                    $res = PlanDeal::find($v['id']);
                    //写入交易返回
                    if ($a['res']['rescode'] == '00') {
                        $res->trade_status = 2;
                        $res->message = $a['res']['resmsg'];
                        $res->no = $a['no'];
                        Log::write('消费订单成功' . $v['id'] .$a['res']['resmsg'] . $a['no']);
                    } else {
                        $res->message = $a['res']['resmsg'];
                        $res->trade_status = 3;
                        Log::write('消费订单失败' . $v['id'] .$a['res']['resmsg'] . $a['no']);
                    }
                    $res->save();
                    dump($res->toArray());
                }
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                Log::write('消费订单' . $v['id'] . $e->getMessage());
                return $e->getMessage();
            }

        }

        return 'start';
    }

    //执行还款消费
    public function planover()
    {
        $plan = PlanDeal::with(['card', 'user', 'PlanDetails'])->where('trade_type', 2)->where('trade_status', 1)->select();

        foreach ($plan as $k => $v) {
            // 启动事务
            Db::startTrans();
            try {
                //还款参数
                $arr = ['orderAmount' => $v['trade_amount'], 'id' => $v['card']['id'], 'channel' => $v['channel'],'merchantNo'=>$v['user']['subMerchantNo']];

                if (time() > strtotime($v['trade_time'])) {
                    $a = $this->hk($arr);
                    $res = PlanDeal::find($v['id']);

                    //写入交易返回
                    if ($a['res']['rescode'] == '00') {
                        $res->trade_status = 2;
                        $res->message = $a['res']['resmsg'];
                        $res->no = $a['no'];
                        Log::write('还款订单成功'. $v['id'] .$a['res']['resmsg'] . $a['no']);
                        //计划剩余还款金额
                        $plan = OrderPlan::find($v['PlanDetails']['plan_id']);
                        $plan->pending_amount = $plan['pending_amount'] - $v['trade_amount'];
                        $plan->plan_status = 3;
                        $plan->save();
                        if ($plan['pending_amount'] <= 0) {
                            $plan->plan_status = 4;
                            $plan->pending_amount = 0;
                            $plan->save();
                        }
                        //分润
                        $d = $this->profit($v['user']['id'], $arr, $v['id']);

                    } else {
                        $res->trade_status = 3;
                        $res->message = $a['res']['resmsg'];
                        Log::write('还款订单失败' .$v['id'] .$a['res']['resmsg'] . $a['no']);
                    }
                    $res->save();
                    dump($res->toArray());
                    dump($a);
                }
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                Log::write('还款订单' . $v['id'] . $e->getMessage());
                return $e->getMessage();
            }

        }

        return 'over';
    }

    //还款记录
    public function history()
    {
        $user = $this->user(request());
        $req = request()->param();
        $where = [];
        if (isset($req['id'])) {
            $where = [
                ['card_id', '=', $req['id']]
            ];
        };
        $order = OrderPlan::with(['details', 'card'])
            ->where('user_id', $user['id'])
            ->where($where)
            ->where('plan_status', 'in', '2,3,4')
            ->order('id', 'desc')
            ->paginate(10);

        return Result::Success($order, '成功');

    }

    //还款记录详情
    public function history_show()
    {
        $user = $this->user(request());
        //获取当前日
        $d = date('d', time());
        $req = request()->param();
        $order = OrderPlan::with(['details' => function (Query $query) {
            $query->with(['deal']);
        }, 'card'])->where('user_id', $user['id'])->where('id', $req['id'])->find();

        return Result::Success($order, '成功');
    }


    //计算预留额度
    public function Quota()
    {
        $req = request()->param();
        $money = ceil($req['bill_amount'] / $req['plan_number'] / 0.992) + 1 * $req['plan_number'];
        return Result::Success($money, '成功');
    }

    //

    //分润
    //本级id,分润,计划
    protected function profit($u, $arr, $plandeal_id)
    {
        $u = User::find($u);

        $a = explode(',', $u['user_pid']);
        $a = array_slice($a, 0, 6);
        $apid = array_reverse($a);
        $statusv3 = true;
        $statusv2 = true;


        //如果本级是v1
        if ($u['vip_label'] == 2) {
            //当前12+1
            $profit = [
                'user_id' => $u['id'],//当前用户
                'rate' => 12 + 1,//费率
                'plan_deal_id' => $plandeal_id,//计划id
                'card_no' => $arr['id'],
                'type' => 1,
                'createtime' => time(),
                'amount' => $arr['orderAmount'],
                'profit' => $arr['orderAmount'] * (13 / 10000),
                'tranTime' => date('Y-m-d H:i:s', time()),
                'describe' => $u['name'] . '还款' . $arr['orderAmount'] . '元',
            ];
            Profit::create($profit);
            $userprofit_balance = User::where('id', $profit['user_id'])->find();
            $userprofit_balance->profit_balance = $userprofit_balance['profit_balance'] + $profit['profit'];
            $userprofit_balance->save();
            foreach ($apid as $k => $v) {

                $n = User::find($v);
                //v3
                if ($n['vip_label'] == 4) {

                    dump($statusv2);
                    dump($statusv3);
                    if ($statusv2 && $statusv3) {
                        //当前第一次出现v3
                        $profit = [
                            'user_id' => $n['id'],
                            'rate' => (24 - 12) + 1,
                            'plan_deal_id' => $plandeal_id,//计划id
                            'card_no' => $arr['id'],
                            'type' => 1,
                            'createtime' => time(),
                            'amount' => $arr['orderAmount'],
                            'profit' => $arr['orderAmount'] * (13 / 10000),
                            'tranTime' => date('Y-m-d H:i:s', time()),
                            'describe' => $u['name'] . '还款' . $arr['orderAmount'] . '元',
                        ];
                        Profit::create($profit);
                        $userprofit_balance = User::where('id', $profit['user_id'])->find();
                        $userprofit_balance->profit_balance = $userprofit_balance['profit_balance'] + $profit['profit'];
                        $userprofit_balance->save();
                        //出现v3后更改状态
                        $statusv3 = false;
                    } elseif ($statusv2 == false && $statusv3) {
                        $profit = [
                            'user_id' => $n['id'],
                            'rate' => (24 - 17) + 1,
                            'plan_deal_id' => $plandeal_id,//计划id
                            'card_no' => $arr['id'],
                            'type' => 1,
                            'createtime' => time(),
                            'amount' => $arr['orderAmount'],
                            'profit' => $arr['orderAmount'] * (8 / 10000),
                            'tranTime' => date('Y-m-d H:i:s', time()),
                            'describe' => $u['name'] . '还款' . $arr['orderAmount'] . '元',
                        ];
                        dump($profit);
                        $statusv3 = false;
                        Profit::create($profit);
                        $userprofit_balance = User::where('id', $profit['user_id'])->find();
                        $userprofit_balance->profit_balance = $userprofit_balance['profit_balance'] + $profit['profit'];
                        $userprofit_balance->save();
                    } else {
                        $profit = [
                            'user_id' => $n['id'],
                            'rate' => 1,
                            'plan_deal_id' => $plandeal_id,//计划id
                            'card_no' => $arr['id'],
                            'type' => 1,
                            'createtime' => time(),
                            'amount' => $arr['orderAmount'],
                            'profit' => $arr['orderAmount'] * (1 / 10000),
                            'tranTime' => date('Y-m-d H:i:s', time()),
                            'describe' => $u['name'] . '还款' . $arr['orderAmount'] . '元',
                        ];
                        Profit::create($profit);
                        $userprofit_balance = User::where('id', $profit['user_id'])->find();
                        $userprofit_balance->profit_balance = $userprofit_balance['profit_balance'] + $profit['profit'];
                        $userprofit_balance->save();
                    }
                }
                //v2
                if ($n['vip_label'] == 3) {
                    //v2第一次出现并且v3没出现过
                    if ($statusv2 && $statusv3) {
                        //当前第一次出现v2
                        $profit = [
                            'user_id' => $n['id'],
                            'rate' => (17 - 12) + 1,
                            'plan_deal_id' => $plandeal_id,//计划id
                            'card_no' => $arr['id'],
                            'type' => 1,
                            'createtime' => time(),
                            'amount' => $arr['orderAmount'],
                            'profit' => $arr['orderAmount'] * (6 / 10000),
                            'tranTime' => date('Y-m-d H:i:s', time()),
                            'describe' => $u['name'] . '还款' . $arr['orderAmount'] . '元',
                        ];
                        Profit::create($profit);
                        $userprofit_balance = User::where('id', $profit['user_id'])->find();
                        $userprofit_balance->profit_balance = $userprofit_balance['profit_balance'] + $profit['profit'];
                        $userprofit_balance->save();
                        //  出现v2后更改v2状态
                        $statusv2 = false;
                    } else {
                        $profit = [
                            'user_id' => $n['id'],
                            'rate' => 1,
                            'plan_deal_id' => $plandeal_id,//计划id
                            'card_no' => $arr['id'],
                            'type' => 1,
                            'createtime' => time(),
                            'amount' => $arr['orderAmount'],
                            'profit' => $arr['orderAmount'] * (1 / 10000),
                            'tranTime' => date('Y-m-d H:i:s', time()),
                            'describe' => $u['name'] . '还款' . $arr['orderAmount'] . '元',
                        ];
                        Profit::create($profit);
                        $userprofit_balance = User::where('id', $profit['user_id'])->find();
                        $userprofit_balance->profit_balance = $userprofit_balance['profit_balance'] + $profit['profit'];
                        $userprofit_balance->save();
                    }
                }
                //v1全部是1
                if ($n['vip_label'] == 2) {
                    $profit = [
                        'user_id' => $n['id'],
                        'rate' => 1,
                        'plan_deal_id' => $plandeal_id,//计划id
                        'card_no' => $arr['id'],
                        'type' => 1,
                        'createtime' => time(),
                        'amount' => $arr['orderAmount'],
                        'profit' => $arr['orderAmount'] * (1 / 10000),
                        'tranTime' => date('Y-m-d H:i:s', time()),
                        'describe' => $u['name'] . '还款' . $arr['orderAmount'] . '元',
                    ];
                    Profit::create($profit);
                    $userprofit_balance = User::where('id', $profit['user_id'])->find();
                    $userprofit_balance->profit_balance = $userprofit_balance['profit_balance'] + $profit['profit'];
                    $userprofit_balance->save();
                }
            }

            //第一次
        }

        //本级v2
        if ($u['vip_label'] == 3) {
            //本级
            $profit = [
                'user_id' => $u['id'],
                'rate' => 17 + 1,
                'plan_deal_id' => $plandeal_id,//计划id
                'card_no' => $arr['id'],
                'type' => 1,
                'createtime' => time(),
                'amount' => $arr['orderAmount'],
                'profit' => $arr['orderAmount'] * (18 / 10000),
                'tranTime' => date('Y-m-d H:i:s', time()),
                'describe' => $u['name'] . '还款' . $arr['orderAmount'] . '元',
            ];
            Profit::create($profit);
            $userprofit_balance = User::where('id', $profit['user_id'])->find();
            $userprofit_balance->profit_balance = $userprofit_balance['profit_balance'] + $profit['profit'];
            $userprofit_balance->save();

            foreach ($apid as $k => $v) {
                $n = User::find($v);
                //定义状态

                if ($n['vip_label'] == 4) {
                    if ($statusv3) {
                        $profit = [
                            'user_id' => $n['id'],
                            'rate' => (24 - 17) + 1,
                            'plan_deal_id' => $plandeal_id,//计划id
                            'card_no' => $arr['id'],
                            'type' => 1,
                            'createtime' => time(),
                            'amount' => $arr['orderAmount'],
                            'profit' => $arr['orderAmount'] * (8 / 10000),
                            'tranTime' => date('Y-m-d H:i:s', time()),
                            'describe' => $u['name'] . '还款' . $arr['orderAmount'] . '元',

                        ];

                        Profit::create($profit);
                        $userprofit_balance = User::where('id', $profit['user_id'])->find();
                        $userprofit_balance->profit_balance = $userprofit_balance['profit_balance'] + $profit['profit'];
                        $userprofit_balance->save();
                        $statusv3 = false;
                    } else {
                        $profit = [
                            'user_id' => $n['id'],
                            'rate' => 1,
                            'plan_deal_id' => $plandeal_id,//计划id
                            'card_no' => $arr['id'],
                            'type' => 1,
                            'createtime' => time(),
                            'amount' => $arr['orderAmount'],
                            'profit' => $arr['orderAmount'] * (1 / 10000),
                            'tranTime' => date('Y-m-d H:i:s', time()),
                            'describe' => $u['name'] . '还款' . $arr['orderAmount'] . '元',
                        ];
                        Profit::create($profit);
                        $userprofit_balance = User::where('id', $profit['user_id'])->find();
                        $userprofit_balance->profit_balance = $userprofit_balance['profit_balance'] + $profit['profit'];
                        $userprofit_balance->save();
                    }
                }
                if ($n['vip_label'] == 3 || $n['vip_label'] == 2) {
                    $profit = [
                        'user_id' => $n['id'],
                        'rate' => 1,
                        'plan_deal_id' => $plandeal_id,//计划id
                        'card_no' => $arr['id'],
                        'type' => 1,
                        'createtime' => time(),
                        'amount' => $arr['orderAmount'],
                        'profit' => $arr['orderAmount'] * (1 / 10000),
                        'tranTime' => date('Y-m-d H:i:s', time()),
                        'describe' => $u['name'] . '还款' . $arr['orderAmount'] . '元',
                    ];

                    Profit::create($profit);
                    $userprofit_balance = User::where('id', $profit['user_id'])->find();
                    $userprofit_balance->profit_balance = $userprofit_balance['profit_balance'] + $profit['profit'];
                    $userprofit_balance->save();
                }
            }
        }

        //本级v3
        if ($u['vip_label'] == 4) {
            //本级
            $profit = [
                'user_id' => $u['id'],
                'rate' => 24 + 1,
                'plan_deal_id' => $plandeal_id,//计划id
                'card_no' => $arr['id'],
                'type' => 1,
                'createtime' => time(),
                'amount' => $arr['orderAmount'],
                'profit' => $arr['orderAmount'] * (25 / 10000),
                'tranTime' => date('Y-m-d H:i:s', time()),
                'describe' => $u['name'] . '还款' . $arr['orderAmount'] . '元',
            ];
            Profit::create($profit);
            $userprofit_balance = User::where('id', $profit['user_id'])->find();
            $userprofit_balance->profit_balance = $userprofit_balance['profit_balance'] + $profit['profit'];
            $userprofit_balance->save();

            foreach ($apid as $k => $v) {
                $n = User::find($v);
                $profit = [
                    'user_id' => $n['id'],
                    'rate' => 1,
                    'plan_deal_id' => $plandeal_id,//计划id
                    'card_no' => $arr['id'],
                    'type' => 1,
                    'createtime' => time(),
                    'amount' => $arr['orderAmount'],
                    'profit' => $arr['orderAmount'] * (1 / 10000),
                    'tranTime' => date('Y-m-d H:i:s', time()),
                    'describe' => $u['name'] . '还款' . $arr['orderAmount'] . '元',

                ];
                dump($profit);
                Profit::create($profit);
                $userprofit_balance = User::where('id', $profit['user_id'])->find();
                $userprofit_balance->profit_balance = $userprofit_balance['profit_balance'] + $profit['profit'];
                $userprofit_balance->save();
            }

        }

        return 'ok';
    }
    //定时删除订单
    public function delplan()
    {
        OrderPlan::where('plan_status', 1)->delete();
    }

}
