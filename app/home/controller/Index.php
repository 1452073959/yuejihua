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
        ];
        $arr= array_merge($req,$arr);
//        dump($arr);
        $feemypay = new Pdd();
        $a=  $feemypay->ceshi('pdd.ddk.goods.recommend.get',$arr);

        return  json($a);
    }

    public function pddcate()
    {

        $arr=[
            'type'=>'pdd.goods.cats.get',
            'client_id'=>'2e46e1e0b1394538960222e5fb1b9009',
            'timestamp'=>time(),
            'parent_cat_id'=>'0',
        ];

        $feemypay = new Pdd();
        $a=  $feemypay->ceshi('pdd.goods.cats.get',$arr);
        return  json($a);
    }


    public function show()
    {
        $req = request()->param();
        $arr=[
            'type'=>'pdd.ddk.goods.detail',
            'client_id'=>'2e46e1e0b1394538960222e5fb1b9009',
            'timestamp'=>time(),
        ];
        $arr= array_merge($req,$arr);
//        dump($arr);
        $feemypay = new Pdd();
        $a=  $feemypay->ceshi('pdd.ddk.goods.detail',$arr);
        return  json($a);
    }

    public function team()
    {
        $a=GetTeamMember('2');
        dump($a);die;
    }
}
