<?php
namespace app\shop\controller;
use app\common\model\Couponlog;
use think\Db;
use think\request;
use app\shop\controller\Common;
class Coupon extends Common
{
    protected  $model;
    public function _initialize(){
        parent::_initialize();
        $this->model = model('Coupon');
    }
    public function index()
    {
        if(Request::instance()->isAjax()){
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $map['shop_id']=SHID;
            $map['type_id']=2;
            $list = $this->model->order('sort desc,id desc')
                 ->where($map)
                 ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                 ->each(function($row){
                     $row['is_expire']=get_status($row['is_expire'],'coupon_is_expire');
                 })
                 ->toArray();
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total']];
        }else{
            return $this->fetch();
        }
    }
    public function listorder(){
        $data = input('post.');
        $this->model->update($data);
        $result = ['msg' => '排序成功！','url'=>url('coupon/index'), 'code' => 1];
        return $result;
    }
    public function listdel()
    {
        $id = input('post.id');

        //删除之前，先清空关联的表
        $res2 = Couponlog::where('coupon_id','in',$id)->delete();

        $res = \app\admin\model\Coupon::destroy($id);
        if($res){
            $result['msg'] = '删除成功！';
            $result['code'] = 1;
            $result['url'] = url('index');
            return $result;
        }else{
            $result['msg'] = '删除失败！';
            $result['code'] = 1;
            $result['url'] = url('index');
            return $result;
        }
    }
    public function add()
    {
        if(Request::instance()->isAjax()) {
            $data = input('post.');
            //最低消费金额
            $min_price = $data['min_price'];
            //优惠金额
            $sub_price = $data['sub_price'];
            //优惠券名称
            $name = "满".$min_price."立减".$sub_price;
            //有效期开始时间 begin_time
            $data['begin_time'] = strtotime($data['begin_time']);
            //有效期结束时间 end_time
            $data['end_time'] = strtotime($data['end_time']);
            $data['name'] = $name;
            $data['add_time'] = time();
            $data['type_id'] = 2;
            $data['shop_id']=SHID;
            $data['shop_name']='';
            $res = $this->model->insert($data);
            if($res){
                $result['code'] = 1;
                $result['msg'] = '添加优惠券成功!';
                $result['url'] = url('index');
                return $result;
            }else{
                $result['code'] = 0;
                $result['msg'] = '添加优惠券失败!';
                $result['url'] = url('index');
                return $result;
            }
        }else{
            return $this->fetch();
        }
    }
    //编辑
    public function edit()
    {
        if(Request::instance()->isAjax()){
            $id = input('post.id');
            $data = input('post.');
            unset($data['id']);
            //最低消费金额
            $min_price = $data['min_price'];
            //优惠金额
            $sub_price = $data['sub_price'];
            //优惠券名称
            $name = "满".$min_price."立".$sub_price;
             //有效期开始时间 begin_time
            $data['begin_time'] = strtotime($data['begin_time']);
            //有效期结束时间 end_time
            $data['end_time'] = strtotime($data['end_time']);
            $data['name'] = $name;
            $data['add_time'] = time();
            $data['type_id'] = 2;
            $data['shop_id']=SHID;
            $data['shop_name']='';
            $res = $this->model->where('id',$id)->update($data);
            if($res){
                $result['code'] = 1;
                $result['msg'] = '修改成功!';
                $result['url'] = url('index');
                return $result;
            }else{
                $result['code'] = 0;
                $result['msg'] = '修改失败!';
                $result['url'] = url('index');
                return $result;
            }
        }else{
            $id = input('get.id');
            $info = $this->model->find($id);
            $this->assign('info',$info);
            return $this->fetch();
        }
    }
}