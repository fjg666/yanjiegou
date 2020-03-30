<?php
namespace app\admin\controller;
use think\request;
use think\Db;
class Orderreason extends Common
{
    protected  $model;
    public function _initialize(){
        parent::_initialize();
        $this->model=Db::name('orderreason');
    }

    public function lists(){
        if(request()->isPost()) {
            $key=input('post.key');
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $list = $this->model
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->toArray();
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }
        return $this->fetch();
    }


    /**
     * 
     **/
    public function add(){
        if (request()->isPost()) {
            $data['name']=input('post.name/s');
            $this->model->insert($data);
            $result['code'] = 1;
            $result['msg'] = '添加成功!';
            $result['url'] = url('lists');
            return $result;
        }   
        return $this->fetch();
    }

    //删除留言
    public function del(){
        $map['id']=input('id');
        $this->model->where($map)->delete();
        return $result = ['code'=>1,'msg'=>'删除成功!'];
    }
    public function delall(){
        $map['id'] =array('IN',input('param.ids/a'));

        $this->model->where($map)->delete();
        $result['msg'] = '删除成功！';
        $result['code'] = 1;
        $result['url'] = url('lists');
        return $result;
    }
}