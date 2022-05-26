<?php
declare (strict_types=1);

namespace app\home\controller;

use app\admin\model\UserCard;
use app\home\help\Dhxe;
use app\home\help\Result;
use app\home\help\Yjh;
use app\HomeController;
use think\facade\Db;

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
        $card->bank_logo = $req['bank_logo'];
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
        $card = UserCard::with('user')->where('user_id', $user['id'])
            ->where('card_type', $req['card_type'])
            ->select();

        foreach ($card as $k => $v) {
            if ($v['channel']) {
                $card[$k]['channel'] = explode(',', $v['channel']);
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


    //进件商户
    public function Merchant_enters()
    {

        $req = request()->param();

        $user = $this->user(request());
        $reqData = [
            'bankCardNo' => $req['bankCardNo'],//储蓄卡卡号
            'bankCardPhoneNo' => $req['bankCardPhoneNo'],//储蓄卡预留手机号
            'bankName' => $req['bankName'],//储蓄卡银行名称
//            'cnaps'=>"310581000210",//储蓄卡联行号
            'fullAddress' => $req['fullAddress'],
            'idCardName' => $req['idCardName'],
            'idCardNo' => $req['idCardNo'],
//            'mcc' => "123",
            'subMerchantName' => $req['idCardName'],
            'subMerchantShortName' => $req['idCardName'],
            'idCardFrontImageUrl' => "",
            'idCardOppositeImageUrl' => "",
            'idCardInHandImageUrl' => "",
            'bankCardFrontImageUrl' => "",
            'bankCardOppositeImageUrl' => ""
        ];
        $a = new Yjh();
        $res = $a->OpenAccount($reqData);
//        dump($res);
        if ($res['code'] == 0) {
            $user->subMerchantNo = $res['subMerchantNo'];
            $user->idCardNo = $req['idCardNo'];
            $user->name = $req['idCardName'];
            $user->save();
            return Result::Success($res, $res['message']);
        } else {
            return Result::Error(1000, $res['message']);
        }

    }

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
            $card->channel = $card['channel'] . 'xt04';
            $card->save();
        }
        return Result::Success($res[1]['content'], $res[1]['resMsg']);
    }

    //代付
    public function payOrderCreate()
    {
        $a = new Dhxe();
        $user = $this->user(request());
        $req = request()->param();
        $card = UserCard::where('id', $req['id'])->find();
        $out_trade_no = 'xf' . date('Ymd') . time() . rand(1, 999999);//订单号，自己生成//订单号，自己生成
        $data = [
            'orderNo' => $out_trade_no,//订单号
            'idCard' => $card['idCardNo'],//身份证号
            'agencyCode' => 'xt04',//通道编码
            'accountNo' => $card['card_no'],//卡号
            'holderName' => $card['card_name'],//持卡人姓名
            'tel' => $card['tel'],//电话
            'orderAmount' => $req['orderAmount'],//代付金额
            'rate' => 0.6,//手续费
            'city' => '上海',//手续费
            'cvn' => $card['cvn2'],//cvn
            'validDate' => $card['expiration_date'],//卡有效期
            'notifyUrl' => 'https://tdnetwork.cn/api/notice/alipay1',
        ];
        $res = $a->payOrderCreate($data);
        dump($res);
        die;
        return Result::Success($res[1]['content'], $res[1]['resMsg']);
    }


    //代还
    public function transferCreate()
    {
        $a = new Dhxe();
        $user = $this->user(request());
        $req = request()->param();
        $card = UserCard::where('id', $req['id'])->find();
        $out_trade_no = 'xf' . date('Ymd') . time() . rand(1, 999999);//订单号，自己生成//订单号，自己生成
        $data = [
            'orderNo' => $out_trade_no,//订单号
            'idCard' => $card['idCardNo'],//身份证号
            'agencyCode' => 'xt04',//通道编码
            'accountNo' => $card['card_no'],//卡号
            'holderName' => $card['card_name'],//持卡人姓名
            'tel' => $card['tel'],//电话
            'orderAmount' => $req['orderAmount'],//代付金额
            'feeAmount' => 1,//手续费
            'notifyUrl' => 'https://tdnetwork.cn/api/notice/alipay1',
        ];
        $res = $a->transferCreate($data);
        dump($res);
        return Result::Success($res[1]['content'], $res[1]['resMsg']);
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
//        dump($res);die;
        return Result::Success($res[1]['content'], $res[1]['resMsg']);
    }


}
