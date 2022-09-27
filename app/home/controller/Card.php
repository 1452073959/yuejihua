<?php
declare (strict_types=1);

namespace app\home\controller;

use app\admin\model\UserCard;
use app\home\help\Dh2;
use app\home\help\Dh3;
use app\home\help\Dh4;
use app\home\help\Dhxe;
use app\home\help\Result;
use app\home\help\Yjh;
use app\HomeController;
use think\facade\Db;
use think\facade\Request;

class Card extends HomeController
{

    protected $middleware = ['\app\middleware\Check::class'];

    //添加卡
    public function cardadd()
    {
        $user = $this->user(request());
        $req = request()->param();
        $card = new UserCard();
        $card->user_id = $user['id'];
        $card->card_type = $req['card_type'];
        $card->card_no = $req['card_no'];
        $card->bank_logo = htmlspecialchars_decode($req['bank_logo']) ;
        $card->bankCode = $req['bankCode'];
        $card->bank = $req['bank'];
        $card->tel = $req['tel'];
        $card->bill_date = $req['bill_date'];
        $card->repayment_date = $req['repayment_date'];
        $card->idCardNo = $req['idCardNo'];
        $card->card_name = $req['card_name'];
        $card->cvn2 = $req['cvn2'];
        $card->expiration_date = $req['expiration_date'];
        $card->save();

        return Result::Success($card, '成功');
    }

//    卡列表
    public function card_list()
    {
        $user = $this->user(request());
        $req = request()->param();
        $date=date('d',time());
        $card = UserCard::with('user')->where('user_id', $user['id'])
            ->where('card_type', $req['card_type'])
            ->where('card_status', 1)
            ->select();

        foreach ($card as $k => $v) {
            if ($v['channel']) {
                $card[$k]['channel'] = explode(',', $v['channel']);
            }
            if($v['repayment_date']){
                if($v['repayment_date']>$date){
                    $card[$k]['distance']=$v['repayment_date']-$date;
                }else{
                    $date2=date('Y-m-d');
                    $card[$k]['distance']=$v['repayment_date']+date("t",strtotime($date2))-$date;
                }
            }
        }

        if ($card) {
            return Result::Success($card);
        } else {
            return Result::Error('失败', 1000);
        }
    }
        //单个卡信息
    public function card_show()
    {
        $req = request()->param();
        $card = UserCard::with('user')->where('id', $req['id'])->find();
        if ($card['channel']) {
            $card['channel'] = explode(',', $card['channel']);
        }
        if ($card) {
            return Result::Success($card);
        } else {
            return Result::Error('失败', 1000);
        }


    }

    //修改卡信息
    public function card_edit()
    {
        $req = request()->param();
        $res = UserCard::update($req);
        if ($res) {
            return Result::Success($res);
        } else {
            return Result::Error('失败', 1000);
        }
    }
    //解绑,删除
    public function card_del()
    {
        $req = request()->param();
        $res = UserCard::update(['card_status'=>2],['id'=>$req['id']]);
        if ($res) {
            return Result::Success($res);
        } else {
            return Result::Error('失败', 1000);
        }
    }



    //进件商户
    public function Merchant_enters(Request $request)
    {

        $req = request()->param();
        $user = $this->user(request());
        $reqData = [
            'merchantNo' => $req['IdCardNumber'],//商户表示
            'MerchantCnName' => $req['MerchantCnName'],//商户简称 （用户姓名）
            'BankAccountName' => $req['BankAccountName'],//持卡人姓名(用户姓名)
            'cnaps' => '1',//持卡人姓名(用户姓名)
            'BankAccount' => $req['BankAccount'],//	银行账号
            'IdCardNumber' => $req['IdCardNumber'],//身份证号
            'LinkPhone' => $req['LinkPhone'],//身份证号
        ];

        $a = new Dh4();
        $res = $a->Merchant_enters($reqData);
        dump($res);
        if ($res['rescode'] == 0) {
            $user->subMerchantNo = $res['acqMerchantNo'];
            $user->save();
            return Result::Success($res);
        } else {
            return Result::Error(1000, $res['resmsg']);
        }

    }

    //绑卡发送短信
    public function sendbind()
    {

        $req = request()->param();;
        $user = $this->user(request());
        $card = UserCard::where('id', $req['id'])->find();
        $reqData = [
            'merchantNo' => $user['subMerchantNo'],//商户表示
            'type' =>$req['type'],//渠道编号，具体咨询业务对接人
            'bankAccount' => $card['card_no'],//卡号
            'bankPhone' => $card['tel'],//预留手机号码
            'isFace' => 1,//		固定值1
            'bankName' => $card['card_name'],//	持卡人姓名
            'idCard' => $card['idCardNo'],//身份证号
            'cvn' => $card['cvn2'],//	Cvn信用卡背面后三位
            'validity' => $card['expiration_date'],//		有效期
            'dcflag' =>1,//	1贷记卡0借记卡
            'notifyUrl' =>"http://47.114.116.249:1314/home/login/bind",//		回调地址
        ];
        $a = new Dh4();
        $res = $a->sendbind($reqData);
        if ($res['rescode'] == 00) {
            return Result::Success($res);
        } else {
            return Result::Error(1000, $res['resmsg']);
        }


    }
        //绑卡确认
    public function YKBindCardConfirm()
    {
        $req = request()->param();;
        $user = $this->user(request());
        $card = UserCard::where('id', $req['id'])->find();
        $reqData = [
            'merchantNo' => $user['subMerchantNo'],//商户表示
            'type' =>$req['type'],//渠道编号，具体咨询业务对接人
            'bankAccount' => $card['card_no'],//卡号
            'bankPhone' => $card['tel'],//预留手机号码
            'smsCode' => $req['smsCode'],//验证码
        ];
        $a = new Dh4();
        $res = $a->YKBindCardConfirm($reqData);
        if ($res['rescode'] == 00) {
            $card->Signing_status = 2;
            $card->channel = $card['channel'] . ','.$req['type'];
            $card->save();
            return Result::Success($res);
        } else {
            return Result::Error(1000, $res['resmsg']);
        }
    }

    //获取城市
    public function citySelect2()
    {
        $req = request()->param();;
        $user = $this->user(request());
        $reqData = [
            'parentId' => 0,//平台下发用户标识
        ];
        $a = new Dh4();
        $res = $a->citySelect2($reqData);
        return Result::Success($res);
    }



    //交易
    public function pay()
    {
        $req = request()->param();;
        $user = $this->user(request());
        $card = UserCard::where('id', $req['id'])->find();
        
        $reqData = [
            'merchantNo' => $user['subMerchantNo'],//平台下发用户标识
            'payCardId' => '0BF8A7151D8A417B867F0EBD955989AB',//支付卡签约ID
            'notifyUrl' => 'http://47.114.116.249:1314//api/notice/alipay1',//		回调地址
            'orderNo' => $out_trade_no = 'hk' . date('Ymd') . time() . rand(1, 999999),//订单号，自己生成//订单号，自己生成,//		订单流水号
            'storeNo' =>'420000',//获取城市六位地区编码
            'bankAccount' =>$card['card_no'],//卡号
            'payType' => 'YK',//YK代还 WK快捷
            'acqCode' => '8979',//固定值YK必填，WK 快捷请咨询商务
            'orderAmt' => 1000,//交易金额 单位：分
            'rate' => 0.80,//交易费率 格式 如0.60, 万六十
            'pro' => 1,//交易代付费 单笔手续费1 单位元,与代付费保持一致
            'merchType' =>0,//固定值 0
        ];
        $a = new Dh4();

        $res = $a->pay($reqData);
        dump($res);
    }

    //还款
    public function hk()
    {
        $req = request()->param();;
        $user = $this->user(request());
        $card = UserCard::where('id', $req['id'])->find();
        $reqData = [
            'merchantNo' => $user['subMerchantNo'],//平台下发用户标识
            'payCardId' => '0BF8A7151D8A417B867F0EBD955989AB',//支付卡签约ID
            'notifyUrl' => 'http://47.114.116.249:1314//api/notice/alipay1',//		回调地址
            'acqCode' => '8979',//固定值YK必填，WK 快捷请咨询商务
            'platOrderList' => '',//
            'orderNo' => $out_trade_no = 'hk' . date('Ymd') . time() . rand(1, 999999),//订单号，自己生成//订单号，自己生成,//		订单流水号
            'bankAccount' =>$card['card_no'],//卡号
            'orderAmt' => 900,//交易金额 单位：分
            'rate' => 0.5,//交易费率 格式 如0.60, 万六十
            'pro' => 0.5//交易代付费 单笔手续费1 单位元,与代付费保持一致
        ];
        $a = new Dh4();
        $res = $a->hk($reqData);
        dump($res);
    }

    //查询余额
    public function ye()
    {
        $req = request()->param();;
        $user = $this->user(request());
        $card = UserCard::where('id', $req['id'])->find();
        $reqData = [
            'merchantNo' => $user['subMerchantNo'],//平台下发用户标识
            'bankAccount' =>$card['card_no'],//卡号
            'acqCode' => '8979',//固定值YK必填，WK 快捷请咨询商务
        ];
        $a = new Dh4();
        $res = $a->ye($reqData);
        dump($res);
    }

    //华丽的分割线,--以下代码废弃-----------------------------------------------------------------------------------------------
    //绑卡
    public function bindCard()
    {
        $req = request()->param();
        $user = $this->user(request());

// 启动事务
        Db::startTrans();
        try {
            $bankcode = Db::table('yj_bank_code')->where('bank_name', $req['bankName'])->find();
            if (!$bankcode) {
                return Result::Error(1000, '请输入正确的银行名称');
            }

            $reqData = [
                'bankCardNo' => $req['bankCardNo'], //信用卡卡号
                'bankCardPhoneNo' => $req['bankCardPhoneNo'], //信用卡预留手机号
                'bankCode' => $bankcode['bank_code'],//银行编码
                'bankName' => $req['bankName'],//银行名称
                'bankSubName' => $req['bankSubName'],//支行名称
                'bindCardType' => $req['bindCardType'],
                'idCardNo' => $req['idCardNo'],//身份
                'subMerchantNo' => $user['subMerchantNo'],//商户号
                'cvn' => $req['cvn'],
                'expire' => $req['expire']
            ];

//            dump($reqData);die;
            $a = new Yjh();
            $res = $a->bindCard($reqData);

            if ($res['code'] == 0) {
//            查询是否存在
                $card = UserCard::where('card_no', $req['bankCardNo'])->find();
                if (!$card) {
                    $card = new UserCard();
                }
                $card->user_id = $user['id'];
                $card->card_type = 2;
                $card->card_no = $req['bankCardNo'];
                $card->bank_logo = '';
                $card->bank = $req['bankSubName'];
                $card->tel = $req['bankCardPhoneNo'];
                $card->bindCardType = $req['bindCardType'];
                $card->bankCode = $bankcode['bank_code'];
                $card->bindId = $res['bindId'];
                $card->expiration_date = $req['expire'];
                $card->idCardNo = $req['idCardNo'];
                $card->save();

                // 提交事务
                Db::commit();
                return Result::Success($res, $res['message']);
            } else {
                return Result::Error($res, $res['errorMessage']);
            }

        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return Result::Error($res, $e->getMessage());
        }


    }
        //通道1
    public function bindcard2()
    {
        $a = new Dhxe();
        $user = $this->user(request());
        $req = request()->param();
        $out_trade_no = date('Ymd') . time() . rand(1, 999999);//订单号，自己生成//订单号，自己生成
        $card = UserCard::where('id', $req['id'])->find();
        $data = [
            'orderNo' => $out_trade_no,//订单号
            'idCard' => $card['idCardNo'],//身份证号
            'agencyCode' => 'xt04',//通道编码
            'accountNo' => $card['card_no'],//卡号
            'holderName' => $card['card_name'],//持卡人姓名
            'tel' => $card['tel'],//电话
            'cvn' => $card['cvn2'],//cvn
            'validDate' => $card['expiration_date']//卡有效期
        ];


        $res = $a->bindCard($data);

        if ($res[1]['resCode'] == '0000') {
            return Result::Success($res[1]['content'], $res[1]['resMsg']);
        }else{
            return Result::Error(1000, $res[1]['resMsg']);
        }

    }
    //短信通知
    public function bindConfirm2()
    {
        $a = new Dhxe();
        $user = $this->user(request());
        $req = request()->param();
        $card = UserCard::where('id', $req['id'])->find();
        $data = [
            'orderNo' => $req['orderNo'],//订单号
            'idCard' => $card['idCardNo'],//身份证号
            'agencyCode' => 'xt04',//通道编码
            'accountNo' => $card['card_no'],//卡号
            'holderName' => $card['card_name'],//持卡人姓名
            'tel' => $card['tel'],//电话
            'cvn' => $card['cvn2'],//cvn
            'validDate' => $card['expiration_date'],//卡有效期
            'smsCode' => $req['smsCode']//短信验证码
        ];
        $res = $a->bindConfirm($data);
        if ($res[1]['resCode'] == '0000') {
            $card->Signing_status = 2;
            $card->channel = $card['channel'] . ',xt04';
            $card->save();
        }
        return Result::Success($res[1]['content'], $res[1]['resMsg']);
    }
    //通道2

    public function bindConfirm3()
    {
        $a = new Dhxe();
        $user = $this->user(request());
        $req = request()->param();
        $card = UserCard::where('id', $req['id'])->find();
        $data = [
            'idCard' => $card['idCardNo'],//身份证号
            'agencyCode' => 'xt24',//通道编码
            'accountNo' => $card['card_no'],//卡号
            'holderName' => $card['card_name'],//持卡人姓名
            'tel' => $card['tel'],//电话
            'cvn' => $card['cvn2'],//cvn
            'validDate' => $card['expiration_date'],//卡有效期

        ];

            $card->channel = $card['channel'].',xt24';
            $card->save();

        return Result::Success($card, '成功');
    }


    public function bindcard4()
    {
        $a = new Dh2();
        $user = $this->user(request());
        $req = request()->param();
        $out_trade_no = date('Ymd') . time() . rand(1, 999999);//订单号，自己生成//订单号，自己生成
        $card = UserCard::where('id', $req['id'])->find();
        $data = [
            'orderNo' => $out_trade_no,//订单号
            'idCard' => $card['idCardNo'],//身份证号
            'agencyCode' => 'xt31',//通道编码
            'accountNo' => $card['card_no'],//卡号
            'holderName' => $card['card_name'],//持卡人姓名
            'tel' => $card['tel'],//电话
            'cvn' => $card['cvn2'],//cvn
            'validDate' => $card['expiration_date']//卡有效期
        ];

//        dump($data);

        $res = $a->bindCard($data);

        if ($res[1]['resCode'] == '0000') {
            return Result::Success($res[1]['content'], $res[1]['resMsg']);
        }else{
            return Result::Error(1000, $res[1]['resMsg']);
        }
    }

    //通道3
    public function bindConfirm4()
    {
        $a = new Dh2();
        $user = $this->user(request());
        $req = request()->param();
        $card = UserCard::where('id', $req['id'])->find();
        $data = [
            'orderNo' => $req['orderNo'],//订单号
            'idCard' => $card['idCardNo'],//身份证号
            'agencyCode' => 'xt31',//通道编码
            'accountNo' => $card['card_no'],//卡号
            'holderName' => $card['card_name'],//持卡人姓名
            'tel' => $card['tel'],//电话
            'cvn' => $card['cvn2'],//cvn
            'validDate' => $card['expiration_date'],//卡有效期
            'smsCode' => $req['smsCode']//短信验证码
        ];
        $res = $a->bindConfirm($data);
        if ($res[1]['resCode'] == '0000') {
            $card->Signing_status = 2;
            $card->channel = $card['channel'] . ',xt31';
            $card->save();
        }
        return Result::Success($res[1]['content'], $res[1]['resMsg']);
    }



    public function bindcard5()
    {
        $a = new Dh2();
        $user = $this->user(request());
        $req = request()->param();
        $out_trade_no = date('Ymd') . time() . rand(1, 999999);//订单号，自己生成//订单号，自己生成
        $card = UserCard::where('id', $req['id'])->find();
        $data = [
            'orderNo' => $out_trade_no,//订单号
            'idCard' => $card['idCardNo'],//身份证号
            'agencyCode' => 'xt34',//通道编码
            'accountNo' => $card['card_no'],//卡号
            'holderName' => $card['card_name'],//持卡人姓名
            'tel' => $card['tel'],//电话
            'cvn' => $card['cvn2'],//cvn
            'validDate' => $card['expiration_date']//卡有效期
        ];

//        dump($data);

        $res = $a->bindCard($data);

        if ($res[1]['resCode'] == '0000') {
            return Result::Success($res[1]['content'], $res[1]['resMsg']);
        }else{
            return Result::Error(1000, $res[1]['resMsg']);
        }
    }

    //通道3
    public function bindConfirm5()
    {
        $a = new Dh2();
        $user = $this->user(request());
        $req = request()->param();
        $card = UserCard::where('id', $req['id'])->find();
        $data = [
            'orderNo' => $req['orderNo'],//订单号
            'idCard' => $card['idCardNo'],//身份证号
            'agencyCode' => 'xt34',//通道编码
            'accountNo' => $card['card_no'],//卡号
            'holderName' => $card['card_name'],//持卡人姓名
            'tel' => $card['tel'],//电话
            'cvn' => $card['cvn2'],//cvn
            'validDate' => $card['expiration_date'],//卡有效期
            'smsCode' => $req['smsCode']//短信验证码
        ];
        $res = $a->bindConfirm($data);
        if ($res[1]['resCode'] == '0000') {
            $card->Signing_status = 2;
            $card->channel = $card['channel'] . ',xt34';
            $card->save();
        }
        return Result::Success($res[1]['content'], $res[1]['resMsg']);
    }





    //代付
    public function payOrderCreate()
    {
        $a = new Dh2();
        $user = $this->user(request());
        $req = request()->param();
        $card = UserCard::where('id', $req['id'])->find();
        $out_trade_no = 'xf' . date('Ymd') . time() . rand(1, 999999);//订单号，自己生成//订单号，自己生成
        $data = [
            'orderNo' => $out_trade_no,//订单号
            'idCard' => $card['idCardNo'],//身份证号
            'agencyCode' => 'xt34',//通道编码
            'accountNo' => $card['card_no'],//卡号
            'holderName' => $card['card_name'],//持卡人姓名
            'tel' => $card['tel'],//电话
            'orderAmount' => $req['orderAmount'],//代付金额
            'rate' => 0.5,//手续费
            'city' => '上海',//手续费
            'cvn' => $card['cvn2'],//cvn
            'validDate' => $card['expiration_date'],//卡有效期
            'notifyUrl' => 'http://47.114.116.249:1314//api/notice/alipay1',
        ];
        dump($data);
        $res = $a->payOrderCreate($data);
        dump($res);
        die;
        return Result::Success($res[1]['content'], $res[1]['resMsg']);
    }


    //代还
    public function transferCreate()
    {
        $a = new Dh2();
        $user = $this->user(request());
        $req = request()->param();
        $card = UserCard::where('id', $req['id'])->find();
        $out_trade_no = 'hk' . date('Ymd') . time() . rand(1, 999999);//订单号，自己生成//订单号，自己生成
        $data = [
            'orderNo' => $out_trade_no,//订单号
            'idCard' => $card['idCardNo'],//身份证号
            'agencyCode' => 'xt34',//通道编码
            'accountNo' => $card['card_no'],//卡号
            'holderName' => $card['card_name'],//持卡人姓名
            'tel' => $card['tel'],//电话
            'orderAmount' => $req['orderAmount'],//代付金额
            'feeAmount' => 0.5,//手续费
            'notifyUrl' => 'http://47.114.116.249:1314//api/notice/alipay1',
        ];
        $res = $a->transferCreate($data);
        dump($res);
        return Result::Success($res[1]['content'], $res[1]['resMsg']);
    }


    //查询余额
    public function balanceQuery()
    {
        $a = new Dh2();
        $user = $this->user(request());
        $req = request()->param();
        $card = UserCard::where('id', $req['id'])->find();
        $data = [
            'cardNo' => $card['idCardNo'],//订单号
            'agencyCode' => 'xt34',//通道编码
        ];
        $res = $a->balanceQuery($data);
//        dump($res);die;
        return Result::Success($res[1]['content'], $res[1]['resMsg']);
    }

    //查询下单
    public function payOrderQuery()
    {
        $a = new Dhxe();
        $user = $this->user(request());
        $req = request()->param();
//        $card = UserCard::where('id', $req['id'])->find();
        $data = [
            'orderNo' => $req['orderNo'],//订单号
        ];
        $res = $a->payOrderQuery($data);
        dump($res);die;
        return Result::Success($res[1]['content'], $res[1]['resMsg']);
    }


}
