<?php
declare (strict_types = 1);

namespace app\home\controller;
use app\home\help\Pdd;
use app\home\help\Result;
use app\HomeController;
class Index extends HomeController
{
    public function index()
    {
      return Result::Success('a');
    }


    public function pdd()
    {
        $req = request()->param();
        $arr=[
            'type'=>'pdd.ddk.goods.recommend.get',
            'client_id'=>'2e46e1e0b1394538960222e5fb1b9009',
            'timestamp'=>time(),
            'activity_tags'=>'[4,7]',
        ];
        $arr= array_merge($req,$arr);
//        dump($arr);
        $feemypay = new Pdd();
        $a=  $feemypay->ceshi('pdd.ddk.goods.recommend.get',$arr);

        return $a;
    }

    public function team()
    {
        $a=GetTeamMember('2');
        dump($a);die;
    }
}
