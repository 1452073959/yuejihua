<?php

namespace app\admin\model;

use app\admin\model\admin\User;
use app\common\model\TimeModel;

class UserCard extends TimeModel
{

    protected $name = "user_card";

    protected $deleteTime = false;


    //关联用户
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

}