<?php
// 这是系统自动生成的公共文件
function GetTeamMember($mid) {
    $members=\think\facade\Db::table('yj_admin_user')->select();
    $Teams=array();//最终结果
    $mids=array($mid);//第一次执行时候的用户id
    do {
        $othermids=array();
        $state=false;
        foreach ($mids as $valueone) {
            foreach ($members as $key =>$valuetwo) {
                if($valuetwo['pid']==$valueone){
                    $Teams[]=$valuetwo['id'];//找到我的下级立即添加到最终结果中
                    $othermids[]=$valuetwo['id'];//将我的下级id保存起来用来下轮循环他的下级
//                    array_splice($members,$key,1);//从所有会员中删除他
                    $state=true;
                }
            }
        }
        $mids=$othermids;//foreach中找到的我的下级集合,用来下次循环
    }while ($state==true);

    return $Teams;
}