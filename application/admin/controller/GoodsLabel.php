<?php
namespace app\admin\Controller;
use think\Db;
use ensh\Tree;
use think\Request;
use app\admin\controller\Common;
class GoodsLabel extends Common
{

    protected  $model;
    public function _initialize(){
        parent::_initialize();
        $this->model=model('GoodsLabel');
    }

    public function index(){
        if(Request::instance()->isAjax()){
            $keyword=input('key');
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $map=[];
            if(!empty($keyword) ){
                $map['title']=array('like','%'.$keyword.'%');
            }
            $list=$this->model->where($map)->order('sort asc')->paginate(array('list_rows'=>$pageSize,'page'=>$page))->toArray();
            $rsult['code'] = 0;
            $rsult['msg'] = "获取成功";
            $lists = $list['data'];
            $rsult['data'] = $lists;
            $rsult['count'] = $list['total'];
            $rsult['rel'] = 1;
            return $rsult;
        }else{
            return $this->fetch();
        }


    }

    public function add(){
        if (Request::instance()->isAjax()) {
            $data=input('post.');
            if($this->model->insert($data)){
                $result['msg'] = '添加成功!';
                $result['code'] = 1;
                return $result;
            }
        }
        return $this->fetch();
    }

    /**
     * 编辑
     *
     * @return void
     * @author 
     **/
    public function edit(){
        if (Request::instance()->isAjax()) {
            $data=input('post.');
            if($this->model->update($data)){
                $result['msg'] = '修改成功!';
                $result['code'] = 1;
                return $result;
            }
        }
        $id = input('id');
        $info=$this->model->find($id);
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function listorder(){
        $data = input('post.');
        $this->model->update($data);
        $result = ['msg' => '排序成功！','url'=>url('index'), 'code' => 1];
        return $result;
    }

    public function listDel(){
        $id = input('post.id');
        $this->model->where(array('id'=>$id))->delete();//转入回收站
        return ['code'=>1,'msg'=>'删除成功！'];
    }


    public function delAll(){
        $id=input('post.ids/a');
        $id=implode(",",$id);
        $this->model->where("id in ($id)")->delete();
        $result['code'] =1;
        $result['msg'] ='删除成功！';
        return $result;
    }












}