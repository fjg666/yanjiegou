<?php
namespace app\api\controller;
use app\api\model\Signlog;
use think\Db;
use think\Request;
class Signin extends Base
{
    //签到
    public function add()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        //查看是否有该用户
        $user = \app\api\model\Users::where('id',$user_id)->find();
        if(empty($user)){
            $this->json_error('非法操作');
            die;
        }
        //1天只能签到1次
        //code_source签到码来源  1 签到  2 分享获取的
        $singlog = Signlog::where(['user_id'=>$user_id,'code_source'=>1])->order('id','desc')->find();
        //最后一次签到时间
        $last_time = strtotime($singlog['add_time']);
        //当前时间
        $t = time();
        $end = mktime(23,59,59,date("m",$t),date("d",$t),date("Y",$t));//当天时间的最后1刻
        $current_day = $end;//当天时间的最后1刻
        $timeIs = $this->timediff($last_time,$current_day);//时间差

        if($timeIs['day']==0){
            $this->json_error('今天已经签到过了');
            die;
        }
        if ($timeIs['day']>=1) {
            //可以签到
            $msg = '签到成功';
        }


        if(empty($singlog)){
            $msg = '首次签到';
        }

        //首次签到
        //签到码随机生成  随机码
        $code = 'YJG'.$this->randCode(5,-1);
        //insertGetId
        $data = [
            'user_id'=>$user_id,
            'add_time'=>date('Y-m-d H:i:s'),
            'code'=>$code,
            'code_source'=>1,
            'sign_time'=>time()
        ];
        $id = Signlog::insertGetId($data);
        if($id){
            $info = Signlog::find($id);

            $this->json_success($info,$msg.'成功');
            die;
        }else{
            $this->json_error($msg.'失败');
            die;
        }
    }

    //今日 昨日 签到状况
    public function daysignlist()
    {
        //当前的页码
        $p = empty(input('post.p')) ?1:input('post.p');

        //每页显示的数量
        $rows = empty(input('post.rows'))?10:input('post.rows');

        $day = empty(input('post.day')) ?1:input('post.day');

        //day为1 是今日  2 昨日
        if($day==1){
            $dayStr = 'today';
        }else if($day==2){
            $dayStr = 'yesterday';
        }else{
            $dayStr = 'today';
        }

        $signlogmodel = new Signlog();
        $signlogs = $signlogmodel->alias('s')
            ->join('__USERS__ u','u.id=s.user_id','LEFT')
            ->order('s.id','desc')
            ->whereTime('s.sign_time', $dayStr)
            ->field('s.*,u.mobile,u.avatar,u.username')
            ->page($p,$rows)
            ->select();
        foreach($signlogs as $k=>$v){
            $signlogs[$k]['avatar'] = $this->domain().$v['avatar'];
            unset($signlogs[$k]['sign_time']);
        }

        $this->json_success($signlogs);
    }

    //获奖用户
    public function prizelist()
    {
        //当前的页码
        $p = empty(input('post.p')) ?1:input('post.p');

        //每页显示的数量
        $rows = empty(input('post.rows'))?10:input('post.rows');

        $day = empty(input('post.day')) ?1:input('post.day');

        //day为1 是今日  2 昨日
        if($day==1){
            $dayStr = 'today';
        }else if($day==2){
            $dayStr = 'yesterday';
        }else{
            $dayStr = 'today';
        }

        $signlogmodel = new Signlog();
        $signlogs = $signlogmodel->alias('s')
            ->join('__USERS__ u','u.id=s.user_id','LEFT')
            ->join('__GOODS__ g','g.id=s.goods_id','LEFT')
            ->order('s.id','desc')
            ->where('s.winstatus', 1)
            ->whereTime('s.sign_time', $dayStr)
            ->field('s.*,u.mobile,u.avatar,u.username,g.id as gid,g.title,g.price,g.headimg')
            ->page($p,$rows)
            ->select();
        foreach($signlogs as $k=>$v){
            $signlogs[$k]['avatar'] = $this->domain().$v['avatar'];
            $headimg = explode(',',$v['headimg']);
            $signlogs[$k]['headimg'] = $this->domain().$headimg[0];
            unset($signlogs[$k]['sign_time']);
        }

        $this->json_success($signlogs);
    }



}