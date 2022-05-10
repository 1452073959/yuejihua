<?php

namespace app\admin\model\plan;

use app\admin\model\UserCard;
use app\common\model\TimeModel;

class OrderPlan extends TimeModel
{

    protected $name = "order_plan";

    protected $deleteTime = false;

    //
    public function details()
    {
        return $this->hasMany(PlanDetails::class,'plan_id','id');
    }

    public function card()
    {
        return $this->belongsTo(UserCard::class,'card_id','id');
    }
}