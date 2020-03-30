<?php
namespace app\admin\controller;
use app\admin\model\Signgoods;
use app\admin\model\Signlog;
use think\Db;
use think\request;
use app\admin\controller\Common;
class Sign extends Common
{
    public function initialize(){
        parent::initialize();
    }
    //抽奖商品
    public function goods()
    {
        if(Request::instance()->isAjax()){
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $signgoodsmodel = new Signgoods();
            $signgoods = $signgoodsmodel->alias('sg')
                ->join('__GOODS__ g','g.id = sg.goods_id','LEFT')
                ->field('sg.*,g.title,g.price as gprice,g.headimg')
                ->order("sg.id desc")
                ->page($page,$pageSize)
                ->select();

            foreach($signgoods as $k=>$v){
                $signgoods[$k]['starttime'] = date('Y-m-d H:i:s',$v['starttime']);
                $signgoods[$k]['stoptime'] = date('Y-m-d H:i:s',$v['stoptime']);
            }

            $count = count($signgoods);
            $result = [
                'code'=>0,
                'msg'=>'ok',
                'count'=>$count,
                'data'=>$signgoods
            ];
            return $result;
        }else{
            return $this->fetch();
        }

    }

    //签到商品
    public function signgoods()
    {
        if(Request::instance()->isAjax()) {
            $data = input('post.');
            $goods_id = $data['ids'];
            $ids = explode(',',$goods_id);
            foreach($ids as $k=>$v){
                $info[$k]['goods_id'] = $v;
                $info[$k]['starttime'] = strtotime($data['starttime']);
                $info[$k]['stoptime'] = strtotime($data['stoptime']);
                $info[$k]['joinnum'] = $data['joinnum'];
            }

            $res = db('signgoods')->insertAll($info);
            if($res){
                $result['code'] = 1;
                $result['msg'] = '设置签到商品成功!';
                $result['url'] = url('admin/sign/goods');
                return $result;
            }else{
                $result['code'] = 0;
                $result['msg'] = '设置签到商品失败!';
                $result['url'] = url('admin/sign/goods');
                return $result;
            }
        }else{
            $ids = input('get.ids');
            $this->assign('ids',$ids);
            return $this->fetch();
        }

    }


    //签到记录
    public function log()
    {
        if(Request::instance()->isAjax()) {
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $signlogmodel = new Signlog();
            $signlogs = $signlogmodel->alias('slog')
                ->join('__USERS__ u','u.id = slog.user_id','LEFT')
                ->field('slog.*,u.id as uid,u.mobile')
                ->order("slog.id desc")
                ->where('slog.winstatus',0)
                ->page($page,$pageSize)
                ->select();


            foreach($signlogs as $k=>$v){
                $signgoods[$k]['add_time'] = $v['add_time'];
                //winstatus 是否获奖 0否  1是
                if($v['winstatus']==0){
                    $signlogs[$k]['winstatus'] = '未中奖';
                }else{
                    $signlogs[$k]['winstatus'] = '<strong style="color: red">已中奖</strong>';
                }
                //code_source签到码来源  1 签到  2 分享获取的
                if($v['code_source']==1){
                    $signlogs[$k]['code_source'] = '签到';
                }else{
                    $signlogs[$k]['code_source'] = '分享获取';
                }
            }

            $count = count($signlogs);
            $result = [
                'code'=>0,
                'msg'=>'ok',
                'count'=>$count,
                'data'=>$signlogs
            ];
            return $result;
        }else{
            return $this->fetch();
        }

    }


    public function delAll(){
        $id=input('post.ids/a');
        $id=implode(",",$id);
        db('signlog')->where("id in ($id)")->delete();
        return $this->resultmsg('删除成功',1);
    }

    public function delAll2(){
        $id=input('post.ids/a');
        $id=implode(",",$id);
        db('signgoods')->where("id in ($id)")->delete();
        return $this->resultmsg('删除成功',1);
    }

    public function setprize()
    {
        $id = input('post.id');
        $goods_id = input('post.goods_id');
        $signlog_id = input('post.signlog_id');

        $data = [
            'goods_id'=>$goods_id,
            'winstatus'=>1
        ];

        //判断是否数量够
        $signgood = Signgoods::find($id);

        $joinnum = $signgood['joinnum'];
        if($joinnum<=0){
            $result['code'] = 0;
            $result['msg'] = '数量不足!';
            $result['url'] = url('admin/sign/winner');
            return $result;
        }




        $res = Signlog::where('id', $signlog_id)->update($data);

        if($res){
            Signgoods::where('id',$id)->setDec('joinnum',1);
            $result['code'] = 1;
            $result['msg'] = '设置中奖成功!';
            $result['url'] = url('admin/sign/winner');
            return $result;
        }else{
            $result['code'] = 0;
            $result['msg'] = '设置中奖失败!';
            $result['url'] = url('admin/sign/winner');
            return $result;
        }
    }


    public function winner()
    {
        if(Request::instance()->isAjax()) {
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $signlogmodel = new Signlog();
            $signlogs = $signlogmodel->alias('slog')
                ->join('__USERS__ u','u.id = slog.user_id','LEFT')
                ->field('slog.*,u.id as uid,u.mobile')
                ->order("slog.id desc")
                ->where('slog.winstatus',1)
                ->page($page,$pageSize)
                ->select();


            foreach($signlogs as $k=>$v){
                $signgoods[$k]['add_time'] = $v['add_time'];
                //winstatus 是否获奖 0否  1是
                if($v['winstatus']==0){
                    $signlogs[$k]['winstatus'] = '未中奖';
                }else{
                    $signlogs[$k]['winstatus'] = '<strong style="color: red">已中奖</strong>';
                }
                //code_source签到码来源  1 签到  2 分享获取的
                if($v['code_source']==1){
                    $signlogs[$k]['code_source'] = '签到';
                }else{
                    $signlogs[$k]['code_source'] = '分享获取';
                }
            }

            $count = count($signlogs);
            $result = [
                'code'=>0,
                'msg'=>'ok',
                'count'=>$count,
                'data'=>$signlogs
            ];
            return $result;
        }else{
            return $this->fetch();
        }
    }



}