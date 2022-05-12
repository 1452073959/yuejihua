<?php

namespace app\admin\model\plan;

use app\admin\model\admin\User;
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

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','id');
    }

    public function PlanDetails()
    {
        return $this->belongsTo(PlanDetails::class,'plan_details_id','id');
    }

}