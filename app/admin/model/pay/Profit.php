<?php

namespace app\admin\model\pay;

use app\admin\model\UserCard;
use app\common\model\TimeModel;

class Profit extends TimeModel
{

    protected $name = "pay_profit";

    protected $deleteTime = false;

    public function card()
    {
        return $this->belongsTo(UserCard::class,'card_no','id');
    }
    

}