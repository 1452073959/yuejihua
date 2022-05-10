<?php
declare (strict_types = 1);

namespace app\home\controller;
use app\home\help\Result;
use app\HomeController;
class Index extends HomeController
{
    public function index()
    {
      return Result::Success('a');
    }

    public function team()
    {
        $a=GetTeamMember('2');
        dump($a);die;
    }
}
