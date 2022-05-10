<?php
declare (strict_types=1);

namespace app\home\controller;

use app\admin\model\UserCard;
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
        $card->bank = $req['bank'];
        $card->tel = $req['tel'];
        $card->bill_date = $req['bill_date'];
        $card->repayment_date = $req['repayment_date'];
        $card->cvn2 = $req['cvn2'];
        $card->expiration_date = $req['expiration_date'];
        $card->save();

        return Result::Success($card);
    }

    public function card_list()
    {
        $user = $this->user(request());
        $req = request()->param();
        $card = UserCard::with('user')->where('user_id', $user['id'])
            ->where('card_type', $req['card_type'])
            ->select();
        return Result::Success($card);
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
                    $card->bill_date =$req['bill_date'];
                    $card->repayment_date =$req['repayment_date'];
                    $card->cvn2 = $req['cvn'];
                    $card->bankCode = $bankcode['bank_code'];
                    $card->bindId = $res['bindId'];
                    $card->expiration_date = $req['expire'];
                    $card->idCardNo = $req['idCardNo'];
                    $card->save();

                // 提交事务
                Db::commit();
                return Result::Success($res, $res['message']);

            }else{
                return Result::Error($res, $res['message']);
            }

        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return Result::Error($res, $e->getMessage());
        }


    }


    //短信确认
    public function confirmCard()
    {
        $a = new Yjh();
        $req = request()->param();
        if (!isset($req['smsCode'])) {
            return Result::Error(1000, '请输入验证码');
        }
        $user = $this->user(request());
        $card = UserCard::where('card_no', $req['bankCardNo'])->find();
        $reqData = [
            'bankCardNo' => $req['bankCardNo'],
            'idCardNo' => $card['idCardNo'],
            'bindCardType' => $card['bindCardType'],
            'bindId' => $card['bindId'],
            'smsCode' => $req['smsCode'],
            'subMerchantNo' => $user['subMerchantNo']
        ];
        $res = $a->signSmsConfirm($reqData);
        if ($res['code'] == 0) {
            $card->Signing_status == 2;
            $card->save();
            return Result::Success($res, $res['message']);
        }

        return Result::Error(1000, $res['message']);

    }

    //查询绑卡
    public function queryBindCard()
    {
        $req = request()->param();
        $user = $this->user(request());
        $card = UserCard::where('card_no', $req['bankCardNo'])->find();
        $reqData = [
            'subMerchantNo' => $user['subMerchantNo'],
            'bankCardNo' => $card['card_no'],
            'bindId' => $card['bindId']
        ];
        $a = new Yjh();
        $res = $a->queryBindCard($reqData);
        if ($res['code'] == 0) {
            return Result::Success($res, '当前状态绑卡为'.$res['bindCardStatus']);
        }
    }

    //查询余额
    public function queryBalance()
    {
        $req = request()->param();
        $user = $this->user(request());
        $card = UserCard::where('card_no', $req['bankCardNo'])->find();
        $reqData = [
            "subMerchantNo" =>$user['subMerchantNo'],
            "bindId" => $card['bindId']
        ];
        $a = new Yjh();
        $res = $a->queryBalance($reqData);
        if($res['code']==0){
            return Result::Success($res);
        }
        dump($res);
    }

}
