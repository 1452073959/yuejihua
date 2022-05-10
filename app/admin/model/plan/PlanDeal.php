<?php

namespace app\admin\model\plan;

use app\admin\model\UserCard;
use app\common\model\TimeModel;

class PlanDeal extends TimeModel
{

    protected $name = "plan_deal";

    protected $deleteTime = false;

    public function card()
    {
        return $this->belongsTo(UserCard::class,'card_id','id');
    }

}