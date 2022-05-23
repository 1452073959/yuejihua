<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/3/9
 * Time: 11:23
 */

namespace app\home\controller;

use app\admin\model\admin\User;
use app\admin\model\plan\PlanDeal;
use app\admin\model\UserCard;
use app\home\help\Result;
use app\HomeController;

class Team extends HomeController
{

    protected $middleware = ['\app\middleware\Check::class'];

    //团队管理
    public function index()
    {
        $user = $this->user(request());
        //团队好友
        $a = GetTeamMember($user['id']);
        $teamnum = count($a);
        //直推好友
        $Directly = User::where('pid', $user['id'])->count();
        //实名好友
        $real_name = User::where('pid', $user['id'])->whereNotNull('name')->count();
        //实习会员(直推,间推)
        $Internship_z = User::where('pid', $user['id'])->where('vip_label', '1')->count();
        $Internship_j = User::where('pid', '<>', $user['id'])->where('vip_label', '1')->whereIn('id', $a)->count();
        $Internship_z_v1 = User::where('pid', $user['id'])->where('vip_label', '2')->count();
        $Internship_jz_v1 = User::where('pid', '<>', $user['id'])->where('vip_label', '2')->whereIn('id', $a)->count();
        $Internship_z_v2 = User::where('pid', $user['id'])->where('vip_label', '3')->count();
        $Internship_jz_v2 = User::where('pid', '<>', $user['id'])->where('vip_label', '3')->whereIn('id', $a)->count();
        $Internship_z_v3 = User::where('pid', $user['id'])->where('vip_label', '4')->count();
        $Internship_jz_v3 = User::where('pid', '<>', $user['id'])->where('vip_label', '4')->whereIn('id', $a)->count();
        $vip = [];
        $vip['sx'] = ['zt' => $Internship_z, 'jt' => $Internship_j];
        $vip['v1'] = ['zt' => $Internship_z_v1, 'jt' => $Internship_jz_v1];
        $vip['v2'] = ['zt' => $Internship_z_v2, 'jt' => $Internship_jz_v2];
        $vip['v3'] = ['zt' => $Internship_z_v3, 'jt' => $Internship_jz_v3];

        return Result::Success(['teamnum' => $teamnum, 'directly' => $Directly, 'real_name' => $real_name, 'vip' => $vip]);
    }

    //我的团队
    public function addteam()
    {
        $user = $this->user(request());
        $a = GetTeamMember($user['id']);
        //本月
        $Month = User::whereMonth('create_time')->whereIn('id', $a)->count();
        $Monthcard = UserCard::whereMonth('create_time')->whereIn('id', $a)->count();
        //上月
        $lastMonth = User::whereMonth('create_time', 'last month')->whereIn('id', $a)->count();
        $lastcard = UserCard::whereMonth('create_time', 'last month')->whereIn('id', $a)->count();
        //本日
        $day = User::whereDay('create_time')->whereIn('id', $a)->count();
        $daycard = UserCard::whereDay('create_time')->whereIn('id', $a)->count();
        //昨日
        $lastday = User::whereDay('create_time', 'yesterday')->whereIn('id', $a)->count();
        $lastdaycard = User::whereDay('create_time', 'yesterday')->whereIn('id', $a)->count();

        return Result::Success(['month' => $Month, 'lastmonth' => $lastMonth, 'day' => $day, 'lastday' => $lastday,
            'Monthcard' => $Monthcard,
            'lastcard' => $lastcard,
            'daycard' => $daycard,
            'lastdaycard' => $lastdaycard,
        ]);
    }

    //直推好友
    public function directlyteam()
    {
        $user = $this->user(request());
        $where = [];
        $req = request()->param();
        if (isset($req['search'])) {
            $where = [
                ['phone|user_code', 'like', '%' . $req['search'] . '%']
            ];
        };
        $directlyteam = User::where('pid', $user['id'])->where($where)->paginate(10)->toArray();
        foreach ($directlyteam['data'] as $k => $v) {
            $directlyteam['data'][$k]['teamnum'] = User::where('pid', $v['id'])->count();
        }
        return Result::Success($directlyteam);
    }

    //会员
    public function member()
    {
        $user = $this->user(request());
        $req = request()->param();
        //验证
        $validate = new \think\Validate();
        $validate->rule([
            'vip_label|会员类型' => 'require',
        ]);
        if (!$validate->check($req)) {
            return Result::Error('1000', $validate->getError());
        }
        //团队好友
        $a = GetTeamMember($user['id']);
        $member = User::where('id', 'in', $a)
            ->where('pid', $user['id'])
            ->where('vip_label', $req['vip_label'])
            ->paginate(10)->toArray();

        foreach ($member['data'] as $k => $v) {
            $member['data'][$k]['teamnum'] = User::where('pid', $v['id'])->count();
        }
        return Result::Success($member);

    }


    //会员详情
    public function member_show()
    {
        $user = $this->user(request());
        $req = request()->param();
        //还款当月/上月
        $hk1 = PlanDeal::where('trade_type', 2)
            ->where('trade_status', 3)
            ->where('user_id', $user['id'])
            ->whereMonth('trade_time')
            ->sum('actual_amount');
        $hk2 = PlanDeal::where('trade_type', 2)
            ->where('trade_status', 3)
            ->where('user_id', $user['id'])
            ->whereMonth('trade_time', 'last month')
            ->sum('actual_amount');
        //新增直推好友当月/上月
        $zt1 = User::where('pid', $user['id'])->whereMonth('create_time')->count();
        $zt2 = User::where('pid', $user['id'])->whereMonth('create_time', 'last month')->count();
        //新增直推v1
        $ztv1 = User::where('pid', $user['id'])->whereMonth('create_time')->where('vip_label',2)->count();
        $ztv2 = User::where('pid', $user['id'])->whereMonth('create_time', 'last month')->where('vip_label',2)->count();
        //获取团队(不包含自己)
        $a = GetTeamMember($user['id']);
        //新增直推好友昨日/当月/上月/累计
        $tzt1 = User::whereDay('create_time', 'yesterday')->whereIn('id', $a)->count();
        $tzt2 = User::whereMonth('create_time')->whereIn('id', $a)->count();
        $tzt3 = User::whereMonth('create_time', 'last month')->whereIn('id', $a)->count();
        $tzt4 = User::whereIn('id', $a)->count();

        //新增直推v1
        $tztv1 = User::whereDay('create_time', 'yesterday')->where('vip_label',2)->whereIn('id', $a)->count();
        $tztv2 = User::whereMonth('create_time')->where('vip_label',2)->whereIn('id', $a)->count();
        $tztv3 = User::whereMonth('create_time', 'last month')->where('vip_label',2)->whereIn('id', $a)->count();
        $tztv4 = User::where('vip_label',2)->whereIn('id', $a)->count();
        //交易量
        $tzthk1 = PlanDeal::where('trade_type', 2)
            ->where('trade_status', 3)
            ->whereIn('id', $a)
            ->whereDay('trade_time','yesterday')
            ->sum('actual_amount');
        $tzthk2 = PlanDeal::where('trade_type', 2)
            ->where('trade_status', 3)
            ->whereIn('id', $a)
            ->whereMonth('trade_time')
            ->sum('actual_amount');
        $tzthk3 = PlanDeal::where('trade_type', 2)
            ->where('trade_status', 3)
            ->whereIn('id', $a)
            ->whereMonth('trade_time', 'last month')
            ->sum('actual_amount');
        $tzthk4 = PlanDeal::where('trade_type', 2)
            ->where('trade_status', 3)
            ->whereIn('id', $a)
            ->sum('actual_amount');

        return Result::Success([
            'hk'=>['month'=>$hk1,'last_month'=>$hk2],
            'zt'=>['month'=>$zt1,'last_month'=>$zt2],
            'ztv1'=>['month'=>$ztv1,'last_month'=>$ztv2],
            'team_zt'=>['month'=>$tzt2,'last_month'=>$tzt3,'yesterday'=>$tzt1,'total'=>$tzt4],
            'team_ztv1'=>['month'=>$tztv2,'last_month'=>$tztv3,'yesterday'=>$tztv1,'total'=>$tztv4],
            'team_jy'=>['month'=>$tzthk2,'last_month'=>$tzthk3,'yesterday'=>$tzthk1,'total'=>$tzthk4],

        ],'成功');
    }

}