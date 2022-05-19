<?php
declare (strict_types = 1);

namespace app\home\controller;
use app\home\help\Pdd;
use app\home\help\Result;
use app\HomeController;
use think\facade\Db;

class Index extends HomeController
{
    public function index()
    {

        $a='{"content":"{\"desc\":\"\u4ea4\u6613\u5904\u7406\u4e2d\",\"orderNo\":\"xf202205191652952824459180\",\"orderStatus\":\"p\"}","merNo":"202200006463","resCode":"0000","resMsg":"\u9884\u4e0b\u5355\u6210\u529f","sign":"GkURXaFks+32Xty3SdrJpe8QIT+C0r+1vR97l6vuc5hdyVHhKsIAw3JfkwO7N5QpnDnaKp\/9p5p33jNPVZm5EwHTLM9OvlhyQN0JN\/MErySczMn\/jlRpho7mlXcNT\/Sqd4vpW8YwixmsgszoBsJJOl+Ej7JIhhU9GG3a8CMS6Yg="}';
        $a=json_decode($a,true);
        dump($a);die;
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

    public function city()
    {
       $city= Db::name('area_region')->select();
        return Result::Success($city,'成功');
    }
}
