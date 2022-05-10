<?php

namespace app\admin\model\plan;

use app\admin\model\UserCard;
use app\common\model\TimeModel;

class PlanDetails extends TimeModel
{

    protected $name = "plan_details";

    protected $deleteTime = false;


    public function deal()
    {
        return $this->hasMany(PlanDeal::class,'plan_details_id','id');
    }
    public function card()
    {
        return $this->belongsTo(UserCard::class,'card_id','id');
    }


}