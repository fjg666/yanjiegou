<?php
namespace app\api\controller;
use app\api\model\Signlog;
use app\api\model\SignZj;
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

    //用户抽奖
    public function draw(){

        //查询今天的签到用户
        $signlogmodel = new Signlog();
        $signlogs = $signlogmodel->alias('s')
            ->join('__USERS__ u','u.id=s.user_id','LEFT')
            ->order('s.id','desc')
            ->whereTime('s.sign_time', 'today')
            ->field('s.*,u.mobile,u.avatar,u.username')
            ->select();

        $signlogs = json_decode(json_encode($signlogs), true);

        //随机抽取一个今天签到的用户
        $zj_user = array_rand($signlogs);

        //判断该用户今天是否已经中奖
        $checkZj = Db::name('sign_zj')->whereTime("zj_date","today")->find();
        if(!$checkZj){
            //存入到数据库中
            $add['qd_id'] = $signlogs[$zj_user]['id'];
            $add['user_id'] = $signlogs[$zj_user]['user_id'];
            $add['goods_id'] = $signlogs[$zj_user]['goods_id'];
            $add['user_name'] = $signlogs[$zj_user]['username'];
            $add['user_avatar'] = $signlogs[$zj_user]['avatar'];
            $add['user_mobile'] = $signlogs[$zj_user]['mobile'];
            $add['user_code'] = $signlogs[$zj_user]['code'];
            $add['zj_date'] = date('Y-m-d');
            $check = SignZj::insert($add);

            if($check){
                $this->json_success($add,'添加成功');
            }else{
                $this->json_error('添加失败');
            }
        }else{
            $this->json_error('今天已有人中奖啦，明天再来吧！');
        }
    }

    //已送出奖品用户列表
    public function give_do(){
        $data = Db::name('sign_zj')->where("is_give",1)->select();
        if($data){
            $this->json_success($data, '已送出列表');
        }else{
            $this->json_error('暂无数据');
        }
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