<?php
namespace app\api\controller;
use think\Db;
use think\Request;

class Information extends Base
{
    public function index()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        //当前的页码
        $p = empty(input('post.p')) ?1:input('post.p');
        //每页显示的数量
        $rows = empty(input('post.rows'))?10:input('post.rows');


        //如果消息已经删除就不显示了
        $delinformation = db('informationlog')->where(['user_id'=>$user_id,'is_delete'=>1])->select();

       $information_id = array_column($delinformation,'information_id');







        //type_id消息类型，1 指定用户消息  2 全用户消息  3 指定商家消息   4 全商家消息    5 指定商圈消息  6 全商圈消息   7 总消息，商家 商圈 用户都能收到
        $where = "(type_id=1 AND user_id=".$user_id.") OR type_id=2 OR type_id=7";
        $informations = \app\api\model\Information::where($where)
            ->where('id','not in',$information_id)
            ->order('id desc,type_id desc')
            ->field('id,title,content,add_time')
            ->page($p,$rows)
            ->select();

        foreach($informations as $k=>$v){
            $informations[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
        }

        $this->json_success($informations);
    }

    //消息删除
    public function delinformation()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        $information_id = input('post.information_id');
        if(null===$information_id){
            $this->json_error('请传过来消息编号');
        }
        //多个消息之间用英文逗号,分割
        $information_ids = explode(',',$information_id);
        foreach($information_ids as $k=>$v){
            $info[$k]['information_id'] = $v;
            $info[$k]['is_delete'] = 1; //消息是否删除 0 没有  1 删除
            $info[$k]['del_time'] = time();
            $info[$k]['user_id'] = $user_id;
        }

        //添加 之前判断是否有

        $information = db('informationlog')
            ->where(['user_id'=>$user_id])
            ->where('information_id','in',$information_id)
            ->find();

        if(!empty($information)){
            $this->json_error('不能重复删除消息');
            die;
        }

        //添加成功
        $res = db('informationlog')->insertAll($info);
        if($res){
           $this->json_success([],'删除消息成功');
           die;
        }else{
           $this->json_error('删除消息失败');
           die;
        }

    }
}
