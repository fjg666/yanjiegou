<?php
//商品评论
namespace app\shop\controller;

use app\common\model\Comment;
use think\Controller;
use think\Request;
use app\shop\controller\Common;

class Evaluate extends Common
{

    public function index()
    {
        if(Request::instance()->isAjax()){

            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $commentmodel = new Comment();
            $comments = $commentmodel->alias('c')
                ->join('__GOODS__ g','g.id = c.goods_id','LEFT')
                ->join('__USERS__ u','u.id = c.user_id','LEFT')
                ->join('__SHOP__ s','s.id = c.shop_id','LEFT')
                ->field('c.*,g.id as gid,g.title as gtitle,u.id as uid,u.mobile as umobile,s.id as sid,s.name as sname')
                ->order("c.id desc")
                ->where(['shop_id'=>SHID])
                ->page($page,$pageSize)
                ->select();


            foreach($comments as $k=>$v){
                $comments[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);

            }


            $count = count($comments);
            $result = [
                'code'=>0,
                'msg'=>'ok',
                'count'=>$count,
                'data'=>$comments
            ];
            return $result;

        }else{
            return $this->fetch();
        }
    }

    public function see()
    {
        $id = input('get.id');
        $commentmodel = new Comment();

        $comments = $commentmodel->alias('c')
            ->join('__GOODS__ g','g.id = c.goods_id','LEFT')
            ->join('__USERS__ u','u.id = c.user_id','LEFT')
            ->join('__SHOP__ s','s.id = c.shop_id','LEFT')
            ->join('__ORDER__ o ','o.id = c.order_gid','LEFT')
            ->field('c.*,g.id as gid,g.title as gtitle,u.id as uid,u.mobile as umobile,s.id as sid,s.name as sname,o.id as oid,o.order_sn as ordersn,o.money as omoney')
            ->order("c.id desc")
            ->where('c.id', $id)
            ->find();
        if(!empty($comments['imgsrc'])){
            $imgsrc = json_decode($comments['imgsrc']);
        }else{
            $imgsrc = [];
        }
        $this->assign('imgsrc',$imgsrc);
        $this->assign('comments',$comments);
        return $this->fetch();

    }

    //设置评论是否显示
    public function editState(){
        $id=input('post.id');
        $is_show=input('post.is_show');
        if(db('comment')->where('id='.$id)->update(['is_show'=>$is_show])!==false){
            return ['status'=>1,'msg'=>'设置成功!'];
        }else{
            return ['status'=>0,'msg'=>'设置失败!'];
        }
    }


}
